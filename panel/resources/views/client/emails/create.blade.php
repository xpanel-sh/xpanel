@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-3xl">
        <div class="mb-8">
            <a href="{{ route('client.emails.index') }}" class="text-sm text-gray-400 hover:text-white">Volver a correos</a>
            <h1 class="mt-4 text-3xl font-black">Crear Correo</h1>
            <p class="mt-2 text-gray-400">Crea una cuenta de correo usando uno de tus dominios registrados.</p>
        </div>

        <form action="{{ route('client.emails.store') }}" method="POST" class="space-y-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            @csrf

            <div class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_1fr]">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-300">Usuario</label>
                    <input type="text" name="local_part" value="{{ old('local_part') }}" required
                        class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white"
                        placeholder="ventas">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-300">Dominio</label>
                    <select name="domain_id" class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white" required>
                        <option value="">Seleccionar dominio</option>
                        @foreach($domains as $domain)
                            <option value="{{ $domain->id }}" @selected(old('domain_id') == $domain->id)>@{{ $domain->domain }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-300">Contraseña</label>
                <input type="password" name="password" required minlength="12" autocomplete="new-password"
                    class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white"
                    placeholder="Mínimo 12 caracteres">
                <p class="mt-2 text-xs text-gray-500">XPanel no mostrará esta contraseña después de crearla.</p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-300">Cuota MB</label>
                <input type="number" name="quota_mb" value="{{ old('quota_mb', 1024) }}" min="128" max="102400" required
                    class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white">
            </div>

            <div class="rounded-xl border border-yellow-500/20 bg-yellow-500/10 p-4 text-sm text-yellow-100">
                La cuenta queda registrada en XPanel y el agente actualiza los artefactos reales del servicio de correo.
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <button class="rounded-xl bg-white px-6 py-3 font-bold text-black transition hover:bg-gray-200">Crear correo</button>
                <a href="{{ route('client.emails.index') }}" class="rounded-xl border border-white/10 px-6 py-3 text-center font-bold text-white transition hover:bg-white/10">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
