<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-black text-2xl text-slate-800 leading-tight flex items-center gap-2">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    <span>Gestión de Inventario y Catálogo</span>
                </h2>
                <p class="text-sm text-slate-500 mt-0.5">Control de existencias multisede, precios y productos</p>
            </div>

            @if(auth()->user()->isAdmin())
            <button type="button" @click="$dispatch('open-create-modal')" onclick="window.dispatchEvent(new CustomEvent('open-create-modal'))" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2.5 rounded-xl shadow-sm text-sm flex items-center gap-2 transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Nuevo Producto</span>
            </button>
            @endif
        </div>
    </x-slot>

    <div class="py-8" x-data="inventoryManager()" @open-create-modal.window="showCreate = true">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if($scopedLocation && !auth()->user()->isAdmin())
            <div class="flex items-center gap-2 px-1">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 shadow-sm">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Sede asignada: {{ $scopedLocation->name }}
                </span>
                <span class="text-xs text-slate-500">Las existencias y alertas de stock corresponden a tu sede de trabajo.</span>
            </div>
            @endif

            <!-- Barra de Filtros y Búsqueda -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                <form method="GET" action="{{ route('inventory.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                    <div class="{{ auth()->user()->isAdmin() ? 'md:col-span-4' : 'md:col-span-5' }} relative">
                        <svg class="w-5 h-5 absolute left-3 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por SKU o Nombre de Producto..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue-500 text-slate-800 font-medium">
                    </div>

                    <div class="{{ auth()->user()->isAdmin() ? 'md:col-span-2' : 'md:col-span-3' }}">
                        <select name="category_id" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm text-slate-700 font-medium focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="">Todas las categorías</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if(auth()->user()->isAdmin())
                    <div class="md:col-span-2">
                        <select name="location_id" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm text-slate-700 font-medium focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="">Todas las sedes</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ $filterLocationId == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="md:col-span-2">
                        <select name="stock_status" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm text-slate-700 font-medium focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="">Todo el stock</option>
                            <option value="bajo_stock" {{ $stockFilter === 'bajo_stock' ? 'selected' : '' }}>Stock Bajo (&le; 5)</option>
                            <option value="sin_stock" {{ $stockFilter === 'sin_stock' ? 'selected' : '' }}>Agotados (0)</option>
                        </select>
                    </div>

                    <div class="md:col-span-2 flex gap-2">
                        <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-4 rounded-xl text-sm transition-all shadow-sm">
                            Filtrar
                        </button>
                        @if($search || $categoryId || $stockFilter || $filterLocationId)
                            <a href="{{ route('inventory.index') }}" class="p-2.5 text-slate-400 hover:text-red-500 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors" title="Limpiar filtros">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Tabla de Inventario -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-400 text-xs uppercase border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4 font-bold">SKU</th>
                                <th class="py-3 px-4 font-bold">Producto</th>
                                <th class="py-3 px-4 font-bold">Categoría</th>
                                <th class="py-3 px-4 font-bold text-right">Precio Base</th>
                                <th class="py-3 px-4 font-bold text-center">
                                    @if($scopedLocation)
                                        Stock en {{ $scopedLocation->name }}
                                    @else
                                        Stock por Sede
                                    @endif
                                </th>
                                <th class="py-3 px-4 font-bold text-center">
                                    @if(auth()->user()->isVendedor())
                                        Almacén Central
                                    @elseif($scopedLocation)
                                        Stock Global
                                    @else
                                        Stock Total
                                    @endif
                                </th>
                                @if(!auth()->user()->isVendedor())
                                    <th class="py-3 px-4 font-bold text-center">Acciones</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($products as $product)
                                @php
                                    $totalStock = $product->stocks->sum('quantity');
                                    $localStockObj = $scopedLocation ? $product->stocks->firstWhere('location_id', $scopedLocation->id) : null;
                                    $localStock = $localStockObj ? (int) $localStockObj->quantity : 0;
                                    $whStock = $centralWarehouse ? ((int) ($product->stocks->firstWhere('location_id', $centralWarehouse->id)?->quantity ?? 0)) : 0;
                                @endphp
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-xs text-blue-600">
                                    {{ $product->sku }}
                                </td>
                                <td class="py-3 px-4">
                                    <p class="font-bold text-slate-800 text-sm">{{ $product->name }}</p>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2.5 py-1 text-xs rounded-lg font-bold bg-slate-100 text-slate-600">
                                        {{ $product->category->name ?? '--' }}
                                    </span>
                                    @if($product->requiresDeviceModel())
                                        <span class="block text-[10px] text-blue-600 font-bold mt-1">&bull; Aplica Modelo Celular</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right font-black text-slate-900 text-sm">
                                    S/ {{ number_format($product->base_price, 2) }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($scopedLocation)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-black shadow-sm {{ $localStock <= 0 ? 'bg-red-50 text-red-700 border border-red-200' : ($localStock <= 5 ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200') }}">
                                            <span class="w-2 h-2 rounded-full {{ $localStock <= 0 ? 'bg-red-500' : ($localStock <= 5 ? 'bg-amber-500' : 'bg-emerald-500') }}"></span>
                                            {{ $localStock }} und
                                        </span>
                                    @else
                                        <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                            @foreach($product->stocks as $stock)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold border 
                                                    {{ $stock->quantity <= 3 ? 'bg-red-50 text-red-700 border-red-200' : 'bg-slate-50 text-slate-600 border-slate-200' }}"
                                                    title="{{ $stock->location->name }}">
                                                    <small class="text-[9px] font-normal text-slate-400">{{ substr($stock->location->name, 0, 8) }}:</small>
                                                    <strong>{{ $stock->quantity }}</strong>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if(auth()->user()->isVendedor())
                                        @if($scopedLocation && $scopedLocation->id !== $centralWarehouse?->id)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold {{ $whStock > 0 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-50 text-slate-400 border border-slate-200' }}" title="Disponibilidad en Almacén Central para reposición">
                                                <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                                {{ $whStock }} und
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-400 font-medium">Almacén Central</span>
                                        @endif
                                    @else
                                        <span class="px-3 py-1 rounded-full text-xs font-black {{ $totalStock <= 5 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">
                                            {{ $totalStock }} und
                                        </span>
                                    @endif
                                </td>
                                
                                @if(!auth()->user()->isVendedor())
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Ajustar Stock (Admin, Encargado, Almacenero) -->
                                        <button @click="openStockModal({{ $product->id }}, '{{ addslashes($product->name) }}')" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Ajustar Stock">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                        </button>

                                        @if(auth()->user()->isAdmin())
                                        <!-- Editar Producto (Solo Admin) -->
                                        <button @click="openEditModal({{ json_encode($product) }})" class="p-1.5 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors" title="Editar Producto">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>

                                        <!-- Eliminar Producto (Solo Admin) -->
                                        <form action="{{ route('inventory.destroy', $product->id) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas archivar este producto?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar Producto">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ auth()->user()->isVendedor() ? 6 : 7 }}" class="py-12 text-center text-slate-400 font-medium">
                                    No se encontraron productos con los criterios seleccionados.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($products->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $products->links() }}
                </div>
                @endif
            </div>

        </div>

        @if(auth()->user()->isAdmin())
        <!-- MODAL: CREAR PRODUCTO (SOLO ADMIN) -->
        <div x-show="showCreate" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden" @click.away="showCreate = false">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="font-bold text-lg text-slate-800">Registrar Nuevo Producto</h3>
                    <button @click="showCreate = false" class="text-slate-400 hover:text-red-500 text-xl font-bold">&times;</button>
                </div>

                <form action="{{ route('inventory.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nombre del Producto</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm font-semibold focus:ring-2 focus:ring-blue-500" placeholder="Ej. Case Magnético MagSafe">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Código SKU</label>
                            <input type="text" name="sku" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm font-mono uppercase focus:ring-2 focus:ring-blue-500" placeholder="AM-CAR-010">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Precio de Venta (S/)</label>
                            <input type="number" step="0.01" min="0" name="base_price" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm font-bold focus:ring-2 focus:ring-blue-500" placeholder="25.00">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Categoría</label>
                        <select name="category_id" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm font-semibold focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="">Selecciona una categoría</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pt-2 border-t border-slate-100">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Stock Inicial por Sede</label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($locations as $loc)
                            <div>
                                <label class="text-[10px] text-slate-500 block truncate">{{ $loc->name }}</label>
                                <input type="number" name="stock_loc_{{ $loc->id }}" value="0" min="0" class="w-full px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-center">
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end gap-2">
                        <button type="button" @click="showCreate = false" class="px-4 py-2 border border-slate-200 rounded-xl text-slate-600 font-bold text-sm hover:bg-slate-50">Cancelar</button>
                        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-sm">Guardar Producto</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: EDITAR PRODUCTO (SOLO ADMIN) -->
        <div x-show="showEdit" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden" @click.away="showEdit = false">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="font-bold text-lg text-slate-800">Modificar Producto</h3>
                    <button @click="showEdit = false" class="text-slate-400 hover:text-red-500 text-xl font-bold">&times;</button>
                </div>

                <form :action="'/inventario/' + editProduct.id" method="POST" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nombre del Producto</label>
                        <input type="text" name="name" x-model="editProduct.name" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm font-semibold focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Código SKU</label>
                            <input type="text" name="sku" x-model="editProduct.sku" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm font-mono uppercase focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Precio Base (S/)</label>
                            <input type="number" step="0.01" min="0" name="base_price" x-model="editProduct.base_price" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm font-bold focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Categoría</label>
                        <select name="category_id" x-model="editProduct.category_id" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm font-semibold focus:ring-2 focus:ring-blue-500 bg-white">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pt-4 flex justify-end gap-2">
                        <button type="button" @click="showEdit = false" class="px-4 py-2 border border-slate-200 rounded-xl text-slate-600 font-bold text-sm hover:bg-slate-50">Cancelar</button>
                        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-sm">Actualizar Producto</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        @if(!auth()->user()->isVendedor())
        <!-- MODAL: AJUSTAR STOCK (ADMIN, ENCARGADO, ALMACENERO) -->
        <div x-show="showStock" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden" @click.away="showStock = false">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800">Ajuste de Stock</h3>
                        <p class="text-xs text-slate-500 truncate" x-text="stockProductName"></p>
                    </div>
                    <button @click="showStock = false" class="text-slate-400 hover:text-red-500 text-xl font-bold">&times;</button>
                </div>

                <form :action="'/inventario/' + stockProductId + '/ajustar'" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Sede / Ubicación</label>
                        <select name="location_id" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm font-semibold focus:ring-2 focus:ring-blue-500 bg-white">
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Operación</label>
                            <select name="operation" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm font-semibold focus:ring-2 focus:ring-blue-500 bg-white">
                                <option value="add">Sumar (+)</option>
                                <option value="subtract">Restar (-)</option>
                                <option value="set">Fijar Exacto (=)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Cantidad</label>
                            <input type="number" min="0" name="quantity" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm font-black text-center focus:ring-2 focus:ring-blue-500" placeholder="10">
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end gap-2">
                        <button type="button" @click="showStock = false" class="px-4 py-2 border border-slate-200 rounded-xl text-slate-600 font-bold text-sm hover:bg-slate-50">Cancelar</button>
                        <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-sm shadow-sm">Aplicar Ajuste</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

    </div>

    <script>
    function inventoryManager() {
        return {
            showCreate: false,
            showEdit: false,
            showStock: false,
            editProduct: {},
            stockProductId: null,
            stockProductName: '',

            init() {
                window.addEventListener('open-create-modal', () => {
                    this.showCreate = true;
                });
            },

            openEditModal(product) {
                this.editProduct = { ...product };
                this.showEdit = true;
            },

            openStockModal(id, name) {
                this.stockProductId = id;
                this.stockProductName = name;
                this.showStock = true;
            }
        }
    }
    </script>
</x-app-layout>
