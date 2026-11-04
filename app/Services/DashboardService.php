<?php

namespace App\Services;

use App\Enums\CashSessionStatus;
use App\Enums\LocationType;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\StandDisplayStatus;
use App\Models\CashSession;
use App\Models\Location;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stand;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Obtiene los datos correspondientes para el dashboard según el rol del usuario.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(User $user, ?string $filterLocationId = null, ?string $filterStandId = null): array
    {
        if ($user->isAlmacen()) {
            return $this->getWarehouseDashboardData($user);
        }

        return $this->getStoreDashboardData($user, $filterLocationId, $filterStandId);
    }

    /**
     * Dashboard específico para el Almacén Central.
     *
     * @return array<string, mixed>
     */
    private function getWarehouseDashboardData(User $user): array
    {
        $almacenLocation = Location::where('type', LocationType::Almacen)->first();
        $almacenId = $almacenLocation?->id ?? $user->location_id;

        $totalSkus = Stock::where('location_id', $almacenId)->where('quantity', '>', 0)->count();
        $totalUnidades = (int) Stock::where('location_id', $almacenId)->sum('quantity');

        $alertasAlmacen = Stock::with(['product.category', 'location'])
            ->where('location_id', $almacenId)
            ->whereColumn('quantity', '<=', 'min_stock')
            ->orderBy('quantity', 'asc')
            ->take(8)
            ->get();

        $transferenciasRecientes = Transfer::with(['toLocation', 'items.product'])
            ->where('from_location_id', $almacenId)
            ->latest()
            ->take(5)
            ->get();

        return compact(
            'user',
            'totalSkus',
            'totalUnidades',
            'alertasAlmacen',
            'transferenciasRecientes',
            'almacenLocation'
        );
    }

    /**
     * Dashboard para Administración, Supervisores y Vendedores.
     *
     * @return array<string, mixed>
     */
    private function getStoreDashboardData(User $user, ?string $filterLocationId, ?string $filterStandId): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        // 1. Queries de Ventas (Hoy y Mes)
        $salesTodayQuery = Sale::where('status', SaleStatus::Completada)
            ->whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->forUserScope($user, $filterStandId, $filterLocationId);

        $salesMonthQuery = Sale::where('status', SaleStatus::Completada)
            ->where('created_at', '>=', $startOfMonth)
            ->forUserScope($user, $filterStandId, $filterLocationId);

        $ventasHoy = (float) (clone $salesTodayQuery)->sum('total_amount');
        $transaccionesHoy = (clone $salesTodayQuery)->count();
        $ventasMes = (float) (clone $salesMonthQuery)->sum('total_amount');

        // 2. Ventas personales del cajero/vendedor
        $personalSales = $this->getPersonalSales($user, $today);

        // 3. Desglose de métodos de pago hoy
        $payments = $this->getPaymentBreakdown($salesTodayQuery);

        // 4. Caja activa del usuario
        $cajaActiva = CashSession::with(['stand.location'])
            ->where('user_id', $user->id)
            ->where('status', CashSessionStatus::Abierta)
            ->first();

        // 5. Monitor en vivo de stands
        $standsStatus = $this->getStandsLiveStatus($user, $today, $filterLocationId, $filterStandId);
        $cajasAbiertasCount = $standsStatus->where('estado', StandDisplayStatus::Abierta->value)->count();
        $cajasPrecierreCount = $standsStatus->where('estado', StandDisplayStatus::Precierre->value)->count();

        // 6. Alertas de stock bajo
        $stockAlerts = $this->getStockAlerts($user, $filterLocationId);

        // 7. Últimas ventas y productos top
        $ultimasVentas = $this->getRecentSales($user, $filterLocationId);
        $topProductos = $this->getTopProducts($user, $filterLocationId);

        // 8. Opciones de filtros
        $filters = $this->getFilterOptions($user, $filterLocationId);

        return array_merge([
            'user' => $user,
            'ventasHoy' => $ventasHoy,
            'ventasMes' => $ventasMes,
            'transaccionesHoy' => $transaccionesHoy,
            'cajaActiva' => $cajaActiva,
            'standsStatus' => $standsStatus,
            'cajasAbiertasCount' => $cajasAbiertasCount,
            'cajasPrecierreCount' => $cajasPrecierreCount,
            'ultimasVentas' => $ultimasVentas,
            'topProductos' => $topProductos,
            'filterLocationId' => $filterLocationId,
            'filterStandId' => $filterStandId,
        ], $personalSales, $payments, $stockAlerts, $filters);
    }

    /**
     * @return array{ventasPersonalesHoy: float, transaccionesPersonalesHoy: int}
     */
    private function getPersonalSales(User $user, Carbon $today): array
    {
        $ventas = (float) Sale::where('status', SaleStatus::Completada)
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->sum('total_amount');

        $transacciones = Sale::where('status', SaleStatus::Completada)
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->count();

        return [
            'ventasPersonalesHoy' => $ventas,
            'transaccionesPersonalesHoy' => $transacciones,
        ];
    }

    /**
     * @return array{totalEfectivoHoy: float, totalYapePlinHoy: float, totalTarjetaHoy: float}
     */
    private function getPaymentBreakdown($salesTodayQuery): array
    {
        $saleTodaySubQuery = (clone $salesTodayQuery)->select('id');
        $paymentTotalsToday = DB::table('sale_payments')
            ->whereIn('sale_id', $saleTodaySubQuery)
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        return [
            'totalEfectivoHoy' => (float) ($paymentTotalsToday[PaymentMethod::Efectivo->value] ?? 0),
            'totalYapePlinHoy' => (float) (($paymentTotalsToday[PaymentMethod::Yape->value] ?? 0) + ($paymentTotalsToday[PaymentMethod::Plin->value] ?? 0)),
            'totalTarjetaHoy' => (float) ($paymentTotalsToday[PaymentMethod::Tarjeta->value] ?? 0),
        ];
    }

    private function getStandsLiveStatus(User $user, Carbon $today, ?string $filterLocationId, ?string $filterStandId): Collection
    {
        $standsQuery = Stand::with(['location', 'cashSessions' => function ($q) use ($today) {
            $q->whereDate('opening_time', $today)->orWhere('status', CashSessionStatus::Abierta)->orWhere('status', CashSessionStatus::Precierre)->latest('id');
        }, 'cashSessions.user'])->orderBy('location_id')->orderBy('name');

        if ($user->isEncargado()) {
            $standsQuery->where('location_id', $user->location_id);
            if (! empty($filterStandId)) {
                $standsQuery->where('id', $filterStandId);
            }
        } elseif ($user->isAdmin()) {
            if (! empty($filterLocationId)) {
                $standsQuery->where('location_id', $filterLocationId);
            }
            if (! empty($filterStandId)) {
                $standsQuery->where('id', $filterStandId);
            }
        } else {
            $activeStand = $user->getActiveStand();
            $standsQuery->where('id', $activeStand?->id ?? 0);
        }

        $allStands = $standsQuery->get();
        $standIds = $allStands->pluck('id');

        $ventasHoyPorTienda = DB::table('sales')
            ->join('cash_sessions', 'sales.cash_session_id', '=', 'cash_sessions.id')
            ->where('sales.status', SaleStatus::Completada)
            ->whereIn('cash_sessions.stand_id', $standIds)
            ->whereBetween('sales.created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->select('cash_sessions.stand_id', DB::raw('SUM(sales.total_amount) as total'))
            ->groupBy('cash_sessions.stand_id')
            ->pluck('total', 'cash_sessions.stand_id');

        return $allStands->map(function ($stand) use ($ventasHoyPorTienda) {
            $activeSession = $stand->cashSessions->first(fn ($cs) => $cs->status === CashSessionStatus::Abierta || $cs->status === CashSessionStatus::Abierta->value);
            $precierreSession = $stand->cashSessions->first(fn ($cs) => $cs->status === CashSessionStatus::Precierre || $cs->status === CashSessionStatus::Precierre->value);
            $ventasHoyTienda = (float) ($ventasHoyPorTienda[$stand->id] ?? 0);

            $estado = StandDisplayStatus::Cerrada->value;
            $session = null;

            if ($activeSession) {
                $estado = StandDisplayStatus::Abierta->value;
                $session = $activeSession;
            } elseif ($precierreSession) {
                $estado = StandDisplayStatus::Precierre->value;
                $session = $precierreSession;
            }

            return (object) [
                'id' => $stand->id,
                'name' => $stand->name,
                'location_name' => $stand->location->name ?? 'Sede',
                'location_id' => $stand->location_id,
                'base_cash' => (float) $stand->base_cash,
                'estado' => $estado,
                'session' => $session,
                'operator_name' => $session ? $session->cashier_name : 'Sin cajero',
                'ventas_hoy' => $ventasHoyTienda,
            ];
        });
    }

    /**
     * @return array{alertasStock: Collection, totalAlertasStock: int}
     */
    private function getStockAlerts(User $user, ?string $filterLocationId): array
    {
        $stockAlertQuery = Stock::with(['product.category', 'location'])
            ->whereColumn('quantity', '<=', 'min_stock');

        if ($user->location_id && ! $user->isAdmin()) {
            $stockAlertQuery->where('location_id', $user->location_id);
        } elseif (! empty($filterLocationId)) {
            $stockAlertQuery->where('location_id', $filterLocationId);
        }

        return [
            'alertasStock' => $stockAlertQuery->orderBy('quantity', 'asc')->take(6)->get(),
            'totalAlertasStock' => $stockAlertQuery->count(),
        ];
    }

    private function getRecentSales(User $user, ?string $filterLocationId): Collection
    {
        $ultimasVentasQuery = Sale::with(['user', 'cashSession.stand.location', 'payments', 'items.product'])
            ->where('status', SaleStatus::Completada)
            ->latest('created_at');

        if ($user->isVendedor()) {
            $activeStand = $user->getActiveStand();
            if ($activeStand) {
                $ultimasVentasQuery->whereHas('cashSession', fn ($q) => $q->where('stand_id', $activeStand->id));
            } else {
                $ultimasVentasQuery->where('user_id', $user->id);
            }
        } elseif ($user->isEncargado()) {
            $ultimasVentasQuery->whereHas('cashSession.stand', fn ($q) => $q->where('location_id', $user->location_id));
        } elseif (! empty($filterLocationId)) {
            $ultimasVentasQuery->whereHas('cashSession.stand', fn ($q) => $q->where('location_id', $filterLocationId));
        }

        return $ultimasVentasQuery->take(6)->get();
    }

    private function getTopProducts(User $user, ?string $filterLocationId): Collection
    {
        return SaleItem::select('product_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(quantity * unit_price) as total_revenue'))
            ->whereHas('sale', function ($q) use ($user, $filterLocationId) {
                $q->where('status', SaleStatus::Completada);
                if ($user->isEncargado()) {
                    $q->whereHas('cashSession.stand', fn ($cs) => $cs->where('location_id', $user->location_id));
                } elseif (! empty($filterLocationId)) {
                    $q->whereHas('cashSession.stand', fn ($cs) => $cs->where('location_id', $filterLocationId));
                }
            })
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->with('product.category')
            ->take(5)
            ->get();
    }

    /**
     * @return array{filterLocations: Collection, availableStandsList: Collection}
     */
    private function getFilterOptions(User $user, ?string $filterLocationId): array
    {
        $filterLocations = Location::where('type', LocationType::Sede)->orderBy('name')->get();
        $filterStands = Stand::with('location')->orderBy('name');

        if ($user->isEncargado()) {
            $filterStands->where('location_id', $user->location_id);
        } elseif (! empty($filterLocationId)) {
            $filterStands->where('location_id', $filterLocationId);
        }

        return [
            'filterLocations' => $filterLocations,
            'availableStandsList' => $filterStands->get(),
        ];
    }
}
