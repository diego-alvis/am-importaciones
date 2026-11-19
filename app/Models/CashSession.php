<?php

namespace App\Models;

use App\Enums\CashSessionStatus;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use Database\Factories\CashSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashSession extends Model
{
    /** @use HasFactory<CashSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'stand_id',
        'operator_name',
        'opening_time',
        'closing_time',
        'expected_cash',
        'declared_cash',
        'approved_by',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'opening_time' => 'datetime',
            'closing_time' => 'datetime',
            'expected_cash' => 'decimal:2',
            'declared_cash' => 'decimal:2',
            'status' => CashSessionStatus::class,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stand()
    {
        return $this->belongsTo(Stand::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function getCashierNameAttribute(): string
    {
        return $this->operator_name ?: ($this->user?->name ?? 'Sin cajero');
    }

    /**
     * Calcula las métricas financieras y de auditoría de la sesión de caja.
     */
    public function getMetrics(): object
    {
        $sales = $this->sales->where('status', SaleStatus::Completada);
        $totalVentas = (float) $sales->sum('total_amount');
        $totalEfectivo = 0.0;
        $totalYapePlin = 0.0;
        $totalTarjeta = 0.0;

        foreach ($sales as $sale) {
            foreach ($sale->payments as $payment) {
                $amount = (float) $payment->amount;
                if ($payment->payment_method === PaymentMethod::Efectivo->value) {
                    $totalEfectivo += $amount;
                } elseif (in_array($payment->payment_method, [PaymentMethod::Yape->value, PaymentMethod::Plin->value], true)) {
                    $totalYapePlin += $amount;
                } elseif ($payment->payment_method === PaymentMethod::Tarjeta->value) {
                    $totalTarjeta += $amount;
                }
            }
        }

        $viaticoFijo = (float) (Setting::where('key', 'viatico_diario')->value('value') ?? 11.00);
        $metaComision = (float) (Setting::where('key', 'meta_comision')->value('value') ?? 400.00);
        $bonoComision = (float) (Setting::where('key', 'bono_comision')->value('value') ?? 10.00);

        $bonoMeta = $metaComision > 0 ? floor($totalVentas / $metaComision) * $bonoComision : 0.0;
        $gastosTotales = $viaticoFijo + $bonoMeta;
        $efectivoEsperado = max(0, $totalEfectivo - $gastosTotales);
        $diferencia = $this->declared_cash !== null ? (((float) $this->declared_cash) - $efectivoEsperado) : 0.0;

        return (object) [
            'total_ventas' => $totalVentas,
            'efectivo' => $totalEfectivo,
            'yape_plin' => $totalYapePlin,
            'tarjeta' => $totalTarjeta,
            'gastos_fijos' => $viaticoFijo,
            'bono_meta' => $bonoMeta,
            'gastos_totales' => $gastosTotales,
            'efectivo_esperado' => $efectivoEsperado,
            'diferencia' => $diferencia,
        ];
    }

    /**
     * Resumen consolidado de productos vendidos en el turno de caja.
     */
    public function getItemsSummary(): array
    {
        $sales = $this->sales()->where('status', SaleStatus::Completada->value)->with(['items.product', 'payments'])->get();
        $summary = [];

        foreach ($sales as $sale) {
            $paymentMethods = $sale->payments->pluck('payment_method')->map(fn ($m) => ucfirst($m))->implode(', ');
            foreach ($sale->items as $item) {
                $unitPriceKey = number_format((float) $item->unit_price, 2, '.', '');
                $key = $item->product_id.'_'.($item->device_model ?? '').'_'.$unitPriceKey;
                if (! isset($summary[$key])) {
                    $summary[$key] = [
                        'sku' => $item->product->sku ?? '',
                        'name' => $item->product->name ?? 'Producto',
                        'device_model' => $item->device_model,
                        'original_price' => (float) ($item->original_price ?? $item->unit_price),
                        'unit_discount' => (float) ($item->unit_discount ?? 0),
                        'unit_price' => (float) $item->unit_price,
                        'quantity' => 0,
                        'total' => 0.0,
                        'payment_methods' => [],
                    ];
                }
                $summary[$key]['quantity'] += (int) $item->quantity;
                $summary[$key]['total'] += ((int) $item->quantity * (float) $item->unit_price);
                if ($paymentMethods && ! in_array($paymentMethods, $summary[$key]['payment_methods'], true)) {
                    $summary[$key]['payment_methods'][] = $paymentMethods;
                }
            }
        }

        return array_values($summary);
    }
}
