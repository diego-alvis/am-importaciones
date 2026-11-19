<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-black text-2xl text-slate-800 leading-tight flex items-center gap-2">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    <span>Gestión de Inventario y Catálogo</span>
                </h2>
                <p class="text-sm text-slate-500 mt-0.5">Control de existencias multisede, reposición y catálogo maestro</p>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                @if(auth()->user()->isAlmacen())
                    <a href="{{ route('transfers.index') }}" class="bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold px-4 py-2.5 rounded-xl shadow-sm text-sm flex items-center gap-2 transition-all">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                        <span>Traslados</span>
                    </a>
                @endif

                @if(auth()->user()->isAdmin() || auth()->user()->isAlmacen())
                    <button type="button" @click="$dispatch('open-create-modal')" onclick="window.dispatchEvent(new CustomEvent('open-create-modal'))" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2.5 rounded-xl shadow-sm text-sm flex items-center gap-2 transition-all cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>Nuevo Producto</span>
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="inventoryManager()" @open-create-modal.window="showCreate = true">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if($scopedLocation && !auth()->user()->isAdmin())
                <div class="flex flex-wrap items-center gap-2 px-1">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 shadow-sm">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Sede asignada: {{ $scopedLocation->name }}
                    </span>
                    <span class="text-xs text-slate-500">
                        {{ auth()->user()->isAlmacen() ? 'Gestionas existencias de Almacén Central y puedes consultar la distribución en tiendas.' : 'Las existencias y alertas de stock corresponden a tu sede de trabajo.' }}
                    </span>
                </div>
            @endif

            <!-- Resumen Rápido (KPIs clicables) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="{{ route('inventory.index', array_filter(['search' => $search, 'category_id' => $categoryId, 'location_id' => $filterLocationId, 'sort' => $sort !== 'name_asc' ? $sort : null])) }}"
                   class="bg-white rounded-2xl border {{ empty($stockFilter) || $stockFilter === 'todos' ? 'border-blue-300 ring-1 ring-blue-100' : 'border-slate-200' }} p-4 shadow-sm hover:border-blue-300 transition-all">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Catálogo Total</span>
                    <div class="flex items-baseline justify-between mt-1">
                        <strong class="text-2xl font-black text-slate-900">{{ $summary['total_products'] }}</strong>
                        <span class="text-xs font-bold text-blue-600">SKUs</span>
                    </div>
                </a>

                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">
                        {{ $scopedLocation ? 'Stock en '.$scopedLocation->name : 'Unidades Totales' }}
                    </span>
                    <div class="flex items-baseline justify-between gap-2 mt-1">
                        <strong class="text-2xl font-black text-emerald-600">{{ number_format($summary['total_units']) }}</strong>
                        @if(!auth()->user()->isVendedor())
                            <span class="text-[11px] font-bold text-slate-500">Val: S/ {{ number_format($summary['inventory_value'], 0) }}</span>
                        @else
                            <span class="text-xs font-bold text-slate-400">unidades</span>
                        @endif
                    </div>
                </div>

                <a href="{{ route('inventory.index', array_filter(['search' => $search, 'category_id' => $categoryId, 'location_id' => $filterLocationId, 'stock_status' => 'bajo_stock', 'sort' => 'stock_asc'])) }}"
                   class="bg-white rounded-2xl border {{ $stockFilter === 'bajo_stock' ? 'border-amber-400 ring-1 ring-amber-200 bg-amber-50/30' : 'border-slate-200' }} p-4 shadow-sm hover:border-amber-300 transition-all">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-amber-600 block">Stock Bajo (&le; mín)</span>
                    <div class="flex items-baseline justify-between mt-1">
                        <strong class="text-2xl font-black text-amber-700">{{ $summary['low_stock'] }}</strong>
                        <span class="text-xs font-bold text-amber-600">por reponer</span>
                    </div>
                </a>

                <a href="{{ route('inventory.index', array_filter(['search' => $search, 'category_id' => $categoryId, 'location_id' => $filterLocationId, 'stock_status' => 'sin_stock'])) }}"
                   class="bg-white rounded-2xl border {{ $stockFilter === 'sin_stock' ? 'border-rose-400 ring-1 ring-rose-200 bg-rose-50/30' : 'border-slate-200' }} p-4 shadow-sm hover:border-rose-300 transition-all">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-rose-600 block">Agotados (0 und)</span>
                    <div class="flex items-baseline justify-between mt-1">
                        <strong class="text-2xl font-black text-rose-600">{{ $summary['out_of_stock'] }}</strong>
                        <span class="text-xs font-bold text-rose-500">sin stock</span>
                    </div>
                </a>
            </div>

            <!-- Barra de Filtros, Chips Rápidos y Ordenamiento -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 space-y-4">
                <form method="GET" action="{{ route('inventory.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                    <div class="{{ auth()->user()->isAdmin() ? 'md:col-span-3' : 'md:col-span-4' }} relative">
                        <svg class="w-5 h-5 absolute left-3 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por SKU o Producto..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue-500 text-slate-800 font-medium">
                    </div>

                    <div class="md:col-span-2">
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
                            <option value="ok" {{ $stockFilter === 'ok' ? 'selected' : '' }}>Stock Óptimo</option>
                            <option value="bajo_stock" {{ $stockFilter === 'bajo_stock' ? 'selected' : '' }}>Stock Bajo (&le; mín)</option>
                            <option value="sin_stock" {{ $stockFilter === 'sin_stock' ? 'selected' : '' }}>Agotados (0)</option>
                        </select>
                    </div>

                    <div class="{{ auth()->user()->isAdmin() ? 'md:col-span-2' : 'md:col-span-2' }}">
                        <select name="sort" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm text-slate-700 font-medium focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="name_asc" {{ $sort === 'name_asc' ? 'selected' : '' }}>Orden: Nombre A-Z</option>
                            <option value="stock_asc" {{ $sort === 'stock_asc' ? 'selected' : '' }}>Menor stock primero</option>
                            <option value="stock_desc" {{ $sort === 'stock_desc' ? 'selected' : '' }}>Mayor stock primero</option>
                            <option value="price_desc" {{ $sort === 'price_desc' ? 'selected' : '' }}>Mayor precio</option>
                            <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>Menor precio</option>
                        </select>
                    </div>

                    <div class="{{ auth()->user()->isAdmin() ? 'md:col-span-1' : 'md:col-span-2' }} flex gap-2">
                        <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-3 rounded-xl text-sm transition-all shadow-sm">
                            Filtrar
                        </button>
                        @if($search || $categoryId || $stockFilter || $filterLocationId || ($sort && $sort !== 'name_asc'))
                            <a href="{{ route('inventory.index') }}" class="p-2.5 text-slate-400 hover:text-red-500 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors shrink-0" title="Limpiar filtros">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </a>
                        @endif
                    </div>
                </form>

                <!-- Chips de filtro rápido por estado -->
                @php
                    $baseChipParams = array_filter([
                        'search' => $search,
                        'category_id' => $categoryId,
                        'location_id' => $filterLocationId,
                        'sort' => $sort !== 'name_asc' ? $sort : null,
                    ]);
                @endphp
                <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-slate-100 text-xs">
                    <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] mr-1">Vista rápida:</span>
                    <a href="{{ route('inventory.index', $baseChipParams) }}"
                       class="px-3 py-1 rounded-full font-bold transition-colors {{ empty($stockFilter) || $stockFilter === 'todos' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        Todos
                    </a>
                    <a href="{{ route('inventory.index', array_merge($baseChipParams, ['stock_status' => 'ok'])) }}"
                       class="px-3 py-1 rounded-full font-bold transition-colors {{ $stockFilter === 'ok' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                        Stock Óptimo
                    </a>
                    <a href="{{ route('inventory.index', array_merge($baseChipParams, ['stock_status' => 'bajo_stock'])) }}"
                       class="px-3 py-1 rounded-full font-bold transition-colors {{ $stockFilter === 'bajo_stock' ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-800 hover:bg-amber-100' }}">
                        Stock Bajo
                    </a>
                    <a href="{{ route('inventory.index', array_merge($baseChipParams, ['stock_status' => 'sin_stock'])) }}"
                       class="px-3 py-1 rounded-full font-bold transition-colors {{ $stockFilter === 'sin_stock' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                        Agotados
                    </a>
                </div>
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
                                    @elseif(auth()->user()->isAlmacen())
                                        Distribución en Sedes
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
                                    $totalStock = (int) $product->stocks->sum('quantity');
                                    $localStockObj = $scopedLocation ? $product->stocks->firstWhere('location_id', $scopedLocation->id) : null;
                                    $localStock = $localStockObj ? (int) $localStockObj->quantity : 0;
                                    $minStockLocal = $localStockObj ? (int) $localStockObj->min_stock : 5;
                                    $whStock = $centralWarehouse ? ((int) ($product->stocks->firstWhere('location_id', $centralWarehouse->id)?->quantity ?? 0)) : 0;
                                    $stockMap = $product->stocks->mapWithKeys(fn ($s) => [
                                        $s->location_id => ['qty' => (int) $s->quantity, 'min' => (int) $s->min_stock],
                                    ]);
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
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-black shadow-sm {{ $localStock <= 0 ? 'bg-red-50 text-red-700 border border-red-200' : ($localStock <= $minStockLocal ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200') }}">
                                                <span class="w-2 h-2 rounded-full {{ $localStock <= 0 ? 'bg-red-500' : ($localStock <= $minStockLocal ? 'bg-amber-500' : 'bg-emerald-500') }}"></span>
                                                {{ $localStock }} und
                                            </span>
                                            <span class="block text-[10px] text-slate-400 font-semibold mt-0.5">Mín: {{ $minStockLocal }}</span>
                                        @else
                                            <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                                @foreach($product->stocks as $stock)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold border
                                                        {{ $stock->quantity <= 0 ? 'bg-red-50 text-red-700 border-red-200' : ($stock->quantity <= $stock->min_stock ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-slate-50 text-slate-600 border-slate-200') }}"
                                                        title="{{ $stock->location->name }} (Mín: {{ $stock->min_stock }})">
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
                                        @elseif(auth()->user()->isAlmacen())
                                            @php
                                                $storeStocks = $product->stocks->filter(fn ($s) => $s->location_id !== $scopedLocation?->id);
                                            @endphp
                                            <div class="flex flex-col items-center gap-1">
                                                <div class="flex items-center justify-center gap-1 flex-wrap max-w-xs">
                                                    @foreach($storeStocks as $st)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border {{ $st->quantity <= 0 ? 'bg-rose-50 text-rose-700 border-rose-200' : ($st->quantity <= $st->min_stock ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-slate-50 text-slate-600 border-slate-200') }}" title="{{ $st->location->name }}">
                                                            <span>{{ str_replace('Sede ', '', $st->location->name) }}:</span>
                                                            <strong>{{ $st->quantity }}</strong>
                                                        </span>
                                                    @endforeach
                                                </div>
                                                <span class="text-[10px] font-bold text-slate-400">Global: {{ $totalStock }} und</span>
                                            </div>
                                        @else
                                            <span class="px-3 py-1 rounded-full text-xs font-black {{ $totalStock <= 5 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">
                                                {{ $totalStock }} und
                                            </span>
                                        @endif
                                    </td>

                                    @if(!auth()->user()->isVendedor())
                                        <td class="py-3 px-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <!-- Ajustar Stock (Admin, Encargado, Almacenero) -->
                                                <button
                                                    type="button"
                                                    @click="openStockModal({{ $product->id }}, {{ \Illuminate\Support\Js::from($product->name) }}, {{ $localStock }}, {{ $minStockLocal }}, {{ $scopedLocation?->id ?? ($locations->first()?->id ?? 'null') }}, {{ \Illuminate\Support\Js::from($stockMap) }})"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition-colors"
                                                    title="Ajustar Stock"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                                    <span>Stock</span>
                                                </button>

                                                @if(auth()->user()->isAdmin() || auth()->user()->isAlmacen())
                                                    <!-- Editar Producto (Admin y Almacenero) -->
                                                    <button
                                                        type="button"
                                                        @click="openEditModal({{ \Illuminate\Support\Js::from($product) }})"
                                                        class="p-1.5 text-slate-600 hover:bg-slate-100 border border-transparent hover:border-slate-200 rounded-lg transition-colors"
                                                        title="Editar Producto"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                    </button>
                                                @endif

                                                @if(auth()->user()->isAdmin())
                                                    <!-- Eliminar Producto (Solo Admin) -->
                                                    <form
                                                        action="{{ route('inventory.destroy', $product->id) }}"
                                                        method="POST"
                                                        data-confirm="¿Seguro que deseas archivar el producto '{{ $product->name }}' del catálogo?"
                                                        data-confirm-title="Archivar Producto"
                                                        data-confirm-button="Sí, Archivar"
                                                        data-confirm-tone="rose"
                                                    >
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Archivar Producto">
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

        @if(auth()->user()->isAdmin() || auth()->user()->isAlmacen())
            <!-- MODAL: CREAR PRODUCTO (ADMIN Y ALMACENERO) -->
            <div x-show="showCreate" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" style="display: none;">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden" @click.away="showCreate = false">
                    <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                        <h3 class="font-bold text-lg text-slate-800">Registrar Nuevo Producto</h3>
                        <button type="button" @click="showCreate = false" class="text-slate-400 hover:text-red-500 text-xl font-bold">&times;</button>
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

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Categoría</label>
                                <select name="category_id" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm font-semibold focus:ring-2 focus:ring-blue-500 bg-white">
                                    <option value="">Selecciona una categoría</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Stock Mínimo (Alerta)</label>
                                <input type="number" min="0" name="min_stock" value="5" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm font-bold text-center focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-100">
                            @if(auth()->user()->isAdmin())
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Stock Inicial por Sede</label>
                                <div class="grid grid-cols-3 gap-2">
                                    @foreach($locations as $loc)
                                        <div>
                                            <label class="text-[10px] text-slate-500 block truncate">{{ $loc->name }}</label>
                                            <input type="number" name="stock_loc_{{ $loc->id }}" value="0" min="0" class="w-full px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-center">
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                @php
                                    $whLocation = $centralWarehouse ?? $scopedLocation;
                                @endphp
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Stock Inicial en {{ $whLocation?->name ?? 'Almacén Central' }}</label>
                                <div class="flex items-center gap-3">
                                    @if($whLocation)
                                        <input type="number" name="stock_loc_{{ $whLocation->id }}" value="0" min="0" class="w-32 px-3 py-2 rounded-lg border border-slate-200 text-sm font-black text-center focus:ring-2 focus:ring-blue-500">
                                    @endif
                                    <p class="text-[11px] text-slate-400">Las tiendas inician en 0 y se abastecen mediante transferencias.</p>
                                </div>
                            @endif
                        </div>

                        <div class="pt-4 flex justify-end gap-2">
                            <button type="button" @click="showCreate = false" class="px-4 py-2 border border-slate-200 rounded-xl text-slate-600 font-bold text-sm hover:bg-slate-50">Cancelar</button>
                            <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-sm">Guardar Producto</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: EDITAR PRODUCTO (ADMIN Y ALMACENERO) -->
            <div x-show="showEdit" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" style="display: none;">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden" @click.away="showEdit = false">
                    <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                        <h3 class="font-bold text-lg text-slate-800">Modificar Producto</h3>
                        <button type="button" @click="showEdit = false" class="text-slate-400 hover:text-red-500 text-xl font-bold">&times;</button>
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
                        <div class="min-w-0 pr-2">
                            <h3 class="font-bold text-lg text-slate-800">Ajuste de Stock</h3>
                            <p class="text-xs text-slate-500 truncate" x-text="stockProductName"></p>
                        </div>
                        <button type="button" @click="showStock = false" class="text-slate-400 hover:text-red-500 text-xl font-bold">&times;</button>
                    </div>

                    <form :action="'/inventario/' + stockProductId + '/ajustar'" method="POST" class="p-6 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Sede / Ubicación</label>
                            <select name="location_id" x-model="stockLocationId" @change="syncLocationMetrics()" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm font-semibold focus:ring-2 focus:ring-blue-500 bg-white">
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Resumen visual actual vs nuevo -->
                        <div class="grid grid-cols-2 gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200 text-center">
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-400 block">Stock Actual</span>
                                <strong class="text-xl font-black text-slate-800" x-text="currentStockQty + ' und'"></strong>
                            </div>
                            <div class="border-l border-slate-200">
                                <span class="text-[10px] font-bold uppercase text-blue-600 block">Stock Resultante</span>
                                <strong class="text-xl font-black" :class="previewNewStock <= 0 ? 'text-rose-600' : 'text-emerald-600'" x-text="previewNewStock + ' und'"></strong>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Tipo de Operación</label>
                            <input type="hidden" name="operation" :value="stockOperation">
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" @click="stockOperation = 'add'" :class="stockOperation === 'add' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'" class="py-2 px-2 rounded-xl border text-xs font-bold transition-all">
                                    + Sumar
                                </button>
                                <button type="button" @click="stockOperation = 'subtract'" :class="stockOperation === 'subtract' ? 'bg-rose-600 text-white border-rose-600 shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'" class="py-2 px-2 rounded-xl border text-xs font-bold transition-all">
                                    - Restar
                                </button>
                                <button type="button" @click="stockOperation = 'set'" :class="stockOperation === 'set' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'" class="py-2 px-2 rounded-xl border text-xs font-bold transition-all">
                                    = Fijar
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Cantidad</label>
                                <input type="number" min="0" name="quantity" x-model.number="stockAdjustQty" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm font-black text-center focus:ring-2 focus:ring-blue-500" placeholder="0">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Stock Mínimo</label>
                                <input type="number" min="0" name="min_stock" x-model.number="stockMinQty" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm font-bold text-center focus:ring-2 focus:ring-blue-500" placeholder="5">
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
            stockLocationId: null,
            stockMap: {},
            currentStockQty: 0,
            stockMinQty: 5,
            stockOperation: 'add',
            stockAdjustQty: 0,

            init() {
                window.addEventListener('open-create-modal', () => {
                    this.showCreate = true;
                });
            },

            openEditModal(product) {
                this.editProduct = { ...product };
                this.showEdit = true;
            },

            openStockModal(id, name, currentQty, minStock, defaultLocationId, stockMap) {
                this.stockProductId = id;
                this.stockProductName = name;
                this.stockMap = stockMap || {};
                this.stockLocationId = defaultLocationId ? String(defaultLocationId) : null;
                this.stockOperation = 'add';
                this.stockAdjustQty = 0;

                if (this.stockLocationId && this.stockMap[this.stockLocationId]) {
                    this.currentStockQty = Number(this.stockMap[this.stockLocationId].qty);
                    this.stockMinQty = Number(this.stockMap[this.stockLocationId].min);
                } else {
                    this.currentStockQty = Number(currentQty || 0);
                    this.stockMinQty = Number(minStock ?? 5);
                }

                this.showStock = true;
            },

            syncLocationMetrics() {
                const entry = this.stockLocationId ? this.stockMap[this.stockLocationId] : null;
                this.currentStockQty = entry ? Number(entry.qty) : 0;
                this.stockMinQty = entry ? Number(entry.min) : 5;
            },

            get previewNewStock() {
                const qty = Math.max(0, Number(this.stockAdjustQty || 0));
                if (this.stockOperation === 'add') {
                    return this.currentStockQty + qty;
                }
                if (this.stockOperation === 'subtract') {
                    return Math.max(0, this.currentStockQty - qty);
                }
                return qty;
            }
        };
    }
    </script>
</x-app-layout>
