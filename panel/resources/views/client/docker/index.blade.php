@extends('layouts.client')

@section('content')
<div class="flex grow rounded-xl bg-background border border-input lg:ms-(--sidebar-width) mt-0 lg:mt-(--header-height) m-5">
    <div class="flex flex-col grow kt-scrollable-y-auto lg:[--kt-scrollbar-width:auto] pt-5" id="scrollable_content">
        <main class="grow" role="content">
            <div class="kt-container-fluid">
                <div class="grid gap-5 lg:gap-7.5">

                    {{-- Cabecera --}}
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h1 class="text-2xl font-semibold text-mono">Apps Docker</h1>
                            <p class="mt-1 text-sm text-secondary-foreground">Gestiona tus contenedores Docker personalizados.</p>
                        </div>
                        <a href="{{ route('client.docker.create') }}" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-plus"></i>
                            Nueva app Docker
                        </a>
                    </div>

                    {{-- Flash --}}
                    @if(session('success'))
                        <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-600 dark:text-emerald-300">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="rounded-xl border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{-- Lista de instancias --}}
                    @if($instances->isEmpty())
                        <div class="kt-card p-10 text-center">
                            <i class="ki-filled ki-cube-3 text-4xl text-secondary-foreground mb-3 block"></i>
                            <p class="text-sm text-secondary-foreground">No tienes apps Docker aún.</p>
                            <a href="{{ route('client.docker.create') }}" class="kt-btn kt-btn-primary mt-4 inline-flex">
                                <i class="ki-filled ki-plus"></i> Crear primera app
                            </a>
                        </div>
                    @else
                        <div class="grid gap-4">
                            @foreach($instances as $instance)
                            @php
                                $statusClass = match($instance->status) {
                                    'running'      => 'kt-badge-success',
                                    'stopped'      => 'kt-badge-secondary',
                                    'error'        => 'kt-badge-danger',
                                    'provisioning' => 'kt-badge-warning',
                                    'partial'      => 'kt-badge-warning',
                                    default        => 'kt-badge-secondary',
                                };
                            @endphp
                            <div class="kt-card">
                                <div class="kt-card-header min-h-14 flex items-center justify-between gap-3 px-5">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <i class="ki-filled ki-cube-3 text-xl text-secondary-foreground shrink-0"></i>
                                        <div class="min-w-0">
                                            <span class="font-semibold text-mono truncate block">{{ $instance->name }}</span>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-xs font-mono text-secondary-foreground">{{ $instance->slug }}</span>
                                                <span class="kt-badge kt-badge-outline {{ $statusClass }} text-xs">{{ $instance->status }}</span>
                                                @if($instance->domain)
                                                    <span class="text-xs text-secondary-foreground font-mono">{{ $instance->domain }}</span>
                                                @endif
                                                @if($instance->template)
                                                    <span class="kt-badge kt-badge-outline kt-badge-info text-xs">{{ $instance->template->name }}</span>
                                                @else
                                                    <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">Custom</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <a href="{{ route('client.docker.show', $instance) }}"
                                           class="kt-btn kt-btn-outline kt-btn-sm">
                                            <i class="ki-filled ki-setting-2 text-xs"></i>
                                            Gestionar
                                        </a>

                                        {{-- Start/Stop rápido --}}
                                        @if($instance->status === 'running' || $instance->status === 'partial')
                                            <form action="{{ route('client.docker.stop', $instance) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline" title="Detener">
                                                    <i class="ki-filled ki-stop text-xs"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('client.docker.start', $instance) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline" title="Arrancar">
                                                    <i class="ki-filled ki-triangle text-xs"></i>
                                                </button>
                                            </form>
                                        @endif

                                        {{-- Eliminar --}}
                                        <form action="{{ route('client.docker.destroy', $instance) }}" method="POST"
                                              onsubmit="return confirm('Eliminar {{ addslashes($instance->name) }} y todos sus datos. Esto no se puede deshacer.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="kt-btn kt-btn-icon kt-btn-sm" title="Eliminar">
                                                <i class="ki-filled ki-trash text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif

                </div>
            </div>
        </main>
        @include('layouts.partials.client.footer')
    </div>
</div>
@endsection
