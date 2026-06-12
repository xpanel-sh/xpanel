@extends('layouts.client')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<div class="space-y-6" x-data="{ search: '{{ request('search') }}' }">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-gray-500">Panel Cliente</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight">Mis Bases de Datos</h1>
                <p class="text-gray-400">Gestiona las credenciales de tus bases de datos por sitio.</p>
            </div>
            <a href="{{ route('client.databases.create') }}"
                class="flex items-center gap-2 rounded-xl bg-gray-900 dark:bg-white px-6 py-3 font-bold text-white dark:text-black transition hover:bg-gray-700 dark:hover:bg-gray-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nueva Base de Datos
            </a>
        </div>

        {{-- Buscador --}}
        <form method="GET" action="{{ route('client.databases.index') }}" class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Buscar por nombre..."
                class="flex-1 rounded-xl border border-white/10 bg-white/[0.03] px-4 py-2.5 text-sm text-white placeholder-gray-500 outline-none focus:border-white/20 dark:bg-white/[0.03] dark:text-white">
            <button type="submit" class="rounded-xl border border-white/10 px-5 py-2.5 text-sm text-gray-300 hover:bg-white/5 transition">Buscar</button>
            @if(request('search'))
                <a href="{{ route('client.databases.index') }}" class="rounded-xl border border-white/10 px-4 py-2.5 text-sm text-gray-400 hover:bg-white/5 transition">✕ Limpiar</a>
            @endif
        </form>

        <div class="rounded-2xl border border-white/10 bg-white/[0.03] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left">
                    <thead class="text-xs uppercase tracking-widest text-gray-500 border-b border-white/10">
                        <tr>
                            <th class="px-6 py-4">Base de datos</th>
                            <th class="px-6 py-4">Usuario</th>
                            <th class="px-6 py-4">Engine</th>
                            <th class="px-6 py-4">Sitio</th>
                            <th class="px-6 py-4">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($databases as $database)
                            <tr class="hover:bg-white/[0.02] transition">
                                <td class="px-6 py-4 font-semibold text-white">{{ $database->name }}</td>
                                <td class="px-6 py-4 text-gray-400">{{ $database->username }}</td>
                                <td class="px-6 py-4 text-gray-400 uppercase text-xs font-bold">{{ $database->engine }}</td>
                                <td class="px-6 py-4 text-gray-500 text-sm">{{ $database->site?->domain ?? 'Sin sitio' }}</td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusClass = str_contains($database->status, 'error')
                                            ? 'bg-red-500/10 text-red-300 border-red-500/30'
                                            : ($database->status === 'provisioning'
                                                ? 'bg-amber-500/10 text-amber-300 border-amber-500/30'
                                                : 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30');
                                    @endphp
                                    <span class="rounded-full border px-3 py-1 text-xs font-bold uppercase {{ $statusClass }}">{{ $database->status }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <x-confirm-modal
                                        action="{{ route('client.databases.destroy', $database) }}"
                                        title="Eliminar base de datos"
                                        message="Se eliminará '{{ $database->name }}' y todos sus datos. Esta acción no se puede deshacer."
                                        btnText="Eliminar BD"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    @if(request('search'))
                                        No se encontraron bases de datos que coincidan con "{{ request('search') }}".
                                    @else
                                        No tienes bases de datos registradas.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-white/10">
                {{ $databases->appends(['search' => request('search')])->links() }}
            </div>
        </div>
    </div>
                    </div>
                </div>
            </main>

            @include('layouts.partials.client.footer')
        </div>
    </div>
@endsection
