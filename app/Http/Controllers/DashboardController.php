<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Stand;
use App\Models\Stock;
use App\Models\Transfer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $user = auth()->user();

        // Filtros (Admin o Supervisor)
        $filterLocationId = $request->input('location_id');
        $filterStandId = $request->input('stand_id');

        // ==========================================
        // CASO 1: VISTA DE ALMACÉN CENTRAL
        // ==========================================
        if ($user->isAlmacen()) {
            $almacenLocation = Location::where('type', 'almacen')->first();
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

            return view('dashboard', compact(
                'user',
                'totalSkus',
                'totalUnidades',
                'alertasAlmacen',
                'transferenciasRecientes',
                'almacenLocation'
            ));
        }

        // ==========================================
        // CASO 2: ADMIN, SUPERVISOR Y VENDEDORES
        // ==========================================
        
        // Query base para ventas de hoy
        $salesTodayQuery = Sale::where('status', 'completada')
            ->whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()]);

        // Query base para ventas del mes
        $salesMonthQuery = Sale::where('status', 'completada')
            ->where('created_at', '>=', $startOfMonth);

        // Ventas personales del usuario (especialmente relevante para el supervisor y vendedor)
        $ventasPersonalesHoy = (float) Sale::where('status', 'completada')
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->sum('total_amount');

        $transaccionesPersonalesHoy = Sale::where('status', 'completada')
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->count();

        // Aplicar restricciones de ámbito
        if ($user->isVendedor()) {
            $activeStand = $user->getActiveStand();
            $standId = $activeStand?->id;
            if ($standId) {
                $salesTodayQuery->whereHas('cashSession', fn($q) => $q->where('stand_id', $standId));
                $salesMonthQuery->whereHas('cashSession', fn($q) => $q->where('stand_id', $standId));
            } else {
                $salesTodayQuery->where('user_id', $user->id);
                $salesMonthQuery->where('user_id', $user->id);
            }
        } elseif ($user->isEncargado()) {
            // Restringir a su sede
            $salesTodayQuery->whereHas('cashSession.stand', fn($q) => $q->where('location_id', $user->location_id));
            $salesMonthQuery->whereHas('cashSession.stand', fn($q) => $q->where('location_id', $user->location_id));

            if (!empty($filterStandId)) {
                $salesTodayQuery->whereHas('cashSession', fn($q) => $q->where('stand_id', $filterStandId));
                $salesMonthQuery->whereHas('cashSession', fn($q) => $q->where('stand_id', $filterStandId));
            }
        } else {
            // Admin: filtros dinámicos
            if (!empty($filterLocationId)) {
                $salesTodayQuery->whereHas('cashSession.stand', fn($q) => $q->where('location_id', $filterLocationId));
                $salesMonthQuery->whereHas('cashSession.stand', fn($q) => $q->where('location_id', $filterLocationId));
            }
            if (!empty($filterStandId)) {
                $salesTodayQuery->whereHas('cashSession', fn($q) => $q->where('stand_id', $filterStandId));
                $salesMonthQuery->whereHas('cashSession', fn($q) => $q->where('stand_id', $filterStandId));
            }
        }

        $ventasHoy = (float) (clone $salesTodayQuery)->sum('total_amount');
        $transaccionesHoy = (clone $salesTodayQuery)->count();
        $ventasMes = (float) (clone $salesMonthQuery)->sum('total_amount');

        // Desglose de Pagos Hoy
        $saleIdsToday = (clone $salesTodayQuery)->pluck('id');
        $totalEfectivoHoy = (float) DB::table('sale_payments')->whereIn('sale_id', $saleIdsToday)->where('payment_method', 'efectivo')->sum('amount');
        $totalYapePlinHoy = (float) DB::table('sale_payments')->whereIn('sale_id', $saleIdsToday)->whereIn('payment_method', ['yape', 'plin'])->sum('amount');
        $totalTarjetaHoy = (float) DB::table('sale_payments')->whereIn('sale_id', $saleIdsToday)->where('payment_method', 'tarjeta')->sum('amount');

        // Estado de Caja personal activa
        $cajaActiva = CashSession::with(['stand.location'])
            ->where('user_id', $user->id)
            ->where('status', 'abierta')
            ->first();

        // MONITOR OPERATIVO EN VIVO DE TIENDAS / STANDS
        $standsQuery = Stand::with(['location', 'cashSessions' => function ($q) use ($today) {
            $q->whereDate('opening_time', $today)->orWhere('status', 'abierta')->orWhere('status', 'precierre')->latest('id');
        }, 'cashSessions.user'])->orderBy('location_id')->orderBy('name');

        if ($user->isEncargado()) {
            $standsQuery->where('location_id', $user->location_id);
            if (!empty($filterStandId)) {
                $standsQuery->where('id', $filterStandId);
            }
        } elseif ($user->isAdmin()) {
            if (!empty($filterLocationId)) {
                $standsQuery->where('location_id', $filterLocationId);
            }
            if (!empty($filterStandId)) {
                $standsQuery->where('id', $filterStandId);
            }
        } else {
            $activeStand = $user->getActiveStand();
            $standsQuery->where('id', $activeStand?->id ?? 0);
        }

        $allStands = $standsQuery->get();

        // Enriquecer cada tienda con su estado operativo actual
        $standsStatus = $allStands->map(function ($stand) use ($today) {
            // Buscar sesión de caja abierta o en precierre
            $activeSession = $stand->cashSessions->first(fn($cs) => $cs->status === 'abierta');
            $precierreSession = $stand->cashSessions->first(fn($cs) => $cs->status === 'precierre');
            $latestSession = $stand->cashSessions->first();

            // Ventas de hoy en esta tienda
            $ventasHoyTienda = (float) Sale::where('status', 'completada')
                ->whereHas('cashSession', fn($q) => $q->where('stand_id', $stand->id))
                ->whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
                ->sum('total_amount');

            $estado = 'cerrada';
            $session = null;

            if ($activeSession) {
                $estado = 'abierta';
                $session = $activeSession;
            } elseif ($precierreSession) {
                $estado = 'precierre';
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
                'operator_name' => $session?->user?->name ?? 'Sin cajero',
                'ventas_hoy' => $ventasHoyTienda,
            ];
        });

        // Contadores globales de cajas
        $cajasAbiertasCount = $standsStatus->where('estado', 'abierta')->count();
        $cajasPrecierreCount = $standsStatus->where('estado', 'precierre')->count();

        // Alertas de Stock Bajo
        $stockAlertQuery = Stock::with(['product.category', 'location'])
            ->whereColumn('quantity', '<=', 'min_stock');

        if ($user->location_id && !$user->isAdmin()) {
            $stockAlertQuery->where('location_id', $user->location_id);
        } elseif (!empty($filterLocationId)) {
            $stockAlertQuery->where('location_id', $filterLocationId);
        }

        $alertasStock = $stockAlertQuery->orderBy('quantity', 'asc')->take(6)->get();
        $totalAlertasStock = $stockAlertQuery->count();

        // Últimas Ventas
        $ultimasVentasQuery = Sale::with(['user', 'cashSession.stand.location', 'payments', 'items.product'])
            ->where('status', 'completada')
            ->latest('created_at');

        if ($user->isVendedor()) {
            $activeStand = $user->getActiveStand();
            if ($activeStand) {
                $ultimasVentasQuery->whereHas('cashSession', fn($q) => $q->where('stand_id', $activeStand->id));
            } else {
                $ultimasVentasQuery->where('user_id', $user->id);
            }
        } elseif ($user->isEncargado()) {
            $ultimasVentasQuery->whereHas('cashSession.stand', fn($q) => $q->where('location_id', $user->location_id));
        } elseif (!empty($filterLocationId)) {
            $ultimasVentasQuery->whereHas('cashSession.stand', fn($q) => $q->where('location_id', $filterLocationId));
        }

        $ultimasVentas = $ultimasVentasQuery->take(6)->get();

        // Top 5 Productos Más Vendidos
        $topProductos = SaleItem::select('product_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(quantity * unit_price) as total_revenue'))
            ->whereHas('sale', fn($q) => $q->where('status', 'completada'))
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->with('product.category')
            ->take(5)
            ->get();

        // Opciones para filtros de Admin y Supervisor
        $filterLocations = Location::where('type', 'sede')->orderBy('name')->get();
        $filterStands = Stand::with('location')->orderBy('name');
        if ($user->isEncargado()) {
            $filterStands->where('location_id', $user->location_id);
        } elseif (!empty($filterLocationId)) {
            $filterStands->where('location_id', $filterLocationId);
        }
        $availableStandsList = $filterStands->get();

        return view('dashboard', compact(
            'user',
            'ventasHoy',
            'ventasMes',
            'transaccionesHoy',
            'ventasPersonalesHoy',
            'transaccionesPersonalesHoy',
            'cajaActiva',
            'standsStatus',
            'cajasAbiertasCount',
            'cajasPrecierreCount',
            'alertasStock',
            'totalAlertasStock',
            'totalEfectivoHoy',
            'totalYapePlinHoy',
            'totalTarjetaHoy',
            'ultimasVentas',
            'topProductos',
            'filterLocations',
            'availableStandsList',
            'filterLocationId',
            'filterStandId'
        ));
    }
}
