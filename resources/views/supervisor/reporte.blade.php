<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                @php
                    $isAprobada = $caja->status === \App\Enums\CashSessionStatus::Aprobada;
                    $cajaStatusLabel = $caja->status instanceof \App\Enums\CashSessionStatus ? $caja->status->label() : ucfirst((string) $caja->status);
                @endphp
                <div class="flex items-center gap-2">
                    <span class="text-xs font-black uppercase tracking-wider px-2 py-0.5 rounded {{ $isAprobada ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        Turno {{ $cajaStatusLabel }}
                    </span>
                    <span class="text-xs text-slate-500 font-bold">Caja #{{ $caja->id }}</span>
                </div>
                <h2 class="font-black text-2xl text-slate-800 leading-tight mt-1">
                    Reporte de Cierre y Arqueo de Caja
                </h2>
                <p class="text-sm text-slate-500">
                    {{ $caja->stand?->location?->name ?? 'Sede' }} &bull; {{ $caja->stand?->name ?? 'Tienda' }}
                </p>
            </div>
            <div class="flex items-center gap-3 no-print">
                <button onclick="window.print()" class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-xl text-sm transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Imprimir Reporte
                </button>
                @if(auth()->user()->isAdmin() || auth()->user()->isEncargado())
                    <a href="{{ route('supervisor.cajas') }}" class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 text-slate-700 font-bold px-4 py-2.5 rounded-xl text-sm border border-slate-200 transition-all">
                        &larr; Volver a Supervisión
                    </a>
                @else
                    <a href="{{ route('cash.index') }}" class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 text-slate-700 font-bold px-4 py-2.5 rounded-xl text-sm border border-slate-200 transition-all">
                        &larr; Volver a Caja
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; font-size: 12px; }
            .print-card { box-shadow: none !important; border: 1px solid #ddd !important; }
        }
    </style>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Ficha de Encabezado Oficial -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm print-card">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 pb-6 border-b border-slate-100">
                    <div>
                        <span class="text-xs font-black uppercase text-blue-600 tracking-widest">AM IMPORTACIONES S.A.C.</span>
                        <h1 class="text-xl font-black text-slate-900">Arqueo y Liquidación de Caja</h1>
                        <p class="text-xs text-slate-500">Documento de control interno y auditoría comercial</p>
                    </div>
                    <div class="text-left md:text-right">
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold {{ $isAprobada ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                            ESTADO: {{ strtoupper($cajaStatusLabel) }}
                        </span>
                        <p class="text-xs text-slate-400 mt-1">Generado: {{ now()->format('d/m/Y h:i A') }}</p>
                    </div>
                </div>

                <!-- Datos de la Operación -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-6 text-sm">
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Sede</span>
                        <span class="font-extrabold text-slate-800">{{ $caja->stand?->location?->name ?? 'No asignada' }}</span>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Tienda / Stand</span>
                        <span class="font-black text-blue-600">{{ $caja->stand?->name ?? 'No asignado' }}</span>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Cajero / Vendedor</span>
                        <span class="font-bold text-slate-800">{{ $caja->cashier_name }}</span>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Supervisor Aprobador</span>
                        <span class="font-bold text-slate-800">{{ $caja->approver?->name ?? 'Pendiente de aprobación' }}</span>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Hora Apertura</span>
                        <span class="font-semibold text-slate-700">{{ $caja->opening_time ? $caja->opening_time->format('d/m/Y h:i A') : 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Hora Cierre</span>
                        <span class="font-semibold text-slate-700">{{ $caja->closing_time ? $caja->closing_time->format('d/m/Y h:i A') : 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Ventas Brutas</span>
                        <span class="font-black text-slate-900">S/ {{ number_format($metrics->total_ventas, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Transacciones Realizadas</span>
                        <span class="font-black text-slate-900">{{ $caja->sales->where('status', \App\Enums\SaleStatus::Completada)->count() }} ventas</span>
                    </div>
                </div>
            </div>

            <!-- Resumen Financiero y Balance de Arqueo -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Desglose de Canales de Cobro -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm print-card">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Ingresos por Método</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-1 border-b border-slate-100">
                            <span class="text-sm font-medium text-slate-600 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Efectivo Físico
                            </span>
                            <span class="font-bold text-slate-800">S/ {{ number_format($metrics->efectivo, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-slate-100">
                            <span class="text-sm font-medium text-slate-600 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span> Yape / Plin
                            </span>
                            <span class="font-bold text-purple-700">S/ {{ number_format($metrics->yape_plin, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-slate-100">
                            <span class="text-sm font-medium text-slate-600 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Tarjeta (POS)
                            </span>
                            <span class="font-bold text-blue-700">S/ {{ number_format($metrics->tarjeta, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-2 font-black text-slate-900">
                            <span>Total Facturado</span>
                            <span>S/ {{ number_format($metrics->total_ventas, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Gastos y Deducciones del Turno -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm print-card">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Deducciones Autorizadas</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-1 border-b border-slate-100">
                            <span class="text-sm font-medium text-slate-600">Fondo Base (Permanece)</span>
                            <span class="font-bold text-slate-700">S/ {{ number_format($caja->expected_cash, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-slate-100">
                            <span class="text-sm font-medium text-slate-600">Viático Diario Fijo</span>
                            <span class="font-bold text-red-600">- S/ {{ number_format($metrics->gastos_fijos, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-slate-100">
                            <span class="text-sm font-medium text-slate-600">Bono por Comisión Meta</span>
                            <span class="font-bold text-red-600">- S/ {{ number_format($metrics->bono_meta, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-2 font-black text-red-600">
                            <span>Total Descontado</span>
                            <span>- S/ {{ number_format($metrics->gastos_totales, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Resultado de Liquidación -->
                <div class="bg-slate-900 rounded-2xl p-5 text-white shadow-sm flex flex-col justify-between print-card">
                    <div>
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Cuadre Final en Efectivo</h3>
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs text-slate-300">
                                <span>Esperado por Sistema:</span>
                                <span class="font-bold text-white">S/ {{ number_format($metrics->efectivo_esperado, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-xs text-slate-300">
                                <span>Declarado por Cajero:</span>
                                <span class="font-bold text-amber-300">S/ {{ number_format($caja->declared_cash ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-700 mt-4">
                        <div class="flex justify-between items-end">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider block text-slate-400">Diferencia</span>
                                <span class="text-2xl font-black {{ $metrics->diferencia == 0 ? 'text-emerald-400' : ($metrics->diferencia > 0 ? 'text-purple-400' : 'text-red-400') }}">
                                    {{ $metrics->diferencia > 0 ? '+' : '' }}S/ {{ number_format($metrics->diferencia, 2) }}
                                </span>
                            </div>
                            <div class="text-right">
                                @if($metrics->diferencia == 0)
                                    <span class="px-2.5 py-1 rounded bg-emerald-500/20 text-emerald-300 font-bold text-xs">Caja Exacta</span>
                                @elseif($metrics->diferencia > 0)
                                    <span class="px-2.5 py-1 rounded bg-purple-500/20 text-purple-300 font-bold text-xs">Sobrante</span>
                                @else
                                    <span class="px-2.5 py-1 rounded bg-red-500/20 text-red-300 font-bold text-xs">Faltante</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Tabla Detallada de Productos Vendidos en el Turno -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden print-card">
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                    <div>
                        <h3 class="font-black text-lg text-slate-900">Detalle de Productos Vendidos</h3>
                        <p class="text-xs text-slate-500">Consolidado exacto por producto, modelo de celular y medios de pago</p>
                    </div>
                    <span class="text-xs font-bold bg-slate-100 text-slate-700 px-3 py-1 rounded-full">
                        {{ count($summaryItems) }} producto(s) único(s)
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600 text-xs uppercase border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3 font-extrabold">SKU</th>
                                <th class="px-5 py-3 font-extrabold">Descripción del Producto</th>
                                <th class="px-5 py-3 font-extrabold">Modelo Celular</th>
                                <th class="px-5 py-3 font-extrabold text-center">Cant.</th>
                                <th class="px-5 py-3 font-extrabold text-right">P. Unitario</th>
                                <th class="px-5 py-3 font-extrabold text-right">Subtotal</th>
                                <th class="px-5 py-3 font-extrabold text-center">Método(s) Pago</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            @php
                                $totalUnidades = 0;
                                $totalSuma = 0;
                            @endphp
                            @forelse($summaryItems as $item)
                                @php
                                    $totalUnidades += $item['quantity'];
                                    $totalSuma += $item['total'];
                                @endphp
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-5 py-3 font-mono text-xs text-slate-400">{{ $item['sku'] ?: '-' }}</td>
                                    <td class="px-5 py-3 font-bold text-slate-900">{{ $item['name'] }}</td>
                                    <td class="px-5 py-3 text-xs">
                                        @if(!empty($item['device_model']))
                                            <span class="inline-block px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-semibold border border-blue-100">
                                                {{ $item['device_model'] }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-center font-black text-slate-900">{{ $item['quantity'] }}</td>
                                    <td class="px-5 py-3 text-right font-medium text-slate-600">
                                        S/ {{ number_format($item['unit_price'], 2) }}
                                        @if(($item['unit_discount'] ?? 0) > 0)
                                            <span class="block text-[10px] text-emerald-600 font-bold">
                                                Reg: S/ {{ number_format($item['original_price'], 2) }} (-S/ {{ number_format($item['unit_discount'], 2) }})
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right font-black text-slate-900">S/ {{ number_format($item['total'], 2) }}</td>
                                    <td class="px-5 py-3 text-center">
                                        <div class="flex flex-wrap gap-1 justify-center">
                                            @foreach($item['payment_methods'] as $pMethod)
                                                <span class="text-[10px] px-2 py-0.5 rounded font-bold
                                                    @if(str_contains(strtolower($pMethod), 'yape') || str_contains(strtolower($pMethod), 'plin'))
                                                        bg-purple-100 text-purple-700
                                                    @elseif(str_contains(strtolower($pMethod), 'tarjeta'))
                                                        bg-blue-100 text-blue-700
                                                    @else
                                                        bg-emerald-100 text-emerald-700
                                                    @endif
                                                ">
                                                    {{ $pMethod }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-slate-400 font-bold">
                                        No se registraron ventas en esta sesión de caja.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($summaryItems) > 0)
                        <tfoot class="bg-slate-100 font-black text-slate-900 border-t-2 border-slate-300">
                            <tr>
                                <td colspan="3" class="px-5 py-3 text-right uppercase text-xs tracking-wider">Totales Consolidados:</td>
                                <td class="px-5 py-3 text-center text-base font-black">{{ $totalUnidades }} und</td>
                                <td></td>
                                <td class="px-5 py-3 text-right text-base text-blue-600 font-black">S/ {{ number_format($totalSuma, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Firmas de Conformidad para Impresión -->
            <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm print-card pt-12 mt-8">
                <div class="grid grid-cols-2 gap-12 text-center">
                    <div class="border-t border-slate-400 pt-3">
                        <p class="font-bold text-slate-800 text-sm">{{ $caja->cashier_name }}</p>
                        <p class="text-xs text-slate-500">Firma del Vendedor (Entrega de Caja)</p>
                    </div>
                    <div class="border-t border-slate-400 pt-3">
                        <p class="font-bold text-slate-800 text-sm">{{ $caja->approver?->name ?? 'Supervisor de Sede / Admin' }}</p>
                        <p class="text-xs text-slate-500">Firma del Supervisor (Recepción y Aprobación)</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
