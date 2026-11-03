<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS - A y M Importaciones</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden font-sans text-slate-800">
    <aside class="w-20 lg:w-64 bg-slate-900 text-slate-300 flex flex-col transition-all duration-300">
        <div class="h-16 flex items-center justify-center border-b border-slate-800 bg-slate-950">
            <span class="text-white font-bold text-xl hidden lg:block tracking-wider">A y M <span class="text-blue-500">POS</span></span>
            <span class="text-white font-bold text-xl lg:hidden text-blue-500">POS</span>
        </div>
        <nav class="flex-1 px-3 py-6 space-y-2">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-md' : 'hover:bg-slate-800 hover:text-white transition-colors' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                <span class="hidden lg:block font-medium">Dashboard</span>
            </a>
            <a href="{{ route('pos.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('pos.*') ? 'bg-blue-600 text-white shadow-md' : 'hover:bg-slate-800 hover:text-white transition-colors' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span class="hidden lg:block font-medium">Punto de Venta</span>
            </a>
            <a href="{{ route('cash.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('cash.*') ? 'bg-blue-600 text-white shadow-md' : 'hover:bg-slate-800 hover:text-white transition-colors' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <span class="hidden lg:block font-medium">Caja y Finanzas</span>
            </a>
            @if(auth()->check() && in_array(auth()->user()->role, ['admin', 'encargado']))
            <a href="{{ route('supervisor.cajas') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('supervisor.*') ? 'bg-blue-600 text-white shadow-md' : 'hover:bg-slate-800 hover:text-white transition-colors' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                <span class="hidden lg:block font-medium">Supervisión</span>
            </a>
            @endif
            <a href="{{ route('inventory.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('inventory.*') ? 'bg-blue-600 text-white shadow-md' : 'hover:bg-slate-800 hover:text-white transition-colors' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                <span class="hidden lg:block font-medium">Inventario</span>
            </a>
            <a href="{{ route('history.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('history.*') ? 'bg-blue-600 text-white shadow-md' : 'hover:bg-slate-800 hover:text-white transition-colors' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="hidden lg:block font-medium">Historial</span>
            </a>
        </nav>
        <div class="p-3 border-t border-slate-800">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-slate-400 hover:text-red-400 hover:bg-slate-800 transition-colors text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span class="hidden lg:block">Cerrar Sesión</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shrink-0">
            <div class="flex items-center gap-4">
                <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-md text-xs font-bold border border-emerald-200 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Sistema Activo
                </span>
                <span class="text-slate-500 text-sm hidden md:block">Sede: <strong class="text-slate-700">{{ Auth::user()->location->name ?? 'Sin Sede Asignada' }}</strong></span>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-semibold text-slate-700 leading-tight">{{ Auth::user()->name ?? 'Usuario' }}</p>
                    <p class="text-xs text-slate-400 uppercase font-bold">{{ Auth::user()->role ?? 'Cajero' }}</p>
                </div>
                <div class="h-9 w-9 bg-slate-900 text-white rounded-full flex items-center justify-center font-bold shadow-inner">
                    {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                </div>
            </div>
        </header>

        <!-- Mensajes Flash -->
        @if (session('success'))
            <div class="mx-4 mt-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-2.5 rounded-xl text-sm font-medium shadow-sm flex items-center gap-2 shrink-0">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if (session('warning'))
            <div class="mx-4 mt-3 bg-amber-50 border border-amber-200 text-amber-800 px-4 py-2.5 rounded-xl text-sm font-medium shadow-sm flex items-center gap-2 shrink-0">
                <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>{{ session('warning') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="mx-4 mt-3 bg-red-50 border border-red-200 text-red-800 px-4 py-2.5 rounded-xl text-sm font-medium shadow-sm flex items-center gap-2 shrink-0">
                <svg class="w-5 h-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif
        
        @yield('content')
    </div>
</body>
</html>