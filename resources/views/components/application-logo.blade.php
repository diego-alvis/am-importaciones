@props(['badge' => true])

<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
    @if ($badge)
        <!-- Marco geométrico minimalista -->
        <rect x="4" y="4" width="56" height="56" rx="16" fill="currentColor" fill-opacity="0.12" stroke="currentColor" stroke-opacity="0.28" stroke-width="2" />
    @endif

    <!-- Monograma A (con barra dinámica de importación) -->
    <path d="M12 45L18.2 19H22.3L28.5 45H23.9L20.25 27.6L16.6 45H12Z" fill="currentColor" />
    <path d="M16.2 35.4H24.3L23.3 39H15.2L16.2 35.4Z" fill="currentColor" />

    <!-- Nodo conector (& / tecnología) -->
    <circle cx="27.8" cy="21.5" r="2.4" fill="currentColor" fill-opacity="0.85" />

    <!-- Monograma M -->
    <path d="M32.2 45V19H36.9L42.1 31.4L47.3 19H52V45H47.5V28.4L43.2 38.4H41L36.7 28.4V45H32.2Z" fill="currentColor" />
</svg>
