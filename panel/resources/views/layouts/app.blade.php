<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>XPanel</title>
    <link rel="shortcut icon" href="{{ asset('assets/media/app/favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/app/favicon-32x32.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
</head>

<body class="antialiased flex h-full text-base text-foreground bg-background [--header-height:58px] [--sidebar-width:58px] [--navbar-height:52px] lg:overflow-hidden bg-muted">

{{-- Theme mode: apply from localStorage before render --}}
<script>
    (function () {
        const t = localStorage.getItem('kt-theme') || 'light';
        const mode = t === 'system'
            ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : t;
        document.documentElement.classList.add(mode);
        document.documentElement.setAttribute('data-kt-theme-mode', mode);
    })();
</script>

@php
    use Illuminate\Support\Facades\Auth;
    $user    = Auth::guard('admin')->user() ?? auth()->user();
    $isAdmin = Auth::guard('admin')->check() && $user?->role === 'admin';
    $isFiles = request()->routeIs('client.files.*') || request()->routeIs('admin.files.*');

    // Sidebar active helper
    $sideActive = fn(string ...$patterns) => collect($patterns)->contains(fn($p) => request()->routeIs($p))
        ? 'bg-background [&_i]:text-primary border-input'
        : '';

    // Client: all sites for domain selector
    $sitesList = collect();
    if (!$isAdmin) {
        $tenant = app('request')->attributes->get('tenant');
        if ($tenant) {
            $sitesList = \App\Models\Site::where('tenant_id', $tenant->id)
                ->select('id','domain')->orderBy('domain')->get();
        }
    }
@endphp

<div class="flex grow">

{{-- ============================================================
     HEADER
     ============================================================ --}}
