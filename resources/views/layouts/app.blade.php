<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow" x-data>
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                @if (session('success'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl font-medium shadow-sm flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span>{{ session('success') }}</span>
                            </div>
                        </div>
                    </div>
                @endif
                @if (session('warning'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl font-medium shadow-sm flex items-center justify-between">
                            <span>{{ session('warning') }}</span>
                        </div>
                    </div>
                @endif
                @if (session('error'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl font-medium shadow-sm flex items-center justify-between">
                            <span>{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>

        <!-- Modal Global de Confirmación y Avisos (reemplaza confirm/alert nativos del navegador) -->
        <div
            x-data="uiDialogManager()"
            @open-ui-dialog.window="open($event.detail)"
            @keydown.escape.window="if (isOpen) close()"
            x-show="isOpen"
            x-transition.opacity.duration.150ms
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4"
            style="display: none;"
            role="dialog"
            aria-modal="true"
        >
            <div
                x-show="isOpen"
                x-transition.scale.95.duration.150ms
                @click.away="close()"
                class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden"
            >
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0" :class="iconWrapperClass">
                            <template x-if="tone === 'emerald'">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"></path></svg>
                            </template>
                            <template x-if="tone === 'rose'">
                                <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </template>
                            <template x-if="tone === 'amber'">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>
                            </template>
                            <template x-if="tone !== 'emerald' && tone !== 'rose' && tone !== 'amber'">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </template>
                        </div>

                        <div class="min-w-0 flex-1">
                            <h3 class="text-base font-black text-slate-900 leading-snug" x-text="title"></h3>
                            <p class="text-sm text-slate-600 mt-1.5 leading-relaxed" x-text="message"></p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button
                        type="button"
                        x-show="mode === 'confirm'"
                        @click="close()"
                        class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 font-bold text-sm transition-colors"
                        x-text="cancelText"
                    ></button>
                    <button
                        type="button"
                        @click="confirm()"
                        class="px-5 py-2 rounded-xl text-white font-bold text-sm shadow-sm transition-colors"
                        :class="confirmButtonClass"
                        x-text="confirmText"
                    ></button>
                </div>
            </div>
        </div>

        <script>
            function uiDialogManager() {
                return {
                    isOpen: false,
                    mode: 'confirm',
                    title: 'Confirmar acción',
                    message: '',
                    confirmText: 'Confirmar',
                    cancelText: 'Cancelar',
                    tone: 'blue',
                    pendingForm: null,

                    open(detail) {
                        this.mode = detail?.mode || 'confirm';
                        this.title = detail?.title || (this.mode === 'alert' ? 'Aviso' : 'Confirmar acción');
                        this.message = detail?.message || '';
                        this.confirmText = detail?.confirmText || (this.mode === 'alert' ? 'Entendido' : 'Confirmar');
                        this.cancelText = detail?.cancelText || 'Cancelar';
                        this.tone = detail?.tone || (this.mode === 'alert' ? 'amber' : 'blue');
                        this.pendingForm = detail?.form || null;
                        this.isOpen = true;
                    },

                    close() {
                        this.isOpen = false;
                        this.pendingForm = null;
                    },

                    confirm() {
                        const formToSubmit = this.pendingForm;
                        this.isOpen = false;
                        this.pendingForm = null;
                        if (this.mode === 'confirm' && formToSubmit) {
                            formToSubmit.submit();
                        }
                    },

                    get iconWrapperClass() {
                        if (this.tone === 'emerald') return 'bg-emerald-100 border border-emerald-200';
                        if (this.tone === 'rose') return 'bg-rose-100 border border-rose-200';
                        if (this.tone === 'amber') return 'bg-amber-100 border border-amber-200';
                        return 'bg-blue-100 border border-blue-200';
                    },

                    get confirmButtonClass() {
                        if (this.tone === 'emerald') return 'bg-emerald-600 hover:bg-emerald-700';
                        if (this.tone === 'rose') return 'bg-rose-600 hover:bg-rose-700';
                        if (this.tone === 'amber') return 'bg-amber-500 hover:bg-amber-600';
                        return 'bg-blue-600 hover:bg-blue-700';
                    }
                };
            }

            window.uiAlert = function (message, title = 'Aviso', tone = 'amber') {
                window.dispatchEvent(new CustomEvent('open-ui-dialog', {
                    detail: {
                        mode: 'alert',
                        title: title,
                        message: message,
                        confirmText: 'Entendido',
                        tone: tone
                    }
                }));
            };

            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) return;

                const message = form.getAttribute('data-confirm');
                if (!message) return;

                event.preventDefault();
                window.dispatchEvent(new CustomEvent('open-ui-dialog', {
                    detail: {
                        mode: 'confirm',
                        title: form.getAttribute('data-confirm-title') || 'Confirmar acción',
                        message: message,
                        confirmText: form.getAttribute('data-confirm-button') || 'Confirmar',
                        cancelText: form.getAttribute('data-confirm-cancel') || 'Cancelar',
                        tone: form.getAttribute('data-confirm-tone') || 'blue',
                        form: form
                    }
                }));
            });
        </script>
    </body>
</html>
