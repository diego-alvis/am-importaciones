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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProcessSaleAction
{
    /**
     * Procesa la venta completa de forma atómica, calcula descuentos unitarios y valida stock en sede.
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
        $productIds = collect($cart)->pluck('id')->unique()->values();
        $products = Product::with('category')->whereIn('id', $productIds)->get()->keyBy('id');

        $prepared = $this->prepareSaleItems($cart, $products);

        if (abs($prepared['total_amount'] - (float) $data['total']) > 0.05) {
            throw new DomainException('Existe una diferencia entre el total enviado y el cálculo del servidor.');
        }

        return DB::transaction(function () use ($user, $cashSession, $locationId, $prepared, $products, $data): Sale {
            $this->validateAndLockStock($prepared['items'], $products, $locationId);

            $operatorName = $cashSession->operator_name ?: session('operator_name', $user->name);

            $sale = Sale::create([
                'cash_session_id' => $cashSession->id,
                'user_id' => $user->id,
                'operator_name' => $operatorName,
                'customer_id' => $data['customer_id'] ?? null,
                'subtotal_amount' => $prepared['subtotal_amount'],
                'discount_amount' => $prepared['discount_amount'],
                'total_amount' => $prepared['total_amount'],
                'status' => SaleStatus::Completada,
            ]);

            $this->createItemsAndDeductStock($sale, $prepared['items'], $locationId);
            $this->createPayments($sale, $prepared['total_amount'], $data);

            return $sale;
        });
    }

    /**
     * Calcula precios finales y descuentos por unidad tomando el precio base desde el catálogo.
     *
     * @param  array<int, array<string, mixed>>  $cart
     * @param  Collection<int, Product>  $products
     * @return array{
     *     items: list<array{
     *         product_id: int,
     *         device_model: ?string,
     *         quantity: int,
     *         original_price: float,
     *         unit_discount: float,
     *         discount_amount: float,
     *         unit_price: float
     *     }>,
     *     subtotal_amount: float,
     *     discount_amount: float,
     *     total_amount: float
     * }
     */
    private function prepareSaleItems(array $cart, Collection $products): array
    {
        $items = [];
        $subtotalAmount = 0.0;
        $totalDiscount = 0.0;
        $calculatedTotal = 0.0;

        foreach ($cart as $item) {
            $product = $products->get((int) $item['id']);
            if (! $product) {
                throw new DomainException('Producto no válido en el catálogo.');
            }

            if ($product->requiresDeviceModel() && empty(trim((string) ($item['device_model'] ?? '')))) {
                throw new DomainException("El producto '{$product->name}' requiere que indiques el modelo de celular.");
            }

            $quantity = (int) $item['quantity'];
            $originalPrice = round((float) $product->base_price, 2);
            $unitDiscount = round((float) ($item['unit_discount'] ?? 0), 2);

            if ($unitDiscount < 0) {
                throw new DomainException("El descuento para '{$product->name}' no puede ser negativo.");
            }

            if ($unitDiscount > $originalPrice) {
                throw new DomainException(
                    "El descuento por unidad para '{$product->name}' (S/ ".number_format($unitDiscount, 2).') no puede superar su precio base (S/ '.number_format($originalPrice, 2).').'
                );
            }

            $finalUnitPrice = round($originalPrice - $unitDiscount, 2);
            $lineSubtotal = round($originalPrice * $quantity, 2);
            $lineDiscount = round($unitDiscount * $quantity, 2);
            $lineTotal = round($finalUnitPrice * $quantity, 2);

            $subtotalAmount = round($subtotalAmount + $lineSubtotal, 2);
            $totalDiscount = round($totalDiscount + $lineDiscount, 2);
            $calculatedTotal = round($calculatedTotal + $lineTotal, 2);

            $deviceModel = $product->requiresDeviceModel() ? trim((string) ($item['device_model'] ?? '')) : null;

            $items[] = [
                'product_id' => $product->id,
                'device_model' => $deviceModel,
                'quantity' => $quantity,
                'original_price' => $originalPrice,
                'unit_discount' => $unitDiscount,
                'discount_amount' => $lineDiscount,
                'unit_price' => $finalUnitPrice,
            ];
        }

        return [
            'items' => $items,
            'subtotal_amount' => $subtotalAmount,
            'discount_amount' => $totalDiscount,
            'total_amount' => $calculatedTotal,
        ];
    }

    /**
     * Valida existencias acumuladas por producto bloqueando las filas de stock en sede.
     *
     * @param  list<array<string, mixed>>  $items
     * @param  Collection<int, Product>  $products
     */
    private function validateAndLockStock(array $items, Collection $products, int $locationId): void
    {
        $requestedQuantities = [];
        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $requestedQuantities[$productId] = ($requestedQuantities[$productId] ?? 0) + (int) $item['quantity'];
        }

        foreach ($requestedQuantities as $productId => $totalNeeded) {
            $stock = Stock::where('product_id', $productId)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->first();

            $disponible = $stock ? $stock->quantity : 0;
            if (! $stock || $disponible < $totalNeeded) {
                $prodName = $products->get($productId)?->name ?? 'Producto';
                throw new DomainException("Stock insuficiente para '{$prodName}'. Disponible en esta sede: {$disponible} unidades.");
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function createItemsAndDeductStock(Sale $sale, array $items, int $locationId): void
    {
        foreach ($items as $item) {
            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $item['product_id'],
                'device_model' => $item['device_model'],
                'quantity' => $item['quantity'],
                'original_price' => $item['original_price'],
                'unit_discount' => $item['unit_discount'],
                'discount_amount' => $item['discount_amount'],
                'unit_price' => $item['unit_price'],
            ]);

            Stock::where('product_id', $item['product_id'])
                ->where('location_id', $locationId)
                ->decrement('quantity', $item['quantity']);
        }
    }

    private function createPayments(Sale $sale, float $total, array $data): void
    {
        $methodRaw = strtolower(trim($data['payment_method']));

        if ($methodRaw === 'mixto') {
            $cashTendered = (float) ($data['cash_tendered'] ?? 0);
            $change = (float) ($data['change'] ?? 0);
            $transferTendered = (float) ($data['transfer_tendered'] ?? 0);

            if (round($cashTendered + $transferTendered, 2) + 0.05 < $total) {
                throw new DomainException('El monto pagado es insuficiente para cubrir el total de la venta.');
            }

            $effectiveCash = max(0, round($cashTendered - $change, 2));

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
                    'amount' => round($transferTendered, 2),
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
