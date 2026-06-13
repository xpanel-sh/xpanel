@php
    $selectedSiteDomain = $selectedSiteDomain ?? null;
    $filesRouteParams = $selectedSiteDomain ? ['domain' => $selectedSiteDomain] : [];

    $navSections = [
        'dashboard' => [
            'match' => ['client.dashboard'],
            'title' => 'Inicio',
            'items' => [
                ['label' => 'Resumen', 'route' => 'client.dashboard', 'match' => ['client.dashboard']],
                ['label' => 'Sitios', 'route' => 'client.sites.index', 'match' => ['client.sites.*']],
                ['label' => 'Dominios', 'route' => 'client.domains.index', 'match' => ['client.domains.*', 'client.dns.*']],
                ['label' => 'Correos', 'route' => 'client.emails.index', 'match' => ['client.emails.*']],
            ],
        ],
        'sites' => [
            'match' => ['client.sites.*', 'client.files.*'],
            'title' => 'Sitios',
            'items' => [
                ['label' => 'Todos los sitios', 'route' => 'client.sites.index', 'match' => ['client.sites.index']],
                [
                    'label' => 'Gestion',
                    'match' => ['client.sites.create', 'client.files.*'],
                    'children' => [
                        ['label' => 'Crear sitio', 'route' => 'client.sites.create', 'match' => ['client.sites.create']],
                        ['label' => 'Administrador de archivos', 'route' => 'client.files.index', 'params' => $filesRouteParams, 'match' => ['client.files.*']],
                        ['label' => 'Listado', 'route' => 'client.sites.index', 'match' => ['client.sites.index']],
                    ],
                ],
                [
                    'label' => 'Recursos',
                    'match' => ['client.databases.*', 'client.domains.*', 'client.dns.*'],
                    'children' => [
                        ['label' => 'Bases de datos', 'route' => 'client.databases.index', 'match' => ['client.databases.*']],
                        ['label' => 'Dominios', 'route' => 'client.domains.index', 'match' => ['client.domains.*']],
                        ['label' => 'DNS', 'route' => 'client.dns.index', 'match' => ['client.dns.*']],
                        ['label' => 'Backups', 'disabled' => true, 'match' => []],
                    ],
                ],
                [
                    'label' => 'Avanzado',
                    'match' => [],
                    'children' => [
                        ['label' => 'Git / Deploy', 'disabled' => true, 'match' => []],
                        ['label' => 'Cron Jobs', 'disabled' => true, 'match' => []],
                        ['label' => 'Runtime PHP', 'disabled' => true, 'match' => []],
                        ['label' => 'Agentes IA', 'disabled' => true, 'match' => []],
                    ],
                ],
            ],
            'actions' => [
                ['label' => 'Crear sitio', 'route' => 'client.sites.create', 'icon' => 'ki-plus'],
            ],
        ],
        'databases' => [
            'match' => ['client.databases.*'],
            'title' => 'Bases de datos',
            'items' => [
                ['label' => 'Administracion', 'route' => 'client.databases.index', 'match' => ['client.databases.index']],
                [
                    'label' => 'Gestion',
                    'match' => ['client.databases.create'],
                    'children' => [
                        ['label' => 'Crear base de datos', 'route' => 'client.databases.create', 'match' => ['client.databases.create']],
                        ['label' => 'Listado', 'route' => 'client.databases.index', 'match' => ['client.databases.index']],
                    ],
                ],
                ['label' => 'Sitios', 'route' => 'client.sites.index', 'match' => ['client.sites.*']],
                ['label' => 'phpMyAdmin', 'disabled' => true, 'match' => []],
            ],
            'actions' => [
                ['label' => 'Crear', 'route' => 'client.databases.create', 'icon' => 'ki-plus'],
            ],
        ],
        'domains' => [
            'match' => ['client.domains.*', 'client.dns.*'],
            'title' => 'Dominios',
            'items' => [
                ['label' => 'Portafolio', 'route' => 'client.domains.index', 'match' => ['client.domains.*']],
                [
                    'label' => 'DNS',
                    'match' => ['client.dns.*'],
                    'children' => [
                        ['label' => 'Registros DNS', 'route' => 'client.dns.index', 'match' => ['client.dns.*']],
                        ['label' => 'Dominios', 'route' => 'client.domains.index', 'match' => ['client.domains.*']],
                    ],
                ],
                ['label' => 'Crear dominio', 'route' => 'client.domains.create', 'match' => ['client.domains.create']],
                ['label' => 'Subdominios', 'disabled' => true, 'match' => []],
            ],
            'actions' => [
                ['label' => 'Crear dominio', 'route' => 'client.domains.create', 'icon' => 'ki-plus'],
            ],
        ],
        'emails' => [
            'match' => ['client.emails.*'],
            'title' => 'Correos',
            'items' => [
                ['label' => 'Cuentas', 'route' => 'client.emails.index', 'match' => ['client.emails.index']],
                [
                    'label' => 'Gestion',
                    'match' => ['client.emails.create'],
                    'children' => [
                        ['label' => 'Crear correo', 'route' => 'client.emails.create', 'match' => ['client.emails.create']],
                        ['label' => 'Listado', 'route' => 'client.emails.index', 'match' => ['client.emails.index']],
                    ],
                ],
                ['label' => 'Dominios', 'route' => 'client.domains.index', 'match' => ['client.domains.*']],
                ['label' => 'Webmail', 'disabled' => true, 'match' => []],
            ],
            'actions' => [
                ['label' => 'Crear', 'route' => 'client.emails.create', 'icon' => 'ki-plus'],
            ],
        ],
        'account' => [
            'match' => ['client.account.*'],
            'title' => 'Cuenta',
            'items' => [
                ['label' => 'Perfil', 'route' => 'client.account.show', 'match' => ['client.account.*']],
                ['label' => 'Dashboard', 'route' => 'client.dashboard', 'match' => ['client.dashboard']],
                ['label' => 'Seguridad', 'disabled' => true, 'match' => []],
            ],
        ],
    ];

    $section = collect($navSections)->first(fn ($section) => collect($section['match'])->contains(fn ($pattern) => request()->routeIs($pattern)))
        ?? $navSections['dashboard'];

    $itemClass = 'kt-menu-link gap-2.5 h-full border-b-2 border-b-transparent';
    $itemActiveClass = 'border-b-mono text-mono font-medium';
    $itemIdleClass = 'text-foreground hover:text-mono';
    $isNavActive = fn (array $patterns) => collect($patterns)->contains(fn ($pattern) => request()->routeIs($pattern));
    $itemHref = fn (array $item) => isset($item['url'])
        ? $item['url']
        : route($item['route'], $item['params'] ?? []);
