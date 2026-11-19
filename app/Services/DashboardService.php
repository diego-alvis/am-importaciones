<?php

namespace App\Services;

use App\Enums\CashSessionStatus;
use App\Enums\LocationType;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\StandDisplayStatus;
use App\Enums\TransferStatus;
use App\Models\CashSession;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
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

        if ($user->isVendedor()) {
            return $this->getSellerDashboardData($user);
        }

        return $this->getStoreDashboardData($user, $filterLocationId, $filterStandId);
    }

    /**
     * Dashboard operativo "Mi Turno" específico para el Vendedor de un stand fijo.
     *
     * @return array<string, mixed>
     */
    private function getSellerDashboardData(User $user): array
    {
        $today = Carbon::today();
        $activeStand = $user->getActiveStand();
        $locationId = $activeStand?->location_id ?? $user->location_id;

        $salesTodayQuery = Sale::where('status', SaleStatus::Completada)
            ->whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->forUserScope($user);

        $ventasHoy = (float) (clone $salesTodayQuery)->sum('total_amount');
        $transaccionesHoy = (clone $salesTodayQuery)->count();
        $payments = $this->getPaymentBreakdown($salesTodayQuery);

        $cajaActiva = CashSession::with([
            'stand.location',
            'sales' => fn ($q) => $q->where('status', SaleStatus::Completada)->with('payments'),
        ])
            ->where('user_id', $user->id)
            ->where('status', CashSessionStatus::Abierta)
            ->first();

        $cajaPrecierre = ! $cajaActiva
            ? CashSession::with(['stand.location', 'sales.payments'])
                ->where('user_id', $user->id)
                ->where('status', CashSessionStatus::Precierre)
                ->latest('closing_time')
                ->first()
            : null;

        $cashMetrics = $cajaActiva ? $cajaActiva->getMetrics() : null;
        $ventasTurno = $cashMetrics ? (float) $cashMetrics->total_ventas : $ventasHoy;
        $efectivoTurno = $cashMetrics ? (float) $cashMetrics->efectivo : (float) $payments['totalEfectivoHoy'];

        $metaComision = (float) (Setting::where('key', 'meta_comision')->value('value') ?? 400.00);
        $bonoComision = (float) (Setting::where('key', 'bono_comision')->value('value') ?? 10.00);

        $bonosGanados = $metaComision > 0 ? (int) floor($ventasTurno / $metaComision) : 0;
        $montoBonoGanado = round($bonosGanados * $bonoComision, 2);
        $progresoEnTramo = $metaComision > 0 ? fmod($ventasTurno, $metaComision) : 0.0;
        $porcentajeMeta = $metaComision > 0 ? min(100, (int) round(($progresoEnTramo / $metaComision) * 100)) : 0;
        $faltanteParaBono = $metaComision > 0 ? round($metaComision - $progresoEnTramo, 2) : 0.0;
        $siguienteMeta = ($bonosGanados + 1) * $metaComision;

        $pedidosActivos = $locationId
            ? Transfer::with(['fromLocation', 'items.product'])
                ->where('to_location_id', $locationId)
                ->whereIn('status', [TransferStatus::EnTransito, TransferStatus::Solicitado])
                ->latest()
                ->take(4)
                ->get()
            : new Collection;

        $ultimasVentas = $this->getRecentSales($user, null);
        $topProductos = $this->getTopProducts($user, null);
        $stockAlerts = $this->getStockAlerts($user, null);

        return array_merge([
            'user' => $user,
            'activeStand' => $activeStand,
            'ventasHoy' => $ventasHoy,
            'transaccionesHoy' => $transaccionesHoy,
            'cajaActiva' => $cajaActiva,
            'cajaPrecierre' => $cajaPrecierre,
            'ventasTurno' => $ventasTurno,
            'efectivoTurno' => $efectivoTurno,
            'metaComision' => $metaComision,
            'bonoComision' => $bonoComision,
            'bonosGanados' => $bonosGanados,
            'montoBonoGanado' => $montoBonoGanado,
            'progresoEnTramo' => $progresoEnTramo,
            'porcentajeMeta' => $porcentajeMeta,
            'faltanteParaBono' => $faltanteParaBono,
            'siguienteMeta' => $siguienteMeta,
            'pedidosActivos' => $pedidosActivos,
            'ultimasVentas' => $ultimasVentas,
            'topProductos' => $topProductos,
        ], $payments, $stockAlerts);
    }

    /**
     * Dashboard específico para el Almacén Central.
     *
     * @return array<string, mixed>
     */
    private function getWarehouseDashboardData(User $user): array
    {
        $today = Carbon::today();
        $startOfMonth = $today->copy()->startOfMonth();
        $almacenLocation = Location::where('type', LocationType::Almacen)->first();
        $almacenId = $almacenLocation?->id ?? $user->location_id;

        $baseWarehouseStock = Stock::query()
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->whereNull('products.deleted_at')
            ->where('stocks.location_id', $almacenId);

        $totalCatalogProducts = Product::count();
        $totalSkus = (clone $baseWarehouseStock)->where('stocks.quantity', '>', 0)->count();
        $totalUnidades = (int) (clone $baseWarehouseStock)->sum('stocks.quantity');
        $valorizacionAlmacen = (float) (clone $baseWarehouseStock)->sum(DB::raw('stocks.quantity * products.base_price'));
        $agotadosCount = (clone $baseWarehouseStock)->where('stocks.quantity', '<=', 0)->count();
        $criticosCount = (clone $baseWarehouseStock)
            ->where('stocks.quantity', '>', 0)
            ->whereColumn('stocks.quantity', '<=', 'stocks.min_stock')
            ->count();
        $alertasTotalCount = $agotadosCount + $criticosCount;

        $solicitudesPendientesQuery = Transfer::with(['toLocation', 'requestedBy', 'items.product'])
            ->where('from_location_id', $almacenId)
            ->where('status', TransferStatus::Solicitado)
            ->latest();

        $solicitudesPendientesCount = (clone $solicitudesPendientesQuery)->count();
        $solicitudesPendientes = $solicitudesPendientesQuery->take(6)->get();

        $enTransitoCount = Transfer::where('from_location_id', $almacenId)
            ->where('status', TransferStatus::EnTransito)
            ->count();

        $despachosHoyCount = Transfer::where('from_location_id', $almacenId)
            ->whereIn('status', [TransferStatus::EnTransito, TransferStatus::Recibido])
            ->whereBetween('updated_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->count();

        $unidadesDespachadasMes = (int) DB::table('transfer_items')
            ->join('transfers', 'transfer_items.transfer_id', '=', 'transfers.id')
            ->where('transfers.from_location_id', $almacenId)
            ->whereIn('transfers.status', [TransferStatus::EnTransito->value, TransferStatus::Recibido->value])
            ->whereBetween('transfers.created_at', [$startOfMonth, $today->copy()->endOfDay()])
            ->sum('transfer_items.quantity');

        $alertasAlmacen = Stock::with(['product.category', 'location'])
            ->whereHas('product')
            ->where('location_id', $almacenId)
            ->whereColumn('quantity', '<=', 'min_stock')
            ->orderBy('quantity', 'asc')
            ->take(10)
            ->get();

        $transferenciasRecientes = Transfer::with(['toLocation', 'items.product'])
            ->where('from_location_id', $almacenId)
            ->where('status', '!=', TransferStatus::Solicitado)
            ->latest()
            ->take(6)
            ->get();

        $despachosUltimos7Dias = $this->getWarehouseDispatches7Days($almacenId, $today);
        $stockPorCategoria = $this->getWarehouseCategoryDistribution($almacenId);
        $necesidadesSedes = $this->getStoreLocationsNeeds($almacenId);

        return compact(
            'user',
            'almacenLocation',
            'totalCatalogProducts',
            'totalSkus',
            'totalUnidades',
            'valorizacionAlmacen',
            'agotadosCount',
            'criticosCount',
            'alertasTotalCount',
            'solicitudesPendientesCount',
            'solicitudesPendientes',
            'enTransitoCount',
            'despachosHoyCount',
            'unidadesDespachadasMes',
            'alertasAlmacen',
            'transferenciasRecientes',
            'despachosUltimos7Dias',
            'stockPorCategoria',
            'necesidadesSedes'
        );
    }

    /**
     * Dashboard para Administración y Supervisores.
     *
     * @return array<string, mixed>
     */
    private function getStoreDashboardData(User $user, ?string $filterLocationId, ?string $filterStandId): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $startOfMonth = Carbon::now()->startOfMonth();

        // 1. Queries de Ventas (Hoy, Ayer y Mes)
        $salesTodayQuery = Sale::where('status', SaleStatus::Completada)
            ->whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->forUserScope($user, $filterStandId, $filterLocationId);

        $salesYesterdayQuery = Sale::where('status', SaleStatus::Completada)
            ->whereBetween('created_at', [$yesterday->copy()->startOfDay(), $yesterday->copy()->endOfDay()])
            ->forUserScope($user, $filterStandId, $filterLocationId);

        $salesMonthQuery = Sale::where('status', SaleStatus::Completada)
            ->where('created_at', '>=', $startOfMonth)
            ->forUserScope($user, $filterStandId, $filterLocationId);

        $ventasHoy = (float) (clone $salesTodayQuery)->sum('total_amount');
        $transaccionesHoy = (clone $salesTodayQuery)->count();
        $ventasAyer = (float) (clone $salesYesterdayQuery)->sum('total_amount');
        $variacionContraAyer = $ventasAyer > 0
            ? round((($ventasHoy - $ventasAyer) / $ventasAyer) * 100, 1)
            : ($ventasHoy > 0 ? 100.0 : 0.0);

        $ventasMes = (float) (clone $salesMonthQuery)->sum('total_amount');
        $transaccionesMes = (clone $salesMonthQuery)->count();

        $ticketPromedioHoy = $transaccionesHoy > 0 ? round($ventasHoy / $transaccionesHoy, 2) : 0.0;
        $ticketPromedioMes = $transaccionesMes > 0 ? round($ventasMes / $transaccionesMes, 2) : 0.0;

        $descuentosHoy = (float) (clone $salesTodayQuery)->sum('discount_amount');
        $descuentosMes = (float) (clone $salesMonthQuery)->sum('discount_amount');

        $pedidosPendientesCount = Transfer::whereIn('status', [TransferStatus::Solicitado, TransferStatus::EnTransito])
            ->when($user->isEncargado(), fn ($q) => $q->where('to_location_id', $user->location_id))
            ->when(! $user->isEncargado() && ! empty($filterLocationId), fn ($q) => $q->where('to_location_id', $filterLocationId))
            ->count();

        // 2. Ventas personales del cajero/vendedor
        $personalSales = $this->getPersonalSales($user, $today);

        // 3. Desglose de métodos de pago (Hoy y Mes)
        $payments = $this->getPaymentBreakdown($salesTodayQuery);
        $paymentsMonth = $this->getPaymentBreakdown($salesMonthQuery, 'Mes');

        // 5. Monitor en vivo de stands
        $standsStatus = $this->getStandsLiveStatus($user, $today, $filterLocationId, $filterStandId);
        $cajasAbiertasCount = $standsStatus->where('estado', StandDisplayStatus::Abierta->value)->count();
        $cajasPrecierreCount = $standsStatus->where('estado', StandDisplayStatus::Precierre->value)->count();

        // 6. Analítica avanzada (7 días, rendimiento por sede y categorías)
        $ventasUltimos7Dias = $this->getLast7DaysTrend($user, $today, $filterLocationId, $filterStandId);
        $rendimientoSedes = $this->getLocationsPerformance($user, $today, $startOfMonth);
        $ventasPorCategoria = $this->getCategorySalesBreakdown($salesMonthQuery);

        // 7. Alertas de stock bajo
        $stockAlerts = $this->getStockAlerts($user, $filterLocationId);

        // 8. Últimas ventas y productos top
        $ultimasVentas = $this->getRecentSales($user, $filterLocationId);
        $topProductos = $this->getTopProducts($user, $filterLocationId);

        // 9. Opciones de filtros
        $filters = $this->getFilterOptions($user, $filterLocationId);

        return array_merge([
            'user' => $user,
            'ventasHoy' => $ventasHoy,
            'ventasAyer' => $ventasAyer,
            'variacionContraAyer' => $variacionContraAyer,
            'ventasMes' => $ventasMes,
            'transaccionesHoy' => $transaccionesHoy,
            'transaccionesMes' => $transaccionesMes,
            'ticketPromedioHoy' => $ticketPromedioHoy,
            'ticketPromedioMes' => $ticketPromedioMes,
            'descuentosHoy' => $descuentosHoy,
            'descuentosMes' => $descuentosMes,
            'pedidosPendientesCount' => $pedidosPendientesCount,
            'standsStatus' => $standsStatus,
            'cajasAbiertasCount' => $cajasAbiertasCount,
            'cajasPrecierreCount' => $cajasPrecierreCount,
            'ventasUltimos7Dias' => $ventasUltimos7Dias,
            'rendimientoSedes' => $rendimientoSedes,
            'ventasPorCategoria' => $ventasPorCategoria,
            'ultimasVentas' => $ultimasVentas,
            'topProductos' => $topProductos,
            'filterLocationId' => $filterLocationId,
            'filterStandId' => $filterStandId,
        ], $personalSales, $payments, $paymentsMonth, $stockAlerts, $filters);
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
     * @return array<string, float>
     */
    private function getPaymentBreakdown(mixed $salesQuery, string $suffix = 'Hoy'): array
    {
        $saleSubQuery = (clone $salesQuery)->select('id');
        $paymentTotals = DB::table('sale_payments')
            ->whereIn('sale_id', $saleSubQuery)
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        return [
            "totalEfectivo{$suffix}" => (float) ($paymentTotals[PaymentMethod::Efectivo->value] ?? 0),
            "totalYapePlin{$suffix}" => (float) (($paymentTotals[PaymentMethod::Yape->value] ?? 0) + ($paymentTotals[PaymentMethod::Plin->value] ?? 0)),
            "totalTarjeta{$suffix}" => (float) ($paymentTotals[PaymentMethod::Tarjeta->value] ?? 0),
        ];
    }

    /**
     * Evolución diaria de ventas, tickets y descuentos en los últimos 7 días.
     *
     * @return list<array{
     *     date: string,
     *     short_day: string,
     *     day_num: string,
     *     full_date: string,
     *     revenue: float,
     *     tickets: int,
     *     avg_ticket: float,
     *     discounts: float,
     *     is_today: bool
     * }>
     */
    private function getLast7DaysTrend(User $user, Carbon $today, ?string $filterLocationId, ?string $filterStandId): array
    {
        $startDate = $today->copy()->subDays(6)->startOfDay();
        $endDate = $today->copy()->endOfDay();

        $rows = Sale::where('status', SaleStatus::Completada)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->forUserScope($user, $filterStandId, $filterLocationId)
            ->selectRaw('DATE(created_at) as sale_date, SUM(total_amount) as revenue, COUNT(*) as tickets, SUM(discount_amount) as discounts')
            ->groupBy('sale_date')
            ->get()
            ->keyBy('sale_date');

        $dayNames = [
            1 => 'Lun',
            2 => 'Mar',
            3 => 'Mié',
            4 => 'Jue',
            5 => 'Vie',
            6 => 'Sáb',
            7 => 'Dom',
        ];

        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $key = $date->toDateString();
            $row = $rows->get($key);

            $revenue = $row ? (float) $row->revenue : 0.0;
            $tickets = $row ? (int) $row->tickets : 0;
            $discounts = $row ? (float) $row->discounts : 0.0;

            $trend[] = [
                'date' => $key,
                'short_day' => $dayNames[$date->dayOfWeekIso],
                'day_num' => $date->format('d/m'),
                'full_date' => $date->format('d/m/Y'),
                'revenue' => round($revenue, 2),
                'tickets' => $tickets,
                'avg_ticket' => $tickets > 0 ? round($revenue / $tickets, 2) : 0.0,
                'discounts' => round($discounts, 2),
                'is_today' => $i === 0,
            ];
        }

        return $trend;
    }

    /**
     * Comparativa de ventas y tickets entre sedes (Hoy y Mes).
     */
    private function getLocationsPerformance(User $user, Carbon $today, Carbon $startOfMonth): Collection
    {
        $locations = Location::where('type', LocationType::Sede->value)
            ->when($user->isEncargado(), fn ($q) => $q->where('id', $user->location_id))
            ->withCount('stands')
            ->orderBy('name')
            ->get();

        $todayByLocation = DB::table('sales')
            ->join('cash_sessions', 'sales.cash_session_id', '=', 'cash_sessions.id')
            ->join('stands', 'cash_sessions.stand_id', '=', 'stands.id')
            ->whereNull('sales.deleted_at')
            ->where('sales.status', SaleStatus::Completada->value)
            ->whereBetween('sales.created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->select('stands.location_id', DB::raw('SUM(sales.total_amount) as total'), DB::raw('COUNT(sales.id) as tickets'))
            ->groupBy('stands.location_id')
            ->get()
            ->keyBy('location_id');

        $monthByLocation = DB::table('sales')
            ->join('cash_sessions', 'sales.cash_session_id', '=', 'cash_sessions.id')
            ->join('stands', 'cash_sessions.stand_id', '=', 'stands.id')
            ->whereNull('sales.deleted_at')
            ->where('sales.status', SaleStatus::Completada->value)
            ->where('sales.created_at', '>=', $startOfMonth)
            ->select('stands.location_id', DB::raw('SUM(sales.total_amount) as total'), DB::raw('COUNT(sales.id) as tickets'))
            ->groupBy('stands.location_id')
            ->get()
            ->keyBy('location_id');

        $sumToday = (float) $todayByLocation->sum('total');
        $sumMonth = (float) $monthByLocation->sum('total');

        return $locations->map(function (Location $location) use ($todayByLocation, $monthByLocation, $sumToday, $sumMonth) {
            $todayRow = $todayByLocation->get($location->id);
            $monthRow = $monthByLocation->get($location->id);

            $ventasHoy = $todayRow ? (float) $todayRow->total : 0.0;
            $ticketsHoy = $todayRow ? (int) $todayRow->tickets : 0;
            $ventasMes = $monthRow ? (float) $monthRow->total : 0.0;
            $ticketsMes = $monthRow ? (int) $monthRow->tickets : 0;

            return [
                'id' => $location->id,
                'name' => $location->name,
                'stands_count' => (int) $location->stands_count,
                'ventas_hoy' => round($ventasHoy, 2),
                'tickets_hoy' => $ticketsHoy,
                'ventas_mes' => round($ventasMes, 2),
                'tickets_mes' => $ticketsMes,
                'pct_hoy' => $sumToday > 0 ? round(($ventasHoy / $sumToday) * 100, 1) : 0.0,
                'pct_mes' => $sumMonth > 0 ? round(($ventasMes / $sumMonth) * 100, 1) : 0.0,
            ];
        });
    }

    /**
     * Distribución de ventas del mes por categoría de producto.
     */
    private function getCategorySalesBreakdown($salesMonthQuery): Collection
    {
        $saleSubQuery = (clone $salesMonthQuery)->select('id');

        $rows = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereIn('sale_items.sale_id', $saleSubQuery)
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('SUM(sale_items.quantity) as total_qty'),
                DB::raw('SUM(sale_items.quantity * sale_items.unit_price) as total_revenue')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_revenue')
            ->get();

        $totalRevenue = (float) $rows->sum('total_revenue');

        return $rows->map(function ($row) use ($totalRevenue) {
            $revenue = (float) $row->total_revenue;

            return (object) [
                'id' => $row->id,
                'name' => $row->name,
                'total_qty' => (int) $row->total_qty,
                'total_revenue' => round($revenue, 2),
                'pct' => $totalRevenue > 0 ? round(($revenue / $totalRevenue) * 100, 1) : 0.0,
            ];
        });
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
            $activeSession = $stand->cashSessions->first(fn ($cs) => $cs->status === CashSessionStatus::Abierta);
            $precierreSession = $stand->cashSessions->first(fn ($cs) => $cs->status === CashSessionStatus::Precierre);
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
        })->sortBy(function ($stand) {
            $priority = match ($stand->estado) {
                StandDisplayStatus::Abierta->value => 0,
                StandDisplayStatus::Precierre->value => 1,
                default => 2,
            };

            return sprintf('%d-%05d-%s', $priority, $stand->location_id, $stand->name);
        })->values();
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
                if ($user->isVendedor()) {
                    $activeStand = $user->getActiveStand();
                    if ($activeStand) {
                        $q->whereHas('cashSession', fn ($cs) => $cs->where('stand_id', $activeStand->id));
                    } else {
                        $q->where('user_id', $user->id);
                    }
                } elseif ($user->isEncargado()) {
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

    /**
     * Evolución diaria de despachos y unidades enviadas desde Almacén en los últimos 7 días.
     *
     * @return list<array{
     *     date: string,
     *     short_day: string,
     *     day_num: string,
     *     full_date: string,
     *     units: int,
     *     transfers: int,
     *     is_today: bool
     * }>
     */
    private function getWarehouseDispatches7Days(?int $almacenId, Carbon $today): array
    {
        $startDate = $today->copy()->subDays(6)->startOfDay();
        $endDate = $today->copy()->endOfDay();

        $rows = DB::table('transfers')
            ->join('transfer_items', 'transfers.id', '=', 'transfer_items.transfer_id')
            ->where('transfers.from_location_id', $almacenId)
            ->whereIn('transfers.status', [TransferStatus::EnTransito->value, TransferStatus::Recibido->value])
            ->whereBetween('transfers.created_at', [$startDate, $endDate])
            ->selectRaw('DATE(transfers.created_at) as dispatch_date, COUNT(DISTINCT transfers.id) as transfers_count, COALESCE(SUM(transfer_items.quantity), 0) as units')
            ->groupBy('dispatch_date')
            ->get()
            ->keyBy('dispatch_date');

        $dayNames = [
            1 => 'Lun',
            2 => 'Mar',
            3 => 'Mié',
            4 => 'Jue',
            5 => 'Vie',
            6 => 'Sáb',
            7 => 'Dom',
        ];

        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $key = $date->toDateString();
            $row = $rows->get($key);

            $trend[] = [
                'date' => $key,
                'short_day' => $dayNames[$date->dayOfWeekIso],
                'day_num' => $date->format('d/m'),
                'full_date' => $date->format('d/m/Y'),
                'units' => $row ? (int) $row->units : 0,
                'transfers' => $row ? (int) $row->transfers_count : 0,
                'is_today' => $i === 0,
            ];
        }

        return $trend;
    }

    /**
     * Distribución del inventario físico en Almacén agrupada por categoría.
     */
    private function getWarehouseCategoryDistribution(?int $almacenId): Collection
    {
        $rows = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereNull('products.deleted_at')
            ->where('stocks.location_id', $almacenId)
            ->select(
                'categories.id as category_id',
                'categories.name as category_name',
                DB::raw('COUNT(*) as skus_count'),
                DB::raw('COALESCE(SUM(stocks.quantity), 0) as units_count'),
                DB::raw('SUM(CASE WHEN stocks.quantity <= stocks.min_stock THEN 1 ELSE 0 END) as low_count')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('units_count')
            ->get();

        $totalUnits = (int) $rows->sum('units_count');

        return $rows->map(function (object $row) use ($totalUnits): array {
            $units = (int) $row->units_count;

            return [
                'category_id' => (int) $row->category_id,
                'category_name' => $row->category_name,
                'skus_count' => (int) $row->skus_count,
                'units_count' => $units,
                'low_count' => (int) $row->low_count,
                'pct' => $totalUnits > 0 ? round(($units / $totalUnits) * 100, 1) : 0.0,
            ];
        });
    }

    /**
     * Resumen de necesidad de reposición por cada sede comercial.
     */
    private function getStoreLocationsNeeds(?int $almacenId): Collection
    {
        $sedes = Location::where('type', LocationType::Sede->value)->orderBy('name')->get();
        $sedeIds = $sedes->pluck('id');

        $stockStats = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->whereNull('products.deleted_at')
            ->whereIn('stocks.location_id', $sedeIds)
            ->select(
                'stocks.location_id',
                DB::raw('SUM(CASE WHEN stocks.quantity <= 0 THEN 1 ELSE 0 END) as agotados_count'),
                DB::raw('SUM(CASE WHEN stocks.quantity > 0 AND stocks.quantity <= stocks.min_stock THEN 1 ELSE 0 END) as bajo_stock_count'),
                DB::raw('COALESCE(SUM(stocks.quantity), 0) as total_units')
            )
            ->groupBy('stocks.location_id')
            ->get()
            ->keyBy('location_id');

        $transferStats = DB::table('transfers')
            ->where('from_location_id', $almacenId)
            ->whereIn('to_location_id', $sedeIds)
            ->whereIn('status', [TransferStatus::Solicitado->value, TransferStatus::EnTransito->value])
            ->selectRaw(
                'to_location_id, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as solicitudes_count, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as en_transito_count',
                [TransferStatus::Solicitado->value, TransferStatus::EnTransito->value]
            )
            ->groupBy('to_location_id')
            ->get()
            ->keyBy('to_location_id');

        return $sedes->map(function (Location $sede) use ($stockStats, $transferStats): array {
            $stock = $stockStats->get($sede->id);
            $transfer = $transferStats->get($sede->id);

            return [
                'id' => $sede->id,
                'name' => $sede->name,
                'agotados_count' => $stock ? (int) $stock->agotados_count : 0,
                'bajo_stock_count' => $stock ? (int) $stock->bajo_stock_count : 0,
                'total_units' => $stock ? (int) $stock->total_units : 0,
                'solicitudes_count' => $transfer ? (int) $transfer->solicitudes_count : 0,
                'en_transito_count' => $transfer ? (int) $transfer->en_transito_count : 0,
            ];
        });
    }
}
