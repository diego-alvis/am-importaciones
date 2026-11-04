<?php

namespace App\Services;

use App\Enums\LocationType;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Location;
use App\Models\Sale;
use App\Models\Stand;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HistoryService
{
    /**
     * Obtiene los datos completos del historial de ventas, métricas y opciones de filtro.
     *
     * @return array<string, mixed>
     */
    public function getHistoryData(User $user, array $filters): array
    {
        $dateRange = $filters['date_range'] ?? 'hoy';
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $locationId = $filters['location_id'] ?? null;
        $standId = $filters['stand_id'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $scope = $filters['scope'] ?? null;
        $paymentMethod = $filters['payment_method'] ?? null;
        $status = $filters['status'] ?? SaleStatus::Completada->value;

        $query = Sale::with(['user', 'customer', 'cashSession.stand.location', 'payments', 'items.product.category'])
            ->latest('created_at')
            ->forUserScope($user, $standId, $locationId, $userId, $scope);

        $this->applyStatusFilter($query, $status);
        $this->applyDateFilter($query, $dateRange, $dateFrom, $dateTo);
        $this->applyPaymentFilter($query, $paymentMethod);

        $metrics = $this->calculateMetrics($query);
        $sales = $query->paginate(15)->withQueryString();
        $filterOptions = $this->getFilterOptions($user, $locationId);

        return array_merge([
            'sales' => $sales,
            'dateRange' => $dateRange,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'locationId' => $locationId,
            'standId' => $standId,
            'userId' => $userId,
            'scope' => $scope,
            'paymentMethod' => $paymentMethod,
            'status' => $status,
        ], $metrics, $filterOptions);
    }

    private function applyStatusFilter(Builder $query, string $status): void
    {
        if ($status !== 'todos') {
            $query->where('status', $status);
        }
    }

    private function applyDateFilter(Builder $query, string $dateRange, ?string $dateFrom, ?string $dateTo): void
    {
        if ($dateRange === 'hoy') {
            $query->whereBetween('created_at', [
                Carbon::today()->startOfDay(),
                Carbon::today()->endOfDay(),
            ]);
        } elseif ($dateRange === 'ayer') {
            $query->whereBetween('created_at', [
                Carbon::yesterday()->startOfDay(),
                Carbon::yesterday()->endOfDay(),
            ]);
        } elseif ($dateRange === 'semana') {
            $query->where('created_at', '>=', Carbon::now()->startOfWeek());
        } elseif ($dateRange === 'mes') {
            $query->where('created_at', '>=', Carbon::now()->startOfMonth());
        } elseif ($dateRange === 'personalizado' && ! empty($dateFrom) && ! empty($dateTo)) {
            $query->whereBetween('created_at', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ]);
        }
    }

    private function applyPaymentFilter(Builder $query, ?string $paymentMethod): void
    {
        if (! empty($paymentMethod)) {
            $query->whereHas('payments', fn ($q) => $q->where('payment_method', $paymentMethod));
        }
    }

    /**
     * @return array<string, float|int>
     */
    private function calculateMetrics(Builder $query): array
    {
        $metricQuery = clone $query;
        $totalMonto = (float) $metricQuery->sum('total_amount');
        $totalTransacciones = $metricQuery->count();

        $saleSubQuery = (clone $query)->select('id');
        $paymentTotals = DB::table('sale_payments')
            ->whereIn('sale_id', $saleSubQuery)
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        $totalEfectivo = (float) ($paymentTotals[PaymentMethod::Efectivo->value] ?? 0);
        $totalDigital = (float) (($paymentTotals[PaymentMethod::Yape->value] ?? 0) + ($paymentTotals[PaymentMethod::Plin->value] ?? 0) + ($paymentTotals[PaymentMethod::Tarjeta->value] ?? 0));

        return [
            'totalMonto' => $totalMonto,
            'totalTransacciones' => $totalTransacciones,
            'totalEfectivo' => $totalEfectivo,
            'totalDigital' => $totalDigital,
        ];
    }

    /**
     * @return array{locations: Collection, stands: Collection, sellers: Collection}
     */
    private function getFilterOptions(User $user, ?string $locationId): array
    {
        if ($user->isEncargado()) {
            return [
                'locations' => Location::where('id', $user->location_id)->get(),
                'stands' => Stand::where('location_id', $user->location_id)->orderBy('name')->get(),
                'sellers' => User::where('location_id', $user->location_id)->orderBy('name')->get(),
            ];
        }

        if ($user->isVendedor()) {
            $activeStand = $user->getActiveStand();

            return [
                'locations' => $activeStand ? Location::where('id', $activeStand->location_id)->get() : collect(),
                'stands' => $activeStand ? collect([$activeStand]) : collect(),
                'sellers' => collect([$user]),
            ];
        }

        $standsQuery = Stand::with('location')->orderBy('name');
        if (! empty($locationId)) {
            $standsQuery->where('location_id', $locationId);
        }

        return [
            'locations' => Location::where('type', LocationType::Sede->value)->orderBy('name')->get(),
            'stands' => $standsQuery->get(),
            'sellers' => User::orderBy('name')->get(),
        ];
    }
}
