@extends('layouts.client')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<section class="grid gap-5 lg:gap-7.5 max-w-4xl">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="font-medium text-lg text-mono">Nueva base de datos</h1>
                <div class="flex items-center gap-1 text-sm">
                    <a class="text-secondary-foreground hover:text-primary" href="{{ route('client.databases.index') }}">Bases de datos</a>
                    <span class="text-muted-foreground">/</span>
                    <span class="text-mono">Nueva</span>
                </div>
            </div>
            <a href="{{ route('client.databases.index') }}" class="kt-btn kt-btn-outline kt-btn-sm">Volver</a>
        </div>

        @if($errors->any())
            <div class="mb-6 p-4 bg-red-500/10 border border-red-500/50 rounded-lg text-red-400">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('client.databases.store') }}" method="POST" class="kt-card">
            @csrf

            <div class="kt-card-header">
                <h3 class="kt-card-title">Credenciales</h3>
            </div>
            <div class="kt-card-content grid gap-5">
                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                    <label class="kt-form-label max-w-56">Base de datos</label>
                    <div class="grow grid gap-1.5">
                        <input class="kt-input" type="text" name="name" value="{{ old('name') }}" required pattern="[A-Za-z0-9_]+" placeholder="app_db">
                        <p class="kt-form-description">Usa solo letras, numeros y guion bajo.</p>
                    </div>
                </div>
                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                    <label class="kt-form-label max-w-56">Usuario</label>
                    <div class="grow grid gap-1.5">
                        <input class="kt-input" type="text" name="username" value="{{ old('username') }}" required pattern="[A-Za-z0-9_]+" placeholder="app_user">
                        <p class="kt-form-description">El usuario se creara dentro del motor seleccionado.</p>
                    </div>
                </div>
                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                    <label class="kt-form-label max-w-56">Contrasena</label>
                    <div class="grow grid gap-1.5">
                        <input class="kt-input" type="password" name="password" required minlength="16" autocomplete="new-password" placeholder="Minimo 16 caracteres">
                        <p class="kt-form-description">XPanel no mostrara esta contrasena despues de crearla.</p>
                    </div>
                </div>
                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                    <label class="kt-form-label max-w-56">Engine</label>
                    <select name="engine" class="kt-select">
                        <option value="mariadb">MariaDB</option>
                        <option value="mysql">MySQL</option>
                        <option value="postgresql" disabled>PostgreSQL proximamente</option>
                    </select>
                </div>
                <div class="flex items-baseline flex-wrap lg:flex-nowrap gap-2.5">
                    <label class="kt-form-label max-w-56">Sitio asociado</label>
                    <select name="site_id" class="kt-select">
                        <option value="">Sin sitio</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" @selected(old('site_id') == $site->id)>{{ $site->domain }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-2.5">
                    <a href="{{ route('client.databases.index') }}" class="kt-btn kt-btn-outline">Cancelar</a>
                    <button type="submit" class="kt-btn kt-btn-primary">Crear base de datos</button>
                </div>
            </div>
        </form>
    </section>
                    </div>
                </div>
            </main>

            @include('layouts.partials.client.footer')
        </div>
    </div>
@endsection
