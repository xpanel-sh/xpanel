@extends('layouts.app')

@section('content')
    <section class="space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <a href="{{ route('admin.clients.index') }}" class="text-sm text-gray-400 hover:text-white">Volver a clientes</a>
                <h1 class="mt-3 text-3xl font-black">{{ $tenant->name }}</h1>
                <p class="mt-2 text-gray-400">{{ $tenant->domain }} · {{ $tenant->user?->email }}</p>
            </div>
            <form action="{{ route('admin.clients.toggle-status', $tenant) }}" method="POST">
                @csrf
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.clients.edit', $tenant) }}" class="rounded-xl bg-white px-5 py-3 text-sm font-bold text-black transition hover:bg-gray-200">
                        Editar cliente
                    </a>
                    <button class="rounded-xl border border-white/10 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">
                        {{ $tenant->status === 'active' ? 'Suspender cliente' : 'Activar cliente' }}
                    </button>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-4">
            <div class="rounded-2xl border border-white/10 bg-black p-5">
                <div class="text-xs uppercase tracking-widest text-gray-500">Estado</div>
                <div class="mt-2 text-2xl font-black {{ $tenant->status === 'active' ? 'text-emerald-300' : 'text-red-300' }}">
                    {{ ucfirst($tenant->status) }}
                </div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-black p-5">
                <div class="text-xs uppercase tracking-widest text-gray-500">Plan</div>
                <div class="mt-2 text-2xl font-black">{{ $tenant->plan?->name ?? 'Sin plan' }}</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-black p-5">
                <div class="text-xs uppercase tracking-widest text-gray-500">Sitios</div>
                <div class="mt-2 text-2xl font-black">{{ $tenant->sites->count() }}</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-black p-5">
                <div class="text-xs uppercase tracking-widest text-gray-500">Dueño</div>
                <div class="mt-2 truncate text-lg font-black">{{ $tenant->user?->name ?? 'Sin usuario' }}</div>
            </div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/[0.03]">
            <div class="border-b border-white/10 p-6">
                <h2 class="text-xl font-bold">Sitios del cliente</h2>
                <p class="mt-1 text-sm text-gray-500">Vista rápida de los proyectos asignados a esta cuenta.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left">
                    <thead class="text-xs uppercase tracking-widest text-gray-500">
                        <tr>
                            <th class="px-6 py-4">Dominio</th>
                            <th class="px-6 py-4">Tipo</th>
                            <th class="px-6 py-4">PHP</th>
                            <th class="px-6 py-4">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($tenant->sites as $site)
                            <tr>
                                <td class="px-6 py-4 font-semibold text-white">{{ $site->domain }}</td>
                                <td class="px-6 py-4 text-gray-400">{{ strtoupper($site->project_type) }}</td>
                                <td class="px-6 py-4 text-gray-400">{{ $site->php_version }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-300">
                                        {{ strtoupper($site->status ?? 'active') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    Este cliente aún no tiene sitios.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
