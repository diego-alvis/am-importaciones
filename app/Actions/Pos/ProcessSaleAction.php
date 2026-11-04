<?php

namespace App\Actions\Pos;

use App\Enums\CashSessionStatus;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\CashSession;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Stock;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ProcessSaleAction
{
    /**
     * Procesa la venta completa de forma atómica y valida stock en sede.
     */
    public function execute(User $user, array $data): Sale
    {
        if ($user->isAlmacen()) {
            throw new DomainException('El Almacén Central no puede registrar ventas directas.');
        }

        $cashSession = CashSession::with('stand.location')
            ->where('user_id', $user->id)
            ->where('status', CashSessionStatus::Abierta)
            ->first();

        if (! $cashSession) {
            throw new DomainException('No tienes un turno de caja abierto. Por favor, abre tu caja antes de registrar ventas.');
        }

        $locationId = $cashSession->stand?->location_id ?? $user->location_id;
        if (! $locationId) {
            throw new DomainException('No se pudo determinar la sede asignada para esta venta.');
        }

        $cart = $data['cart'];
        $calculatedTotal = $this->calculateAndValidateCart($cart);

        if (abs($calculatedTotal - (float) $data['total']) > 0.05) {
            throw new DomainException('Existe una diferencia entre el total enviado y el cálculo del servidor.');
        }

        return DB::transaction(function () use ($user, $cashSession, $locationId, $cart, $calculatedTotal, $data) {
            $this->validateAndLockStock($cart, $locationId);

            $operatorName = $cashSession->operator_name ?: session('operator_name', $user->name);

            $sale = Sale::create([
                'cash_session_id' => $cashSession->id,
                'user_id' => $user->id,
                'operator_name' => $operatorName,
                'customer_id' => $data['customer_id'] ?? null,
                'total_amount' => $calculatedTotal,
                'status' => SaleStatus::Completada,
            ]);

            $this->createItemsAndDeductStock($sale, $cart, $locationId);
            $this->createPayments($sale, $calculatedTotal, $data);

            return $sale;
        });
    }

    private function calculateAndValidateCart(array $cart): float
    {
        $calculatedTotal = 0.0;
        foreach ($cart as $item) {
            $product = Product::with('category')->find($item['id']);
            if (! $product) {
                throw new DomainException('Producto no válido en el catálogo.');
            }

            if ($product->requiresDeviceModel() && empty(trim($item['device_model'] ?? ''))) {
                throw new DomainException("El producto '{$product->name}' requiere que indiques el modelo de celular.");
            }

            $calculatedTotal += round(((int) $item['quantity']) * ((float) $item['price']), 2);
        }

        return $calculatedTotal;
    }

    private function validateAndLockStock(array $cart, int $locationId): void
    {
        foreach ($cart as $item) {
            $stock = Stock::where('product_id', $item['id'])
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->first();

            $disponible = $stock ? $stock->quantity : 0;
            if (! $stock || $disponible < (int) $item['quantity']) {
                $prodName = Product::find($item['id'])->name ?? 'Producto';
                throw new DomainException("Stock insuficiente para '{$prodName}'. Disponible en esta sede: {$disponible} unidades.");
            }
        }
    }

    private function createItemsAndDeductStock(Sale $sale, array $cart, int $locationId): void
    {
        foreach ($cart as $item) {
            $product = Product::with('category')->find($item['id']);
            $model = ($product && $product->requiresDeviceModel()) ? trim($item['device_model'] ?? '') : null;

            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $item['id'],
                'device_model' => $model,
                'quantity' => (int) $item['quantity'],
                'unit_price' => (float) $item['price'],
            ]);

            Stock::where('product_id', $item['id'])
                ->where('location_id', $locationId)
                ->decrement('quantity', (int) $item['quantity']);
        }
    }

    private function createPayments(Sale $sale, float $total, array $data): void
    {
        $methodRaw = strtolower(trim($data['payment_method']));

        if ($methodRaw === 'mixto') {
            $cashTendered = (float) ($data['cash_tendered'] ?? 0);
            $change = (float) ($data['change'] ?? 0);
            $effectiveCash = max(0, $cashTendered - $change);
            $transferTendered = (float) ($data['transfer_tendered'] ?? 0);

            if ($effectiveCash > 0) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'payment_method' => PaymentMethod::Efectivo,
                    'amount' => $effectiveCash,
                ]);
            }

            if ($transferTendered > 0) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'payment_method' => PaymentMethod::Yape,
                    'amount' => $transferTendered,
                    'reference_code' => $data['reference_code'] ?? ('TX-'.rand(100000, 999999)),
                ]);
            }
        } else {
            $method = match ($methodRaw) {
                'yape/plin', 'yape' => PaymentMethod::Yape,
                'plin' => PaymentMethod::Plin,
                'tarjeta' => PaymentMethod::Tarjeta,
                default => PaymentMethod::Efectivo,
            };

            $refCode = in_array($method, [PaymentMethod::Yape, PaymentMethod::Plin, PaymentMethod::Tarjeta], true)
                ? ($data['reference_code'] ?? ('TX-'.rand(100000, 999999)))
                : null;

            SalePayment::create([
                'sale_id' => $sale->id,
                'payment_method' => $method,
                'amount' => $total,
                'reference_code' => $refCode,
            ]);
        }
    }
}
