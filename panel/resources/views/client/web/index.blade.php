@extends('layouts.app')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-gray-500">Panel Cliente</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight">Mis Sitios Web</h1>
                <p class="text-gray-400">Administra tus dominios y aplicaciones web.</p>
            </div>
            <a href="{{ route('client.sites.create') }}"
                class="flex items-center gap-2 rounded-xl bg-white px-6 py-3 font-bold text-black transition hover:bg-gray-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nuevo Sitio
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($sites as $site)
                @php
                    $status = $site->status ?? 'active';
                    $statusClass = str_contains($status, 'error')
                        ? 'border-red-500/30 bg-red-500/10 text-red-200'
                        : ($status === 'provisioning'
                            ? 'border-amber-500/30 bg-amber-500/10 text-amber-200'
                            : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200');
                @endphp
                <div
                    class="group relative overflow-hidden rounded-3xl border border-white/10 bg-white/[0.03] p-6 transition hover:border-white/20 hover:bg-white/[0.06]">
                    <div class="absolute -right-16 -top-16 h-36 w-36 rounded-full bg-white/5 blur-2xl transition group-hover:bg-white/10"></div>
                    <div class="flex items-center gap-4 mb-4">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/10 bg-black text-xl font-black uppercase text-white">
                            {{ substr($site->domain, 0, 1) }}</div>
                        <div>
                            <h3 class="font-bold text-lg text-white">{{ $site->domain }}</h3>
                            <span class="mt-2 inline-flex rounded-full border px-3 py-1 text-xs font-bold uppercase {{ $statusClass }}">{{ $status }}</span>
                        </div>
                    </div>

                    <div class="space-y-3 text-sm text-gray-400">
                        <div class="flex justify-between pb-2 border-b border-gray-700/50">
                            <span>Tipo</span>
                            <span class="text-gray-300 uppercase">{{ $site->project_type }}</span>
                        </div>
                        <div class="flex justify-between pb-2 border-b border-gray-700/50">
                            <span>PHP Version</span>
                            <span class="text-gray-300">{{ $site->php_version ?? 'N/A' }}</span>
                        </div>
                    </div>

                    <div class="mt-6 flex gap-2">
                        <form action="{{ route('client.sites.restart', $site) }}" method="POST" class="flex-1">
                            @csrf
                            <button class="w-full py-2 text-center bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm font-medium transition">Reiniciar</button>
                        </form>
                        <form action="{{ route('client.sites.destroy', $site) }}" method="POST" onsubmit="return confirm('¿Eliminar sitio?')">
                            @csrf
                            @method('DELETE')
                            <button class="px-3 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-300 rounded-lg transition">Eliminar</button>
                        </form>
                        <a href="http://{{ $site->domain }}" target="_blank"
                            class="px-3 py-2 bg-gray-700/50 hover:bg-gray-600 text-gray-300 hover:text-white rounded-lg transition"><svg
                                class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg></a>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-gray-500 bg-gray-800/50 rounded-xl border border-dashed border-gray-700">
                    No tienes sitios web registrados.
                </div>
            @endforelse
        </div>
    </div>
@endsection
