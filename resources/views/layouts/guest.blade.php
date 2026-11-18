@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? $title . ' — ' . config('app.name', 'A&M Importaciones') : config('app.name', 'A&M Importaciones') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased bg-slate-50 selection:bg-blue-600 selection:text-white">
        <div class="min-h-screen relative overflow-hidden flex flex-col items-center justify-center px-4 py-10 bg-gradient-to-br from-slate-50 via-blue-50/40 to-slate-100">

            <!-- Fondo ambiental sutil (glows + anillos decorativos minimalistas) -->
            <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
                <div class="absolute -top-32 -right-32 w-[30rem] h-[30rem] rounded-full bg-blue-400/15 blur-3xl"></div>
                <div class="absolute -bottom-32 -left-32 w-[28rem] h-[28rem] rounded-full bg-sky-300/20 blur-3xl"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[54rem] h-[54rem] rounded-full border border-blue-200/30"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[38rem] h-[38rem] rounded-full border border-slate-200/50"></div>
            </div>

            <!-- Cabecera de Marca Centrada -->
            <div class="relative z-10 mb-7">
                <a href="/" class="flex items-center gap-3.5 group focus:outline-none">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-600 to-blue-700 shadow-lg shadow-blue-600/25 flex items-center justify-center p-1.5 transition-transform duration-200 group-hover:scale-105">
                        <x-application-logo :badge="false" class="w-full h-full text-white" />
                    </div>
                    <div class="text-left">
                        <span class="font-black text-slate-900 text-xl tracking-tight block leading-none">
                            A&amp;M <span class="text-blue-600">IMPORTACIONES</span>
                        </span>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1 block">
                            Sistema de Ventas
                        </span>
                    </div>
                </a>
            </div>

            <!-- Tarjeta Principal -->
            <div class="relative z-10 w-full max-w-[440px] bg-white/95 backdrop-blur-sm rounded-3xl shadow-2xl shadow-slate-200/80 border border-slate-200/70 p-7 sm:p-10">
                {{ $slot }}
            </div>

            <!-- Pie de Página -->
            <div class="relative z-10 mt-7 flex flex-col items-center gap-2.5 text-center">
                <p class="text-xs text-slate-400">
                    &copy; {{ date('Y') }} <strong class="font-semibold text-slate-500">A&amp;M Importaciones</strong> &middot; Sistema de Ventas
                </p>
                <span class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white/90 border border-slate-200/80 shadow-sm text-[11px] font-medium text-slate-500">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Plataforma interna &middot; Acceso seguro
                </span>
            </div>

        </div>
    </body>
</html>
