@extends('layouts.client')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<div class="max-w-3xl mx-auto p-8">
        <div class="mb-8">
            <a href="{{ route('client.databases.index') }}" class="text-gray-400 hover:text-white">Volver a bases de datos</a>
            <h1 class="text-3xl font-bold text-white mt-4">Nueva Base de Datos</h1>
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

        <form action="{{ route('client.databases.store') }}" method="POST" class="space-y-6 bg-gray-800 rounded-xl border border-gray-700 p-8">
            @csrf

            <div>
                <label class="block text-sm text-gray-300 mb-2">Nombre de Base de Datos</label>
                <input type="text" name="name" value="{{ old('name') }}" required pattern="[A-Za-z0-9_]+"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white"
                    placeholder="app_db">
                <p class="mt-2 text-xs text-gray-500">Usa solo letras, números y guion bajo.</p>
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-2">Usuario</label>
                <input type="text" name="username" value="{{ old('username') }}" required pattern="[A-Za-z0-9_]+"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white"
                    placeholder="app_user">
                <p class="mt-2 text-xs text-gray-500">El usuario se creará dentro de MariaDB/MySQL.</p>
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-2">Contraseña de la base de datos</label>
                <input type="password" name="password" required minlength="16" autocomplete="new-password"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white"
                    placeholder="Mínimo 16 caracteres">
                <p class="mt-2 text-xs text-gray-500">XPanel no mostrará esta contraseña después de crearla.</p>
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-2">Engine</label>
                <select name="engine" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white">
                    <option value="mariadb">MariaDB</option>
                    <option value="mysql">MySQL</option>
                    <option value="postgresql" disabled>PostgreSQL próximamente</option>
                </select>
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-2">Sitio Asociado (Opcional)</label>
                <select name="site_id" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white">
                    <option value="">Sin sitio</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected(old('site_id') == $site->id)>{{ $site->domain }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 rounded-lg py-3 font-semibold text-white">
                Crear Base de Datos
            </button>
        </form>
    </div>
                    </div>
                </div>
            </main>

            @include('layouts.partials.client.footer')
        </div>
    </div>
@endsection
