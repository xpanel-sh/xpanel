@extends('layouts.admin')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<div class="max-w-4xl mx-auto p-8">
        <div class="mb-8">
            <a href="{{ route('admin.clients.index') }}"
                class="text-gray-400 hover:text-white mb-4 inline-flex items-center gap-2 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
                Volver a la lista
            </a>
            <h1 class="text-3xl font-bold text-white">Nuevo Cliente</h1>
            <p class="text-gray-400 mt-2">Crea una nueva cuenta de cliente y su acceso al panel.</p>
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

        <form action="{{ route('admin.clients.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-800 p-8 rounded-xl border border-gray-700 shadow-xl">
                <!-- Company Data -->
                <div class="md:col-span-2">
                    <h3 class="text-blue-500 font-bold border-b border-gray-700 mb-4 pb-2 uppercase text-xs">Información del
                        Cliente</h3>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Nombre de la Empresa</label>
                    <input type="text" name="company_name" value="{{ old('company_name') }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Mi Cliente S.L." required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Dominio del Panel</label>
                    <input type="text" name="domain" value="{{ old('domain') }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="cliente.com" required>
                </div>

                <div class="md:col-span-2 mt-4">
                    <h3 class="text-blue-500 font-bold border-b border-gray-700 mb-4 pb-2 uppercase text-xs">Plan asignado</h3>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Plan de Hosting</label>
                    <select name="plan_id"
                        class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Sin plan por ahora</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>
                                {{ $plan->name }} - {{ $plan->max_sites }} sitios / {{ $plan->max_databases }} DB / {{ number_format($plan->storage_mb / 1024, 1) }} GB
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- User Data -->
                <div class="md:col-span-2 mt-4">
                    <h3 class="text-blue-500 font-bold border-b border-gray-700 mb-4 pb-2 uppercase text-xs">Datos de Acceso
                        (Usuario Principal)</h3>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Nombre Completo</label>
                    <input type="text" name="owner_name" value="{{ old('owner_name') }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Juan Pérez" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Correo Electrónico</label>
                    <input type="email" name="owner_email" value="{{ old('owner_email') }}"
                        class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="email@cliente.com" required>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Contraseña Temporal</label>
                    <div class="relative">
                        <input type="text" name="owner_password" id="pass" value="{{ Str::random(12) }}"
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                        <button type="button"
                            onclick="document.getElementById('pass').value = Math.random().toString(36).slice(-12)"
                            class="absolute right-3 top-3 text-xs bg-gray-700 px-2 py-1 rounded hover:bg-gray-600 text-gray-300">Generar</button>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Asegúrate de copiar esta contraseña para dársela al cliente.</p>
                </div>

                <div class="md:col-span-2 pt-6">
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white font-bold py-4 px-8 rounded-lg shadow-lg transition duration-200 transform hover:-translate-y-0.5">
                        Crear Cliente y Acceso
                    </button>
                </div>
            </div>
        </form>
    </div>
                    </div>
                </div>
            </main>

            @include('layouts.partials.admin.footer')
        </div>
    </div>
@endsection
