@extends('layouts.client')

@php
    $plan = $tenant->plan;
    $status = $tenant->status ?? 'active';

    $limitLabel = fn ($value) => $value === null ? 'Sin asignar' : $value;
    $percent = function ($used, $limit) {
        if (!$limit || !is_numeric($limit) || (float) $limit <= 0) {
            return null;
        }

        return min(100, round(((float) $used / (float) $limit) * 100, 1));
    };

    $sitePercent = $percent($siteCount, $plan?->max_sites);
    $databasePercent = $percent($databaseCount, $plan?->max_databases);
    $emailPercent = $percent($emailCount, $plan?->email_accounts);

    $quotaCards = [
        [
            'label' => 'Sitios',
            'used' => $siteCount,
            'limit' => $plan?->max_sites,
            'percent' => $sitePercent,
            'icon' => 'ki-website',
            'route' => 'client.sites.index',
        ],
        [
            'label' => 'Bases de datos',
            'used' => $databaseCount,
            'limit' => $plan?->max_databases,
            'percent' => $databasePercent,
            'icon' => 'ki-data',
            'route' => 'client.databases.index',
        ],
        [
            'label' => 'Correos',
            'used' => $emailCount,
            'limit' => $plan?->email_accounts,
            'percent' => $emailPercent,
            'icon' => 'ki-sms',
            'route' => 'client.emails.index',
        ],
    ];
