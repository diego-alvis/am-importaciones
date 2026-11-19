<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-black text-2xl text-slate-800 leading-tight">
                    Gestión y Arqueo de Caja
                </h2>
                <p class="text-sm text-slate-500">
                    Control de apertura, ventas del turno y cuadre de efectivo
                </p>
            </div>
            @if($activeSession)
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-100 text-emerald-900 font-bold text-xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                        Caja Abierta &bull; {{ $activeSession->stand?->name }} ({{ $activeSession->stand?->location?->name }})
                    </span>
                    <a href="{{ route('pos.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-4 py-2 rounded-xl shadow-sm transition-all">
                        Ir al POS &rarr;
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8" x-data="{ showCloseModal: false, showSwitchStoreModal: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(!$activeSession)
                <!-- Estado: Sin caja abierta -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <!-- Formulario de Apertura -->
                    <div class="lg:col-span-5 bg-white rounded-2xl shadow-sm border border-slate-200 p-8 flex flex-col justify-between">
                        <div>
                            <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-5 shadow-inner">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            </div>
                            <h3 class="text-2xl font-black text-slate-800">Abrir Turno de Caja</h3>
                            <p class="text-sm text-slate-500 mt-1 mb-6">
                                Configura la tienda e ingresa el monto base en efectivo que queda en caja física para iniciar las ventas.
                            </p>

                            <form action="{{ route('cash.open') }}" method="POST" class="space-y-4">
                                @csrf
                                
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Tu Nombre y Apellido (Vendedor de Turno)</label>
                                    <input type="text" name="operator_name" value="{{ session('operator_name') }}" required
                                        placeholder="Ej. Juan Pérez"
                                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 font-bold text-slate-800 text-sm">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Tienda / Stand de Venta</label>
                                    @if(auth()->user()->hasFixedStand() && $activeStand)
                                        <input type="hidden" name="stand_id" value="{{ $activeStand->id }}">
                                        <div class="w-full px-4 py-3 rounded-xl border border-blue-200 bg-blue-50/70 font-bold text-blue-900 text-sm flex items-center justify-between gap-2">
                                            <span>{{ $activeStand->name }} — {{ $activeStand->location?->name }}</span>
                                            <span class="text-[10px] uppercase tracking-wider bg-blue-100 text-blue-700 px-2 py-0.5 rounded-md font-extrabold shrink-0">Stand Fijo</span>
                                        </div>
                                        <span class="text-[11px] text-slate-400 block mt-1">Base recomendada: S/ {{ number_format($activeStand->base_cash, 2) }}</span>
                                    @else
                                        <select name="stand_id" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 font-bold text-slate-800 text-sm">
                                            @foreach($availableStands as $std)
                                                <option value="{{ $std->id }}" {{ ($activeStand?->id == $std->id) ? 'selected' : '' }}>
                                                    {{ $std->name }} — {{ $std->location?->name }} (Base recomendada: S/ {{ number_format($std->base_cash, 2) }})
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Fondo Inicial en Efectivo (S/)</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-slate-400">S/</span>
                                        <input type="number" step="0.01" min="0" name="opening_amount" required 
                                            value="{{ $activeStand?->base_cash ?? '100.00' }}"
                                            class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 font-black text-xl text-slate-800" placeholder="0.00">
                                    </div>
                                    <span class="text-xs text-slate-400 block mt-1">Este monto queda como fondo permanente en el punto de venta.</span>
                                </div>

                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-4 rounded-xl shadow-md transition-all text-base flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    <span>Aperturar Caja y Comenzar</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Estado del Turno Anterior o Pendiente -->
                    <div class="lg:col-span-7 space-y-6">
                        @if($pendingSession)
                            @php $pm = $pendingSession->getMetrics(); @endphp
                            <div class="bg-amber-50 border-2 border-amber-200 rounded-2xl p-6 shadow-sm">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="w-3 h-3 rounded-full bg-amber-500 animate-ping"></span>
                                    <h4 class="font-black text-amber-900 text-lg">Turno en Precierre (Pendiente de Aprobación)</h4>
                                </div>
                                <p class="text-xs text-amber-800 mb-4">
                                    Cerraste el turno para la tienda <strong>{{ $pendingSession->stand?->name }}</strong> el {{ $pendingSession->closing_time?->format('d/m/Y h:i A') }}.
                                    Tu supervisor está verificando el sobre de efectivo declarado.
                                </p>
                                <div class="grid grid-cols-3 gap-3 bg-white p-4 rounded-xl border border-amber-200 text-center mb-4">
                                    <div>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase">Venta Bruta</span>
                                        <p class="font-black text-slate-800">S/ {{ number_format($pm->total_ventas, 2) }}</p>
                                    </div>
                                    <div>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase">Efectivo Declarado</span>
                                        <p class="font-black text-blue-600">S/ {{ number_format($pendingSession->declared_cash, 2) }}</p>
                                    </div>
                                    <div>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase">Diferencia</span>
                                        <p class="font-black {{ $pm->diferencia == 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                            S/ {{ number_format($pm->diferencia, 2) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <a href="{{ route('supervisor.reporte', $pendingSession->id) }}" class="text-xs font-bold text-amber-900 bg-amber-200 hover:bg-amber-300 px-3 py-2 rounded-lg transition-colors">
                                        Ver Comprobante de Arqueo &rarr;
                                    </a>
                                </div>
                            </div>
                        @endif

                        <!-- Historial de Turnos Anteriores -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                            <h4 class="font-black text-slate-800 text-base mb-1">Mis Turnos de Caja Recientes</h4>
                            <p class="text-xs text-slate-400 mb-4">Últimos turnos cerrados y validados en el sistema.</p>

                            <div class="space-y-3">
                                @forelse($pastSessions as $past)
                                    <div class="flex items-center justify-between p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-colors">
                                        <div>
                                            <span class="font-black text-slate-900 text-sm block">{{ $past->stand?->name }}</span>
                                            <span class="text-xs text-slate-400">
                                                Cierre: {{ $past->closing_time?->format('d/m/Y h:i A') }} &bull; Aprobado por {{ $past->approver?->name ?? 'Admin' }}
                                            </span>
                                        </div>
                                        <div class="text-right flex items-center gap-3">
                                            <div>
                                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Declarado</span>
                                                <span class="font-black text-slate-800 text-sm">S/ {{ number_format($past->declared_cash, 2) }}</span>
                                            </div>
                                            <a href="{{ route('supervisor.reporte', $past->id) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-3 py-1.5 rounded-lg transition-colors">
                                                Reporte
                                            </a>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400 text-center py-6">No tienes turnos archivados aún.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                </div>
            @else
                <!-- Estado: Caja Abierta Operativa -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <!-- Columna Izquierda: KPIs y Detalle de Ventas -->
                    <div class="lg:col-span-8 bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col overflow-hidden">
                        
                        <!-- KPIs del Turno -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 p-4 bg-slate-50 border-b border-slate-100">
                            <div class="bg-blue-600 rounded-xl p-3 text-white shadow-sm">
                                <p class="text-blue-200 text-[10px] font-bold uppercase tracking-wider mb-1">Venta Bruta</p>
                                <p class="text-2xl font-black leading-none">S/ {{ number_format($resumen->total_ventas, 2) }}</p>
                            </div>
                            <div class="bg-emerald-500 rounded-xl p-3 text-white shadow-sm">
                                <p class="text-emerald-100 text-[10px] font-bold uppercase tracking-wider mb-1">Efectivo Físico</p>
                                <p class="text-2xl font-black leading-none">S/ {{ number_format($resumen->efectivo, 2) }}</p>
                            </div>
                            <div class="bg-purple-600 rounded-xl p-3 text-white shadow-sm">
                                <p class="text-purple-200 text-[10px] font-bold uppercase tracking-wider mb-1">Yape / Plin</p>
                                <p class="text-2xl font-black leading-none">S/ {{ number_format($resumen->yape_plin, 2) }}</p>
                            </div>
                            <div class="bg-red-500 rounded-xl p-3 text-white shadow-sm">
                                <p class="text-red-100 text-[10px] font-bold uppercase tracking-wider mb-1">Gastos / Bonos</p>
                                <p class="text-2xl font-black leading-none">S/ {{ number_format($resumen->gastos_totales, 2) }}</p>
                            </div>
                        </div>

                        <!-- Tabla de Ventas en Vivo -->
                        <div class="flex-1 overflow-y-auto max-h-[460px]">
                            <table class="w-full text-left text-sm whitespace-nowrap">
                                <thead class="sticky top-0 bg-slate-100 text-slate-500 text-xs uppercase shadow-xs">
                                    <tr>
                                        <th class="px-4 py-3 font-bold">Producto</th>
                                        <th class="px-4 py-3 font-bold text-center">Método</th>
                                        <th class="px-4 py-3 font-bold text-center">Cant</th>
                                        <th class="px-4 py-3 font-bold text-right">P. Unit</th>
                                        <th class="px-4 py-3 font-bold text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                                    @forelse($activeSession->sales as $venta)
                                        @php
                                            $metodos = $venta->payments->pluck('payment_method')->map(fn($m) => ucfirst($m))->implode(' / ');
                                            $metodoColor = match(true) {
                                                str_contains(strtolower($metodos), 'yape') || str_contains(strtolower($metodos), 'plin') => 'bg-purple-100 text-purple-700',
                                                str_contains(strtolower($metodos), 'tarjeta') => 'bg-blue-100 text-blue-700',
                                                default => 'bg-emerald-100 text-emerald-700'
                                            };
                                        @endphp
                                        @foreach($venta->items as $item)
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-4 py-3">
                                                <span class="font-bold text-slate-900">{{ $item->product->name }}</span>
                                                @if($item->device_model)
                                                    <span class="text-xs text-blue-600 block">{{ $item->device_model }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="px-2 py-0.5 text-[10px] rounded font-bold {{ $metodoColor }}">{{ $metodos }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-center font-black">{{ $item->quantity }}</td>
                                            <td class="px-4 py-3 text-right text-slate-500">S/ {{ number_format($item->unit_price, 2) }}</td>
                                            <td class="px-4 py-3 text-right font-black text-slate-900">S/ {{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-12 text-center text-slate-400 font-bold">
                                                No hay ventas registradas en este turno todavía.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Columna Derecha: Panel de Liquidación y Cierre -->
                    <div class="lg:col-span-4 flex flex-col gap-4">
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex flex-col">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <span class="relative flex h-3.5 w-3.5">
                                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                      <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500"></span>
                                    </span>
                                    <h3 class="text-lg font-black text-slate-800">Caja Operativa</h3>
                                </div>
                                <span class="text-xs font-bold bg-slate-100 text-slate-600 px-2.5 py-1 rounded-lg">
                                    {{ $activeSession->stand?->name }}
                                </span>
                            </div>
                            
                            <!-- Cálculo de Arqueo -->
                            <div class="bg-slate-900 rounded-xl p-5 text-slate-300 space-y-3 shadow-inner mb-6">
                                <div class="flex justify-between items-center border-b border-slate-800 pb-2">
                                    <span class="text-xs text-slate-400">Fondo Base Fijo (Permanece)</span>
                                    <span class="font-bold text-slate-300">S/ {{ number_format($activeSession->expected_cash, 2) }}</span>
                                </div>

                                <div class="flex justify-between items-center text-emerald-400">
                                    <span class="text-xs">Ventas en Efectivo (+)</span>
                                    <span class="font-black text-sm">S/ {{ number_format($resumen->efectivo, 2) }}</span>
                                </div>
                                
                                <div class="flex justify-between items-center text-red-400 border-b border-slate-800 pb-2">
                                    <span class="text-xs flex flex-col">
                                        Gastos y Viáticos (-)
                                        <small class="text-[10px] text-slate-500">Viático S/11 + Bono S/{{ $resumen->bono_meta }}</small>
                                    </span>
                                    <span class="font-black text-sm">- S/ {{ number_format($resumen->gastos_totales, 2) }}</span>
                                </div>
                                
                                <div class="pt-2">
                                    <p class="text-[10px] text-slate-400 uppercase tracking-widest font-black mb-1">Efectivo Líquido a Entregar</p>
                                    <p class="text-3xl font-black text-white">S/ {{ number_format($resumen->efectivo_esperado, 2) }}</p>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3">
                                <a href="{{ route('pos.index') }}" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-3.5 rounded-xl transition-all text-center shadow-sm">
                                    Ir a Registrar Ventas (POS)
                                </a>
                                <button @click="showCloseModal = true" class="w-full bg-red-600 hover:bg-red-700 text-white font-black py-3.5 rounded-xl transition-all shadow-sm">
                                    Cerrar Turno y Hacer Arqueo
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Modal de Arqueo y Declaración de Efectivo -->
                <div x-show="showCloseModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" style="display: none;">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden text-left" @click.away="showCloseModal = false">
                        <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                            <div>
                                <h3 class="font-black text-lg text-slate-800">Cierre y Liquidación de Caja</h3>
                                <p class="text-xs text-slate-500">{{ $activeSession->stand?->name }} &bull; Turno #{{ $activeSession->id }}</p>
                            </div>
                            <button @click="showCloseModal = false" class="text-slate-400 hover:text-slate-600 font-black text-2xl">&times;</button>
                        </div>
                        
                        <form action="{{ route('cash.close') }}" method="POST">
                            @csrf
                            <div class="p-6 space-y-4">
                                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-xs text-blue-900">
                                    <strong>Efectivo físico calculado por sistema:</strong>
                                    <p class="text-2xl font-black text-blue-700 mt-1">S/ {{ number_format($resumen->efectivo_esperado, 2) }}</p>
                                    <p class="text-[11px] text-blue-600 mt-1">Recuerda contar el dinero físico excluyendo los S/ {{ number_format($activeSession->expected_cash, 2) }} del fondo base.</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Efectivo Físico a Declarar (S/)
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-slate-400 text-lg">S/</span>
                                        <input type="number" step="0.01" min="0" name="declared_cash" required 
                                            class="w-full pl-12 pr-4 py-3.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-red-500 font-black text-2xl text-slate-900 text-center" 
                                            placeholder="0.00">
                                    </div>
                                    <span class="text-xs text-slate-400 block mt-1">Este monto será verificado y aprobado por tu supervisor.</span>
                                </div>
                            </div>
                            <div class="p-5 border-t border-slate-100 bg-slate-50 flex gap-3">
                                <button type="button" @click="showCloseModal = false" class="w-1/3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-3 rounded-xl text-sm transition-colors">
                                    Cancelar
                                </button>
                                <button type="submit" class="w-2/3 bg-red-600 hover:bg-red-700 text-white font-black py-3 rounded-xl shadow-md transition-all text-sm">
                                    Confirmar Cierre de Caja
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            @endif

        </div>
    </div>
</x-app-layout>
