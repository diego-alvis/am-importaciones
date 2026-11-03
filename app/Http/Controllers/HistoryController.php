<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Sale;
use App\Models\Stand;
use App\Models\Stock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        $dateRange = $request->input('date_range', 'hoy'); // Por defecto 'hoy' para ver de inmediato las ventas del día
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $locationId = $request->input('location_id');
        $standId = $request->input('stand_id');
        $userId = $request->input('user_id');
        $scope = $request->input('scope'); // 'personal' o 'sede' (para supervisores)
        $paymentMethod = $request->input('payment_method');
        $status = $request->input('status', 'completada');

        $query = Sale::with(['user', 'customer', 'cashSession.stand.location', 'payments', 'items.product.category'])
            ->latest('created_at');

        // 1. RESTRICCIONES SEGÚN ROL (Multi-tienda y Sedes)
        if ($user->isVendedor()) {
            // El vendedor solo ve ventas de su tienda activa o donde haya participado
            $activeStand = $user->getActiveStand();
            if ($activeStand) {
                $query->whereHas('cashSession', function ($q) use ($activeStand) {
                    $q->where('stand_id', $activeStand->id);
                });
            } else {
                $query->where('user_id', $user->id);
            }
        } elseif ($user->isEncargado()) {
            // El supervisor está restringido a su sede
            $query->whereHas('cashSession.stand', function ($q) use ($user) {
                $q->where('location_id', $user->location_id);
            });

            // Si seleccionó ver solo sus ventas personales
            if ($scope === 'personal') {
                $query->where('user_id', $user->id);
            } elseif (!empty($userId)) {
                $query->where('user_id', $userId);
            }

            // Filtro por tienda específica dentro de su sede
            if (!empty($standId)) {
                $query->whereHas('cashSession', function ($q) use ($standId) {
                    $q->where('stand_id', $standId);
                });
            }
        } else {
            // Admin: acceso global con filtros opcionales
            if (!empty($locationId)) {
                $query->whereHas('cashSession.stand', function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                });
            }

            if (!empty($standId)) {
                $query->whereHas('cashSession', function ($q) use ($standId) {
                    $q->where('stand_id', $standId);
                });
            }

            if (!empty($userId)) {
                $query->where('user_id', $userId);
            }
        }

        // 2. Filtro por Estado
        if ($status !== 'todos') {
            $query->where('status', $status);
        }

        // 3. Filtro de Fechas (Ajustado con timezone de Lima)
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
        } elseif ($dateRange === 'personalizado' && !empty($dateFrom) && !empty($dateTo)) {
            $query->whereBetween('created_at', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ]);
        }
        // 'todos' no aplica filtro de fecha

        // 4. Filtro por Método de Pago
        if (!empty($paymentMethod)) {
            $query->whereHas('payments', function ($q) use ($paymentMethod) {
                $q->where('payment_method', $paymentMethod);
            });
        }

        // Calcular Métricas Financieras del Conjunto Filtrado
        $metricQuery = clone $query;
        $totalMonto = (float) $metricQuery->sum('total_amount');
        $totalTransacciones = $metricQuery->count();

        $saleIds = (clone $query)->pluck('id');
        $totalEfectivo = (float) DB::table('sale_payments')->whereIn('sale_id', $saleIds)->where('payment_method', 'efectivo')->sum('amount');
        $totalDigital = (float) DB::table('sale_payments')->whereIn('sale_id', $saleIds)->whereIn('payment_method', ['yape', 'plin', 'tarjeta'])->sum('amount');

        $sales = $query->paginate(15)->withQueryString();

        // Opciones para los filtros
        if ($user->isEncargado()) {
            $locations = Location::where('id', $user->location_id)->get();
            $stands = Stand::where('location_id', $user->location_id)->orderBy('name')->get();
            $sellers = User::where('location_id', $user->location_id)->orderBy('name')->get();
        } elseif ($user->isVendedor()) {
            $activeStand = $user->getActiveStand();
            $locations = $activeStand ? Location::where('id', $activeStand->location_id)->get() : collect();
            $stands = $activeStand ? collect([$activeStand]) : collect();
            $sellers = collect([$user]);
        } else {
            $locations = Location::where('type', 'sede')->orderBy('name')->get();
            $standsQuery = Stand::with('location')->orderBy('name');
            if (!empty($locationId)) {
                $standsQuery->where('location_id', $locationId);
            }
            $stands = $standsQuery->get();
            $sellers = User::orderBy('name')->get();
        }

        return view('history.index', compact(
            'sales',
            'locations',
            'stands',
            'sellers',
            'dateRange',
            'dateFrom',
            'dateTo',
            'locationId',
            'standId',
            'userId',
            'scope',
            'paymentMethod',
            'status',
            'totalMonto',
            'totalTransacciones',
            'totalEfectivo',
            'totalDigital'
        ));
    }

    public function cancel(Request $request, int|string $id): RedirectResponse
    {
        $user = auth()->user();

        // Solo admin o encargado pueden anular ventas
        if (!$user->isAdmin() && !$user->isEncargado()) {
            return back()->with('error', 'No tienes autorización para anular comprobantes de venta.');
        }

        $sale = Sale::with(['cashSession.stand', 'items'])->findOrFail($id);

        if ($sale->status === 'anulada') {
            return back()->with('warning', 'Esta venta ya se encuentra anulada.');
        }

        if ($user->isEncargado() && (int) $sale->cashSession?->stand?->location_id !== (int) $user->location_id) {
            return back()->with('error', 'Solo puedes anular ventas correspondientes a tu sede asignada.');
        }

        DB::beginTransaction();
        try {
            // Revertir el stock en la sede correspondiente
            $locationId = $sale->cashSession?->stand?->location_id;

            if ($locationId) {
                foreach ($sale->items as $item) {
                    Stock::where('location_id', $locationId)
                        ->where('product_id', $item->product_id)
                        ->increment('quantity', $item->quantity);
                }
            }

            $sale->update(['status' => 'anulada']);

            DB::commit();
            return back()->with('success', "Venta #{$sale->id} anulada correctamente y stock devuelto a inventario.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al procesar la anulación: ' . $e->getMessage());
        }
    }
}
