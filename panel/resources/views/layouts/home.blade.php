<!DOCTYPE html>
<html class="h-full" lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'XPanel')</title>
    <link rel="shortcut icon" href="{{ asset('assets/media/app/favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/app/favicon-32x32.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
</head>

<body class="antialiased min-h-full text-base text-foreground bg-background bg-muted">

<script>
    (function () {
        const t = localStorage.getItem('kt-theme') || 'light';
        const mode = t === 'system'
            ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : t;
        document.documentElement.classList.add(mode);
    })();
</script>

{{-- Public nav --}}
<nav class="border-b border-input bg-background">
    <div class="max-w-6xl mx-auto px-4 h-[58px] flex items-center justify-between gap-4">
        <a href="{{ url('/') }}" class="flex items-center gap-2">
            <img class="dark:hidden h-[22px]" src="{{ asset('assets/media/app/mini-logo-primary.svg') }}" alt="XPanel">
            <img class="hidden dark:block h-[22px]" src="{{ asset('assets/media/app/mini-logo-primary-dark.svg') }}" alt="XPanel">
            <span class="text-sm font-bold text-mono tracking-tight">XPanel</span>
        </a>
        <div class="flex items-center gap-2">
            @yield('nav_actions')
            <a href="{{ route('client.login') }}"
               class="kt-btn kt-btn-primary kt-btn-sm">
                Iniciar sesión
            </a>
        </div>
    </div>
</nav>

<main>
    @yield('content')
</main>

<footer class="border-t border-input bg-background mt-20">
    <div class="max-w-6xl mx-auto px-4 py-6 text-center text-xs text-muted-foreground">
        &copy; {{ date('Y') }} XPanel. Todos los derechos reservados.
    </div>
</footer>

<script src="{{ asset('assets/js/core.bundle.js') }}"></script>
@stack('scripts')

</body>
</html>