<header class="flex items-center fixed z-10 top-0 left-0 right-0 shrink-0 h-(--header-height) bg-muted" id="header">
    <div class="kt-container-fluid flex justify-between items-stretch px-4 lg:ps-0 lg:gap-4" id="header_container">

        {{-- Logo + Brand --}}
        <div class="flex items-center gap-3">
            {{-- Mobile drawer toggle --}}
            <button class="kt-btn kt-btn-icon kt-btn-ghost lg:hidden" data-kt-drawer-toggle="#sidebar">
                <i class="ki-filled ki-menu text-lg"></i>
            </button>

            {{-- Logo icon in sidebar-width zone --}}
            <div class="hidden lg:flex items-center justify-center w-(--sidebar-width) shrink-0">
                <a href="{{ $isAdmin ? route('admin.dashboard') : route('client.dashboard') }}">
                    <img class="dark:hidden h-[26px]" src="{{ asset('assets/media/app/mini-logo-primary.svg') }}" alt="XPanel">
                    <img class="hidden dark:block h-[26px]" src="{{ asset('assets/media/app/mini-logo-primary-dark.svg') }}" alt="XPanel">
                </a>
            </div>

            {{-- Brand name --}}
            <span class="text-sm font-bold text-mono tracking-tight">XPanel</span>

            @if(!$isAdmin)
                {{-- Client: separator + domain selector --}}
                <span class="text-muted-foreground hidden sm:inline">/</span>
                @if($sitesList->count())
                <div class="hidden sm:block">
                    <select class="kt-select text-sm h-8 min-w-[160px] max-w-[220px]"
                            onchange="if(this.value) window.location=this.value">
                        <option value="">Seleccionar dominio…</option>
                        @foreach($sitesList as $s)
                            <option value="{{ route('client.files.index', $s->domain) }}"
                                {{ (isset($site) && $site->id === $s->id) ? 'selected' : '' }}>
                                {{ $s->domain }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @else
                    <span class="text-sm text-muted-foreground hidden sm:inline">Panel Cliente</span>
                @endif
            @else
                <span class="text-muted-foreground hidden sm:inline">/</span>
                <span class="text-sm text-muted-foreground hidden sm:inline">Admin</span>
            @endif
        </div>

        {{-- Topbar right --}}
        <div class="flex items-center gap-1 lg:gap-2">

            {{-- Dark/light toggle --}}
            <button class="kt-btn kt-btn-ghost kt-btn-icon size-9 rounded-full"
                    title="Cambiar tema"
                    onclick="(function(){
                        const html=document.documentElement;
                        const isDark=html.classList.contains('dark');
                        const next=isDark?'light':'dark';
                        html.classList.toggle('dark',!isDark);
                        html.classList.toggle('light',isDark);
                        html.setAttribute('data-kt-theme-mode',next);
                        localStorage.setItem('kt-theme',next);
                    })()">
                <i class="ki-filled ki-sun text-lg dark:hidden text-amber-500"></i>
                <i class="ki-filled ki-moon text-lg hidden dark:block text-indigo-400"></i>
            </button>

            {{-- User dropdown --}}
            <div class="kt-menu" data-kt-menu="true">
                <div class="kt-menu-item"
                     data-kt-menu-item-offset="0, 8px"
                     data-kt-menu-item-placement="bottom-end"
                     data-kt-menu-item-toggle="dropdown"
                     data-kt-menu-item-trigger="click">
                    <button class="kt-menu-toggle flex items-center gap-2 kt-btn kt-btn-ghost rounded-xl px-2 h-9">
                        <span class="size-7 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                            <i class="ki-filled ki-user text-sm text-primary"></i>
                        </span>
                        <span class="hidden md:block text-sm font-medium text-mono max-w-[120px] truncate">{{ $user?->name }}</span>
                        <i class="ki-filled ki-down text-xs text-muted-foreground"></i>
                    </button>
                    <div class="kt-menu-dropdown kt-menu-default py-2 min-w-[200px]" data-kt-menu-dismiss="true">
                        <div class="px-4 py-2.5 border-b border-border mb-1">
                            <div class="text-sm font-semibold text-mono">{{ $user?->name }}</div>
                            <div class="text-xs text-muted-foreground truncate mt-0.5">{{ $user?->email }}</div>
                        </div>
                        @if(!$isAdmin)
                        <div class="kt-menu-item">
                            <a class="kt-menu-link" href="{{ route('client.account.show') }}">
                                <span class="kt-menu-icon"><i class="ki-filled ki-profile-circle"></i></span>
                                <span class="kt-menu-title">Mi Cuenta</span>
                            </a>
                        </div>
                        @endif
                        <div class="border-t border-border mt-1 pt-1">
                            <form method="POST" action="{{ $isAdmin ? route('admin.logout') : route('client.logout') }}">
                                @csrf
                                <button type="submit" class="kt-menu-link w-full">
                                    <span class="kt-menu-icon"><i class="ki-filled ki-logout text-red-500"></i></span>
                                    <span class="kt-menu-title text-red-500">Cerrar sesión</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</header>
{{-- End Header --}}

{{-- ============================================================
     SIDEBAR  (icon-only, 58px, con tooltips)
     ============================================================ --}}
