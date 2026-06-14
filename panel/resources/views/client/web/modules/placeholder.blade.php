<section class="grid gap-5 lg:gap-7.5">
    <div class="kt-card">
        <div class="kt-card-content p-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="min-w-0">
                <div class="text-xs uppercase tracking-wide text-muted-foreground">
                    {{ $activeModule['parent_label'] ?? 'Modulo del sitio' }}
                </div>
                <h2 class="mt-1 text-xl font-semibold text-mono truncate">{{ $activeModule['label'] }}</h2>
                <p class="mt-1 text-sm text-secondary-foreground">
                    Ruta activa: /client/websites/{{ $site->domain }}/{{ $activePath }}
                </p>
            </div>

            @if(($activeModule['path'] ?? null) === 'files/file-manager')
                <a class="kt-btn kt-btn-primary" href="{{ $activeModule['primary_url'] ?? route('client.files.index', ['domain' => $site->domain]) }}">
                    <i class="ki-filled ki-folder"></i>
                    Abrir administrador
                </a>
            @else
                <span class="kt-badge kt-badge-outline kt-badge-warning">Vista preparada</span>
            @endif
        </div>
    </div>

    <div class="kt-card">
        <div class="kt-card-content p-8 text-center">
            <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-accent">
                <i class="ki-filled {{ $activeModule['icon'] ?? $activeModule['parent_icon'] ?? 'ki-element-11' }} text-xl text-primary"></i>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-mono">{{ $activeModule['label'] }}</h3>
            <p class="mx-auto mt-2 max-w-xl text-sm text-secondary-foreground">
                Esta seccion ya tiene ruta y menu conectado. La vista final se implementara encima de este placeholder.
            </p>
        </div>
    </div>
</section>
