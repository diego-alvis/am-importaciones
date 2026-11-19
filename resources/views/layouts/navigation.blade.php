<nav x-data="{ open: false, showStoreModal: false }" class="bg-white border-b border-gray-100 sticky top-0 z-40 shadow-sm">
    <!-- Primary Navigation Menu -->
    <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16 gap-3">
            <div class="flex items-center gap-4 2xl:gap-6 shrink-0">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-blue-700 shadow-sm shadow-blue-600/20 flex items-center justify-center p-1 shrink-0 transition-transform duration-150 group-hover:scale-105">
                            <x-application-logo :badge="false" class="w-full h-full text-white" />
                        </div>
                        <span class="font-black text-slate-800 text-base sm:text-lg hidden sm:inline tracking-tight whitespace-nowrap">A&M <span class="text-blue-600">IMPORTACIONES</span></span>
                    </a>
                </div>

                <!-- Navigation Links según Rol -->
                <div class="hidden xl:flex items-center gap-2.5 2xl:gap-4 h-16 shrink-0">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="whitespace-nowrap text-sm font-semibold">
                        {{ auth()->check() && auth()->user()->isVendedor() ? __('Mi Turno') : __('Dashboard') }}
                    </x-nav-link>
                    
                    @if(auth()->check() && !auth()->user()->isAlmacen())
                        <x-nav-link :href="route('pos.index')" :active="request()->routeIs('pos.*')" class="whitespace-nowrap text-sm font-semibold">
                            {{ __('POS') }}
                        </x-nav-link>

                        <x-nav-link :href="route('cash.index')" :active="request()->routeIs('cash.*')" class="whitespace-nowrap text-sm font-semibold">
                            {{ __('Caja') }}
                        </x-nav-link>
                    @endif

                    @if (auth()->check() && in_array(auth()->user()->role, ['admin', 'encargado']))
                        <x-nav-link :href="route('supervisor.cajas')" :active="request()->routeIs('supervisor.*')" class="whitespace-nowrap text-sm font-semibold">
                            {{ __('Supervisión') }}
                        </x-nav-link>
                    @endif

                    <x-nav-link :href="route('inventory.index')" :active="request()->routeIs('inventory.*')" class="whitespace-nowrap text-sm font-semibold">
                        {{ __('Inventario') }}
                    </x-nav-link>

                    @if (auth()->check() && auth()->user()->role === 'admin')
                        <x-nav-link :href="route('categories.index')" :active="request()->routeIs('categories.*')" class="whitespace-nowrap text-sm font-semibold">
                            {{ __('Categorías') }}
                        </x-nav-link>
                    @endif

                    @if (auth()->check() && in_array(auth()->user()->role, ['admin', 'almacenero', 'encargado', 'vendedor']))
                        <x-nav-link :href="route('transfers.index')" :active="request()->routeIs('transfers.*')" class="whitespace-nowrap text-sm font-semibold">
                            {{ __('Transferencias') }}
                        </x-nav-link>
                    @endif

                    @if (auth()->check() && !auth()->user()->isAlmacen())
                        <x-nav-link :href="route('history.index')" :active="request()->routeIs('history.*')" class="whitespace-nowrap text-sm font-semibold">
                            {{ __('Historial') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings & Store Selector -->
            <div class="hidden sm:flex items-center gap-3 shrink-0">
                
                @if(auth()->check())
                    @php
                        $currentUser = Auth::user();
                        $activeStand = $currentUser->getActiveStand();
                        $availableStands = $currentUser->getAvailableStands();
                    @endphp

                    @if(!$currentUser->isAlmacen() && $availableStands->count() > 0)
                        <!-- Selector Rápido de Tienda Activa -->
                        <button type="button" @click="showStoreModal = true" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-bold border border-blue-200 transition-colors shrink-0 whitespace-nowrap">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                            <span class="truncate max-w-[140px] 2xl:max-w-none">{{ $activeStand?->name ?? 'Elegir Tienda' }}</span>
                            <span class="text-blue-400 font-normal hidden 2xl:inline">({{ $activeStand?->location?->name ?? $currentUser->location?->name }})</span>
                            <svg class="w-3.5 h-3.5 text-blue-500 ml-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                    @elseif($currentUser->isAlmacen())
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold border border-slate-200 shrink-0 whitespace-nowrap">
                            <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            Almacén Central (Distribución)
                        </span>
                    @endif

                    <div class="text-right hidden sm:block shrink-0">
                        <p class="text-[13px] font-bold text-slate-800 leading-tight truncate max-w-[180px]">{{ $currentUser->display_name }}</p>
                        <span class="text-[10px] text-blue-600 font-extrabold uppercase tracking-wide">{{ $currentUser->role }}</span>
                    </div>

                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center justify-center w-9 h-9 border border-slate-200 rounded-full text-slate-700 bg-slate-50 hover:bg-slate-100 focus:outline-none transition ease-in-out duration-150 shrink-0">
                                <span class="font-bold text-xs">{{ substr($currentUser->display_name ?? 'U', 0, 1) }}</span>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="px-4 py-2 border-b border-slate-100 text-xs text-slate-500">
                                Sede: <strong>{{ $currentUser->location->name ?? 'Sin Sede' }}</strong>
                            </div>

                            @if(!$currentUser->isAlmacen() && $availableStands->count() > 0)
                                <button type="button" @click="showStoreModal = true" class="w-full text-left px-4 py-2 text-xs font-bold text-blue-600 hover:bg-blue-50 transition-colors">
                                    {{ $currentUser->hasFixedStand() ? 'Cambiar Operador de Turno' : 'Cambiar Tienda / Operador' }}
                                </button>
                            @endif

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('Cerrar Sesión') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @endif
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center xl:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden xl:hidden bg-white border-t border-slate-100">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ auth()->check() && auth()->user()->isVendedor() ? __('Mi Turno') : __('Dashboard') }}
            </x-responsive-nav-link>

            @if(auth()->check() && !auth()->user()->isAlmacen())
                <x-responsive-nav-link :href="route('pos.index')" :active="request()->routeIs('pos.*')">
                    {{ __('POS') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('cash.index')" :active="request()->routeIs('cash.*')">
                    {{ __('Caja') }}
                </x-responsive-nav-link>
            @endif

            @if (auth()->check() && in_array(auth()->user()->role, ['admin', 'encargado']))
                <x-responsive-nav-link :href="route('supervisor.cajas')" :active="request()->routeIs('supervisor.*')">
                    {{ __('Supervisión') }}
                </x-responsive-nav-link>
            @endif

            <x-responsive-nav-link :href="route('inventory.index')" :active="request()->routeIs('inventory.*')">
                {{ __('Inventario') }}
            </x-responsive-nav-link>

            @if (auth()->check() && auth()->user()->role === 'admin')
                <x-responsive-nav-link :href="route('categories.index')" :active="request()->routeIs('categories.*')">
                    {{ __('Categorías') }}
                </x-responsive-nav-link>
            @endif

            @if (auth()->check() && in_array(auth()->user()->role, ['admin', 'almacenero', 'encargado', 'vendedor']))
                <x-responsive-nav-link :href="route('transfers.index')" :active="request()->routeIs('transfers.*')">
                    {{ __('Transferencias') }}
                </x-responsive-nav-link>
            @endif

            @if (auth()->check() && !auth()->user()->isAlmacen())
                <x-responsive-nav-link :href="route('history.index')" :active="request()->routeIs('history.*')">
                    {{ __('Historial') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-2 border-t border-gray-200 bg-slate-50">
            <div class="px-4">
                <div class="font-bold text-base text-gray-800">{{ Auth::user()->display_name }}</div>
                <div class="text-xs text-slate-500">{{ Auth::user()->email }} ({{ Auth::user()->role }})</div>
                @if(!Auth::user()->isAlmacen() && Auth::user()->getActiveStand())
                    <button type="button" @click="showStoreModal = true; open = false" class="mt-2 text-xs font-bold text-blue-600 block">
                        {{ Auth::user()->hasFixedStand() ? 'Stand fijo: ' . Auth::user()->getActiveStand()->name . ' · Cambiar Operador →' : 'Tienda: ' . Auth::user()->getActiveStand()->name . ' (Cambiar →)' }}
                    </button>
                @endif
            </div>

            <div class="mt-3 space-y-1">
                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Cerrar Sesión') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL GLOBAL: CAMBIAR TIENDA U OPERADOR -->
    @if(auth()->check() && !auth()->user()->isAlmacen())
        @php
            $mUser = Auth::user();
            $mStands = $mUser->getAvailableStands();
            $mActive = $mUser->getActiveStand();
            $mHasFixedStand = $mUser->hasFixedStand();
            $mOperatorValue = $mUser->getActiveCashSession()?->operator_name ?: session('operator_name', $mHasFixedStand ? '' : $mUser->display_name);
        @endphp
        <div x-show="showStoreModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden text-left" @click.away="showStoreModal = false">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <div>
                        <h3 class="font-black text-lg text-slate-800">
                            {{ $mHasFixedStand ? 'Vendedor / Operador de Turno' : 'Seleccionar Tienda / Operador' }}
                        </h3>
                        <p class="text-xs text-slate-500">Sede: {{ $mUser->location->name ?? 'AM Importaciones' }}</p>
                    </div>
                    <button type="button" @click="showStoreModal = false" class="text-slate-400 hover:text-slate-600 font-black text-xl">&times;</button>
                </div>

                <form action="{{ route('tienda.set') }}" method="POST">
                    @csrf
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nombre del Vendedor / Operador</label>
                            <input type="text" name="operator_name" value="{{ $mOperatorValue }}" required placeholder="Ej. María Torres"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-blue-500">
                            <span class="text-[11px] text-slate-400 block mt-1">Nombre que figurará en los tickets, historial y arqueo del turno.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                {{ $mHasFixedStand ? 'Stand Fijo Asignado' : 'Tienda en la que operará hoy' }}
                            </label>
                            @if($mHasFixedStand && $mActive)
                                <input type="hidden" name="stand_id" value="{{ $mActive->id }}">
                                <div class="w-full px-4 py-2.5 rounded-xl border border-blue-200 bg-blue-50/70 text-sm font-bold text-blue-900 flex items-center justify-between">
                                    <span>{{ $mActive->name }} &bull; {{ $mActive->location?->name }}</span>
                                    <span class="text-[10px] uppercase tracking-wider bg-blue-100 text-blue-700 px-2 py-0.5 rounded-md font-extrabold">Fijo</span>
                                </div>
                                <span class="text-[11px] text-slate-400 block mt-1">Esta cuenta pertenece exclusivamente a este stand.</span>
                            @else
                                <select name="stand_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-blue-500">
                                    @foreach($mStands as $st)
                                        <option value="{{ $st->id }}" {{ ($mActive?->id == $st->id) ? 'selected' : '' }}>
                                            {{ $st->name }} &bull; {{ $st->location->name }} (Fondo: S/ {{ number_format($st->base_cash, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                                <span class="text-[11px] text-slate-400 block mt-1">Las tiendas disponibles pertenecen a tu sede autorizada.</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-2">
                        <button type="button" @click="showStoreModal = false" class="w-1/3 py-2.5 rounded-xl border border-slate-200 font-bold text-xs text-slate-600 hover:bg-slate-100">
                            Cancelar
                        </button>
                        <button type="submit" class="w-2/3 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs shadow-sm">
                            {{ $mHasFixedStand ? 'Guardar Operador' : 'Confirmar y Cambiar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</nav>