<div class="fixed w-(--sidebar-width) lg:top-(--header-height) top-0 bottom-0 z-20 hidden lg:flex flex-col items-stretch shrink-0 group py-3 lg:py-0 [--kt-drawer-enable:true] lg:[--kt-drawer-enable:false]"
     data-kt-drawer="true"
     data-kt-drawer-class="kt-drawer kt-drawer-start top-0 bottom-0"
     id="sidebar">
    <div class="flex grow shrink-0" id="sidebar_content">
        <div class="kt-scrollable-y-auto grow gap-2.5 shrink-0 flex items-center flex-col lg:max-h-[calc(100dvh-70px)] pt-2">

            @php
                $sideBtn = 'kt-btn kt-btn-ghost kt-btn-icon rounded-full size-10 border border-transparent text-secondary-foreground hover:bg-background hover:[&_i]:text-primary hover:border-input';
            @endphp

            @if($isAdmin)
                {{-- ADMIN sidebar --}}
                <a class="{{ $sideBtn }} {{ $sideActive('admin.dashboard') }}"
                   data-kt-tooltip="" data-kt-tooltip-placement="right"
                   href="{{ route('admin.dashboard') }}">
                    <i class="ki-filled ki-element-11 text-lg"></i>
                    <span class="kt-tooltip" data-kt-tooltip-content="true">Dashboard</span>
                </a>
                <a class="{{ $sideBtn }} {{ $sideActive('admin.clients.*') }}"
                   data-kt-tooltip="" data-kt-tooltip-placement="right"
                   href="{{ route('admin.clients.index') }}">
                    <i class="ki-filled ki-people text-lg"></i>
                    <span class="kt-tooltip" data-kt-tooltip-content="true">Clientes</span>
                </a>
                <a class="{{ $sideBtn }} {{ $sideActive('admin.plans.*') }}"
                   data-kt-tooltip="" data-kt-tooltip-placement="right"
                   href="{{ route('admin.plans.index') }}">
                    <i class="ki-filled ki-dollar text-lg"></i>
                    <span class="kt-tooltip" data-kt-tooltip-content="true">Planes</span>
                </a>
                <a class="{{ $sideBtn }} {{ $sideActive('admin.sites.*', 'admin.files.*') }}"
                   data-kt-tooltip="" data-kt-tooltip-placement="right"
                   href="{{ route('admin.sites.index') }}">
                    <i class="ki-filled ki-website text-lg"></i>
                    <span class="kt-tooltip" data-kt-tooltip-content="true">Sitios</span>
                </a>
                <a class="{{ $sideBtn }} {{ $sideActive('admin.domains.*') }}"
                   data-kt-tooltip="" data-kt-tooltip-placement="right"
                   href="{{ route('admin.domains.index') }}">
                    <i class="ki-filled ki-globe text-lg"></i>
                    <span class="kt-tooltip" data-kt-tooltip-content="true">Dominios</span>
                </a>
                <a class="{{ $sideBtn }} {{ $sideActive('admin.servers.*') }}"
                   data-kt-tooltip="" data-kt-tooltip-placement="right"
                   href="{{ route('admin.servers.index') }}">
                    <i class="ki-filled ki-setting-3 text-lg"></i>
                    <span class="kt-tooltip" data-kt-tooltip-content="true">Servidores</span>
                </a>
                <a class="{{ $sideBtn }} {{ $sideActive('admin.dns.*') }}"
                   data-kt-tooltip="" data-kt-tooltip-placement="right"
                   href="{{ route('admin.dns.nameservers') }}">
                    <i class="ki-filled ki-code text-lg"></i>
                    <span class="kt-tooltip" data-kt-tooltip-content="true">Nameservers</span>
                </a>
                <a class="{{ $sideBtn }} {{ $sideActive('admin.daemon.*') }}"
                   data-kt-tooltip="" data-kt-tooltip-placement="right"
                   href="{{ route('admin.daemon.operations') }}">
                    <i class="ki-filled ki-chart-line-star text-lg"></i>
                    <span class="kt-tooltip" data-kt-tooltip-content="true">Operaciones</span>
                </a>
            @else
                {{-- CLIENT sidebar --}}
                <a class="{{ $sideBtn }} {{ $sideActive('client.dashboard') }}"
                   data-kt-tooltip="" data-kt-tooltip-placement="right"
                   href="{{ route('client.dashboard') }}">
                    <i class="ki-filled ki-element-11 text-lg"></i>
                    <span class="kt-tooltip" data-kt-tooltip-content="true">Dashboard</span>
                </a>
                <a class="{{ $sideBtn }} {{ $sideActive('client.sites.*', 'client.files.*') }}"
                   data-kt-tooltip="" data-kt-tooltip-placement="right"
                   href="{{ route('client.sites.index') }}">
                    <i class="ki-filled ki-website text-lg"></i>
                    <span class="kt-tooltip" data-kt-tooltip-content="true">Sitios Web</span>
                </a>
                <a class="{{ $sideBtn }} {{ $sideActive('client.databases.*') }}"
                   data-kt-tooltip="" data-kt-tooltip-placement="right"
                   href="{{ route('client.databases.index') }}">
                    <i class="ki-filled ki-storage text-lg"></i>
                    <span class="kt-tooltip" data-kt-tooltip-content="true">Bases de Datos</span>
                </a>
                <a class="{{ $sideBtn }} {{ $sideActive('client.domains.*') }}"
                   data-kt-tooltip="" data-kt-tooltip-placement="right"
                   href="{{ route('client.domains.index') }}">
                    <i class="ki-filled ki-globe text-lg"></i>
                    <span class="kt-tooltip" data-kt-tooltip-content="true">Dominios</span>
                </a>
                <a class="{{ $sideBtn }} {{ $sideActive('client.dns.*') }}"
                   data-kt-tooltip="" data-kt-tooltip-placement="right"
                   href="{{ route('client.dns.index') }}">
                    <i class="ki-filled ki-setting-2 text-lg"></i>
                    <span class="kt-tooltip" data-kt-tooltip-content="true">DNS</span>
                </a>
                <a class="{{ $sideBtn }} {{ $sideActive('client.emails.*') }}"
                   data-kt-tooltip="" data-kt-tooltip-placement="right"
                   href="{{ route('client.emails.index') }}">
                    <i class="ki-filled ki-sms text-lg"></i>
                    <span class="kt-tooltip" data-kt-tooltip-content="true">Correos</span>
                </a>
                <a class="{{ $sideBtn }} {{ $sideActive('client.account.*') }}"
                   data-kt-tooltip="" data-kt-tooltip-placement="right"
                   href="{{ route('client.account.show') }}">
                    <i class="ki-filled ki-profile-circle text-lg"></i>
                    <span class="kt-tooltip" data-kt-tooltip-content="true">Mi Cuenta</span>
                </a>
            @endif

        </div>
    </div>
