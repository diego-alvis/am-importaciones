<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div class="flex items-center gap-3">
                <div>
                    <h2 class="font-black text-2xl text-slate-800 leading-tight flex items-center gap-2">
                        <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span>Punto de Venta (POS)</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Sede: <strong class="text-slate-700">{{ auth()->user()->location->name ?? 'General' }}</strong> 
                        &bull; Tienda Activa: <strong class="text-blue-700 font-bold">{{ $activeStand->name ?? 'No asignada' }}</strong>
                        &bull; Operador: <strong class="text-slate-800">{{ auth()->user()->display_name }}</strong>
                    </p>
                </div>

                <!-- Botón de Cambiar Tienda -->
                @if($availableStands->count() > 1)
                <button type="button" @click="$dispatch('open-change-stand-modal')" onclick="window.dispatchEvent(new CustomEvent('open-change-stand-modal'))" class="px-3 py-1.5 rounded-lg border border-blue-200 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    <span>Cambiar Tienda</span>
                </button>
                @endif
            </div>

            <!-- Estado de Turno de Caja -->
            <div class="flex items-center gap-2">
                @if($activeSession)
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Turno de Caja Abierto
                    </span>
                    <a href="{{ route('cash.index') }}" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition-all">
                        Ver Turno
                    </a>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Caja Cerrada
                    </span>
                    <a href="{{ route('cash.index') }}" class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm transition-all">
                        Abrir Turno para Vender
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6 px-4 max-w-7xl mx-auto" x-data="posSystem()" x-cloak>
        <div class="flex flex-col lg:flex-row gap-6 items-start">
            
            <!-- PANEL IZQUIERDO: CATÁLOGO DE PRODUCTOS -->
            <div class="flex-1 w-full bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col h-[calc(100vh-14rem)] min-h-[550px] overflow-hidden">
                <!-- Filtros -->
                <div class="p-4 border-b border-slate-100 bg-slate-50 flex gap-3 rounded-t-2xl shrink-0">
                    <div class="relative flex-1">
                        <svg class="w-5 h-5 absolute left-3 top-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <input type="text" x-model="search" placeholder="Buscar por SKU o Nombre de Producto..." class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium">
                    </div>
                    <select x-model="category" class="px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white font-medium text-slate-700">
                        <option value="">Todas las categorías</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Grid de Productos -->
                <div class="flex-1 overflow-y-auto p-4">
                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                        @forelse($products as $product)
                            @php
                                $stockQty = $product->stocks->first()?->quantity ?? 0;
                            @endphp
                        <div x-show="matchesSearch('{{ addslashes($product->name) }}', '{{ $product->sku }}', '{{ $product->category_id }}')"
                             x-on:click="addToCart({ 
                                 id: {{ $product->id }}, 
                                 name: '{{ addslashes($product->name) }}', 
                                 price: {{ (float) $product->base_price }}, 
                                 requires_device_model: {{ $product->requiresDeviceModel() ? 'true' : 'false' }} 
                             })"
                             class="border border-slate-200 rounded-xl p-3 hover:border-blue-500 hover:shadow-md transition-all cursor-pointer bg-white flex flex-col justify-between group">
                            <div>
                                <span class="text-[10px] font-mono text-slate-400 uppercase tracking-wider block mb-1">{{ $product->sku }}</span>
                                <h3 class="font-bold text-xs text-slate-800 leading-tight line-clamp-2 h-8">{{ $product->name }}</h3>
                            </div>
                            <div class="mt-3 flex justify-between items-center pt-2 border-t border-slate-100">
                                <span class="font-black text-blue-600 text-sm">S/ {{ number_format($product->base_price, 2) }}</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black {{ $stockQty <= 3 ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $stockQty }} und
                                </span>
                            </div>
                        </div>
                        @empty
                        <div class="col-span-full text-center text-slate-400 py-16 font-medium">
                            No hay productos disponibles en el catálogo.
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- PANEL DERECHO: TICKET DE VENTA -->
            <div class="w-full lg:w-[420px] bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col h-[calc(100vh-14rem)] min-h-[550px] overflow-hidden shrink-0">
                <!-- Encabezado de Ticket -->
                <div class="p-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center rounded-t-2xl shrink-0">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2 text-base">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        <span>Ticket de Venta</span>
                    </h3>
                    <button x-on:click="emptyCart()" x-show="cart.length > 0" class="text-red-500 hover:text-red-700 text-xs font-bold transition-colors">
                        Vaciar
                    </button>
                </div>

                <!-- Lista de Artículos en Carrito -->
                <div class="flex-1 overflow-y-auto p-3 space-y-2">
                    <div x-show="cart.length === 0" class="text-center text-slate-400 text-sm py-16 font-medium">
                        Haz clic en los productos para agregarlos al ticket de venta.
                    </div>

                    <template x-for="(item, index) in cart" :key="index">
                        <div class="p-3 bg-slate-50 border rounded-xl relative group transition-colors" :class="parseDiscount(item) > 0 ? 'border-emerald-300 bg-emerald-50/30' : 'border-slate-200'">
                            <button x-on:click="removeFromCart(index)" class="absolute -right-2 -top-2 bg-red-100 text-red-600 w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold opacity-0 group-hover:opacity-100 transition-opacity">✕</button>
                            
                            <div class="flex justify-between items-start gap-2">
                                <div class="min-w-0">
                                    <span class="font-bold text-xs text-slate-800 leading-tight block" x-text="item.name"></span>
                                    <div class="flex items-center gap-1.5 mt-0.5 text-[11px]">
                                        <template x-if="parseDiscount(item) > 0">
                                            <span class="line-through text-slate-400" x-text="'S/ ' + item.base_price.toFixed(2)"></span>
                                        </template>
                                        <span class="font-bold" :class="parseDiscount(item) > 0 ? 'text-emerald-600' : 'text-slate-500'" x-text="'S/ ' + getEffectivePrice(item).toFixed(2) + ' c/u'"></span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <template x-if="parseDiscount(item) > 0">
                                        <span class="block text-[10px] line-through text-slate-400" x-text="'S/ ' + (item.base_price * item.quantity).toFixed(2)"></span>
                                    </template>
                                    <span class="font-black text-xs text-slate-900" x-text="'S/ ' + (getEffectivePrice(item) * item.quantity).toFixed(2)"></span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-2 mt-2.5 pt-2 border-t border-slate-200/70">
                                <!-- Campo de Modelo de Celular: SOLO habilitado para Micas y Cases -->
                                <template x-if="item.requires_device_model">
                                    <input type="text" x-model="item.device_model" placeholder="Modelo (Ej. iPhone 13)" class="text-xs px-2.5 py-1.5 rounded-lg border border-blue-200 bg-blue-50/70 focus:outline-none focus:ring-1 focus:ring-blue-500 text-blue-900 placeholder-blue-400 font-medium w-full sm:w-36">
                                </template>

                                <!-- Descuento por unidad en Soles -->
                                <div class="flex items-center gap-1 bg-white border rounded-lg px-2 py-1 shadow-sm" :class="parseDiscount(item) > 0 ? 'border-emerald-300 ring-1 ring-emerald-200' : 'border-slate-200'">
                                    <span class="text-[10px] font-bold text-slate-500 whitespace-nowrap" title="Descuento por cada unidad">Desc/u S/</span>
                                    <input
                                        type="number"
                                        min="0"
                                        :max="item.base_price"
                                        step="0.50"
                                        x-model.number="item.unit_discount"
                                        @blur="sanitizeDiscount(index)"
                                        placeholder="0.00"
                                        class="w-14 p-0 border-0 text-xs font-black text-right text-slate-800 focus:ring-0 bg-transparent"
                                    >
                                </div>

                                <!-- Contador de Cantidad -->
                                <div class="flex items-center border border-slate-200 rounded-lg bg-white overflow-hidden shadow-sm ml-auto">
                                    <button x-on:click="updateQuantity(index, -1)" class="w-7 h-7 text-slate-600 hover:bg-slate-100 font-bold text-xs transition-colors">-</button>
                                    <span class="w-7 text-center text-xs font-black text-slate-800" x-text="item.quantity"></span>
                                    <button x-on:click="updateQuantity(index, 1)" class="w-7 h-7 text-slate-600 hover:bg-slate-100 font-bold text-xs transition-colors">+</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Footer y Cobro -->
                <div class="p-4 bg-slate-50 border-t border-slate-200 rounded-b-2xl shrink-0">
                    <div x-show="totalDiscount > 0" class="space-y-1 mb-2 pb-2 border-b border-slate-200/80 text-xs">
                        <div class="flex justify-between items-center text-slate-500">
                            <span>Subtotal</span>
                            <span class="font-bold" x-text="'S/ ' + subtotal.toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between items-center text-emerald-600 font-bold">
                            <span>Descuento aplicado</span>
                            <span x-text="'- S/ ' + totalDiscount.toFixed(2)"></span>
                        </div>
                    </div>

                    <div class="flex justify-between items-center mb-4">
                        <span class="text-slate-500 font-bold uppercase text-xs tracking-wider">Total a Cobrar</span>
                        <span class="text-3xl font-black text-blue-600" x-text="'S/ ' + total.toFixed(2)"></span>
                    </div>

                    <div class="grid grid-cols-3 gap-2 mb-2">
                        <button @click="processDirectPayment('Efectivo')" :disabled="cart.length === 0" :class="{'opacity-50 cursor-not-allowed': cart.length === 0}" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl shadow-sm transition-all text-xs flex flex-col items-center justify-center gap-1">
                            <span>Efectivo</span>
                        </button>
                        <button @click="processDirectPayment('Yape/Plin')" :disabled="cart.length === 0" :class="{'opacity-50 cursor-not-allowed': cart.length === 0}" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-xl shadow-sm transition-all text-xs flex flex-col items-center justify-center gap-1">
                            <span>Yape / Plin</span>
                        </button>
                        <button @click="processDirectPayment('Tarjeta')" :disabled="cart.length === 0" :class="{'opacity-50 cursor-not-allowed': cart.length === 0}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-sm transition-all text-xs flex flex-col items-center justify-center gap-1">
                            <span>Tarjeta</span>
                        </button>
                    </div>

                    <button x-on:click="showCheckout = true" :disabled="cart.length === 0" :class="{'opacity-50 cursor-not-allowed': cart.length === 0}" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 rounded-xl shadow-sm transition-all text-xs flex justify-center items-center gap-2">
                        Dividir Pago / Calcular Vuelto
                    </button>
                </div>
            </div>

        </div>

        <!-- MODAL DE COBRO MIXTO / VUELTO -->
        <div x-show="showCheckout" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.away="showCheckout = false">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="font-bold text-lg text-slate-800">Dividir Pago / Vuelto</h3>
                    <button @click="showCheckout = false" class="text-slate-400 hover:text-red-500 font-bold text-xl">&times;</button>
                </div>
                
                <div class="p-6 space-y-4">
                    <div class="flex justify-between items-center bg-blue-50 p-4 rounded-xl border border-blue-100">
                        <span class="font-bold text-blue-800 text-sm">Total a Pagar</span>
                        <span class="font-black text-2xl text-blue-600" x-text="'S/ ' + total.toFixed(2)"></span>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Efectivo Recibido (S/)</label>
                            <input type="number" step="0.10" x-model.number="payCash" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 font-black text-lg text-slate-800" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Yape / Plin / Tarjeta (S/)</label>
                            <input type="number" step="0.10" x-model.number="payTransfer" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 font-black text-lg text-slate-800" placeholder="0.00">
                        </div>
                        <div x-show="payTransfer > 0">
                            <label class="block text-xs font-bold text-slate-700 mb-1">N° Operación / Voucher (Opcional)</label>
                            <input type="text" x-model="referenceCode" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm font-mono text-slate-800 focus:ring-2 focus:ring-blue-500" placeholder="Ej. OP-489123">
                        </div>
                    </div>

                    <div class="flex justify-between items-center p-4 rounded-xl border" :class="change >= 0 ? 'bg-emerald-50 border-emerald-100' : 'bg-red-50 border-red-100'">
                        <span class="font-bold text-sm" :class="change >= 0 ? 'text-emerald-800' : 'text-red-800'">Vuelto / Faltante</span>
                        <span class="font-black text-xl" :class="change >= 0 ? 'text-emerald-600' : 'text-red-600'" x-text="'S/ ' + Math.abs(change).toFixed(2)"></span>
                    </div>
                </div>

                <div class="p-5 border-t border-slate-100 bg-slate-50">
                    <button @click="confirmSale()" :disabled="change < 0" :class="{'opacity-50 cursor-not-allowed': change < 0}" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-md transition-all text-base">
                        Confirmar Venta
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL DE CONFIRMACIÓN DE VENTA (SIN IMPRESIÓN AUTOMÁTICA) -->
        <div x-show="showConfirmation" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden text-center p-6 space-y-4">
                <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>

                <div>
                    <h3 class="text-xl font-black text-slate-800">¡Venta Exitosa!</h3>
                    <p class="text-xs text-slate-500 mt-1">Ticket #<span x-text="String(lastSaleId).padStart(6, '0')"></span> registrado en el sistema.</p>
                </div>

                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <span class="text-xs text-slate-500 block uppercase font-bold tracking-wider">Monto Total Cobrado</span>
                    <span class="text-3xl font-black text-slate-900" x-text="'S/ ' + parseFloat(lastSaleTotal).toFixed(2)"></span>
                </div>

                <div class="space-y-2 pt-2">
                    <button @click="closeConfirmation()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-sm text-sm transition-all">
                        Nueva Venta (Continuar)
                    </button>
                    <a :href="'/pos/ticket/' + lastSaleId" target="_blank" class="w-full block py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-bold text-xs transition-colors border border-slate-200">
                        Ver / Imprimir Ticket Opcional
                    </a>
                </div>
            </div>
        </div>

        <!-- MODAL: CAMBIAR TIENDA ACTIVA -->
        <div x-data="{ showModal: false }" @open-change-stand-modal.window="showModal = true">
            <div x-show="showModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" style="display: none;">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden" @click.away="showModal = false">
                    <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                        <h3 class="font-bold text-lg text-slate-800">Seleccionar Tienda de Operación</h3>
                        <button @click="showModal = false" class="text-slate-400 hover:text-red-500 text-xl font-bold">&times;</button>
                    </div>

                    <form action="{{ route('tienda.set') }}" method="POST" class="p-6 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nombre del Vendedor / Operador</label>
                            <input type="text" name="operator_name" value="{{ auth()->user()->display_name }}" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-bold">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tienda en la que Operarás</label>
                            <select name="stand_id" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold bg-white">
                                @foreach($availableStands as $st)
                                    <option value="{{ $st->id }}" {{ ($activeStand?->id == $st->id) ? 'selected' : '' }}>
                                        {{ $st->name }} ({{ $st->location->name ?? 'Sede' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="pt-4 flex justify-end gap-2 border-t border-slate-100">
                            <button type="button" @click="showModal = false" class="px-4 py-2 border border-slate-200 rounded-xl text-slate-600 font-bold text-sm">Cancelar</button>
                            <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-sm">Confirmar Tienda</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <script>
    function posSystem() {
        return {
            search: '',
            category: '',
            cart: [],
            showCheckout: false,
            showConfirmation: false,
            lastSaleId: null,
            lastSaleTotal: 0,
            payCash: 0,
            payTransfer: 0,
            referenceCode: '',

            parseDiscount(item) {
                const raw = parseFloat(item.unit_discount);
                if (isNaN(raw) || raw < 0) return 0;
                return Math.min(Math.round(raw * 100) / 100, item.base_price);
            },

            getEffectivePrice(item) {
                const price = item.base_price - this.parseDiscount(item);
                return Math.max(0, Math.round(price * 100) / 100);
            },

            sanitizeDiscount(index) {
                if (!this.cart[index]) return;
                this.cart[index].unit_discount = this.parseDiscount(this.cart[index]);
            },

            get subtotal() {
                const val = this.cart.reduce((sum, item) => sum + (item.base_price * item.quantity), 0);
                return Math.round(val * 100) / 100;
            },

            get totalDiscount() {
                const val = this.cart.reduce((sum, item) => sum + (this.parseDiscount(item) * item.quantity), 0);
                return Math.round(val * 100) / 100;
            },

            get total() {
                const val = this.cart.reduce((sum, item) => sum + (this.getEffectivePrice(item) * item.quantity), 0);
                return Math.round(val * 100) / 100;
            },

            get change() {
                return (Number(this.payCash) + Number(this.payTransfer)) - this.total;
            },

            matchesSearch(name, sku, categoryId) {
                const matchesText = name.toLowerCase().includes(this.search.toLowerCase()) || sku.toLowerCase().includes(this.search.toLowerCase());
                const matchesCategory = this.category === '' || categoryId.toString() === this.category;
                return matchesText && matchesCategory;
            },

            addToCart(product) {
                let existingItem = this.cart.find(i => i.id === product.id && (!i.requires_device_model || i.device_model === ''));
                if (existingItem) {
                    existingItem.quantity++;
                } else {
                    this.cart.push({
                        id: product.id,
                        name: product.name,
                        base_price: parseFloat(product.price),
                        unit_discount: 0,
                        requires_device_model: product.requires_device_model,
                        quantity: 1,
                        device_model: ''
                    });
                }
            },

            removeFromCart(index) {
                this.cart.splice(index, 1);
            },

            updateQuantity(index, delta) {
                if (this.cart[index].quantity + delta > 0) {
                    this.cart[index].quantity += delta;
                } else {
                    this.removeFromCart(index);
                }
            },

            emptyCart() {
                this.cart = [];
            },

            async submitToServer(method, cash, transfer, changeValue) {
                for (const item of this.cart) {
                    if (item.requires_device_model && (!item.device_model || item.device_model.trim() === '')) {
                        alert(`Por favor ingresa el modelo de celular para '${item.name}' antes de continuar.`);
                        return;
                    }

                    const rawDiscount = parseFloat(item.unit_discount);
                    if (!isNaN(rawDiscount) && (rawDiscount < 0 || rawDiscount > item.base_price)) {
                        alert(`El descuento por unidad de '${item.name}' debe estar entre S/ 0.00 y S/ ${item.base_price.toFixed(2)}.`);
                        return;
                    }
                }

                const payloadCart = this.cart.map(item => ({
                    id: item.id,
                    quantity: item.quantity,
                    price: this.getEffectivePrice(item),
                    unit_discount: this.parseDiscount(item),
                    device_model: item.device_model
                }));

                try {
                    const response = await fetch('{{ route('pos.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            cart: payloadCart,
                            total: this.total,
                            payment_method: method,
                            cash_tendered: cash,
                            transfer_tendered: transfer,
                            change: changeValue,
                            reference_code: this.referenceCode
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.showCheckout = false;
                        this.lastSaleId = data.sale_id;
                        this.lastSaleTotal = data.total;
                        this.cart = [];
                        this.payCash = 0;
                        this.payTransfer = 0;
                        this.referenceCode = '';
                        this.showConfirmation = true; // Confirmación sin imprimir automáticamente
                    } else {
                        alert('Error: ' + data.error);
                    }
                } catch (error) {
                    alert('Fallo de conexión al registrar venta.');
                }
            },

            processDirectPayment(method) {
                if (this.cart.length === 0) return;
                this.submitToServer(method, this.total, 0, 0);
            },

            confirmSale() {
                if (this.change < 0) return;
                this.submitToServer('Mixto', this.payCash, this.payTransfer, this.change);
            },

            closeConfirmation() {
                this.showConfirmation = false;
            }
        }
    }
    </script>
</x-app-layout>