@endphp

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
                        <div class="grid lg:grid-cols-3 gap-5 lg:gap-7.5 items-stretch">
                            <div class="lg:col-span-2">
                                <div class="kt-card h-full">
                                    <div class="kt-card-content flex flex-col place-content-center gap-5">
                                        <div class="flex justify-center">
                                            <img alt="image" class="dark:hidden max-h-[180px]" src="{{ asset('assets/media/illustrations/32.svg') }}" />
                                            <img alt="image" class="light:hidden max-h-[180px]" src="{{ asset('assets/media/illustrations/32-dark.svg') }}" />
                                        </div>
                                        <div class="flex flex-col gap-4">
                                            <div class="flex flex-col gap-3 text-center">
                                                <div class="flex justify-center">
                                                    <span class="kt-badge kt-badge-sm {{ $status === 'active' ? 'kt-badge-success' : 'kt-badge-warning' }} kt-badge-outline">
                                                        Servicio {{ $status === 'active' ? 'activo' : $status }}
                                                    </span>
                                                </div>
                                                <h2 class="text-xl font-semibold text-mono">
                                                    {{ $tenant->name ?? 'Mi cuenta' }}
                                                </h2>
                                                <p class="text-sm font-medium text-secondary-foreground">
                                                    Administra tus sitios, dominios, bases de datos y correos desde tu panel de hosting.
                                                    <br />
                                                    Tu cuenta opera separada del Admin global de XPanel.
                                                </p>
                                            </div>
                                            <div class="flex justify-center gap-2">
                                                <a class="kt-btn kt-btn-mono" href="{{ route('client.sites.create') }}">
                                                    Crear sitio
                                                </a>
                                                <a class="kt-btn kt-btn-outline" href="{{ route('client.account.show') }}">
                                                    Ver cuenta
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="lg:col-span-1">
                                <div class="kt-card h-full">
                                    <div class="kt-card-header">
                                        <h3 class="kt-card-title">Highlights</h3>
                                        <a class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" href="{{ route('client.account.show') }}">
                                            <i class="ki-filled ki-dots-vertical text-lg"></i>
                                        </a>
                                    </div>
                                    <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-sm font-normal text-secondary-foreground">Plan actual</span>
                                            <div class="flex items-center gap-2.5">
                                                <span class="text-3xl font-semibold text-mono">
                                                    {{ $plan?->name ?? 'Sin plan' }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="grid gap-3">
                                            <div class="flex items-center justify-between flex-wrap gap-2">
                                                <div class="flex items-center gap-1.5">
                                                    <i class="ki-filled ki-hard-drive text-base text-muted-foreground"></i>
                                                    <span class="text-sm font-normal text-mono">Almacenamiento</span>
                                                </div>
                                                <span class="text-sm font-medium text-foreground">
                                                    {{ $plan ? number_format($plan->storage_mb / 1024, 1) . ' GB' : 'Sin asignar' }}
                                                </span>
                                            </div>
                                            <div class="flex items-center justify-between flex-wrap gap-2">
                                                <div class="flex items-center gap-1.5">
                                                    <i class="ki-filled ki-arrow-right-left text-base text-muted-foreground"></i>
                                                    <span class="text-sm font-normal text-mono">Transferencia</span>
                                                </div>
                                                <span class="text-sm font-medium text-foreground">
                                                    {{ $plan ? $plan->bandwidth_gb . ' GB' : 'Sin asignar' }}
                                                </span>
                                            </div>
                                            <div class="flex items-center justify-between flex-wrap gap-2">
                                                <div class="flex items-center gap-1.5">
                                                    <i class="ki-filled ki-dollar text-base text-muted-foreground"></i>
                                                    <span class="text-sm font-normal text-mono">Mensualidad</span>
                                                </div>
                                                <span class="text-sm font-medium text-foreground">
                                                    {{ $plan ? '$' . number_format((float) $plan->monthly_price, 2) : 'Sin plan' }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="border-b border-input"></div>

                                        <div class="grid gap-3">
                                            <a class="kt-btn kt-btn-outline justify-start" href="{{ route('client.domains.create') }}">
                                                <i class="ki-filled ki-globe"></i>
                                                Agregar dominio
                                            </a>
                                            <a class="kt-btn kt-btn-outline justify-start" href="{{ route('client.databases.create') }}">
                                                <i class="ki-filled ki-data"></i>
                                                Crear base de datos
                                            </a>
                                            <a class="kt-btn kt-btn-outline justify-start" href="{{ route('client.emails.create') }}">
                                                <i class="ki-filled ki-sms"></i>
                                                Crear correo
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4 lg:gap-7.5">
                            <div class="kt-card">
                                <div class="kt-card-content p-5">
                                    <div class="text-sm text-secondary-foreground">Servicio</div>
                                    <div class="mt-2 text-3xl font-semibold text-mono">{{ ucfirst($status) }}</div>
                                    <div class="mt-1 text-xs text-secondary-foreground">Estado de la cuenta</div>
                                </div>
                            </div>
                            <div class="kt-card">
                                <div class="kt-card-content p-5">
                                    <div class="text-sm text-secondary-foreground">Dominios</div>
                                    <div class="mt-2 text-3xl font-semibold text-mono">{{ $domainCount }}</div>
                                    <div class="mt-1 text-xs text-secondary-foreground">Dominios configurados</div>
                                </div>
                            </div>
                            <div class="kt-card">
                                <div class="kt-card-content p-5">
                                    <div class="text-sm text-secondary-foreground">Bases de datos</div>
                                    <div class="mt-2 text-3xl font-semibold text-mono">{{ $databaseCount }}</div>
                                    <div class="mt-1 text-xs text-secondary-foreground">Límite: {{ $limitLabel($plan?->max_databases) }}</div>
                                </div>
                            </div>
                            <div class="kt-card">
                                <div class="kt-card-content p-5">
                                    <div class="text-sm text-secondary-foreground">Correos</div>
                                    <div class="mt-2 text-3xl font-semibold text-mono">{{ $emailCount }}</div>
                                    <div class="mt-1 text-xs text-secondary-foreground">Límite: {{ $limitLabel($plan?->email_accounts) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="grid lg:grid-cols-3 gap-5 lg:gap-7.5 items-stretch">
                            <div class="kt-card lg:col-span-2">
                                <div class="kt-card-header">
                                    <h3 class="kt-card-title">Uso de recursos</h3>
                                </div>
                                <div class="kt-card-content grid gap-5 p-5">
                                    @foreach($quotaCards as $quota)
                                        <a class="grid gap-2 rounded-md border border-input px-4 py-3 hover:border-primary/40" href="{{ route($quota['route']) }}">
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="flex items-center gap-2">
                                                    <i class="ki-filled {{ $quota['icon'] }} text-muted-foreground"></i>
                                                    <span class="text-sm font-medium text-mono">{{ $quota['label'] }}</span>
                                                </div>
                                                <span class="text-sm font-semibold text-mono">
                                                    {{ $quota['used'] }} / {{ $limitLabel($quota['limit']) }}
                                                </span>
                                            </div>
                                            <div class="h-2 rounded-xs bg-muted">
                                                <div class="h-2 rounded-xs bg-primary" style="width: {{ $quota['percent'] ?? 0 }}%"></div>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                            <div class="kt-card">
                                <div class="kt-card-header">
                                    <h3 class="kt-card-title">Accesos rápidos</h3>
                                </div>
                                <div class="kt-card-content grid gap-3 p-5">
                                    <a class="kt-btn kt-btn-outline justify-start" href="{{ route('client.sites.create') }}">
                                        <i class="ki-filled ki-plus"></i>
                                        Crear sitio
                                    </a>
                                    <a class="kt-btn kt-btn-outline justify-start" href="{{ route('client.dns.index') }}">
                                        <i class="ki-filled ki-cloud"></i>
                                        Gestionar DNS
                                    </a>
                                    <a class="kt-btn kt-btn-outline justify-start" href="{{ route('client.domains.index') }}">
                                        <i class="ki-filled ki-globe"></i>
                                        Dominios
                                    </a>
                                    <a class="kt-btn kt-btn-outline justify-start" href="{{ route('client.emails.index') }}">
                                        <i class="ki-filled ki-sms"></i>
                                        Correos
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="grid lg:grid-cols-3 gap-5 lg:gap-7.5 items-stretch">
                            <div class="lg:col-span-2">
                                <div class="kt-card kt-card-grid h-full min-w-full">
                                    <div class="kt-card-header">
                                        <div>
                                            <h3 class="kt-card-title">Sitios recientes</h3>
                                            <p class="mt-1 text-sm text-secondary-foreground">Tus dominios y aplicaciones creadas en XPanel.</p>
                                        </div>
                                        <a class="kt-btn kt-btn-sm kt-btn-primary" href="{{ route('client.sites.create') }}">Crear sitio</a>
                                    </div>
                                    <div class="kt-card-table">
                                        <div class="kt-scrollable-x-auto">
                                            <table class="kt-table kt-table-border table-fixed">
                                                <thead>
                                                    <tr>
                                                        <th class="w-[280px]">Dominio</th>
                                                        <th class="w-[140px]">Tipo</th>
                                                        <th class="w-[140px]">Web server</th>
                                                        <th class="w-[120px]">Estado</th>
                                                        <th class="w-[120px] text-end">Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($sites as $site)
                                                        <tr>
                                                            <td>
                                                                <div class="flex flex-col gap-1">
                                                                    <span class="font-medium text-sm text-mono">{{ $site->domain }}</span>
                                                                    <span class="text-xs text-secondary-foreground">{{ $site->created_at?->diffForHumans() }}</span>
                                                                </div>
                                                            </td>
                                                            <td class="text-sm text-foreground">{{ strtoupper($site->project_type ?? '-') }}</td>
                                                            <td class="text-sm text-foreground">{{ $site->web_server ?? '-' }}</td>
                                                            <td>
                                                                <span class="kt-badge kt-badge-sm kt-badge-outline {{ ($site->status ?? 'active') === 'active' ? 'kt-badge-success' : 'kt-badge-warning' }}">
                                                                    {{ strtoupper($site->status ?? 'active') }}
                                                                </span>
                                                            </td>
                                                            <td class="text-end">
                                                                <a class="kt-btn kt-btn-sm kt-btn-outline" href="{{ route('client.sites.index') }}">
                                                                    Gestionar
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="py-8 text-center text-sm text-secondary-foreground">
                                                                Todavía no tienes sitios web. Crea el primero para empezar.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="kt-card">
                                <div class="kt-card-header">
                                    <h3 class="kt-card-title">Tu plan</h3>
                                </div>
                                <div class="kt-card-content grid gap-4 p-5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-secondary-foreground">Plan</span>
                                        <span class="text-sm font-semibold text-mono">{{ $plan?->name ?? 'Sin plan' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-secondary-foreground">Sitios</span>
                                        <span class="text-sm font-semibold text-mono">{{ $siteCount }} / {{ $limitLabel($plan?->max_sites) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-secondary-foreground">Bases</span>
                                        <span class="text-sm font-semibold text-mono">{{ $databaseCount }} / {{ $limitLabel($plan?->max_databases) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-secondary-foreground">Correos</span>
                                        <span class="text-sm font-semibold text-mono">{{ $emailCount }} / {{ $limitLabel($plan?->email_accounts) }}</span>
                                    </div>
                                    <div class="border-b border-input"></div>
                                    <a class="kt-btn kt-btn-outline justify-center" href="{{ route('client.account.show') }}">
                                        Ver detalles de cuenta
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            @include('layouts.partials.client.footer')
        </div>
    </div>
@endsection