</div>
{{-- End Sidebar --}}

{{-- ============================================================
     NAVBAR  (horizontal tab bar, hidden en file manager)
     ============================================================ --}}
@if(!$isFiles)
<div class="flex items-stretch lg:fixed z-5 top-(--header-height) start-(--sidebar-width) end-5 h-(--navbar-height) mx-5 lg:mx-0 bg-muted" id="navbar">
    <div class="rounded-t-xl border border-input bg-background flex items-stretch grow">
        <div class="kt-container-fluid flex justify-between items-stretch gap-5 px-5">

            {{-- Tabs / section nav --}}
            <div class="grid items-stretch">
                <div class="kt-scrollable-x-auto flex items-stretch">
                    <div class="kt-menu gap-4 lg:gap-5" data-kt-menu="true">

                        @php
                            /* ---------- ADMIN ---------- */
                            $adminTabs = [
                                'admin.dashboard'   => ['Dashboard',     route('admin.dashboard')],
                                'admin.clients.*'   => ['Clientes',      route('admin.clients.index')],
                                'admin.plans.*'     => ['Planes',        route('admin.plans.index')],
                                'admin.sites.*'     => ['Sitios',        route('admin.sites.index')],
                                'admin.domains.*'   => ['Dominios',      route('admin.domains.index')],
                                'admin.servers.*'   => ['Servidores',    route('admin.servers.index')],
                                'admin.dns.*'       => ['Nameservers',   route('admin.dns.nameservers')],
                                'admin.daemon.*'    => ['Operaciones',   route('admin.daemon.operations')],
                            ];
                            /* ---------- CLIENT ---------- */
                            $clientTabs = [
                                'client.dashboard'  => ['Dashboard',     route('client.dashboard')],
                                'client.sites.*'    => ['Sitios Web',    route('client.sites.index')],
                                'client.databases.*'=> ['Bases de Datos',route('client.databases.index')],
                                'client.domains.*'  => ['Dominios',      route('client.domains.index')],
                                'client.dns.*'      => ['DNS',           route('client.dns.index')],
                                'client.emails.*'   => ['Correos',       route('client.emails.index')],
                                'client.account.*'  => ['Mi Cuenta',     route('client.account.show')],
                            ];
                            $tabs = $isAdmin ? $adminTabs : $clientTabs;
                        @endphp

                        @foreach($tabs as $pattern => [$label, $href])
                            @php $tabActive = request()->routeIs($pattern); @endphp
                            <div class="kt-menu-item border-b-2 {{ $tabActive ? 'border-b-primary' : 'border-b-transparent' }}">
                                <a class="kt-menu-link gap-1.5 py-3" href="{{ $href }}">
                                    <span class="kt-menu-title text-nowrap text-sm {{ $tabActive ? 'text-mono font-medium' : 'text-muted-foreground' }}">
                                        {{ $label }}
                                    </span>
                                </a>
                            </div>
                        @endforeach

                    </div>
                </div>
            </div>

            {{-- Right-side navbar actions (views can push content here) --}}
            <div class="flex items-center gap-3">
                @yield('navbar_actions')
            </div>

        </div>
    </div>
