@php
    $navSections = [
        'dashboard' => [
            'match' => ['client.dashboard'],
            'title' => 'Dashboard',
            'items' => [
                ['label' => 'Resumen', 'route' => 'client.dashboard', 'match' => ['client.dashboard']],
                [
                    'label' => 'Hosting',
                    'match' => ['client.sites.*', 'client.databases.*', 'client.files.*'],
                    'children' => [
                        ['label' => 'Sitios', 'route' => 'client.sites.index', 'match' => ['client.sites.*']],
                        ['label' => 'Crear sitio', 'route' => 'client.sites.create', 'match' => ['client.sites.create']],
                        ['label' => 'Bases de datos', 'route' => 'client.databases.index', 'match' => ['client.databases.*']],
                    ],
                ],
                [
                    'label' => 'Dominios',
                    'match' => ['client.domains.*', 'client.dns.*'],
                    'children' => [
                        ['label' => 'Dominios', 'route' => 'client.domains.index', 'match' => ['client.domains.*']],
                        ['label' => 'Crear dominio', 'route' => 'client.domains.create', 'match' => ['client.domains.create']],
                        ['label' => 'DNS', 'route' => 'client.dns.index', 'match' => ['client.dns.*']],
                    ],
                ],
                ['label' => 'Correos', 'route' => 'client.emails.index', 'match' => ['client.emails.*']],
            ],
        ],
        'sites' => [
            'match' => ['client.sites.*'],
            'title' => 'Sitios',
            'items' => [
                ['label' => 'Todos', 'route' => 'client.sites.index', 'match' => ['client.sites.index']],
                [
                    'label' => 'Gestion',
                    'match' => ['client.sites.create'],
                    'children' => [
                        ['label' => 'Crear sitio', 'route' => 'client.sites.create', 'match' => ['client.sites.create']],
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
                ['label' => 'Todas', 'route' => 'client.databases.index', 'match' => ['client.databases.index']],
                [
                    'label' => 'Gestion',
                    'match' => ['client.databases.create'],
                    'children' => [
                        ['label' => 'Crear base de datos', 'route' => 'client.databases.create', 'match' => ['client.databases.create']],
                        ['label' => 'Listado', 'route' => 'client.databases.index', 'match' => ['client.databases.index']],
                    ],
                ],
                ['label' => 'Sitios', 'route' => 'client.sites.index', 'match' => ['client.sites.*']],
            ],
            'actions' => [
                ['label' => 'Crear', 'route' => 'client.databases.create', 'icon' => 'ki-plus'],
            ],
        ],
        'domains' => [
            'match' => ['client.domains.*', 'client.dns.*'],
            'title' => 'Dominios',
            'items' => [
                ['label' => 'Dominios', 'route' => 'client.domains.index', 'match' => ['client.domains.*']],
                [
                    'label' => 'DNS',
                    'match' => ['client.dns.*'],
                    'children' => [
                        ['label' => 'Registros DNS', 'route' => 'client.dns.index', 'match' => ['client.dns.*']],
                        ['label' => 'Dominios', 'route' => 'client.domains.index', 'match' => ['client.domains.*']],
                    ],
                ],
                ['label' => 'Crear dominio', 'route' => 'client.domains.create', 'match' => ['client.domains.create']],
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
            ],
        ],
    ];

    $section = collect($navSections)->first(fn ($section) => collect($section['match'])->contains(fn ($pattern) => request()->routeIs($pattern)))
        ?? $navSections['dashboard'];

    $itemClass = 'kt-menu-link gap-2.5 h-full border-b-2 border-b-transparent';
    $itemActiveClass = 'border-b-mono text-mono font-medium';
    $itemIdleClass = 'text-foreground hover:text-mono';
    $isNavActive = fn (array $patterns) => collect($patterns)->contains(fn ($pattern) => request()->routeIs($pattern));
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
                                $active = $isNavActive($item['match']);
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
                                                $childActive = $isNavActive($child['match']);
                                            @endphp

                                            <div class="kt-menu-item {{ $childActive ? 'active' : '' }}">
                                                <a class="kt-menu-link" href="{{ route($child['route']) }}" tabindex="0">
                                                    <span class="kt-menu-title">
                                                        {{ $child['label'] }}
                                                    </span>
                                                    @if($childActive)
                                                        <span class="kt-menu-icon ms-auto">
                                                            <i class="ki-filled ki-check text-primary"></i>
                                                        </span>
                                                    @endif
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="kt-menu-item border-b-2 border-b-transparent {{ $active ? 'here' : '' }}">
                                    <a class="{{ $itemClass }} {{ $active ? $itemActiveClass : $itemIdleClass }}"
                                       href="{{ route($item['route']) }}"
                                       tabindex="0">
                                        <span class="kt-menu-title text-nowrap text-sm">
                                            {{ $item['label'] }}
                                        </span>
                                    </a>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @yield('navbar_actions')

                @foreach($section['actions'] ?? [] as $action)
                    <a class="kt-btn kt-btn-primary flex-nowrap" href="{{ route($action['route']) }}">
                        <i class="ki-filled {{ $action['icon'] }}"></i>
                        <span class="hidden sm:inline text-nowrap">{{ $action['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
<!-- End of Navbar -->
