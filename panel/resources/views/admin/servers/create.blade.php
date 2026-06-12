@extends('layouts.admin')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<div class="max-w-3xl mx-auto p-8">
        <div class="mb-8">
            <a href="{{ route('admin.servers.index') }}" class="text-gray-400 hover:text-white">Volver a servidores</a>
            <h1 class="text-3xl font-bold text-white mt-4">Agregar Servidor Conectado</h1>
            <p class="mt-2 text-gray-400">Registra un servidor donde el agente XPanel podrá ejecutar tareas seguras.</p>
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

        <form action="{{ route('admin.servers.store') }}" method="POST" class="space-y-6 bg-gray-800 rounded-xl border border-gray-700 p-8">
            @csrf

            <div>
                <label class="block text-sm text-gray-300 mb-2">Nombre</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white" placeholder="Servidor LATAM 1">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm text-gray-300 mb-2">IP</label>
                    <input type="text" name="ip_address" value="{{ old('ip_address') }}" required class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white" placeholder="10.0.0.10">
                </div>
                <div>
                    <label class="block text-sm text-gray-300 mb-2">Puerto del Agente</label>
                    <input type="number" name="port" value="{{ old('port', 7070) }}" required class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white">
                </div>
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-2">Token (opcional)</label>
                <input type="text" name="auth_token" value="{{ old('auth_token') }}" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white" placeholder="Si lo dejas vacio se genera uno">
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 rounded-lg py-3 font-semibold text-white">
                Agregar Servidor
            </button>
        </form>
    </div>
                    </div>
                </div>
            </main>

            @include('layouts.partials.admin.footer')
        </div>
    </div>
@endsection
