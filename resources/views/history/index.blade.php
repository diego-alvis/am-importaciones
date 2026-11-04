<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-black text-2xl text-slate-800 leading-tight flex items-center gap-2">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    <span>Historial de Ventas y Reportes</span>
                </h2>
                <p class="text-sm text-slate-500 mt-0.5">
                    @if(auth()->user()->isVendedor())
                        Auditoría de ventas para tu tienda: <strong>{{ auth()->user()->getActiveStand()?->name ?? 'Mi Tienda' }}</strong>
                    @elseif(auth()->user()->isEncargado())
                        Auditoría de ventas de tu sede: <strong>{{ auth()->user()->location->name ?? 'Mi Sede' }}</strong>
                    @else
                        Auditoría global de transacciones, sedes y tiendas
                    @endif
                </p>
            </div>
            
            <a href="{{ route('pos.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-black px-4 py-2.5 rounded-xl shadow-sm text-sm flex items-center gap-2 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span>Nueva Venta (POS)</span>
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="historyManager()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- TARJETAS RESUMEN DE FILTRO -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Monto Total</p>
                    <p class="text-2xl font-black text-slate-900 leading-none">S/ {{ number_format($totalMonto, 2) }}</p>
                    <span class="text-[11px] text-slate-400 mt-1 block">{{ $totalTransacciones }} venta(s) encontradas</span>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                    <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider mb-1">Efectivo Físico</p>
                    <p class="text-2xl font-black text-emerald-600 leading-none">S/ {{ number_format($totalEfectivo, 2) }}</p>
                    <span class="text-[11px] text-slate-400 mt-1 block">Recaudación en caja</span>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                    <p class="text-[10px] font-bold text-purple-600 uppercase tracking-wider mb-1">Digital (Yape / Plin / POS)</p>
                    <p class="text-2xl font-black text-purple-600 leading-none">S/ {{ number_format($totalDigital, 2) }}</p>
                    <span class="text-[11px] text-slate-400 mt-1 block">Pagos electrónicos</span>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                    <p class="text-[10px] font-bold text-blue-600 uppercase tracking-wider mb-1">Ticket Promedio</p>
                    <p class="text-2xl font-black text-blue-600 leading-none">
                        S/ {{ $totalTransacciones > 0 ? number_format($totalMonto / $totalTransacciones, 2) : '0.00' }}
                    </p>
                    <span class="text-[11px] text-slate-400 mt-1 block">Por transacción</span>
                </div>
            </div>

            <!-- BARRA DE FILTROS AVANZADOS -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                <form method="GET" action="{{ route('history.index') }}" class="space-y-4">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        
                        <!-- Rango Fecha -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Periodo</label>
                            <select name="date_range" x-model="dateRange" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 font-bold focus:ring-2 focus:ring-blue-500 bg-white">
                                <option value="hoy">Hoy</option>
                                <option value="ayer">Ayer</option>
                                <option value="semana">Esta Semana</option>
                                <option value="mes">Este Mes</option>
                                <option value="todos">Histórico Completo</option>
                                <option value="personalizado">Personalizado</option>
                            </select>
                        </div>

                        @if(auth()->user()->isAdmin())
                            <!-- Filtro Sede (Solo Admin) -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Sede</label>
                                <select name="location_id" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 font-bold focus:ring-2 focus:ring-blue-500 bg-white">
                                    <option value="">Todas las sedes</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" {{ $locationId == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <!-- Filtro Tienda / Stand -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Tienda / Stand</label>
                            <select name="stand_id" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 font-bold focus:ring-2 focus:ring-blue-500 bg-white">
                                <option value="">Todas las tiendas</option>
                                @foreach($stands as $std)
                                    <option value="{{ $std->id }}" {{ $standId == $std->id ? 'selected' : '' }}>
                                        {{ $std->name }} {{ auth()->user()->isAdmin() && $std->location ? '('.$std->location->name.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if(auth()->user()->isEncargado())
                            <!-- Filtro Alcance para Supervisor -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Alcance</label>
                                <select name="scope" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 font-bold focus:ring-2 focus:ring-blue-500 bg-white">
                                    <option value="" {{ empty($scope) ? 'selected' : '' }}>Toda mi Sede</option>
                                    <option value="personal" {{ $scope === 'personal' ? 'selected' : '' }}>Solo mis ventas personales</option>
                                </select>
                            </div>
                        @elseif(auth()->user()->isAdmin())
                            <!-- Filtro Vendedor (Solo Admin) -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Vendedor</label>
                                <select name="user_id" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 font-bold focus:ring-2 focus:ring-blue-500 bg-white">
                                    <option value="">Todos los vendedores</option>
                                    @foreach($sellers as $seller)
                                        <option value="{{ $seller->id }}" {{ $userId == $seller->id ? 'selected' : '' }}>{{ $seller->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <!-- Método de Pago -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Método de Pago</label>
                            <select name="payment_method" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 font-bold focus:ring-2 focus:ring-blue-500 bg-white">
                                <option value="">Todos</option>
                                <option value="efectivo" {{ $paymentMethod === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                                <option value="yape" {{ $paymentMethod === 'yape' ? 'selected' : '' }}>Yape</option>
                                <option value="plin" {{ $paymentMethod === 'plin' ? 'selected' : '' }}>Plin</option>
                                <option value="tarjeta" {{ $paymentMethod === 'tarjeta' ? 'selected' : '' }}>Tarjeta</option>
                            </select>
                        </div>

                        <!-- Estado y Botón -->
                        <div class="flex items-end gap-1">
                            <div class="flex-1">
                                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Estado</label>
                                <select name="status" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 font-bold focus:ring-2 focus:ring-blue-500 bg-white">
                                    <option value="completada" {{ $status === 'completada' ? 'selected' : '' }}>Completadas</option>
                                    <option value="anulada" {{ $status === 'anulada' ? 'selected' : '' }}>Anuladas</option>
                                    <option value="todos" {{ $status === 'todos' ? 'selected' : '' }}>Todos</option>
                                </select>
                            </div>
                            <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-bold py-2.5 px-3 rounded-xl text-xs transition-all shadow-sm">
                                Filtrar
                            </button>
                            <a href="{{ route('history.index') }}" class="p-2.5 text-slate-400 hover:text-red-500 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors" title="Limpiar filtros">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Campos de fecha personalizada condicional -->
                    <div x-show="dateRange === 'personalizado'" class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-100">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Desde</label>
                            <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full py-2 px-3 rounded-xl border border-slate-200 text-xs font-semibold">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Hasta</label>
                            <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full py-2 px-3 rounded-xl border border-slate-200 text-xs font-semibold">
                        </div>
                    </div>
                </form>
            </div>

            <!-- TABLA DE VENTAS -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-400 text-xs uppercase border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4 font-bold">Ticket</th>
                                <th class="py-3 px-4 font-bold">Fecha / Hora</th>
                                <th class="py-3 px-4 font-bold">Sede &bull; Tienda</th>
                                <th class="py-3 px-4 font-bold">Vendedor</th>
                                <th class="py-3 px-4 font-bold">Métodos de Pago</th>
                                <th class="py-3 px-4 font-bold text-right">Total</th>
                                <th class="py-3 px-4 font-bold text-center">Estado</th>
                                <th class="py-3 px-4 font-bold text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($sales as $sale)
                                @php
                                    $metodos = $sale->payments->pluck('payment_method')->map(fn($m) => ucfirst($m))->implode(' + ');
                                @endphp
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-xs text-blue-600">
                                    #{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="py-3 px-4">
                                    <p class="font-bold text-slate-800 text-xs">{{ $sale->created_at->format('d/m/Y') }}</p>
                                    <span class="text-[10px] text-slate-400">{{ $sale->created_at->format('h:i A') }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <p class="font-bold text-slate-800 text-xs">{{ $sale->cashSession?->stand?->name ?? 'Tienda' }}</p>
                                    <span class="text-[10px] text-slate-400">{{ $sale->cashSession?->stand?->location?->name ?? 'Sede' }}</span>
                                </td>
                                <td class="py-3 px-4 text-xs font-semibold text-slate-800">
                                    {{ $sale->seller_name }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 text-[10px] rounded-md font-bold bg-slate-100 text-slate-700 uppercase">
                                        {{ $metodos ?: 'Efectivo' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-black text-slate-900 text-sm">
                                    S/ {{ number_format($sale->total_amount, 2) }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase {{ $sale->status === 'completada' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $sale->status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Ver Detalle Modal -->
                                        <button @click="openDetailModal({{ json_encode($sale) }})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Ver Detalle">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </button>

                                        <!-- Imprimir Ticket -->
                                        <a href="{{ route('pos.ticket', $sale->id) }}" target="_blank" class="p-1.5 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors" title="Imprimir Ticket">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                        </a>

                                        @if($sale->status === 'completada' && in_array(auth()->user()->role, ['admin', 'encargado']))
                                            @if($sale->cashSession?->status === 'abierta')
                                            <!-- Anular Venta (Solo si la caja sigue abierta) -->
                                            <form action="{{ route('history.cancel', $sale->id) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas anular esta venta #{{ $sale->id }} y devolver el stock?')">
                                                @csrf
                                                <button type="submit" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Anular Venta">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                </button>
                                            </form>
                                            @else
                                            <span class="text-[10px] text-slate-400 font-semibold px-1" title="Caja cerrada o aprobada (no anulable)">Cerrada</span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400 font-medium">
                                    No se encontraron ventas para los filtros seleccionados.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($sales->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $sales->links() }}
                </div>
                @endif
            </div>

        </div>

        <!-- MODAL: DETALLE COMPLETO DE VENTA -->
        <div x-show="showDetail" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl overflow-hidden" @click.away="showDetail = false">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800">
                            Detalle de Venta #<span x-text="selectedSale.id ? String(selectedSale.id).padStart(6, '0') : ''"></span>
                        </h3>
                        <p class="text-xs text-slate-400" x-text="'Atendido por: ' + (selectedSale.user ? selectedSale.user.name : '')"></p>
                    </div>
                    <button @click="showDetail = false" class="text-slate-400 hover:text-red-500 text-xl font-bold">&times;</button>
                </div>

                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <!-- Artículos Vendidos -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Artículos del Ticket</h4>
                        <div class="divide-y divide-slate-100 border border-slate-100 rounded-xl overflow-hidden">
                            <template x-for="item in (selectedSale.items || [])" :key="item.id">
                                <div class="p-3 bg-white flex justify-between items-center text-xs">
                                    <div>
                                        <p class="font-bold text-slate-800" x-text="item.product ? item.product.name : 'Producto'"></p>
                                        <template x-if="item.device_model">
                                            <span class="text-[10px] text-blue-600 block" x-text="'Modelo: ' + item.device_model"></span>
                                        </template>
                                        <span class="text-slate-400" x-text="item.quantity + ' und x S/ ' + parseFloat(item.unit_price).toFixed(2)"></span>
                                    </div>
                                    <span class="font-black text-slate-800 text-sm" x-text="'S/ ' + (item.quantity * item.unit_price).toFixed(2)"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Métodos de Pago Usados -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Desglose de Pagos</h4>
                        <div class="space-y-1.5">
                            <template x-for="p in (selectedSale.payments || [])" :key="p.id">
                                <div class="flex justify-between items-center p-2.5 rounded-lg bg-slate-50 text-xs">
                                    <span class="font-bold text-slate-700 uppercase" x-text="p.payment_method + (p.reference_code ? ' (' + p.reference_code + ')' : '')"></span>
                                    <span class="font-black text-slate-900" x-text="'S/ ' + parseFloat(p.amount).toFixed(2)"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Resumen Total -->
                    <div class="pt-3 border-t border-slate-200 flex justify-between items-center">
                        <span class="font-bold text-sm text-slate-700">TOTAL PAGADO</span>
                        <span class="font-black text-2xl text-blue-600" x-text="'S/ ' + (selectedSale.total_amount ? parseFloat(selectedSale.total_amount).toFixed(2) : '0.00')"></span>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-between items-center">
                    <button type="button" @click="showDetail = false" class="px-4 py-2 border border-slate-200 rounded-xl text-slate-600 font-bold text-sm hover:bg-slate-100">Cerrar</button>
                    <a :href="'/pos/ticket/' + selectedSale.id" target="_blank" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        <span>Imprimir Ticket</span>
                    </a>
                </div>
            </div>
        </div>

    </div>

    <script>
    function historyManager() {
        return {
            dateRange: '{{ $dateRange }}',
            showDetail: false,
            selectedSale: {},

            openDetailModal(sale) {
                this.selectedSale = sale;
                this.showDetail = true;
            }
        }
    }
    </script>
</x-app-layout>
