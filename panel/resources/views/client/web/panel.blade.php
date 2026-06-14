@extends('layouts.client')

@php
    $status = $site->status ?? 'active';
    $statusClass = str_contains($status, 'error')
        ? 'kt-badge-danger'
        : ($status === 'provisioning' ? 'kt-badge-warning' : 'kt-badge-success');

    $quickActions = [
        ['label' => 'Abrir archivos', 'icon' => 'ki-folder', 'url' => route('client.files.index', ['domain' => $site->domain])],
        ['label' => 'Bases de datos', 'icon' => 'ki-data', 'url' => route('client.websites.module', ['domain' => $site->domain, 'section' => 'databases', 'page' => 'my-sql-databases'])],
        ['label' => 'Dominios', 'icon' => 'ki-click', 'url' => route('client.websites.module', ['domain' => $site->domain, 'section' => 'domains', 'page' => 'subdomains'])],
    ];

    $activePath = $activePath ?? null;
    $activeModule = $activeModule ?? ($siteMenu[0] ?? ['label' => 'Panel']);
@endphp

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="kt-badge kt-badge-outline {{ $statusClass }}">{{ $status }}</span>
                                    <span class="text-xs text-secondary-foreground uppercase">{{ $site->project_type ?? 'sitio web' }}</span>
                                </div>
                                <h1 class="mt-2 text-2xl font-semibold text-mono truncate">{{ $site->domain }}</h1>
                                <p class="mt-1 text-sm text-secondary-foreground">
                                    {{ $activeModule['description'] ?? 'Centro de gestion del sitio seleccionado.' }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <a class="kt-btn kt-btn-outline" href="http://{{ $site->domain }}" target="_blank" rel="noopener">
                                    <i class="ki-filled ki-exit-right-corner"></i>
                                    Abrir web
                                </a>
                                <a class="kt-btn kt-btn-primary" href="{{ route('client.files.index', ['domain' => $site->domain]) }}">
                                    <i class="ki-filled ki-folder"></i>
                                    Archivos
                                </a>
                            </div>
                        </div>

                        <section class="grid gap-5 lg:gap-7.5">
                            @if($activePath)
                                @include('client.web.modules.placeholder', [
                                    'site' => $site,
                                    'activePath' => $activePath,
                                    'activeModule' => $activeModule,
                                ])
                            @else

                                <div class="grid gap-5 md:grid-cols-3">
                                    <div class="kt-card">
                                        <div class="kt-card-content p-5">
                                            <div class="text-sm text-secondary-foreground">Dominios vinculados</div>
                                            <div class="mt-2 text-2xl font-semibold text-mono">{{ $stats['domains'] }}</div>
                                        </div>
                                    </div>
                                    <div class="kt-card">
                                        <div class="kt-card-content p-5">
                                            <div class="text-sm text-secondary-foreground">Bases de datos</div>
                                            <div class="mt-2 text-2xl font-semibold text-mono">{{ $stats['databases'] }}</div>
                                        </div>
                                    </div>
                                    <div class="kt-card">
                                        <div class="kt-card-content p-5">
                                            <div class="text-sm text-secondary-foreground">Correos del sitio</div>
                                            <div class="mt-2 text-2xl font-semibold text-mono">{{ $stats['emails'] }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="kt-card">
                                    <div class="kt-card-header">
                                        <h3 class="kt-card-title">Acciones del sitio</h3>
                                    </div>
                                    <div class="kt-card-content">
                                        <div class="grid gap-3 md:grid-cols-3">
                                            @foreach($quickActions as $action)
                                                <a class="kt-btn kt-btn-outline justify-start" href="{{ $action['url'] }}">
                                                    <i class="ki-filled {{ $action['icon'] }}"></i>
                                                    {{ $action['label'] }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="kt-card">
                                    <div class="kt-card-header">
                                        <h3 class="kt-card-title">Resumen tecnico</h3>
                                    </div>
                                    <div class="kt-card-content">
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="flex items-center justify-between border-b border-border pb-3">
                                                <span class="text-sm text-secondary-foreground">Servidor web</span>
                                                <span class="text-sm font-medium text-mono">{{ $site->web_server ?? 'N/A' }}</span>
                                            </div>
                                            <div class="flex items-center justify-between border-b border-border pb-3">
                                                <span class="text-sm text-secondary-foreground">PHP</span>
                                                <span class="text-sm font-medium text-mono">{{ $site->php_version ?? 'N/A' }}</span>
                                            </div>
                                            <div class="flex items-center justify-between border-b border-border pb-3">
                                                <span class="text-sm text-secondary-foreground">Proyecto</span>
                                                <span class="text-sm font-medium text-mono">{{ $site->project_type ?? 'N/A' }}</span>
                                            </div>
                                            <div class="flex items-center justify-between border-b border-border pb-3">
                                                <span class="text-sm text-secondary-foreground">Actualizado</span>
                                                <span class="text-sm font-medium text-mono">{{ $site->updated_at?->diffForHumans() ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            </section>
                    </div>
                </div>
            </main>

            @include('layouts.partials.client.footer')
        </div>
    </div>
@endsection
