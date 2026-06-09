@extends('layouts.app')

@section('content')
    <section class="space-y-8">
        <div class="rounded-3xl border border-white/10 bg-white/[0.03] p-6 md:p-8">
            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-gray-500">Panel Cliente</p>
            <h1 class="mt-3 text-3xl font-black tracking-tight md:text-5xl">
                {{ $tenant->name ?? 'Mi Cuenta' }}
            </h1>
            <p class="mt-4 max-w-3xl text-gray-400">
                Administra tus sitios, bases de datos y servicios de hosting desde una experiencia separada del Admin global.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
            <div class="rounded-2xl border border-white/10 bg-black p-6">
                <div class="text-sm uppercase tracking-widest text-gray-500">Servicio</div>
                <div class="mt-3 text-3xl font-black text-emerald-300">Activo</div>
                <p class="mt-2 text-sm text-gray-500">Cuenta operativa.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-black p-6">
                <div class="text-sm uppercase tracking-widest text-gray-500">Sitios</div>
                <div class="mt-3 text-3xl font-black text-white">{{ $siteCount }}</div>
                <p class="mt-2 text-sm text-gray-500">
                    Límite: {{ $tenant->plan?->max_sites ?? 'sin asignar' }}
                </p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-black p-6">
                <div class="text-sm uppercase tracking-widest text-gray-500">Plan</div>
                <div class="mt-3 text-3xl font-black text-white">{{ $tenant->plan?->name ?? 'Sin plan' }}</div>
                <p class="mt-2 text-sm text-gray-500">
                    DB: {{ $databaseCount }} / {{ $tenant->plan?->max_databases ?? 'sin asignar' }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-6">
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                <div class="text-xs uppercase tracking-widest text-gray-500">Almacenamiento</div>
                <div class="mt-2 text-2xl font-black">{{ $tenant->plan ? number_format($tenant->plan->storage_mb / 1024, 1) . ' GB' : 'Sin plan' }}</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                <div class="text-xs uppercase tracking-widest text-gray-500">Transferencia</div>
                <div class="mt-2 text-2xl font-black">{{ $tenant->plan ? $tenant->plan->bandwidth_gb . ' GB' : 'Sin plan' }}</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                <div class="text-xs uppercase tracking-widest text-gray-500">Correos</div>
                <div class="mt-2 text-2xl font-black">{{ $emailCount }} / {{ $tenant->plan?->email_accounts ?? 'Sin plan' }}</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                <div class="text-xs uppercase tracking-widest text-gray-500">Dominios</div>
                <div class="mt-2 text-2xl font-black">{{ $domainCount }}</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                <div class="text-xs uppercase tracking-widest text-gray-500">Bases de datos</div>
                <div class="mt-2 text-2xl font-black">{{ $databaseCount }}</div>
            </div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/[0.03]">
            <div class="flex flex-col gap-3 border-b border-white/10 p-6 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-bold">Sitios recientes</h2>
                    <p class="mt-1 text-sm text-gray-500">Tus dominios y aplicaciones creadas en XPanel.</p>
                </div>
                <a href="{{ route('client.sites.create') }}" class="rounded-xl bg-white px-5 py-3 text-sm font-bold text-black transition hover:bg-gray-200">
                    Crear sitio
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left">
                    <thead class="text-xs uppercase tracking-widest text-gray-500">
                        <tr>
                            <th class="px-6 py-4">Dominio</th>
                            <th class="px-6 py-4">Tipo</th>
                            <th class="px-6 py-4">Estado</th>
                            <th class="px-6 py-4 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($sites as $site)
                            <tr class="transition hover:bg-white/[0.03]">
                                <td class="px-6 py-4 font-semibold text-white">{{ $site->domain }}</td>
                                <td class="px-6 py-4 text-sm uppercase text-gray-400">{{ $site->project_type }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-300">
                                        {{ strtoupper($site->status ?? 'active') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('client.sites.index') }}" class="text-sm font-semibold text-white hover:text-gray-300">Gestionar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    Todavía no tienes sitios web. Crea el primero para empezar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
