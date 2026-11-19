<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-black text-2xl text-slate-800 leading-tight flex items-center gap-2">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    @if(auth()->user()->isAlmacenero() || auth()->user()->isAdmin())
                        <span>Centro de Despacho y Pedidos de Almacén</span>
                    @else
                        <span>Pedidos de Mercadería a Almacén Central</span>
                    @endif
                </h2>
                <p class="text-sm text-slate-500 mt-0.5">
                    @if(auth()->user()->isAlmacenero() || auth()->user()->isAdmin())
                        Atención de solicitudes de stock de tiendas y transferencias entre sedes
                    @else
                        Solicita reposición y abastecimiento de productos para tu sede: <strong class="text-blue-700">{{ auth()->user()->location->name ?? 'Tu Tienda' }}</strong>
                    @endif
                </p>
            </div>

            <div class="flex items-center gap-2">
                @if(auth()->user()->isAlmacenero() || auth()->user()->isAdmin())
                    <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-direct-transfer-modal'))" class="bg-slate-800 hover:bg-slate-900 text-white font-bold px-4 py-2.5 rounded-xl shadow-sm text-sm flex items-center gap-2 transition-all cursor-pointer">
                        <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>+ Traslado Directo</span>
                    </button>
                @endif

                @if(!auth()->user()->isAlmacenero())
                    <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-order-modal'))" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2.5 rounded-xl shadow-sm text-sm flex items-center gap-2 transition-all cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        <span>+ Realizar Pedido de Mercadería</span>
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="orderManager()" 
         @open-order-modal.window="showOrderModal = true" 
         @open-direct-transfer-modal.window="showDirectModal = true">
        
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- BANNER DE SOLICITUDES PENDIENTES PARA ALMACÉN Y ADMIN -->
            @if((auth()->user()->isAlmacenero() || auth()->user()->isAdmin()) && $pendingCount > 0)
            <div class="bg-amber-50 border-2 border-amber-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-amber-500 animate-ping"></span>
                        <h3 class="font-black text-amber-900 text-base">Solicitudes de Tiendas Pendientes de Despacho ({{ $pendingCount }})</h3>
                    </div>
                    <span class="text-xs font-bold text-amber-700 uppercase bg-amber-200/60 px-2.5 py-1 rounded-lg">Requieren Alistamiento</span>
                </div>
                <p class="text-xs text-amber-800 mb-4">
                    Las tiendas han solicitado estos productos. Revisa la disponibilidad en el Almacén Central, genera la hoja de picking y confirma el envío.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($transfers->where('status', \App\Enums\TransferStatus::Solicitado) as $pTransf)
                    <div class="bg-white p-4 rounded-xl border border-amber-200 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-mono font-bold text-xs text-blue-600">#PED-{{ str_pad($pTransf->id, 5, '0', STR_PAD_LEFT) }}</span>
                                <span class="text-[10px] text-slate-400 font-semibold">{{ $pTransf->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="font-black text-slate-800 text-sm mb-1">
                                Para: <span class="text-blue-700">{{ $pTransf->toLocation->name }}</span>
                            </div>
                            <div class="text-xs text-slate-500 mb-2">
                                Solicitado por: <strong>{{ $pTransf->requestedBy->name ?? 'Personal de Sede' }}</strong>
                            </div>

                            @if($pTransf->notes)
                            <div class="bg-slate-50 p-2 rounded-lg text-[11px] text-slate-600 mb-2 italic">
                                &ldquo;{{ $pTransf->notes }}&rdquo;
                            </div>
                            @endif

                            <div class="border-t border-slate-100 pt-2 mb-3 max-h-24 overflow-y-auto space-y-1">
                                @foreach($pTransf->items as $it)
                                    <div class="text-xs flex justify-between text-slate-700 font-medium">
                                        <span class="truncate max-w-[170px]">&bull; {{ $it->product->name ?? 'Producto' }}</span>
                                        <span class="font-bold text-blue-600">{{ $it->quantity }} und</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-100 flex gap-2">
                            <a href="{{ route('transfers.picking', $pTransf->id) }}" target="_blank" class="px-2.5 py-1.5 border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-lg text-xs font-bold text-center" title="Imprimir hoja de alistamiento">
                                🖨️ Picking
                            </a>
                            <form
                                action="{{ route('transfers.dispatch', $pTransf->id) }}"
                                method="POST"
                                class="flex-1"
                                data-confirm="¿Confirmas que este pedido ha sido alistado y sale despachado hacia {{ $pTransf->toLocation->name }}?"
                                data-confirm-title="Despachar Pedido #PED-{{ str_pad($pTransf->id, 5, '0', STR_PAD_LEFT) }}"
                                data-confirm-button="Alistar y Enviar"
                                data-confirm-tone="emerald"
                            >
                                @csrf
                                <button type="submit" class="w-full py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-xs transition-colors">
                                    ✓ Alistar y Enviar
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- TABLA HISTORIAL GENERAL -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <div>
                        <span class="font-bold text-sm text-slate-700">Movimientos, Solicitudes y Envíos de Mercadería</span>
                        @if(auth()->user()->location && !auth()->user()->isAlmacenero() && !auth()->user()->isAdmin())
                            <span class="text-xs text-blue-600 ml-2 font-semibold">({{ auth()->user()->location->name }})</span>
                        @endif
                    </div>
                    <span class="text-xs text-slate-400">Total registrados: {{ $transfers->total() }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-400 text-xs uppercase border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-6 font-bold"># Pedido / Transf</th>
                                <th class="py-3 px-6 font-bold">Fecha</th>
                                <th class="py-3 px-6 font-bold">Origen</th>
                                <th class="py-3 px-6 font-bold">Destino</th>
                                <th class="py-3 px-6 font-bold">Artículos y Cantidades</th>
                                <th class="py-3 px-6 font-bold">Personal</th>
                                <th class="py-3 px-6 font-bold text-center">Estado / Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($transfers as $transf)
                            @php
                                $statusEnum = $transf->status instanceof \App\Enums\TransferStatus ? $transf->status : \App\Enums\TransferStatus::tryFrom($transf->status) ?? \App\Enums\TransferStatus::Solicitado;
                                $statusVal = $statusEnum->value;
                            @endphp
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-4 px-6 font-mono font-bold text-xs text-blue-600">
                                    #TR-{{ str_pad($transf->id, 5, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="py-4 px-6 text-xs text-slate-500">
                                    {{ $transf->created_at->format('d/m/Y h:i A') }}
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-bold text-slate-800 text-xs block">{{ $transf->fromLocation->name }}</span>
                                    <span class="text-[10px] text-slate-400 uppercase font-semibold">{{ $transf->fromLocation->type }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-bold text-blue-700 text-xs block">{{ $transf->toLocation->name }}</span>
                                    <span class="text-[10px] text-slate-400 uppercase font-semibold">{{ $transf->toLocation->type }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex flex-col gap-1 max-w-xs">
                                        @foreach($transf->items->take(3) as $item)
                                            <span class="text-xs font-semibold text-slate-700 truncate">
                                                &bull; {{ $item->product->name ?? 'Producto' }}: <strong class="text-blue-600">{{ $item->quantity }} und</strong>
                                            </span>
                                        @endforeach
                                        @if($transf->items->count() > 3)
                                            <span class="text-[11px] text-slate-400 italic">+ {{ $transf->items->count() - 3 }} productos más...</span>
                                        @endif
                                        <span class="text-[10px] font-bold text-slate-500 mt-0.5">Total: {{ $transf->items->sum('quantity') }} unidades</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-xs">
                                    @if($transf->requestedBy)
                                        <div class="text-slate-600">Solicitado: <strong>{{ $transf->requestedBy->name }}</strong></div>
                                    @endif
                                    @if($transf->dispatchedBy)
                                        <div class="text-slate-500 text-[11px]">Despachado: {{ $transf->dispatchedBy->name }}</div>
                                    @endif
                                    @if(!$transf->requestedBy && !$transf->dispatchedBy)
                                        <span class="text-slate-400">Sistema</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-center">
                                    @if($statusVal === 'solicitado')
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-800">
                                            <svg class="w-3 h-3 text-amber-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <span>SOLICITADO</span>
                                        </span>
                                        
                                        @can('dispatch', $transf)
                                            <div class="mt-2 flex items-center justify-center gap-1.5">
                                                <a href="{{ route('transfers.picking', $transf->id) }}" target="_blank" class="px-2 py-1 border border-slate-200 hover:bg-slate-100 rounded text-[11px] font-bold text-slate-600" title="Imprimir hoja de picking">
                                                    🖨️
                                                </a>
                                                <form
                                                    action="{{ route('transfers.dispatch', $transf->id) }}"
                                                    method="POST"
                                                    data-confirm="¿Confirmas el despacho del pedido #TR-{{ str_pad($transf->id, 5, '0', STR_PAD_LEFT) }} hacia {{ $transf->toLocation->name }}?"
                                                    data-confirm-title="Despachar Pedido"
                                                    data-confirm-button="Alistar y Enviar"
                                                    data-confirm-tone="emerald"
                                                >
                                                    @csrf
                                                    <button type="submit" class="px-2.5 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] shadow-xs">
                                                        Alistar y Enviar
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="block text-[10px] text-slate-400 mt-1">En espera de almacén</span>
                                        @endcan

                                    @elseif($statusVal === 'en_transito')
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-black uppercase bg-blue-100 text-blue-800">
                                            <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path></svg>
                                            <span>EN CAMINO</span>
                                        </span>

                                        @can('receive', $transf)
                                            <form
                                                action="{{ route('transfers.receive', $transf->id) }}"
                                                method="POST"
                                                class="mt-2"
                                                data-confirm="¿Confirmas que la mercadería del traslado #TR-{{ str_pad($transf->id, 5, '0', STR_PAD_LEFT) }} llegó físicamente a tu tienda?"
                                                data-confirm-title="Confirmar Recepción"
                                                data-confirm-button="Sí, Mercadería Recibida"
                                                data-confirm-tone="emerald"
                                            >
                                                @csrf
                                                <button type="submit" class="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] shadow-xs flex items-center gap-1 mx-auto">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                    <span>Confirmar Recepción</span>
                                                </button>
                                            </form>
                                        @else
                                            <span class="block text-[10px] text-slate-400 mt-1">Esperando llegada a destino</span>
                                        @endcan

                                    @elseif($statusVal === 'recibido')
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800">
                                            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                            <span>RECIBIDO</span>
                                        </span>
                                    @elseif($statusVal === 'cancelado')
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-black uppercase bg-red-100 text-red-800">
                                            <span>CANCELADO</span>
                                        </span>
                                    @endif

                                    @can('cancel', $transf)
                                        @if(in_array($statusVal, ['solicitado', 'en_transito']))
                                            <form
                                                action="{{ route('transfers.cancel', $transf->id) }}"
                                                method="POST"
                                                class="mt-1"
                                                data-confirm="¿Deseas cancelar la solicitud/traslado #TR-{{ str_pad($transf->id, 5, '0', STR_PAD_LEFT) }}?"
                                                data-confirm-title="Cancelar Solicitud / Traslado"
                                                data-confirm-button="Sí, Cancelar"
                                                data-confirm-tone="rose"
                                            >
                                                @csrf
                                                <button type="submit" class="text-[10px] text-red-500 hover:text-red-700 hover:underline">
                                                    Cancelar
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400 font-medium">
                                    No hay solicitudes o transferencias de mercadería registradas.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($transfers->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $transfers->links() }}
                </div>
                @endif
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- MODAL AVANZADO: REALIZAR PEDIDO MASIVO A ALMACÉN (PARA TIENDAS / VENDEDORES) -->
        <!-- ========================================================================= -->
        <div x-show="showOrderModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-3 sm:p-5" style="display: none;">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-5xl overflow-hidden flex flex-col max-h-[92vh]" @click.away="showOrderModal = false">
                
                <!-- Header del Modal -->
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center shrink-0">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-blue-100 text-blue-700 uppercase">Solicitud de Pedido</span>
                            <span class="text-xs text-slate-400">&bull; Origen: <strong>Almacén Central</strong> &rarr; Destino: <strong class="text-blue-700">{{ auth()->user()->location->name ?? 'Tu Tienda' }}</strong></span>
                        </div>
                        <h3 class="font-black text-xl text-slate-800 mt-0.5">Catálogo de Abastecimiento para tu Tienda</h3>
                    </div>
                    <button type="button" @click="showOrderModal = false" class="text-slate-400 hover:text-red-500 text-2xl font-bold leading-none p-1">&times;</button>
                </div>

                <!-- Barra de Acciones y Filtros Rápidos -->
                <div class="p-4 bg-white border-b border-slate-100 grid grid-cols-1 md:grid-cols-12 gap-3 items-center shrink-0">
                    <!-- Buscador -->
                    <div class="md:col-span-5 relative">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <input type="text" x-model="searchQuery" placeholder="Buscar por producto o SKU..." class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-blue-500 text-slate-800 placeholder-slate-400">
                    </div>

                    <!-- Filtro Categoría -->
                    <div class="md:col-span-3">
                        <select x-model="selectedCategory" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-blue-500 text-slate-700 bg-white">
                            <option value="">Todas las categorías</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Botón Autocompletar Críticos -->
                    <div class="md:col-span-4 flex justify-end">
                        <button type="button" @click="loadLowStockItems()" class="w-full md:w-auto px-3.5 py-2 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 font-bold text-xs flex items-center justify-center gap-1.5 transition-colors shadow-2xs">
                            <span>⚡</span>
                            <span>Cargar productos con bajo stock (<span x-text="lowStockCount"></span>)</span>
                        </button>
                    </div>
                </div>

                <!-- Cuerpo en 2 Columnas -->
                <div class="grid grid-cols-1 md:grid-cols-12 flex-1 overflow-hidden divide-y md:divide-y-0 md:divide-x divide-slate-100">
                    
                    <!-- Columna 1: Catálogo de Productos Disponibles -->
                    <div class="md:col-span-7 p-4 overflow-y-auto space-y-2 bg-slate-50/50">
                        <div class="flex justify-between items-center text-xs font-bold text-slate-500 mb-1 px-1">
                            <span>Productos Disponibles (<span x-text="filteredProducts.length"></span>)</span>
                            <span class="text-[11px] font-normal text-slate-400">Existencias en tu tienda vs Almacén</span>
                        </div>

                        <template x-for="prod in filteredProducts" :key="prod.id">
                            <div class="bg-white p-3 rounded-2xl border border-slate-200 hover:border-blue-300 transition-all shadow-2xs flex items-center justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5 mb-0.5">
                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-600" x-text="prod.sku"></span>
                                        <span class="text-[10px] font-bold text-blue-600 uppercase" x-text="prod.category_name"></span>
                                    </div>
                                    <h4 class="font-bold text-xs text-slate-800 truncate" x-text="prod.name"></h4>
                                    
                                    <div class="flex items-center gap-3 mt-1.5 text-[11px]">
                                        <span :class="prod.is_low_stock ? 'text-red-600 font-bold' : 'text-slate-600'">
                                            Tu tienda: <strong x-text="prod.store_stock"></strong> und
                                            <template x-if="prod.is_low_stock">
                                                <span class="text-[9px] px-1 py-0.2 rounded bg-red-100 text-red-700 ml-0.5 uppercase font-black">Bajo</span>
                                            </template>
                                        </span>
                                        <span class="text-slate-400">&bull;</span>
                                        <span class="text-emerald-700 font-semibold">
                                            Almacén: <strong x-text="prod.almacen_stock"></strong> und
                                        </span>
                                    </div>
                                </div>

                                <!-- Selector de cantidad y agregar -->
                                <div class="shrink-0 flex items-center gap-1.5">
                                    <div class="flex items-center border border-slate-200 rounded-lg bg-slate-50 overflow-hidden">
                                        <button type="button" @click="prod.quantityToAdd = Math.max(1, (prod.quantityToAdd || 5) - 5)" class="px-2 py-1 text-slate-500 hover:bg-slate-200 font-bold text-xs">-</button>
                                        <input type="number" min="1" x-model.number="prod.quantityToAdd" class="w-12 py-1 px-1 text-center font-bold text-xs border-0 bg-white text-slate-800" placeholder="5">
                                        <button type="button" @click="prod.quantityToAdd = (prod.quantityToAdd || 5) + 5" class="px-2 py-1 text-slate-500 hover:bg-slate-200 font-bold text-xs">+</button>
                                    </div>
                                    <button type="button" @click="addToOrder(prod)" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition-colors flex items-center gap-1">
                                        <span>+</span>
                                        <span>Pedir</span>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div x-show="filteredProducts.length === 0" class="py-12 text-center text-slate-400 text-xs">
                            No se encontraron productos con ese criterio de búsqueda.
                        </div>
                    </div>

                    <!-- Columna 2: Lista / Canasta del Pedido -->
                    <div class="md:col-span-5 p-4 flex flex-col justify-between bg-white overflow-hidden">
                        <div class="flex-1 flex flex-col overflow-hidden">
                            <div class="flex justify-between items-center pb-2 border-b border-slate-100 mb-2">
                                <h4 class="font-black text-sm text-slate-800 flex items-center gap-1.5">
                                    <span>📦 Artículos a Solicitar</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-700" x-text="orderList.length"></span>
                                </h4>
                                <span class="text-xs font-bold text-slate-600">
                                    Total: <strong class="text-blue-600" x-text="totalUnits"></strong> und
                                </span>
                            </div>

                            <div class="flex-1 overflow-y-auto space-y-2 pr-1">
                                <template x-for="(item, idx) in orderList" :key="item.id">
                                    <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-200 flex items-center justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <div class="font-mono text-[9px] text-slate-400 font-semibold" x-text="item.sku"></div>
                                            <div class="font-bold text-xs text-slate-800 truncate" x-text="item.name"></div>
                                        </div>

                                        <div class="shrink-0 flex items-center gap-1.5">
                                            <input type="number" min="1" x-model.number="item.quantity" class="w-16 px-1 py-1 rounded border border-slate-200 text-xs font-black text-center text-slate-800 focus:ring-1 focus:ring-blue-500">
                                            <span class="text-[10px] text-slate-400">und</span>
                                            <button type="button" @click="removeFromOrder(idx)" class="text-red-500 hover:text-red-700 p-1 font-bold text-xs" title="Quitar">✕</button>
                                        </div>
                                    </div>
                                </template>

                                <div x-show="orderList.length === 0" class="py-16 text-center text-slate-400">
                                    <div class="text-3xl mb-1">🛒</div>
                                    <p class="text-xs font-bold text-slate-600">Tu lista de pedido está vacía</p>
                                    <p class="text-[11px] text-slate-400 mt-1 max-w-xs mx-auto">
                                        Selecciona productos del catálogo a la izquierda o haz clic en "Cargar productos con bajo stock".
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Formulario oculto y botón de confirmación -->
                        <div class="pt-3 border-t border-slate-100 mt-2 shrink-0">
                            <form action="{{ route('transfers.store') }}" method="POST" id="storeOrderForm">
                                @csrf
                                <template x-for="(item, index) in orderList" :key="index">
                                    <div>
                                        <input type="hidden" :name="'items[' + index + '][product_id]'" :value="item.id">
                                        <input type="hidden" :name="'items[' + index + '][quantity]'" :value="item.quantity">
                                    </div>
                                </template>

                                <div class="mb-3">
                                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Notas u Observaciones (Opcional)</label>
                                    <input type="text" name="notes" placeholder="Ej: Pedido urgente para fin de semana..." class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div class="flex gap-2">
                                    <button type="button" @click="showOrderModal = false" class="w-1/3 py-2.5 rounded-xl border border-slate-200 font-bold text-xs text-slate-600 hover:bg-slate-50">
                                        Cancelar
                                    </button>
                                    <button type="submit" :disabled="orderList.length === 0" :class="orderList.length === 0 ? 'opacity-50 cursor-not-allowed bg-slate-400' : 'bg-blue-600 hover:bg-blue-700'" class="w-2/3 py-2.5 rounded-xl text-white font-black text-xs shadow-sm flex items-center justify-center gap-1.5 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <span>Enviar Pedido a Almacén (<span x-text="totalUnits"></span> und)</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL TRASLADO DIRECTO (EXCLUSIVO PARA ALMACÉN Y ADMINISTRADOR)           -->
        <!-- ========================================================================= -->
        @if(auth()->user()->isAlmacenero() || auth()->user()->isAdmin())
        <div x-show="showDirectModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl overflow-hidden" @click.away="showDirectModal = false">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800">Nuevo Traslado Directo de Mercadería</h3>
                        <p class="text-xs text-slate-500">Despacha mercadería inmediatamente desde Almacén Central hacia cualquier sede.</p>
                    </div>
                    <button type="button" @click="showDirectModal = false" class="text-slate-400 hover:text-red-500 text-2xl font-bold leading-none">&times;</button>
                </div>

                <form action="{{ route('transfers.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Origen (Despacha)</label>
                            <select name="from_location_id" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm font-semibold focus:ring-2 focus:ring-blue-500 bg-white">
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ ($almacen && $almacen->id === $loc->id) ? 'selected' : '' }}>
                                        {{ $loc->name }} ({{ strtoupper($loc->type) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Destino (Recibe)</label>
                            <select name="to_location_id" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm font-semibold focus:ring-2 focus:ring-blue-500 bg-white">
                                @foreach($locations->where('type', '!=', 'almacen') as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }} (SEDE)</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-100">
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-xs font-bold text-slate-700 uppercase">Productos a Despachar</label>
                            <button type="button" @click="addDirectItem()" class="text-xs text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1">
                                + Agregar producto
                            </button>
                        </div>

                        <div class="space-y-2.5 max-h-52 overflow-y-auto pr-1">
                            <template x-for="(dItem, dIdx) in directItems" :key="dIdx">
                                <div class="flex items-center gap-2 bg-slate-50 p-2.5 rounded-xl border border-slate-200">
                                    <div class="flex-1">
                                        <select :name="'items[' + dIdx + '][product_id]'" x-model="dItem.product_id" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs font-semibold bg-white text-slate-800">
                                            <template x-for="p in catalog" :key="p.id">
                                                <option :value="p.id" x-text="p.name + ' (Almacén: ' + p.almacen_stock + ' und)'"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="w-24">
                                        <input type="number" :name="'items[' + dIdx + '][quantity]'" x-model.number="dItem.quantity" min="1" required class="w-full px-2 py-2 rounded-lg border border-slate-200 text-xs font-black text-center" placeholder="Cant">
                                    </div>
                                    <template x-if="directItems.length > 1">
                                        <button type="button" @click="removeDirectItem(dIdx)" class="text-red-500 hover:bg-red-50 p-2 rounded-lg font-bold" title="Quitar">✕</button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Notas de Despacho (Opcional)</label>
                        <input type="text" name="notes" placeholder="Ej: Envío de reposición general..." class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="pt-4 flex justify-end gap-2 border-t border-slate-100">
                        <button type="button" @click="showDirectModal = false" class="px-4 py-2 border border-slate-200 rounded-xl text-slate-600 font-bold text-sm hover:bg-slate-50">Cancelar</button>
                        <button type="submit" class="px-5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold text-sm shadow-sm flex items-center gap-1.5">
                            <span>Despachar Traslado &rarr;</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

    </div>

    <script>
    function orderManager() {
        return {
            showOrderModal: false,
            showDirectModal: false,
            searchQuery: '',
            selectedCategory: '',
            catalog: @json($catalogProducts),
            orderList: [],
            directItems: [{ product_id: '{{ $catalogProducts->first()['id'] ?? 1 }}', quantity: 10 }],

            init() {
                // Inicializar quantityToAdd en el catálogo
                this.catalog.forEach(p => {
                    p.quantityToAdd = 5;
                });
            },

            get lowStockCount() {
                return this.catalog.filter(p => p.is_low_stock && p.almacen_stock > 0).length;
            },

            get filteredProducts() {
                return this.catalog.filter(p => {
                    const matchSearch = this.searchQuery === '' || 
                        p.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                        p.sku.toLowerCase().includes(this.searchQuery.toLowerCase());
                    const matchCat = this.selectedCategory === '' || String(p.category_id) === String(this.selectedCategory);
                    return matchSearch && matchCat;
                });
            },

            get totalUnits() {
                return this.orderList.reduce((sum, item) => sum + (parseInt(item.quantity) || 0), 0);
            },

            addToOrder(product) {
                const qty = parseInt(product.quantityToAdd) || 5;
                const existing = this.orderList.find(item => item.id === product.id);
                if (existing) {
                    existing.quantity += qty;
                } else {
                    this.orderList.push({
                        id: product.id,
                        name: product.name,
                        sku: product.sku,
                        quantity: qty,
                    });
                }
            },

            removeFromOrder(index) {
                this.orderList.splice(index, 1);
            },

            loadLowStockItems() {
                const lowItems = this.catalog.filter(p => p.is_low_stock && p.almacen_stock > 0);
                if (lowItems.length === 0) {
                    window.uiAlert('No hay productos con stock bajo en tu tienda que tengan stock disponible en Almacén Central.', 'Sin productos sugeridos', 'amber');
                    return;
                }

                lowItems.forEach(p => {
                    const existing = this.orderList.find(item => item.id === p.id);
                    const suggestedQty = Math.max(10, (p.min_stock * 2) - p.store_stock);
                    if (existing) {
                        existing.quantity = Math.max(existing.quantity, suggestedQty);
                    } else {
                        this.orderList.push({
                            id: p.id,
                            name: p.name,
                            sku: p.sku,
                            quantity: suggestedQty,
                        });
                    }
                });
            },

            addDirectItem() {
                const firstId = this.catalog.length > 0 ? this.catalog[0].id : 1;
                this.directItems.push({ product_id: firstId, quantity: 10 });
            },

            removeDirectItem(index) {
                if (this.directItems.length > 1) {
                    this.directItems.splice(index, 1);
                }
            }
        };
    }
    </script>
</x-app-layout>
