@extends('layouts.client')

@php
    $dbPrefix = ($site->tenant?->code ?: 'X000000') . '_';
    $nameSuffix = old('name_suffix', str_starts_with((string) old('name'), $dbPrefix) ? substr((string) old('name'), strlen($dbPrefix)) : '');
    $usernameSuffix = old('username_suffix', str_starts_with((string) old('username'), $dbPrefix) ? substr((string) old('username'), strlen($dbPrefix)) : '');
    $suffixMaxLength = max(1, 32 - strlen($dbPrefix));
@endphp

@section('content')
    <div class="flex grow rounded-xl bg-background border border-input lg:ms-(--sidebar-width) mt-0 lg:mt-(--header-height) m-5">
        <div class="flex flex-col grow kt-scrollable-y-auto lg:[--kt-scrollbar-width:auto] pt-5" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h1 class="text-2xl font-semibold text-mono">Administracion</h1>
                                <p class="mt-1 text-sm text-secondary-foreground">Crea y administra bases de datos MySQL desde {{ $site->domain }}.</p>
                            </div>
                            <a class="kt-btn kt-btn-outline" href="{{ route('client.websites.show', ['domain' => $site->domain]) }}">
                                Panel del sitio
                            </a>
                        </div>

                        <form action="{{ route('client.databases.store') }}" method="POST" class="kt-card overflow-hidden">
                            @csrf
                            <input type="hidden" name="site_id" value="{{ $site->id }}">
                            <input type="hidden" name="engine" value="mysql">

                            <div class="kt-card-header min-h-16">
                                <h2 class="kt-card-title flex items-center gap-3 text-xl">
                                    <i class="ki-filled ki-plus text-2xl text-secondary-foreground"></i>
                                    Crear nueva base de datos MySQL y usuario de base de datos
                                </h2>
                            </div>

                            <div class="kt-card-content grid gap-7 p-6">
                                @if($errors->any())
                                    <div class="rounded-xl border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                                        {{ $errors->first() }}
                                    </div>
                                @endif

                                <div class="grid gap-2">
                                    <label class="text-sm font-semibold text-mono" for="name_suffix">Nombre de la base de datos MySQL</label>
                                    <div class="grid overflow-hidden rounded-xl border border-input bg-background md:grid-cols-[minmax(170px,300px)_minmax(0,1fr)]">
                                        <div class="flex items-center bg-muted px-4 text-sm text-secondary-foreground">{{ $dbPrefix }}</div>
                                        <input id="name_suffix" class="kt-input h-12 rounded-none border-0" type="text" name="name_suffix" value="{{ $nameSuffix }}" required pattern="[A-Za-z0-9_]+" maxlength="{{ $suffixMaxLength }}" placeholder="Nombre de la base de datos">
                                    </div>
                                    <p class="kt-form-description">Nombre final: {{ $dbPrefix }}<span id="db_name_preview">{{ $nameSuffix ?: 'nombre' }}</span></p>
                                </div>

                                <div class="grid gap-2">
                                    <label class="text-sm font-semibold text-mono" for="username_suffix">Nombre de usuario MySQL</label>
                                    <div class="grid overflow-hidden rounded-xl border border-input bg-background md:grid-cols-[minmax(170px,300px)_minmax(0,1fr)]">
                                        <div class="flex items-center bg-muted px-4 text-sm text-secondary-foreground">{{ $dbPrefix }}</div>
                                        <input id="username_suffix" class="kt-input h-12 rounded-none border-0" type="text" name="username_suffix" value="{{ $usernameSuffix }}" required pattern="[A-Za-z0-9_]+" maxlength="{{ $suffixMaxLength }}" placeholder="Nombre de usuario">
                                    </div>
                                    <p class="kt-form-description">Usuario final: {{ $dbPrefix }}<span id="db_user_preview">{{ $usernameSuffix ?: 'usuario' }}</span></p>
                                </div>

                                <div class="grid gap-2">
                                    <label class="text-sm font-semibold text-mono" for="db_password">Contrasena</label>
                                    <div class="flex items-center rounded-xl border border-input bg-background">
                                        <input id="db_password" class="kt-input h-12 rounded-none border-0" type="password" name="password" required minlength="16" maxlength="128" autocomplete="new-password" placeholder="Contrasena">
                                        <button class="kt-btn kt-btn-icon kt-btn-ghost me-1" type="button" data-password-toggle title="Mostrar u ocultar">
                                            <i class="ki-filled ki-eye"></i>
                                        </button>
                                    </div>
                                    <p class="kt-form-description">XPanel no mostrara esta contrasena despues de crearla.</p>
                                </div>

                                <div>
                                    <button class="kt-btn kt-btn-primary" type="submit">
                                        <i class="ki-filled ki-check"></i>
                                        Crear
                                    </button>
                                </div>
                            </div>
                        </form>

                        <section class="grid gap-4">
                            <div>
                                <h2 class="text-xl font-semibold text-mono">Lista de bases de datos y usuarios actuales de MySQL</h2>
                                <p class="mt-1 text-sm text-secondary-foreground">Bases creadas desde este panel.</p>
                            </div>

                            <div class="kt-card overflow-visible">
                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-[860px] text-left">
                                        <thead class="border-b border-border text-xs font-semibold uppercase text-secondary-foreground">
                                            <tr>
                                                <th class="px-5 py-4">Base de datos MySQL</th>
                                                <th class="px-5 py-4">Usuario MySQL</th>
                                                <th class="px-5 py-4">Creado el</th>
                                                <th class="px-5 py-4">Sitio web</th>
                                                <th class="px-5 py-4 text-right">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-border">
                                            @forelse($databases as $database)
                                                @php
                                                    $statusClass = str_contains($database->status, 'error')
                                                        ? 'kt-badge-danger'
                                                        : ($database->status === 'provisioning' ? 'kt-badge-warning' : 'kt-badge-success');
                                                @endphp
                                                <tr class="align-middle">
                                                    <td class="px-5 py-4">
                                                        <div class="font-semibold text-mono">{{ $database->name }}</div>
                                                        <div class="mt-1 text-xs text-secondary-foreground">{{ strtoupper($database->engine) }} · Estado <span class="kt-badge kt-badge-outline {{ $statusClass }}">{{ $database->status }}</span></div>
                                                    </td>
                                                    <td class="px-5 py-4 text-sm text-mono">{{ $database->username }}</td>
                                                    <td class="px-5 py-4 text-sm text-secondary-foreground">{{ $database->created_at?->format('Y-m-d') ?? 'N/A' }}</td>
                                                    <td class="px-5 py-4 text-sm text-mono">{{ $database->site?->domain ?? $site->domain }}</td>
                                                    <td class="px-5 py-4">
                                                        <div class="flex items-center justify-end gap-2">
                                                            <a class="kt-btn kt-btn-outline kt-btn-sm" href="{{ route('client.databases.phpmyadmin', $database) }}" target="_blank" rel="noopener">
                                                                Acceder a phpMyAdmin
                                                            </a>

                                                            <details class="relative">
                                                                <summary class="kt-btn kt-btn-icon kt-btn-ghost kt-btn-sm list-none cursor-pointer" title="Opciones">
                                                                    <i class="ki-filled ki-dots-vertical"></i>
                                                                </summary>
                                                                <div class="absolute end-0 z-20 mt-2 w-52 rounded-xl border border-border bg-background p-1.5 shadow-lg">
                                                                    <button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-mono hover:bg-muted" type="button">
                                                                        <i class="ki-filled ki-key text-secondary-foreground"></i>
                                                                        Cambiar contrasena
                                                                    </button>
                                                                    <form action="{{ route('client.databases.destroy', $database) }}" method="POST" onsubmit="return confirm('Se eliminara {{ $database->name }} y todos sus datos. Esta accion no se puede deshacer.');">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-destructive hover:bg-destructive/10" type="submit">
                                                                            <i class="ki-filled ki-trash"></i>
                                                                            Eliminar
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </details>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="px-5 py-12 text-center">
                                                        <div class="mx-auto flex size-12 items-center justify-center rounded-xl bg-muted">
                                                            <i class="ki-filled ki-data text-xl text-secondary-foreground"></i>
                                                        </div>
                                                        <div class="mt-4 text-sm font-semibold text-mono">Aun no hay bases de datos</div>
                                                        <div class="mt-1 text-sm text-secondary-foreground">Crea la primera base MySQL para este sitio.</div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </main>

            @include('layouts.partials.client.footer')
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const syncPreview = (inputId, previewId, fallback) => {
                const input = document.getElementById(inputId);
                const preview = document.getElementById(previewId);

                input?.addEventListener('input', () => {
                    preview.textContent = input.value.trim() || fallback;
                });
            };

            syncPreview('name_suffix', 'db_name_preview', 'nombre');
            syncPreview('username_suffix', 'db_user_preview', 'usuario');

            document.querySelector('[data-password-toggle]')?.addEventListener('click', () => {
                const input = document.getElementById('db_password');
                if (!input) return;
                input.type = input.type === 'password' ? 'text' : 'password';
            });
        })();
    </script>
@endpush
