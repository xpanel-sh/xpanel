@extends('layouts.admin')

@section('content')
    <section class="mx-auto max-w-4xl">
        <div class="mb-8">
            <a href="{{ route('admin.clients.show', $tenant) }}" class="text-sm text-gray-400 hover:text-white">Volver al cliente</a>
            <h1 class="mt-4 text-3xl font-black">Editar Cliente</h1>
            <p class="mt-2 text-gray-400">Actualiza datos de cuenta, plan, estado y acceso principal.</p>
        </div>

        <form action="{{ route('admin.clients.update', $tenant) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6 md:grid-cols-2">
                <div class="md:col-span-2">
                    <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500">Cliente</h2>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-300">Nombre / Empresa</label>
                    <input type="text" name="company_name" value="{{ old('company_name', $tenant->name) }}" required
                        class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-300">Dominio principal</label>
                    <input type="text" name="domain" value="{{ old('domain', $tenant->domain) }}" required
                        class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-300">Estado</label>
                    <select name="status" class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white">
                        <option value="active" @selected(old('status', $tenant->status) === 'active')>Activo</option>
                        <option value="suspended" @selected(old('status', $tenant->status) === 'suspended')>Suspendido</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-300">Plan</label>
                    <select name="plan_id" class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white">
                        <option value="">Sin plan</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" @selected(old('plan_id', $tenant->plan_id) == $plan->id)>
                                {{ $plan->name }} - {{ $plan->max_sites }} sitios / {{ $plan->max_databases }} DB
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2 mt-4">
                    <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500">Usuario principal</h2>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-300">Nombre</label>
                    <input type="text" name="owner_name" value="{{ old('owner_name', $tenant->user?->name) }}" required
                        class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-300">Email</label>
                    <input type="email" name="owner_email" value="{{ old('owner_email', $tenant->user?->email) }}" required
                        class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white">
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-gray-300">Nueva contraseña opcional</label>
                    <input type="text" name="owner_password" value="{{ old('owner_password') }}"
                        class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white"
                        placeholder="Dejar vacío para mantener la actual">
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <button class="rounded-xl bg-white px-6 py-3 font-bold text-black transition hover:bg-gray-200">
                    Guardar cambios
                </button>
                <a href="{{ route('admin.clients.show', $tenant) }}" class="rounded-xl border border-white/10 px-6 py-3 text-center font-bold text-white transition hover:bg-white/10">
                    Cancelar
                </a>
            </div>
        </form>
    </section>
@endsection
