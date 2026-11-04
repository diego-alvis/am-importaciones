<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-black text-2xl text-slate-800 leading-tight">
                    Supervisión y Arqueo de Cajas
                </h2>
                <p class="text-sm text-slate-500">
                    Control de aperturas, precierres y reportes auditados por sede
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-100 text-amber-900 font-bold text-xs">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    {{ $cajas->count() }} por aprobar
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-100 text-emerald-900 font-bold text-xs">
                    {{ $cajasAprobadas->count() }} aprobadas
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            
            <!-- SECCIÓN 1: Cajas Pendientes de Aprobación (Precierre) -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                            <span>Turnos en Precierre (Pendientes de Aprobación)</span>
                            @if($cajas->count() > 0)
                                <span class="bg-amber-500 text-white text-xs px-2 py-0.5 rounded-full font-bold">{{ $cajas->count() }}</span>
                            @endif
                        </h3>
                        <p class="text-xs text-slate-500">El vendedor cerró el turno y declaró el efectivo recaudado. Revisa y aprueba para generar el reporte definitivo.</p>
                    </div>
                </div>

                <div class="space-y-4">
                    @forelse($cajas as $caja)
                        @php
                            $metrics = $caja->getMetrics();
                        @endphp

                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border-2 border-amber-200 transition-all hover:shadow-md">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-0">
                                
                                <!-- Info de la Sede y Vendedor -->
                                <div class="md:col-span-3 p-6 bg-amber-50/50 border-r border-amber-100 flex flex-col justify-center">
                                    <span class="text-xs font-black text-blue-600 uppercase tracking-wider mb-1">{{ $caja->stand?->location?->name ?? 'Sin Sede' }}</span>
                                    <h4 class="text-xl font-black text-slate-900 leading-tight">{{ $caja->stand?->name ?? 'Punto de Venta' }}</h4>
                                    <div class="mt-4 flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-slate-900 text-white flex items-center justify-center font-black text-xs shadow-sm">
                                            {{ substr($caja->cashier_name ?? 'U', 0, 1) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-800">{{ $caja->cashier_name }}</p>
                                            <p class="text-xs text-slate-500">Cierre: {{ $caja->closing_time ? \Carbon\Carbon::parse($caja->closing_time)->format('d/m h:i A') : '--:--' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Auditoría de Montos -->
                                <div class="md:col-span-6 p-6 grid grid-cols-3 gap-4 items-center">
                                    <div>
                                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Efectivo Sistema</p>
                                        <p class="text-xl font-black text-slate-800">S/ {{ number_format($metrics->efectivo_esperado, 2) }}</p>
                                        <span class="text-[10px] text-slate-400 block mt-0.5">Ventas: S/ {{ number_format($metrics->efectivo, 2) }}</span>
                                    </div>
                                    
                                    <div>
                                        <p class="text-[11px] font-bold text-blue-500 uppercase tracking-widest mb-1">Declarado</p>
                                        <p class="text-xl font-black text-blue-700">S/ {{ number_format($caja->declared_cash ?? 0, 2) }}</p>
                                        <span class="text-[10px] text-blue-500 block mt-0.5">En sobre físico</span>
                                    </div>

                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-widest mb-1 
                                            {{ $metrics->diferencia == 0 ? 'text-emerald-500' : ($metrics->diferencia > 0 ? 'text-purple-500' : 'text-red-500') }}">
                                            Diferencia
                                        </p>
                                        <p class="text-xl font-black 
                                            {{ $metrics->diferencia == 0 ? 'text-emerald-500' : ($metrics->diferencia > 0 ? 'text-purple-600' : 'text-red-600') }}">
                                            {{ $metrics->diferencia > 0 ? '+' : '' }}S/ {{ number_format($metrics->diferencia, 2) }}
                                        </p>
                                        <span class="text-[10px] font-bold block mt-0.5 {{ $metrics->diferencia == 0 ? 'text-emerald-500' : ($metrics->diferencia > 0 ? 'text-purple-600' : 'text-red-600') }}">
                                            {{ $metrics->diferencia == 0 ? 'Cuadre exacto' : ($metrics->diferencia > 0 ? 'Sobrante' : 'Faltante') }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Botones de Acción -->
                                <div class="md:col-span-3 p-6 flex flex-col gap-2 items-center justify-center border-t md:border-t-0 md:border-l border-amber-100 bg-slate-50/50">
                                    <form action="{{ route('supervisor.aprobar', $caja->id) }}" method="POST" class="w-full">
                                        @csrf
                                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-xl shadow-sm transition-all flex items-center justify-center gap-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            <span>Aprobar Cierre</span>
                                        </button>
                                    </form>
                                    <a href="{{ route('supervisor.reporte', $caja->id) }}" class="w-full text-center bg-white hover:bg-slate-100 text-slate-700 font-bold py-2 px-3 rounded-xl border border-slate-200 text-xs transition-colors">
                                        Previsualizar Reporte
                                    </a>
                                </div>

                            </div>
                        </div>
                    @empty
                        <div class="bg-white rounded-2xl p-8 text-center border border-slate-200 shadow-sm">
                            <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <h4 class="font-bold text-slate-800 text-base">No hay precierres pendientes</h4>
                            <p class="text-xs text-slate-400 mt-1">Todos los turnos finalizados han sido aprobados o no hay cajas cerradas en espera.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- SECCIÓN 2: Historial de Cierres Aprobados y Reportes -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-black text-slate-800">Historial de Reportes de Cierre Aprobados</h3>
                        <p class="text-xs text-slate-500">Arqueos definitivos con auditoría de productos, medios de pago y firmas.</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-slate-50 text-slate-500 text-xs uppercase border-b border-slate-200">
                                <tr>
                                    <th class="px-5 py-3 font-bold">Turno #</th>
                                    <th class="px-5 py-3 font-bold">Sede / Tienda</th>
                                    <th class="px-5 py-3 font-bold">Vendedor</th>
                                    <th class="px-5 py-3 font-bold">Aprobado Por</th>
                                    <th class="px-5 py-3 font-bold text-right">Total Ventas</th>
                                    <th class="px-5 py-3 font-bold text-right">Recaudado Efectivo</th>
                                    <th class="px-5 py-3 font-bold text-center">Diferencia</th>
                                    <th class="px-5 py-3 font-bold text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse($cajasAprobadas as $aprobada)
                                    @php
                                        $m = $aprobada->getMetrics();
                                    @endphp
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-5 py-3 font-mono font-bold text-xs text-slate-400">#{{ $aprobada->id }}</td>
                                        <td class="px-5 py-3">
                                            <span class="font-extrabold text-slate-900 block">{{ $aprobada->stand?->name ?? 'Tienda' }}</span>
                                            <span class="text-xs text-slate-400">{{ $aprobada->stand?->location?->name ?? 'Sede' }}</span>
                                        </td>
                                        <td class="px-5 py-3 font-medium text-slate-800">{{ $aprobada->cashier_name }}</td>
                                        <td class="px-5 py-3 text-xs text-slate-600">
                                            <span class="font-bold text-emerald-700">{{ $aprobada->approver?->name ?? 'Admin' }}</span>
                                            <span class="block text-slate-400 text-[10px]">{{ $aprobada->closing_time ? $aprobada->closing_time->format('d/m/Y h:i A') : '' }}</span>
                                        </td>
                                        <td class="px-5 py-3 text-right font-black text-slate-900">S/ {{ number_format($m->total_ventas, 2) }}</td>
                                        <td class="px-5 py-3 text-right font-bold text-blue-700">S/ {{ number_format($aprobada->declared_cash ?? 0, 2) }}</td>
                                        <td class="px-5 py-3 text-center">
                                            <span class="inline-block px-2 py-0.5 rounded text-xs font-bold
                                                {{ $m->diferencia == 0 ? 'bg-emerald-100 text-emerald-800' : ($m->diferencia > 0 ? 'bg-purple-100 text-purple-800' : 'bg-red-100 text-red-800') }}">
                                                {{ $m->diferencia > 0 ? '+' : '' }}S/ {{ number_format($m->diferencia, 2) }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-center">
                                            <a href="{{ route('supervisor.reporte', $aprobada->id) }}" class="inline-flex items-center gap-1.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs px-3.5 py-1.5 rounded-lg shadow-sm transition-all">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                <span>Ver Reporte</span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-5 py-8 text-center text-slate-400 font-bold">
                                            No hay cierres aprobados en el historial todavía.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