</div>
@endif
{{-- End Navbar --}}

{{-- ============================================================
     MAIN CONTENT WRAPPER
     ============================================================ --}}
<div class="flex grow {{ $isFiles ? '' : 'rounded-b-xl' }} bg-background {{ $isFiles ? '' : 'border-x border-b border-input' }} {{ $isFiles ? '' : 'lg:mt-(--navbar-height)' }} mx-5 lg:ms-(--sidebar-width) mb-5 lg:{{ $isFiles ? 'mb-0 mx-0' : 'mb-5' }}">
    @if(!$isFiles)
    <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-6 lg:[&_.kt-container-fluid]:pe-4" id="scrollable_content">

        {{-- Alerts --}}
        @if(session('success'))
        <div class="kt-container-fluid mb-5" x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,5000)"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 dark:bg-green-900/10 dark:border-green-900/30 px-4 py-3 text-sm text-green-700 dark:text-green-400">
                <i class="ki-filled ki-check-circle text-green-500 text-base shrink-0"></i>
                <span class="grow">{{ session('success') }}</span>
                <button @click="show=false" class="shrink-0 text-green-500 hover:text-green-700 dark:hover:text-green-300">
                    <i class="ki-filled ki-cross text-xs"></i>
                </button>
            </div>
        </div>
        @endif

        @if($errors->any())
        <div class="kt-container-fluid mb-5" x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,7000)"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 dark:bg-red-900/10 dark:border-red-900/30 px-4 py-3 text-sm text-red-700 dark:text-red-400">
                <i class="ki-filled ki-information-5 text-red-500 text-base shrink-0"></i>
                <span class="grow">{{ $errors->first() }}</span>
                <button @click="show=false" class="shrink-0 text-red-500 hover:text-red-700 dark:hover:text-red-300">
                    <i class="ki-filled ki-cross text-xs"></i>
                </button>
            </div>
        </div>
        @endif

        <main class="grow" role="content">
            <div class="kt-container-fluid">
                @yield('content')
            </div>
        </main>
    </div>
    @else
    {{-- File manager: full-height, no padding, no container --}}
    <div class="flex flex-col grow overflow-hidden" id="scrollable_content">
        @yield('content')
    </div>
    @endif
</div>
{{-- End Content --}}

</div>{{-- End .flex.grow --}}

{{-- Scripts --}}
<script src="{{ asset('assets/js/core.bundle.js') }}"></script>
<script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}"></script>
@stack('scripts')

</body>
</html>
