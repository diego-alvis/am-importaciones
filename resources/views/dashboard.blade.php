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
                            Mi Panel de Ventas &bull; {{ $user->getActiveStand()?->name ?? 'Mi Tienda' }}
                        @endif
                    </span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Operador: <strong class="text-slate-800">{{ $user->name }}</strong>
                    &bull; Rol: <span class="capitalize px-2 py-0.5 rounded text-[11px] font-bold bg-blue-100 text-blue-800">{{ $user->role }}</span>
                    @if(!$user->isAlmacen() && $user->getActiveStand())
                        &bull; Tienda activa: <strong class="text-blue-600">{{ $user->getActiveStand()->name }}</strong> ({{ $user->getActiveStand()->location->name }})
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

            @else
                <!-- ============================================== -->
                <!-- VISTA COMERCIAL: ADMIN, SUPERVISORES, VENDEDOR -->
                <!-- ============================================== -->

                @if($user->isAdmin())
                    <!-- BARRA DE FILTROS SUPERVISIÓN ADMIN -->
                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                        <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-blue-600"></span>
                                <span class="font-black text-sm text-slate-800 uppercase tracking-wider">Supervisión en Tiempo Real:</span>
                            </div>

                            <div class="flex items-center gap-3 flex-wrap">
                                <!-- Filtro Sede -->
                                <select name="location_id" onchange="this.form.submit()" class="py-2 px-3 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white focus:ring-2 focus:ring-blue-500">
                                    <option value="">Todas las Sedes (Consolidado)</option>
                                    @foreach($filterLocations as $loc)
                                        <option value="{{ $loc->id }}" {{ $filterLocationId == $loc->id ? 'selected' : '' }}>
                                            {{ $loc->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <!-- Filtro Tienda -->
                                <select name="stand_id" onchange="this.form.submit()" class="py-2 px-3 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white focus:ring-2 focus:ring-blue-500">
                                    <option value="">Todas las Tiendas</option>
                                    @foreach($availableStandsList as $st)
                                        <option value="{{ $st->id }}" {{ $filterStandId == $st->id ? 'selected' : '' }}>
                                            {{ $st->name }} ({{ $st->location->name }})
                                        </option>
                                    @endforeach
                                </select>

                                @if($filterLocationId || $filterStandId)
                                    <a href="{{ route('dashboard') }}" class="p-2 text-slate-400 hover:text-red-500 text-xs font-bold transition-colors">
                                        Limpiar filtro
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                @endif

                <!-- TARJETAS DE MÉTRICAS (KPIs) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    
                    <!-- Ventas de Hoy -->
                    <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl shadow-md p-6 text-white relative overflow-hidden">
                        <div class="relative z-10">
                            <p class="text-emerald-100 text-xs font-bold uppercase tracking-wider mb-1">
                                {{ $user->isVendedor() ? 'Ventas de Mi Tienda Hoy' : 'Ventas de Hoy' }}
                            </p>
                            <h3 class="text-3xl font-black">S/ {{ number_format($ventasHoy, 2) }}</h3>
                            <p class="text-xs text-emerald-100 mt-2 font-medium">{{ $transaccionesHoy }} venta(s) completada(s)</p>
                        </div>
                        <svg class="w-24 h-24 text-emerald-400 absolute -bottom-4 -right-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    </div>

                    <!-- Acumulado Mes -->
                    <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl shadow-md p-6 text-white relative overflow-hidden">
                        <div class="relative z-10">
                            <p class="text-blue-100 text-xs font-bold uppercase tracking-wider mb-1">Acumulado del Mes</p>
                            <h3 class="text-3xl font-black">S/ {{ number_format($ventasMes, 2) }}</h3>
                            <p class="text-xs text-blue-100 mt-2 font-medium">Mes en curso</p>
                        </div>
                        <svg class="w-24 h-24 text-blue-400 absolute -bottom-4 -right-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>

                    @if($user->isEncargado())
                        <!-- KPI Específico de Supervisor: Venta Personal -->
                        <div class="bg-gradient-to-br from-purple-600 to-indigo-800 rounded-2xl shadow-md p-6 text-white relative overflow-hidden">
                            <div class="relative z-10">
                                <p class="text-purple-200 text-xs font-bold uppercase tracking-wider mb-1">Mi Venta Personal Hoy</p>
                                <h3 class="text-3xl font-black">S/ {{ number_format($ventasPersonalesHoy, 2) }}</h3>
                                <p class="text-xs text-purple-200 mt-2 font-medium">{{ $transaccionesPersonalesHoy }} transacción(es) mías</p>
                            </div>
                            <svg class="w-24 h-24 text-purple-400 absolute -bottom-4 -right-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                    @else
                        <!-- Cajas Operativas -->
                        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl shadow-md p-6 text-white relative overflow-hidden">
                            <div class="relative z-10">
                                <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Control de Cajas</p>
                                <h3 class="text-2xl font-black text-white flex items-center gap-2">
                                    <span class="text-emerald-400">{{ $cajasAbiertasCount }}</span>
                                    <span class="text-slate-500 text-sm">abiertas</span>
                                    @if($cajasPrecierreCount > 0)
                                        <span class="text-amber-400 text-sm">/ {{ $cajasPrecierreCount }} precierre</span>
                                    @endif
                                </h3>
                                <p class="text-xs text-slate-400 mt-2">
                                    {{ $cajaActiva ? 'Tu caja personal está abierta' : 'No tienes caja personal abierta' }}
                                </p>
                            </div>
                            <svg class="w-24 h-24 text-slate-700 absolute -bottom-4 -right-4 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    @endif

                    <!-- Alertas de Stock Bajo -->
                    <div class="bg-gradient-to-br from-amber-500 to-rose-600 rounded-2xl shadow-md p-6 text-white relative overflow-hidden">
                        <div class="relative z-10">
                            <p class="text-amber-100 text-xs font-bold uppercase tracking-wider mb-1">Alertas de Inventario</p>
                            <h3 class="text-3xl font-black">{{ $totalAlertasStock }}</h3>
                            <p class="text-xs text-amber-100 mt-2 font-medium">Productos con stock bajo (&le; 5 uds)</p>
                        </div>
                        <svg class="w-24 h-24 text-amber-300 absolute -bottom-4 -right-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>

                </div>

                <!-- ======================================================== -->
                <!-- MONITOR OPERATIVO EN VIVO DE TIENDAS (POR SEDE / STAND)  -->
                <!-- ======================================================== -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-6">
                        <div>
                            <h3 class="text-xl font-black text-slate-800 flex items-center gap-2">
                                <span class="relative flex h-3 w-3">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                                </span>
                                <span>Monitor Operativo de Tiendas</span>
                            </h3>
                            <p class="text-xs text-slate-400">
                                Estado actual en tiempo real de cada punto de venta en sus sedes correspondientes
                            </p>
                        </div>
                        <div class="flex items-center gap-2 text-xs font-bold">
                            <span class="inline-flex items-center gap-1 text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-md">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Abierta
                            </span>
                            <span class="inline-flex items-center gap-1 text-amber-700 bg-amber-50 px-2.5 py-1 rounded-md">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span> En Precierre
                            </span>
                            <span class="inline-flex items-center gap-1 text-slate-600 bg-slate-100 px-2.5 py-1 rounded-md">
                                <span class="w-2 h-2 rounded-full bg-slate-400"></span> Cerrada
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        @forelse($standsStatus as $standSt)
                            <div class="p-4 rounded-xl border transition-all hover:shadow-md
                                @if($standSt->estado === 'abierta')
                                    border-emerald-300 bg-emerald-50/20
                                @elseif($standSt->estado === 'precierre')
                                    border-amber-300 bg-amber-50/30
                                @else
                                    border-slate-200 bg-slate-50/50
                                @endif
                            ">
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

                <!-- SECCIÓN INFERIOR: DESGLOSE DE COBROS & ALERTAS -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    
                    <!-- Desglose de Pagos Hoy -->
                    <div class="lg:col-span-5 bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex flex-col justify-between">
                        <div>
                            <h3 class="font-black text-slate-800 text-lg mb-1">Recaudación por Método de Pago Hoy</h3>
                            <p class="text-xs text-slate-400 mb-6">Desglose físico vs pasarelas digitales</p>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-sm">
                                            S/
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-slate-800">Efectivo Físico</p>
                                            <span class="text-[10px] text-slate-400">Recaudado en gaveta</span>
                                        </div>
                                    </div>
                                    <span class="font-black text-slate-900 text-lg">S/ {{ number_format($totalEfectivoHoy, 2) }}</span>
                                </div>

                                <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center font-bold text-sm">
                                            YP
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-slate-800">Yape / Plin</p>
                                            <span class="text-[10px] text-slate-400">Billeteras móviles</span>
                                        </div>
                                    </div>
                                    <span class="font-black text-slate-900 text-lg">S/ {{ number_format($totalYapePlinHoy, 2) }}</span>
                                </div>

                                <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">
                                            POS
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-slate-800">Tarjetas de Débito/Crédito</p>
                                            <span class="text-[10px] text-slate-400">Terminal POS</span>
                                        </div>
                                    </div>
                                    <span class="font-black text-slate-900 text-lg">S/ {{ number_format($totalTarjetaHoy, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 mt-4 flex justify-between items-center text-sm">
                            <span class="font-bold text-slate-500 uppercase text-xs">Total Consolidado</span>
                            <span class="font-black text-xl text-blue-600">S/ {{ number_format($ventasHoy, 2) }}</span>
                        </div>
                    </div>

                    <!-- Top 5 Productos y Alertas de Stock -->
                    <div class="lg:col-span-7 space-y-6">
                        
                        <!-- Top Productos -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                            <h3 class="font-black text-slate-800 text-base mb-1">Top Productos Más Vendidos</h3>
                            <p class="text-xs text-slate-400 mb-4">Artículos con mayor rotación</p>

                            <div class="space-y-3">
                                @forelse($topProductos as $top)
                                    <div class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 transition-colors">
                                        <div>
                                            <p class="font-bold text-slate-800 text-xs">{{ $top->product->name }}</p>
                                            <span class="text-[10px] text-slate-400">{{ $top->product->category->name ?? 'Accesorio' }}</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="font-black text-slate-900 text-xs">{{ $top->total_qty }} und</span>
                                            <span class="text-[10px] text-slate-400 block">S/ {{ number_format($top->total_revenue, 2) }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400 text-center py-4">No hay datos de ventas aún.</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Alertas de Stock -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <h3 class="font-black text-slate-800 text-base">Alertas de Stock Bajo</h3>
                                    <p class="text-xs text-slate-400">Productos por agotarse</p>
                                </div>
                                <a href="{{ route('transfers.index') }}" class="text-xs font-bold text-blue-600 hover:underline">Pedir traslado &rarr;</a>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @forelse($alertasStock as $al)
                                    <div class="p-3 rounded-xl border border-rose-100 bg-rose-50/40 flex justify-between items-center">
                                        <div>
                                            <p class="font-bold text-slate-800 text-xs">{{ $al->product->name }}</p>
                                            <span class="text-[10px] text-slate-400">{{ $al->location->name }}</span>
                                        </div>
                                        <div class="text-right">
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

                </div>

            @endif

        </div>
    </div>
</x-app-layout>
