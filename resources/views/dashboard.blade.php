<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-black text-2xl text-slate-800 leading-tight flex items-center gap-2">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    <span>
                        @if($user->isAlmacen())
                            Panel de Control - Almacén Central de Distribución
                        @elseif($user->isAdmin())
                            Panel de Control General &bull; Supervisión Global
                        @elseif($user->isEncargado())
                            Panel de Supervisión &bull; {{ $user->location->name ?? 'Mi Sede' }}
                        @else
                            Mi Turno &bull; {{ $user->getActiveStand()?->name ?? 'Mi Tienda' }}
                        @endif
                    </span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Operador: <strong class="text-slate-800">{{ $user->display_name }}</strong>
                    &bull; Rol: <span class="capitalize px-2 py-0.5 rounded text-[11px] font-bold bg-blue-100 text-blue-800">{{ $user->role }}</span>
                    @if(!$user->isAlmacen() && $user->getActiveStand())
                        &bull; Stand: <strong class="text-blue-600">{{ $user->getActiveStand()->name }}</strong> ({{ $user->getActiveStand()->location->name }})
                    @endif
                </p>
            </div>
            
            <div class="flex items-center gap-2 flex-wrap">
                @if($user->isAlmacen())
                    <a href="{{ route('transfers.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-black px-4 py-2.5 rounded-xl text-sm shadow-sm flex items-center gap-2 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                        <span>Gestionar Traslados</span>
                    </a>
                    <a href="{{ route('inventory.index') }}" class="bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold px-4 py-2.5 rounded-xl text-sm shadow-sm flex items-center gap-2 transition-all">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        <span>Stock e Inventario</span>
                    </a>
                @else
                    <a href="{{ route('pos.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-black px-4 py-2.5 rounded-xl text-sm shadow-sm flex items-center gap-2 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span>Ir al POS</span>
                    </a>
                    <a href="{{ route('cash.index') }}" class="bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold px-4 py-2.5 rounded-xl text-sm shadow-sm flex items-center gap-2 transition-all">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Caja y Turno</span>
                    </a>
                    @if(in_array($user->role, ['admin', 'encargado']))
                    <a href="{{ route('supervisor.cajas') }}" class="bg-purple-600 hover:bg-purple-700 text-white font-bold px-4 py-2.5 rounded-xl text-sm shadow-sm flex items-center gap-2 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        <span>Arqueos ({{ $cajasPrecierreCount ?? 0 }})</span>
                    </a>
                    @endif
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            @if($user->isAlmacen())
                <!-- ============================================== -->
                <!-- VISTA EXCLUSIVA: ALMACÉN CENTRAL               -->
                <!-- ============================================== -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-gradient-to-br from-blue-700 to-indigo-800 rounded-2xl shadow-md p-6 text-white">
                        <p class="text-blue-200 text-xs font-bold uppercase tracking-wider mb-1">Catálogo Activo en Almacén</p>
                        <h3 class="text-3xl font-black">{{ $totalSkus }} SKUs</h3>
                        <p class="text-xs text-blue-200 mt-2 font-medium">Líneas de accesorios con stock físico</p>
                    </div>

                    <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl shadow-md p-6 text-white">
                        <p class="text-emerald-100 text-xs font-bold uppercase tracking-wider mb-1">Stock Físico Global</p>
                        <h3 class="text-3xl font-black">{{ number_format($totalUnidades) }} Unidades</h3>
                        <p class="text-xs text-emerald-100 mt-2 font-medium">Disponibles para enviar a sedes</p>
                    </div>

                    <div class="bg-gradient-to-br from-amber-500 to-rose-600 rounded-2xl shadow-md p-6 text-white">
                        <p class="text-amber-100 text-xs font-bold uppercase tracking-wider mb-1">Alertas de Reabastecimiento</p>
                        <h3 class="text-3xl font-black">{{ $alertasAlmacen->count() }}</h3>
                        <p class="text-xs text-amber-100 mt-2 font-medium">Productos con stock crítico en almacén</p>
                    </div>
                </div>

                <!-- Traslados recientes y alertas -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <div class="lg:col-span-7 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="font-black text-slate-800 text-lg">Últimos Despachos a Sedes</h3>
                                <p class="text-xs text-slate-400">Mercadería enviada desde Almacén Central</p>
                            </div>
                            <a href="{{ route('transfers.index') }}" class="text-xs font-bold text-blue-600 hover:underline">Ver todos &rarr;</a>
                        </div>

                        <div class="space-y-3">
                            @forelse($transferenciasRecientes as $tr)
                                <div class="p-4 rounded-xl border border-slate-100 bg-slate-50/50 flex items-center justify-between">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-black text-blue-600">Envío #{{ $tr->id }}</span>
                                            <span class="text-xs text-slate-400">&bull; {{ $tr->created_at->format('d/m/Y h:i A') }}</span>
                                        </div>
                                        <p class="text-sm font-bold text-slate-800 mt-0.5">Destino: {{ $tr->toLocation->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $tr->items->sum('quantity') }} unidades enviadas ({{ $tr->items->count() }} ítems)</p>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800">
                                        {{ $tr->status }}
                                    </span>
                                </div>
                            @empty
                                <p class="text-xs text-slate-400 text-center py-6">No hay transferencias recientes registradas.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="lg:col-span-5 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                        <h3 class="font-black text-slate-800 text-lg mb-1">Productos por Agotarse en Almacén</h3>
                        <p class="text-xs text-slate-400 mb-4">Requieren compra a proveedores</p>

                        <div class="space-y-3">
                            @forelse($alertasAlmacen as $al)
                                <div class="flex items-center justify-between p-3 rounded-xl border border-rose-100 bg-rose-50/30">
                                    <div>
                                        <p class="font-bold text-slate-800 text-xs">{{ $al->product->name }}</p>
                                        <span class="text-[10px] text-slate-400">{{ $al->product->category->name ?? 'Accesorio' }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-black text-rose-600 text-sm">{{ $al->quantity }} und</span>
                                        <span class="text-[10px] text-slate-400 block">Mín: {{ $al->min_stock }}</span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-emerald-600 text-center py-6 font-bold">¡Inventario óptimo en Almacén!</p>
                            @endforelse
                        </div>
                    </div>
                </div>

            @elseif($user->isVendedor())
                <!-- ============================================== -->
                <!-- VISTA OPERATIVA DE MOSTRADOR: VENDEDOR         -->
                <!-- ============================================== -->

                <!-- FILA 1: ESTADO DE TURNO + META DE BONOS -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <!-- Estado de Caja / Turno Actual -->
                    <div class="lg:col-span-5">
                        @if($cajaActiva)
                            <div class="h-full bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 rounded-2xl shadow-md p-6 text-white flex flex-col justify-between relative overflow-hidden">
                                <div class="absolute -right-8 -top-8 w-36 h-36 rounded-full bg-emerald-500/10 blur-2xl pointer-events-none"></div>
                                <div>
                                    <div class="flex items-center justify-between gap-2 mb-4">
                                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-xs font-bold">
                                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                                            Turno Abierto
                                        </span>
                                        <span class="text-xs font-semibold text-slate-400">
                                            Desde {{ $cajaActiva->opening_time?->format('h:i A') }}
                                        </span>
                                    </div>

                                    <p class="text-xs text-slate-400 uppercase tracking-wider font-bold">Efectivo recaudado en turno</p>
                                    <h3 class="text-3xl font-black text-white mt-1">S/ {{ number_format($efectivoTurno, 2) }}</h3>
                                    <p class="text-xs text-slate-300 mt-2">
                                        Vendedor activo: <strong class="text-white">{{ $cajaActiva->cashier_name }}</strong>
                                        &bull; Fondo base: <strong>S/ {{ number_format($cajaActiva->expected_cash, 2) }}</strong>
                                    </p>
                                </div>

                                <div class="grid grid-cols-2 gap-2.5 mt-6 pt-4 border-t border-white/10">
                                    <a href="{{ route('pos.index') }}" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2.5 px-3 rounded-xl text-xs text-center shadow-sm transition-all flex items-center justify-center gap-1.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                        <span>Vender (POS)</span>
                                    </a>
                                    <a href="{{ route('cash.index') }}" class="bg-white/10 hover:bg-white/20 text-white font-bold py-2.5 px-3 rounded-xl text-xs text-center transition-all flex items-center justify-center gap-1.5">
                                        <span>Arqueo / Cierre</span>
                                    </a>
                                </div>
                            </div>
                        @elseif($cajaPrecierre)
                            <div class="h-full bg-amber-50 border-2 border-amber-200 rounded-2xl shadow-sm p-6 flex flex-col justify-between">
                                <div>
                                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-200/70 text-amber-900 text-xs font-bold mb-3">
                                        <span class="w-2 h-2 rounded-full bg-amber-600 animate-pulse"></span>
                                        Turno en Precierre
                                    </span>
                                    <h3 class="text-xl font-black text-amber-950">Esperando aprobación del supervisor</h3>
                                    <p class="text-xs text-amber-800 mt-1.5">
                                        Cerraste tu turno el {{ $cajaPrecierre->closing_time?->format('d/m h:i A') }} declarando
                                        <strong>S/ {{ number_format($cajaPrecierre->declared_cash ?? 0, 2) }}</strong>.
                                    </p>
                                </div>

                                <div class="grid grid-cols-2 gap-2.5 mt-6">
                                    <a href="{{ route('supervisor.reporte', $cajaPrecierre->id) }}" class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-2.5 px-3 rounded-xl text-xs text-center transition-all">
                                        Ver Arqueo
                                    </a>
                                    <a href="{{ route('cash.index') }}" class="bg-white hover:bg-amber-100 text-amber-900 border border-amber-300 font-bold py-2.5 px-3 rounded-xl text-xs text-center transition-all">
                                        Abrir Nuevo Turno
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="h-full bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl shadow-md p-6 text-white flex flex-col justify-between">
                                <div>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 text-blue-100 text-xs font-bold mb-3">
                                        Caja Cerrada
                                    </span>
                                    <h3 class="text-2xl font-black">Listo para iniciar tu jornada</h3>
                                    <p class="text-xs text-blue-100 mt-1.5 leading-relaxed">
                                        Abre tu turno de caja en <strong>{{ $activeStand?->name ?? 'tu stand' }}</strong> ingresando tu nombre para habilitar el POS.
                                    </p>
                                </div>

                                <div class="mt-6">
                                    <a href="{{ route('cash.index') }}" class="w-full bg-white hover:bg-blue-50 text-blue-700 font-black py-3 px-4 rounded-xl text-sm text-center shadow-sm transition-all block">
                                        Abrir Turno de Caja &rarr;
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Progreso de Meta y Bonos del Turno + Canales de Cobro -->
                    <div class="lg:col-span-7 bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex flex-col justify-between">
                        <div>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                                <div>
                                    <h3 class="font-black text-slate-900 text-lg">Meta de Ventas y Bonos del Turno</h3>
                                    <p class="text-xs text-slate-500">
                                        Obtienes <strong class="text-emerald-600">S/ {{ number_format($bonoComision, 2) }}</strong> de bono por cada <strong>S/ {{ number_format($metaComision, 0) }}</strong> en ventas
                                    </p>
                                </div>
                                <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl {{ $bonosGanados > 0 ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-slate-50 border border-slate-200 text-slate-700' }}">
                                    <span class="text-xs font-bold uppercase tracking-wider">Bonos ganados:</span>
                                    <span class="text-lg font-black {{ $bonosGanados > 0 ? 'text-emerald-600' : 'text-slate-900' }}">
                                        {{ $bonosGanados }} (+S/ {{ number_format($montoBonoGanado, 2) }})
                                    </span>
                                </div>
                            </div>

                            <!-- Barra de Progreso -->
                            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                <div class="flex items-baseline justify-between mb-2">
                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Vendido en turno</span>
                                        <p class="text-2xl font-black text-slate-900">S/ {{ number_format($ventasTurno, 2) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-bold text-blue-600">{{ $porcentajeMeta }}% hacia S/ {{ number_format($siguienteMeta, 0) }}</span>
                                        <p class="text-xs text-slate-500">
                                            Faltan <strong class="text-slate-800">S/ {{ number_format($faltanteParaBono, 2) }}</strong> para el próximo bono
                                        </p>
                                    </div>
                                </div>

                                <div class="w-full h-3.5 bg-slate-200 rounded-full overflow-hidden">
                                    <div
                                        class="h-full rounded-full bg-gradient-to-r from-blue-600 via-indigo-500 to-emerald-500 transition-all duration-500"
                                        style="width: {{ max(3, $porcentajeMeta) }}%"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Resumen de Hoy por Canal -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5 pt-4 border-t border-slate-100">
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-[10px] font-bold uppercase text-slate-400 block">Ventas Hoy</span>
                                <span class="text-sm font-black text-slate-900">S/ {{ number_format($ventasHoy, 2) }}</span>
                                <span class="text-[10px] text-slate-400 block">{{ $transaccionesHoy }} ticket(s)</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-emerald-50/60 border border-emerald-100">
                                <span class="text-[10px] font-bold uppercase text-emerald-700 block">Efectivo Hoy</span>
                                <span class="text-sm font-black text-emerald-700">S/ {{ number_format($totalEfectivoHoy, 2) }}</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-purple-50/60 border border-purple-100">
                                <span class="text-[10px] font-bold uppercase text-purple-700 block">Yape / Plin</span>
                                <span class="text-sm font-black text-purple-700">S/ {{ number_format($totalYapePlinHoy, 2) }}</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-blue-50/60 border border-blue-100">
                                <span class="text-[10px] font-bold uppercase text-blue-700 block">Tarjeta POS</span>
                                <span class="text-sm font-black text-blue-700">S/ {{ number_format($totalTarjetaHoy, 2) }}</span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- FILA 2: ÚLTIMAS VENTAS DEL STAND + LOGÍSTICA / TOP / STOCK -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <!-- Últimas Ventas de Mi Stand -->
                    <div class="lg:col-span-7 bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <h3 class="font-black text-slate-800 text-lg">Últimas Ventas en {{ $activeStand?->name ?? 'Mi Stand' }}</h3>
                                    <p class="text-xs text-slate-400">Tickets recientes con acceso rápido a reimpresión</p>
                                </div>
                                <a href="{{ route('history.index') }}" class="text-xs font-bold text-blue-600 hover:underline">Ver historial &rarr;</a>
                            </div>

                            <div class="space-y-2.5">
                                @forelse($ultimasVentas as $venta)
                                    @php
                                        $metodos = $venta->payments->pluck('payment_method')->map(fn($m) => ucfirst($m))->unique()->implode(' + ');
                                    @endphp
                                    <div class="p-3.5 rounded-xl border border-slate-100 hover:border-slate-200 hover:bg-slate-50/70 transition-all flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="font-mono font-bold text-xs text-blue-600">#{{ str_pad($venta->id, 6, '0', STR_PAD_LEFT) }}</span>
                                                <span class="text-[11px] text-slate-400">{{ $venta->created_at->format('d/m h:i A') }}</span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-600">{{ $metodos ?: 'Efectivo' }}</span>
                                                @if((float) ($venta->discount_amount ?? 0) > 0)
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">Desc -S/ {{ number_format($venta->discount_amount, 2) }}</span>
                                                @endif
                                            </div>
                                            <p class="text-xs font-semibold text-slate-700 truncate mt-1">
                                                {{ $venta->items->map(fn($i) => $i->quantity.'x '.($i->product->name ?? 'Producto').($i->device_model ? " ({$i->device_model})" : ''))->implode(', ') }}
                                            </p>
                                        </div>

                                        <div class="flex items-center gap-3 shrink-0">
                                            <span class="font-black text-sm text-slate-900">S/ {{ number_format($venta->total_amount, 2) }}</span>
                                            <a href="{{ route('pos.ticket', $venta->id) }}" target="_blank" class="p-2 rounded-lg border border-slate-200 text-slate-500 hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50 transition-colors" title="Ver / Imprimir Ticket">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                            </a>
                                        </div>
                                    </div>
                                @empty
                                    <div class="py-12 text-center text-slate-400">
                                        <p class="text-sm font-semibold">Aún no hay ventas registradas en este stand.</p>
                                        <p class="text-xs mt-1">Las ventas del día aparecerán aquí automáticamente.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Pedidos en Camino + Top Productos + Stock Bajo -->
                    <div class="lg:col-span-5 space-y-6">

                        <!-- Pedidos de Mercadería de Mi Sede -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <h3 class="font-black text-slate-800 text-base">Pedidos de Mercadería</h3>
                                    <p class="text-xs text-slate-400">Envíos en camino o solicitados al Almacén</p>
                                </div>
                                <a href="{{ route('transfers.index') }}" class="text-xs font-bold text-blue-600 hover:underline">Gestionar &rarr;</a>
                            </div>

                            <div class="space-y-2.5">
                                @forelse($pedidosActivos as $pedido)
                                    @php
                                        $enCamino = $pedido->status === \App\Enums\TransferStatus::EnTransito;
                                    @endphp
                                    <div class="p-3 rounded-xl border {{ $enCamino ? 'border-blue-200 bg-blue-50/60' : 'border-amber-200 bg-amber-50/50' }} flex items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="font-mono font-bold text-xs text-slate-800">#TR-{{ str_pad($pedido->id, 5, '0', STR_PAD_LEFT) }}</span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase {{ $enCamino ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-800' }}">
                                                    {{ $pedido->status->label() }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-slate-600 mt-1">
                                                {{ $pedido->items->sum('quantity') }} und ({{ $pedido->items->count() }} productos) desde {{ $pedido->fromLocation->name }}
                                            </p>
                                        </div>

                                        @if($enCamino)
                                            <form action="{{ route('transfers.receive', $pedido->id) }}" method="POST" onsubmit="return confirm('¿Confirmas haber recibido esta mercadería en tu sede?')">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-lg shadow-sm whitespace-nowrap">
                                                    Recibir
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400 text-center py-3">No tienes pedidos pendientes o en tránsito.</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Top Productos de Mi Stand -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                            <h3 class="font-black text-slate-800 text-base mb-1">Lo Más Vendido en Mi Stand</h3>
                            <p class="text-xs text-slate-400 mb-3">Artículos con mayor rotación aquí</p>

                            <div class="space-y-2">
                                @forelse($topProductos as $top)
                                    <div class="flex items-center justify-between p-2 rounded-xl hover:bg-slate-50 transition-colors">
                                        <div class="min-w-0 pr-2">
                                            <p class="font-bold text-slate-800 text-xs truncate">{{ $top->product->name }}</p>
                                            <span class="text-[10px] text-slate-400">{{ $top->product->category->name ?? 'Accesorio' }}</span>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="font-black text-slate-900 text-xs">{{ $top->total_qty }} und</span>
                                            <span class="text-[10px] text-slate-400 block">S/ {{ number_format($top->total_revenue, 2) }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400 text-center py-3">Aún no hay productos vendidos en este stand.</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Alertas de Stock Bajo en Sede -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                            <div class="flex justify-between items-center mb-3">
                                <div>
                                    <h3 class="font-black text-slate-800 text-base">Alertas de Stock ({{ $totalAlertasStock }})</h3>
                                    <p class="text-xs text-slate-400">Productos por agotarse en tu sede</p>
                                </div>
                                <a href="{{ route('transfers.index') }}" class="text-xs font-bold text-blue-600 hover:underline">Pedir stock &rarr;</a>
                            </div>

                            <div class="space-y-2">
                                @forelse($alertasStock as $al)
                                    <div class="p-2.5 rounded-xl border border-rose-100 bg-rose-50/40 flex justify-between items-center">
                                        <span class="font-bold text-slate-800 text-xs truncate pr-2">{{ $al->product->name }}</span>
                                        <span class="font-black text-rose-600 text-xs shrink-0">{{ $al->quantity }} und</span>
                                    </div>
                                @empty
                                    <p class="text-xs text-emerald-600 text-center py-3 font-bold">¡Stock en niveles óptimos!</p>
                                @endforelse
                            </div>
                        </div>

                    </div>

                </div>

            @else
                <!-- ============================================== -->
                <!-- VISTA COMERCIAL: ADMIN Y SUPERVISORES          -->
                <!-- ============================================== -->

                @if($user->isAdmin())
                    <!-- BARRA DE FILTROS SUPERVISIÓN ADMIN -->
                    <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-slate-200">
                        <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                            <div class="flex items-center gap-2.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-600 shrink-0"></span>
                                <span class="font-black text-xs sm:text-sm text-slate-800 uppercase tracking-wider">Supervisión en Tiempo Real</span>
                            </div>

                            <div class="flex items-center gap-3 flex-wrap">
                                <!-- Filtro Sede -->
                                <select name="location_id" onchange="this.form.submit()" class="py-2.5 pl-3.5 pr-9 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white focus:ring-2 focus:ring-blue-500 min-w-[210px]">
                                    <option value="">Todas las Sedes (Consolidado)</option>
                                    @foreach($filterLocations as $loc)
                                        <option value="{{ $loc->id }}" {{ $filterLocationId == $loc->id ? 'selected' : '' }}>
                                            {{ $loc->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <!-- Filtro Tienda -->
                                <select name="stand_id" onchange="this.form.submit()" class="py-2.5 pl-3.5 pr-9 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white focus:ring-2 focus:ring-blue-500 min-w-[210px]">
                                    <option value="">Todas las Tiendas</option>
                                    @foreach($availableStandsList as $st)
                                        <option value="{{ $st->id }}" {{ $filterStandId == $st->id ? 'selected' : '' }}>
                                            {{ $st->name }} ({{ $st->location->name }})
                                        </option>
                                    @endforeach
                                </select>

                                @if($filterLocationId || $filterStandId)
                                    <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-xl border border-slate-200 text-slate-500 hover:text-red-600 hover:border-red-200 text-xs font-bold transition-colors">
                                        Limpiar
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                @endif

                <!-- FILA 1: 4 KPIs EJECUTIVOS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">

                    <!-- KPI 1: Ventas de Hoy + Variación vs Ayer -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col justify-between">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                                </div>
                                <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Ventas de Hoy</span>
                            </div>

                            @if($variacionContraAyer > 0)
                                <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[11px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    &uarr; {{ number_format($variacionContraAyer, 1) }}%
                                </span>
                            @elseif($variacionContraAyer < 0)
                                <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[11px] font-black bg-rose-50 text-rose-700 border border-rose-200">
                                    &darr; {{ number_format(abs($variacionContraAyer), 1) }}%
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">
                                    0.0% vs ayer
                                </span>
                            @endif
                        </div>

                        <div class="mt-4">
                            <h3 class="text-3xl font-black text-slate-900 tracking-tight">S/ {{ number_format($ventasHoy, 2) }}</h3>
                            <p class="text-xs text-slate-500 mt-1.5 flex items-center justify-between gap-2">
                                <span><strong>{{ $transaccionesHoy }}</strong> venta(s)</span>
                                <span>Ticket prom: <strong class="text-slate-700">S/ {{ number_format($ticketPromedioHoy, 2) }}</strong></span>
                            </p>
                            <p class="text-[11px] text-slate-400 mt-1">
                                Ayer en este alcance: S/ {{ number_format($ventasAyer, 2) }}
                            </p>
                        </div>
                    </div>

                    <!-- KPI 2: Acumulado del Mes + Descuentos -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col justify-between">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                </div>
                                <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Acumulado del Mes</span>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                {{ $transaccionesMes }} tickets
                            </span>
                        </div>

                        <div class="mt-4">
                            <h3 class="text-3xl font-black text-slate-900 tracking-tight">S/ {{ number_format($ventasMes, 2) }}</h3>
                            <p class="text-xs text-slate-500 mt-1.5 flex items-center justify-between gap-2">
                                <span>Ticket prom: <strong class="text-slate-700">S/ {{ number_format($ticketPromedioMes, 2) }}</strong></span>
                                <span class="{{ $descuentosHoy > 0 ? 'text-amber-600 font-bold' : 'text-slate-400' }}">
                                    Desc. hoy: -S/ {{ number_format($descuentosHoy, 2) }}
                                </span>
                            </p>
                            <p class="text-[11px] text-slate-400 mt-1">
                                Descuentos del mes: -S/ {{ number_format($descuentosMes, 2) }}
                            </p>
                        </div>
                    </div>

                    <!-- KPI 3: Control de Cajas / Mi Venta Personal -->
                    @if($user->isEncargado())
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col justify-between">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-10 h-10 rounded-xl bg-purple-50 border border-purple-100 text-purple-600 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    </div>
                                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Mi Venta Personal</span>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-100">
                                    {{ $transaccionesPersonalesHoy }} hoy
                                </span>
                            </div>

                            <div class="mt-4">
                                <h3 class="text-3xl font-black text-slate-900 tracking-tight">S/ {{ number_format($ventasPersonalesHoy, 2) }}</h3>
                                <p class="text-xs text-slate-500 mt-1.5">
                                    Cajas en sede: <strong class="text-emerald-600">{{ $cajasAbiertasCount }} abiertas</strong>
                                    @if($cajasPrecierreCount > 0)
                                        &bull; <strong class="text-amber-600">{{ $cajasPrecierreCount }} precierre</strong>
                                    @endif
                                </p>
                                <a href="{{ route('supervisor.cajas') }}" class="text-[11px] font-bold text-purple-600 hover:underline mt-1 inline-block">
                                    Ir a supervisión de cajas &rarr;
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col justify-between">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Control de Cajas</span>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700">
                                    {{ $standsStatus->count() }} stands
                                </span>
                            </div>

                            <div class="mt-4">
                                <div class="flex items-baseline gap-2.5">
                                    <h3 class="text-3xl font-black text-emerald-600">{{ $cajasAbiertasCount }}</h3>
                                    <span class="text-sm font-bold text-slate-600">abiertas</span>
                                    @if($cajasPrecierreCount > 0)
                                        <span class="px-2 py-0.5 rounded-lg bg-amber-100 text-amber-800 text-xs font-black">
                                            {{ $cajasPrecierreCount }} en precierre
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500 mt-1.5">
                                    Monitoreo de turnos en vivo por punto de venta
                                </p>
                                <a href="{{ route('supervisor.cajas') }}" class="text-[11px] font-bold text-blue-600 hover:underline mt-1 inline-block">
                                    Revisar arqueos y cierres &rarr;
                                </a>
                            </div>
                        </div>
                    @endif

                    <!-- KPI 4: Alertas de Inventario y Logística -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col justify-between">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                </div>
                                <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Stock &amp; Logística</span>
                            </div>
                            @if($pedidosPendientesCount > 0)
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-black bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ $pedidosPendientesCount }} envío(s)
                                </span>
                            @endif
                        </div>

                        <div class="mt-4">
                            <div class="flex items-baseline gap-2">
                                <h3 class="text-3xl font-black {{ $totalAlertasStock > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $totalAlertasStock }}</h3>
                                <span class="text-xs font-bold text-slate-600">productos con stock bajo</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1.5">
                                Pedidos activos en almacén: <strong class="text-slate-800">{{ $pedidosPendientesCount }}</strong>
                            </p>
                            <a href="{{ route('transfers.index') }}" class="text-[11px] font-bold text-blue-600 hover:underline mt-1 inline-block">
                                Gestionar traslados y pedidos &rarr;
                            </a>
                        </div>
                    </div>

                </div>

                <!-- FILA 2: GRÁFICOS ANALÍTICOS DINÁMICOS -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <!-- GRÁFICO 1: Evolución de Ventas (Últimos 7 Días) -->
                    <div
                        class="lg:col-span-7 bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex flex-col justify-between"
                        x-data="salesTrendChart(@js($ventasUltimos7Dias))"
                    >
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
                            <div>
                                <h3 class="font-black text-slate-900 text-lg">Evolución de Ventas (Últimos 7 Días)</h3>
                                <p class="text-xs text-slate-400">Tendencia diaria con promedio y detalle interactivo al pasar el cursor</p>
                            </div>

                            <div class="inline-flex p-1 rounded-xl bg-slate-100 border border-slate-200 self-start sm:self-auto">
                                <button
                                    type="button"
                                    @click="metric = 'revenue'"
                                    :class="metric === 'revenue' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                                >
                                    Ingresos (S/)
                                </button>
                                <button
                                    type="button"
                                    @click="metric = 'tickets'"
                                    :class="metric === 'tickets' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                                >
                                    N° Ventas
                                </button>
                            </div>
                        </div>

                        <!-- Resumen Rápido 7 Días -->
                        <div class="grid grid-cols-3 gap-3 mb-5">
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-[10px] font-bold uppercase text-slate-400 block">Total 7 días</span>
                                <span class="text-sm sm:text-base font-black text-slate-900" x-text="metric === 'revenue' ? formatMoney(totalPeriod) : totalPeriod + ' ventas'"></span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-[10px] font-bold uppercase text-slate-400 block">Promedio diario</span>
                                <span class="text-sm sm:text-base font-black text-blue-600" x-text="metric === 'revenue' ? formatMoney(avgPeriod) : avgPeriod.toFixed(1) + ' / día'"></span>
                            </div>
                            <div class="p-3 rounded-xl bg-emerald-50/60 border border-emerald-100">
                                <span class="text-[10px] font-bold uppercase text-emerald-700 block">Mejor día</span>
                                <span class="text-sm sm:text-base font-black text-emerald-700 truncate block" x-text="bestDay ? (bestDay.short_day + ' ' + bestDay.day_num + ' · ' + (metric === 'revenue' ? formatMoney(bestDay.revenue) : bestDay.tickets)) : '-'"></span>
                            </div>
                        </div>

                        <!-- Área del Gráfico de Barras -->
                        <div class="relative pt-10 pb-1">
                            <!-- Tooltip Flotante -->
                            <div
                                x-show="hoveredDay !== null"
                                x-transition.opacity.duration.150ms
                                style="display: none;"
                                class="absolute top-0 right-0 z-20 bg-slate-900 text-white rounded-xl px-3.5 py-2.5 shadow-lg text-xs flex flex-wrap items-center gap-x-4 gap-y-1 pointer-events-none"
                            >
                                <span class="font-black text-blue-300" x-text="hoveredDay ? hoveredDay.short_day + ' ' + hoveredDay.full_date : ''"></span>
                                <span>Ingresos: <strong x-text="hoveredDay ? formatMoney(hoveredDay.revenue) : ''"></strong></span>
                                <span>Tickets: <strong x-text="hoveredDay ? hoveredDay.tickets : ''"></strong></span>
                                <span>Prom: <strong x-text="hoveredDay ? formatMoney(hoveredDay.avg_ticket) : ''"></strong></span>
                                <template x-if="hoveredDay && hoveredDay.discounts > 0">
                                    <span class="text-amber-300">Desc: <strong x-text="'-' + formatMoney(hoveredDay.discounts)"></strong></span>
                                </template>
                            </div>

                            <div class="relative h-52 border-b border-slate-200 flex items-end">
                                <!-- Líneas guía de fondo -->
                                <div class="absolute inset-0 flex flex-col justify-between pointer-events-none">
                                    <div class="border-t border-dashed border-slate-100 w-full"></div>
                                    <div class="border-t border-dashed border-slate-100 w-full"></div>
                                    <div class="border-t border-dashed border-slate-100 w-full"></div>
                                    <div class="w-full"></div>
                                </div>

                                <!-- Línea punteada de promedio -->
                                <div
                                    x-show="avgPeriod > 0"
                                    class="absolute left-0 right-0 border-t-2 border-dashed border-blue-400/50 pointer-events-none z-10 flex justify-end"
                                    :style="'bottom: ' + avgHeightPct + '%'"
                                >
                                    <span class="text-[9px] font-bold bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded -mt-4 border border-blue-100">
                                        Prom
                                    </span>
                                </div>

                                <!-- 7 Columnas Diarias -->
                                <div class="w-full h-full grid grid-cols-7 gap-2 sm:gap-3 items-end relative z-10 px-1">
                                    <template x-for="(day, index) in days" :key="day.date">
                                        <div
                                            class="h-full flex flex-col justify-end items-center group cursor-pointer"
                                            @mouseenter="hoveredIndex = index"
                                            @mouseleave="hoveredIndex = null"
                                        >
                                            <span
                                                class="text-[10px] font-bold mb-1 transition-opacity"
                                                :class="(hoveredIndex === index || day.is_today) ? 'text-slate-800 opacity-100' : 'text-slate-400 opacity-0 group-hover:opacity-100'"
                                                x-text="metric === 'revenue' ? (day.revenue > 0 ? 'S/' + Math.round(day.revenue) : '0') : day.tickets"
                                            ></span>

                                            <div class="w-full max-w-[44px] h-[82%] flex items-end justify-center">
                                                <div
                                                    class="w-full rounded-t-xl transition-all duration-300"
                                                    :class="day.is_today
                                                        ? 'bg-gradient-to-t from-blue-600 to-indigo-500 shadow-md shadow-blue-500/20'
                                                        : (getVal(day) > 0
                                                            ? (hoveredIndex === index ? 'bg-blue-500' : 'bg-blue-200 group-hover:bg-blue-400')
                                                            : 'bg-slate-100')"
                                                    :style="'height: ' + getBarHeightPct(day) + '%'"
                                                ></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Etiquetas eje X -->
                            <div class="grid grid-cols-7 gap-2 sm:gap-3 mt-2 px-1">
                                <template x-for="day in days" :key="'lbl-' + day.date">
                                    <div class="text-center">
                                        <span
                                            class="text-[11px] font-bold block leading-tight"
                                            :class="day.is_today ? 'text-blue-600' : 'text-slate-600'"
                                            x-text="day.is_today ? 'Hoy' : day.short_day"
                                        ></span>
                                        <span class="text-[10px] text-slate-400 block" x-text="day.day_num"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- GRÁFICO 2: Donut Interactivo de Métodos de Pago -->
                    <div
                        class="lg:col-span-5 bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex flex-col justify-between"
                        x-data="paymentDonutChart({
                            hoy: {
                                efectivo: {{ (float) $totalEfectivoHoy }},
                                yape_plin: {{ (float) $totalYapePlinHoy }},
                                tarjeta: {{ (float) $totalTarjetaHoy }}
                            },
                            mes: {
                                efectivo: {{ (float) $totalEfectivoMes }},
                                yape_plin: {{ (float) $totalYapePlinMes }},
                                tarjeta: {{ (float) $totalTarjetaMes }}
                            }
                        })"
                    >
                        <div class="flex items-start justify-between gap-2 mb-4">
                            <div>
                                <h3 class="font-black text-slate-900 text-lg">Distribución por Método de Pago</h3>
                                <p class="text-xs text-slate-400">Participación por canal de cobro</p>
                            </div>

                            <div class="inline-flex p-1 rounded-xl bg-slate-100 border border-slate-200 shrink-0">
                                <button
                                    type="button"
                                    @click="period = 'hoy'"
                                    :class="period === 'hoy' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all"
                                >
                                    Hoy
                                </button>
                                <button
                                    type="button"
                                    @click="period = 'mes'"
                                    :class="period === 'mes' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all"
                                >
                                    Este Mes
                                </button>
                            </div>
                        </div>

                        <!-- Gráfico Donut SVG -->
                        <div class="flex items-center justify-center my-2">
                            <div class="relative w-44 h-44">
                                <svg viewBox="0 0 120 120" class="w-full h-full -rotate-90">
                                    <circle cx="60" cy="60" r="46" fill="none" stroke="#f1f5f9" stroke-width="14" />

                                    <template x-for="seg in segments" :key="seg.key">
                                        <circle
                                            x-show="seg.amount > 0"
                                            cx="60"
                                            cy="60"
                                            r="46"
                                            fill="none"
                                            :stroke="seg.color"
                                            :stroke-width="hoveredKey === seg.key ? 17 : 14"
                                            stroke-linecap="butt"
                                            :stroke-dasharray="seg.dashArray"
                                            :stroke-dashoffset="seg.dashOffset"
                                            class="transition-all duration-300 cursor-pointer"
                                            @mouseenter="hoveredKey = seg.key"
                                            @mouseleave="hoveredKey = null"
                                        />
                                    </template>
                                </svg>

                                <!-- Lectura Central -->
                                <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-4 pointer-events-none">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400" x-text="centerLabel"></span>
                                    <span class="text-base font-black text-slate-900 leading-tight mt-0.5" x-text="centerValue"></span>
                                    <span x-show="centerSub" class="text-[11px] font-extrabold text-blue-600 mt-0.5" x-text="centerSub"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Desglose de Canales con Barra -->
                        <div class="space-y-3 mt-3">
                            <template x-for="seg in segments" :key="'row-' + seg.key">
                                <div
                                    class="p-2.5 rounded-xl border transition-colors cursor-pointer"
                                    :class="hoveredKey === seg.key ? 'bg-slate-50 border-slate-300' : 'bg-slate-50/60 border-slate-100'"
                                    @mouseenter="hoveredKey = seg.key"
                                    @mouseleave="hoveredKey = null"
                                >
                                    <div class="flex items-center justify-between text-xs mb-1.5">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="'background-color:' + seg.color"></span>
                                            <span class="font-bold text-slate-800" x-text="seg.label"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[11px] font-bold text-slate-500" x-text="seg.pct.toFixed(1) + '%'"></span>
                                            <span class="font-black text-slate-900" x-text="formatMoney(seg.amount)"></span>
                                        </div>
                                    </div>
                                    <div class="w-full h-1.5 bg-slate-200/80 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-300" :style="'width:' + seg.pct + '%; background-color:' + seg.color"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>

                <!-- ======================================================== -->
                <!-- FILA 3: MONITOR OPERATIVO DE TIENDAS + COMPARATIVA SEDES -->
                <!-- ======================================================== -->
                <div
                    class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6"
                    x-data="{ selectedSede: 'all', sedePeriod: 'hoy', sedes: @js($rendimientoSedes) }"
                >
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                        <div>
                            <h3 class="text-xl font-black text-slate-800 flex items-center gap-2">
                                <span class="relative flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                                </span>
                                <span>Monitor Operativo de Tiendas</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Estado en tiempo real de cada punto de venta (priorizando cajas abiertas y en precierre)
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
                            <span class="inline-flex items-center gap-1 text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-md">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Abierta ({{ $cajasAbiertasCount }})
                            </span>
                            <span class="inline-flex items-center gap-1 text-amber-700 bg-amber-50 px-2.5 py-1 rounded-md">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span> En Precierre ({{ $cajasPrecierreCount }})
                            </span>
                            <span class="inline-flex items-center gap-1 text-slate-600 bg-slate-100 px-2.5 py-1 rounded-md">
                                <span class="w-2 h-2 rounded-full bg-slate-400"></span> Cerrada
                            </span>
                        </div>
                    </div>

                    <!-- Comparativa Rápida por Sede -->
                    @if($rendimientoSedes->count() > 0)
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-600">
                                    Rendimiento por Sede
                                </span>
                                <div class="inline-flex p-0.5 rounded-lg bg-white border border-slate-200 text-[11px] font-bold">
                                    <button type="button" @click="sedePeriod = 'hoy'" :class="sedePeriod === 'hoy' ? 'bg-blue-600 text-white' : 'text-slate-500'" class="px-2.5 py-1 rounded-md transition-colors">Hoy</button>
                                    <button type="button" @click="sedePeriod = 'mes'" :class="sedePeriod === 'mes' ? 'bg-blue-600 text-white' : 'text-slate-500'" class="px-2.5 py-1 rounded-md transition-colors">Este Mes</button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <template x-for="sede in sedes" :key="sede.id">
                                    <div class="bg-white rounded-xl p-3.5 border border-slate-200/80">
                                        <div class="flex items-center justify-between gap-2">
                                            <div>
                                                <p class="font-black text-slate-800 text-sm" x-text="sede.name"></p>
                                                <span class="text-[11px] text-slate-400" x-text="sede.stands_count + ' tienda(s) · ' + (sedePeriod === 'hoy' ? sede.tickets_hoy : sede.tickets_mes) + ' venta(s)'"></span>
                                            </div>
                                            <div class="text-right">
                                                <span class="font-black text-blue-600 text-base block" x-text="'S/ ' + Number(sedePeriod === 'hoy' ? sede.ventas_hoy : sede.ventas_mes).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
                                                <span class="text-[10px] font-bold text-slate-500" x-text="(sedePeriod === 'hoy' ? sede.pct_hoy : sede.pct_mes).toFixed(1) + '% del total'"></span>
                                            </div>
                                        </div>
                                        <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden mt-2.5">
                                            <div class="h-full bg-blue-600 rounded-full transition-all duration-300" :style="'width: ' + (sedePeriod === 'hoy' ? sede.pct_hoy : sede.pct_mes) + '%'"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    @endif

                    <!-- Pestañas de Filtro Rápido por Sede -->
                    @if($rendimientoSedes->count() > 1)
                        <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 pb-4">
                            <button
                                type="button"
                                @click="selectedSede = 'all'"
                                :class="selectedSede === 'all' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all"
                            >
                                Todas ({{ $standsStatus->count() }})
                            </button>
                            @foreach($rendimientoSedes as $sede)
                                <button
                                    type="button"
                                    @click="selectedSede = '{{ $sede['id'] }}'"
                                    :class="selectedSede === '{{ $sede['id'] }}' ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all"
                                >
                                    {{ $sede['name'] }} ({{ $standsStatus->where('location_id', $sede['id'])->count() }})
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <!-- Tarjetas de Stands -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        @forelse($standsStatus as $standSt)
                            <div
                                x-show="selectedSede === 'all' || selectedSede === '{{ $standSt->location_id }}'"
                                class="p-4 rounded-xl border transition-all hover:shadow-md
                                    @if($standSt->estado === 'abierta')
                                        border-emerald-300 bg-emerald-50/20
                                    @elseif($standSt->estado === 'precierre')
                                        border-amber-300 bg-amber-50/30
                                    @else
                                        border-slate-200 bg-slate-50/50
                                    @endif
                                "
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">{{ $standSt->location_name }}</span>
                                        <h4 class="font-black text-slate-900 text-lg leading-tight">{{ $standSt->name }}</h4>
                                    </div>
                                    @if($standSt->estado === 'abierta')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-100 text-emerald-800">Abierta</span>
                                    @elseif($standSt->estado === 'precierre')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-amber-100 text-amber-800">Precierre</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-slate-200 text-slate-600">Cerrada</span>
                                    @endif
                                </div>

                                <div class="mt-4 pt-3 border-t border-slate-100 space-y-1 text-xs">
                                    <div class="flex justify-between text-slate-600">
                                        <span>Cajero / Operador:</span>
                                        <strong class="text-slate-800">{{ $standSt->operator_name }}</strong>
                                    </div>
                                    <div class="flex justify-between text-slate-600">
                                        <span>Ventas Hoy:</span>
                                        <strong class="text-blue-600 font-black">S/ {{ number_format($standSt->ventas_hoy, 2) }}</strong>
                                    </div>
                                </div>

                                @if($standSt->estado === 'precierre' && in_array($user->role, ['admin', 'encargado']))
                                    <div class="mt-3 pt-2">
                                        <a href="{{ route('supervisor.cajas') }}" class="w-full text-center block bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs py-1.5 rounded-lg transition-colors">
                                            Revisar y Aprobar Cierre &rarr;
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 text-center py-8 col-span-4">No hay tiendas para los criterios seleccionados.</p>
                        @endforelse
                    </div>
                </div>

                <!-- ======================================================== -->
                <!-- FILA 4: ROTACIÓN, CATEGORÍAS, ÚLTIMAS VENTAS Y STOCK     -->
                <!-- ======================================================== -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                    <!-- Columna Izquierda: Top Productos / Categorías + Alertas de Stock -->
                    <div class="lg:col-span-6 space-y-6">

                        <!-- Pestañas: Top 5 Productos <-> Ventas por Categoría -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6" x-data="{ tab: 'products' }">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
                                <div>
                                    <h3 class="font-black text-slate-900 text-base" x-text="tab === 'products' ? 'Top 5 Productos Más Vendidos' : 'Ventas por Categoría (Mes)'"></h3>
                                    <p class="text-xs text-slate-400" x-text="tab === 'products' ? 'Artículos con mayor rotación acumulada' : 'Participación de ingresos por línea de producto'"></p>
                                </div>

                                <div class="inline-flex p-1 rounded-xl bg-slate-100 border border-slate-200 self-start sm:self-auto">
                                    <button
                                        type="button"
                                        @click="tab = 'products'"
                                        :class="tab === 'products' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all"
                                    >
                                        Top Productos
                                    </button>
                                    <button
                                        type="button"
                                        @click="tab = 'categories'"
                                        :class="tab === 'categories' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all"
                                    >
                                        Categorías
                                    </button>
                                </div>
                            </div>

                            <!-- Tab 1: Top 5 Productos -->
                            <div x-show="tab === 'products'" class="space-y-3">
                                @php
                                    $maxQtyTop = max(1, (int) ($topProductos->max('total_qty') ?? 1));
                                @endphp
                                @forelse($topProductos as $idx => $top)
                                    @php
                                        $pctBar = round(((int) $top->total_qty / $maxQtyTop) * 100);
                                    @endphp
                                    <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100">
                                        <div class="flex items-center justify-between gap-2 mb-1.5">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <span class="w-6 h-6 rounded-lg {{ $idx === 0 ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-700' }} text-[11px] font-black flex items-center justify-center shrink-0">
                                                    #{{ $idx + 1 }}
                                                </span>
                                                <div class="min-w-0">
                                                    <p class="font-bold text-slate-800 text-xs truncate">{{ $top->product->name }}</p>
                                                    <span class="text-[10px] text-slate-400">{{ $top->product->category->name ?? 'Accesorio' }}</span>
                                                </div>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <span class="font-black text-slate-900 text-xs">{{ $top->total_qty }} und</span>
                                                <span class="text-[10px] text-slate-500 block">S/ {{ number_format($top->total_revenue, 2) }}</span>
                                            </div>
                                        </div>
                                        <div class="w-full h-1.5 bg-slate-200/70 rounded-full overflow-hidden">
                                            <div class="h-full bg-blue-600 rounded-full" style="width: {{ $pctBar }}%"></div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400 text-center py-6">No hay datos de ventas aún.</p>
                                @endforelse
                            </div>

                            <!-- Tab 2: Ventas por Categoría -->
                            <div x-show="tab === 'categories'" style="display: none;" class="space-y-3">
                                @forelse($ventasPorCategoria as $cat)
                                    <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100">
                                        <div class="flex items-center justify-between gap-2 mb-1.5">
                                            <div>
                                                <p class="font-bold text-slate-800 text-xs">{{ $cat->name }}</p>
                                                <span class="text-[10px] text-slate-400">{{ $cat->total_qty }} unidades vendidas</span>
                                            </div>
                                            <div class="text-right">
                                                <span class="font-black text-slate-900 text-xs">S/ {{ number_format($cat->total_revenue, 2) }}</span>
                                                <span class="text-[10px] font-bold text-indigo-600 block">{{ number_format($cat->pct, 1) }}%</span>
                                            </div>
                                        </div>
                                        <div class="w-full h-1.5 bg-slate-200/70 rounded-full overflow-hidden">
                                            <div class="h-full bg-indigo-600 rounded-full" style="width: {{ $cat->pct }}%"></div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400 text-center py-6">No hay ventas por categoría este mes.</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Alertas de Stock Bajo -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <h3 class="font-black text-slate-800 text-base">Alertas de Stock Bajo</h3>
                                    <p class="text-xs text-slate-400">Productos por agotarse en sedes</p>
                                </div>
                                <a href="{{ route('transfers.index') }}" class="text-xs font-bold text-blue-600 hover:underline">Pedir traslado &rarr;</a>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @forelse($alertasStock as $al)
                                    <div class="p-3 rounded-xl border border-rose-100 bg-rose-50/40 flex justify-between items-center gap-2">
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-800 text-xs truncate">{{ $al->product->name }}</p>
                                            <span class="text-[10px] text-slate-400">{{ $al->location->name }}</span>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="font-black text-rose-600 text-sm">{{ $al->quantity }} und</span>
                                            <span class="text-[9px] text-slate-400 block">Mín: {{ $al->min_stock }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-emerald-600 text-center py-4 col-span-2 font-bold">¡Inventario en niveles óptimos!</p>
                                @endforelse
                            </div>
                        </div>

                    </div>

                    <!-- Columna Derecha: Últimas Ventas en Tiempo Real -->
                    <div class="lg:col-span-6 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="font-black text-slate-900 text-base">Últimas Ventas en Tiempo Real</h3>
                                <p class="text-xs text-slate-400">Transacciones recientes registradas en el sistema</p>
                            </div>
                            <a href="{{ route('history.index') }}" class="text-xs font-bold text-blue-600 hover:underline">Ver historial &rarr;</a>
                        </div>

                        <div class="space-y-3">
                            @forelse($ultimasVentas as $venta)
                                @php
                                    $metodos = $venta->payments->pluck('payment_method')->map(fn($m) => ucfirst($m))->unique()->implode(' + ');
                                @endphp
                                <div class="p-3.5 rounded-xl border border-slate-100 hover:border-slate-200 hover:bg-slate-50/70 transition-all flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-mono font-bold text-xs text-blue-600">#{{ str_pad($venta->id, 6, '0', STR_PAD_LEFT) }}</span>
                                            <span class="text-[11px] font-bold text-slate-700">{{ $venta->cashSession?->stand?->name ?? 'Stand' }}</span>
                                            <span class="text-[10px] text-slate-400">&bull; {{ $venta->cashSession?->stand?->location?->name ?? 'Sede' }}</span>
                                            <span class="text-[10px] text-slate-400">{{ $venta->created_at->format('d/m h:i A') }}</span>
                                        </div>
                                        <p class="text-xs text-slate-600 truncate mt-1">
                                            <strong class="text-slate-800">{{ $venta->seller_name }}:</strong>
                                            {{ $venta->items->map(fn($i) => $i->quantity.'x '.($i->product->name ?? 'Producto').($i->device_model ? " ({$i->device_model})" : ''))->implode(', ') }}
                                        </p>
                                        <div class="flex items-center gap-1.5 mt-1.5">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-600">{{ $metodos ?: 'Efectivo' }}</span>
                                            @if((float) ($venta->discount_amount ?? 0) > 0)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">Desc -S/ {{ number_format($venta->discount_amount, 2) }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2.5 shrink-0">
                                        <span class="font-black text-sm text-slate-900">S/ {{ number_format($venta->total_amount, 2) }}</span>
                                        <a href="{{ route('pos.ticket', $venta->id) }}" target="_blank" class="p-2 rounded-lg border border-slate-200 text-slate-500 hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50 transition-colors" title="Ver / Imprimir Ticket">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <div class="py-14 text-center text-slate-400">
                                    <p class="text-sm font-semibold">No hay ventas registradas para este filtro.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                </div>

                <script>
                    function salesTrendChart(days) {
                        return {
                            days: days,
                            metric: 'revenue',
                            hoveredIndex: null,

                            getVal(day) {
                                return this.metric === 'revenue' ? Number(day.revenue) : Number(day.tickets);
                            },

                            get totalPeriod() {
                                return this.days.reduce((sum, d) => sum + this.getVal(d), 0);
                            },

                            get avgPeriod() {
                                return this.days.length ? this.totalPeriod / this.days.length : 0;
                            },

                            get maxValue() {
                                const m = Math.max(...this.days.map(d => this.getVal(d)), 0);
                                return m > 0 ? m : 1;
                            },

                            get avgHeightPct() {
                                if (this.avgPeriod <= 0) return 0;
                                return Math.min(82, Math.round((this.avgPeriod / this.maxValue) * 82));
                            },

                            get bestDay() {
                                if (!this.days.length) return null;
                                return this.days.reduce((best, curr) => this.getVal(curr) > this.getVal(best) ? curr : best, this.days[0]);
                            },

                            get hoveredDay() {
                                return this.hoveredIndex !== null ? this.days[this.hoveredIndex] : null;
                            },

                            getBarHeightPct(day) {
                                const val = this.getVal(day);
                                if (val <= 0) return 4;
                                return Math.max(8, Math.round((val / this.maxValue) * 100));
                            },

                            formatMoney(val) {
                                return 'S/ ' + Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                        };
                    }

                    function paymentDonutChart(data) {
                        const circumference = 2 * Math.PI * 46;

                        return {
                            data: data,
                            period: 'hoy',
                            hoveredKey: null,

                            get current() {
                                return this.data[this.period] || { efectivo: 0, yape_plin: 0, tarjeta: 0 };
                            },

                            get total() {
                                return Number(this.current.efectivo) + Number(this.current.yape_plin) + Number(this.current.tarjeta);
                            },

                            get segments() {
                                const total = this.total;
                                const defs = [
                                    { key: 'efectivo', label: 'Efectivo Físico', color: '#10b981', amount: Number(this.current.efectivo) },
                                    { key: 'yape_plin', label: 'Yape / Plin', color: '#a855f7', amount: Number(this.current.yape_plin) },
                                    { key: 'tarjeta', label: 'Tarjeta POS', color: '#3b82f6', amount: Number(this.current.tarjeta) },
                                ];

                                let offset = 0;
                                return defs.map(def => {
                                    const pct = total > 0 ? (def.amount / total) * 100 : 0;
                                    const length = total > 0 ? (def.amount / total) * circumference : 0;
                                    const segment = {
                                        ...def,
                                        pct: pct,
                                        dashArray: `${length.toFixed(2)} ${circumference.toFixed(2)}`,
                                        dashOffset: (-offset).toFixed(2),
                                    };
                                    offset += length;
                                    return segment;
                                });
                            },

                            get hoveredSegment() {
                                return this.hoveredKey ? this.segments.find(s => s.key === this.hoveredKey) : null;
                            },

                            get centerLabel() {
                                return this.hoveredSegment ? this.hoveredSegment.label : (this.period === 'hoy' ? 'Total Hoy' : 'Total Mes');
                            },

                            get centerValue() {
                                return this.hoveredSegment ? this.formatMoney(this.hoveredSegment.amount) : this.formatMoney(this.total);
                            },

                            get centerSub() {
                                return this.hoveredSegment ? this.hoveredSegment.pct.toFixed(1) + '%' : '';
                            },

                            formatMoney(val) {
                                return 'S/ ' + Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                        };
                    }
                </script>

            @endif

        </div>
    </div>
</x-app-layout>