@endphp

<!-- Navbar -->
<div class="flex items-stretch lg:fixed z-5 top-(--header-height) start-(--sidebar-width) end-5 h-(--navbar-height) mx-5 lg:mx-0 bg-muted" id="navbar">
    <div class="rounded-t-xl border border-input border-b-input bg-background flex items-stretch grow">
        <div class="kt-container-fluid flex justify-between items-stretch gap-5">
            <div class="grid items-stretch min-w-0">
                <div class="kt-scrollable-x-auto flex items-stretch">
                    <div class="kt-menu gap-5 lg:gap-7.5" data-kt-menu="true">
                        <div class="flex items-center pe-2">
                            <span class="text-sm font-semibold text-mono text-nowrap">{{ $section['title'] }}</span>
                        </div>

                        @foreach($section['items'] as $item)
                            @php
                                $active = $isNavActive($item['match'] ?? []);
                                $disabled = $item['disabled'] ?? false;
                            @endphp

                            @if(isset($item['children']))
                                <div class="kt-menu-item border-b-2 border-b-transparent {{ $active ? 'here' : '' }}"
                                     data-kt-menu-item-placement="bottom-start"
                                     data-kt-menu-item-placement-rtl="bottom-end"
                                     data-kt-menu-item-toggle="dropdown"
                                     data-kt-menu-item-trigger="click|lg:hover">
                                    <button class="{{ $itemClass }} {{ $active ? $itemActiveClass : $itemIdleClass }}" type="button">
                                        <span class="kt-menu-title text-nowrap text-sm">
                                            {{ $item['label'] }}
                                        </span>
                                        <span class="kt-menu-arrow">
                                            <i class="ki-filled ki-down text-xs text-muted-foreground"></i>
                                        </span>
                                    </button>

                                    <div class="kt-menu-dropdown kt-menu-default py-2 min-w-[210px]">
                                        @foreach($item['children'] as $child)
                                            @php
                                                $childActive = $isNavActive($child['match'] ?? []);
                                                $childDisabled = $child['disabled'] ?? false;
                                            @endphp

                                            <div class="kt-menu-item {{ $childActive ? 'active' : '' }}">
                                                @if($childDisabled)
                                                    <button class="kt-menu-link opacity-50 cursor-not-allowed" type="button" aria-disabled="true">
                                                        <span class="kt-menu-title">
                                                            {{ $child['label'] }}
                                                        </span>
                                                    </button>
                                                @else
                                                    <a class="kt-menu-link" href="{{ $itemHref($child) }}" tabindex="0">
                                                        <span class="kt-menu-title">
                                                            {{ $child['label'] }}
                                                        </span>
                                                        @if($childActive)
                                                            <span class="kt-menu-icon ms-auto">
                                                                <i class="ki-filled ki-check text-primary"></i>
                                                            </span>
                                                        @endif
                                                    </a>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="kt-menu-item border-b-2 border-b-transparent {{ $active ? 'here' : '' }}">
                                    @if($disabled)
                                        <button class="{{ $itemClass }} opacity-50 cursor-not-allowed text-foreground" type="button" aria-disabled="true">
                                            <span class="kt-menu-title text-nowrap text-sm">
                                                {{ $item['label'] }}
                                            </span>
                                        </button>
                                    @else
                                        <a class="{{ $itemClass }} {{ $active ? $itemActiveClass : $itemIdleClass }}"
                                           href="{{ $itemHref($item) }}"
                                           tabindex="0">
                                            <span class="kt-menu-title text-nowrap text-sm">
                                                {{ $item['label'] }}
                                            </span>
                                        </a>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @yield('navbar_actions')

                @foreach($section['actions'] ?? [] as $action)
                    <a class="kt-btn kt-btn-primary flex-nowrap" href="{{ $itemHref($action) }}">
                        <i class="ki-filled {{ $action['icon'] }}"></i>
                        <span class="hidden sm:inline text-nowrap">{{ $action['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
<!-- End of Navbar -->
