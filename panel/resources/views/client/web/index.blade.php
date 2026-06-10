@extends('layouts.app')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-gray-500">Panel Cliente</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight">Mis Sitios Web</h1>
                <p class="text-gray-400 dark:text-gray-400">Administra tus dominios y aplicaciones web.</p>
            </div>
            <a href="{{ route('client.sites.create') }}"
                class="flex items-center gap-2 rounded-xl bg-gray-900 dark:bg-white px-6 py-3 font-bold text-white dark:text-black transition hover:bg-gray-700 dark:hover:bg-gray-200">
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
                <div class="group relative overflow-hidden rounded-3xl border border-white/10 bg-white/[0.03] p-6 transition hover:border-white/20 hover:bg-white/[0.06]">
                    <div class="absolute -right-16 -top-16 h-36 w-36 rounded-full bg-white/5 blur-2xl transition group-hover:bg-white/10"></div>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/10 bg-black text-xl font-black uppercase text-white">
                            {{ substr($site->domain, 0, 1) }}
                        </div>
                        <div>
                            <h3 class="font-bold text-lg text-white">{{ $site->domain }}</h3>
                            <span class="mt-1 inline-flex rounded-full border px-3 py-1 text-xs font-bold uppercase {{ $statusClass }}">{{ $status }}</span>
                        </div>
                    </div>

                    <div class="space-y-2 text-sm mb-4">
                        <div class="flex justify-between py-1.5 border-b border-white/5">
                            <span class="text-gray-500">Tipo</span>
                            <span class="text-gray-300 uppercase font-medium">{{ $site->project_type }}</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-white/5">
                            <span class="text-gray-500">Servidor</span>
                            <span class="text-gray-300">{{ $site->web_server ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between py-1.5">
                            <span class="text-gray-500">PHP</span>
                            <span class="text-gray-300">{{ $site->php_version ?? 'N/A' }}</span>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        {{-- Gestionar archivos --}}
                        @if(isset($fileManagerEnabled) ? $fileManagerEnabled : true)
                        <a href="{{ route('client.files.index', $site) }}"
                            class="flex items-center gap-1.5 px-3 py-2 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-300 rounded-lg transition text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                            </svg>
                            Archivos
                        </a>
                        @endif

                        {{-- Reiniciar --}}
                        <form action="{{ route('client.sites.restart', $site) }}" method="POST" class="flex-1 min-w-[80px]">
                            @csrf
                            <button type="submit"
                                class="w-full py-2 text-center bg-white/5 hover:bg-white/10 text-white/70 hover:text-white rounded-lg text-sm font-medium transition">
                                Reiniciar
                            </button>
                        </form>

                        {{-- Abrir en navegador --}}
                        <a href="http://{{ $site->domain }}" target="_blank"
                            class="px-3 py-2 bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </a>

                        {{-- Eliminar --}}
                        <x-confirm-modal
                            action="{{ route('client.sites.destroy', $site) }}"
                            title="Eliminar sitio"
                            message="Se eliminará '{{ $site->domain }}' y todos sus datos. Esta acción no se puede deshacer."
                            btnText="Eliminar sitio"
                            triggerClass="px-3 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-300 rounded-lg transition text-sm font-medium"
                            triggerText="Eliminar"
                        />
                    </div>
                </div>
            @empty
                <div class="col-span-full py-16 text-center rounded-2xl border border-dashed border-white/10 bg-white/[0.02]">
                    <svg class="mx-auto w-12 h-12 text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"></path>
                    </svg>
                    <p class="text-gray-500 mb-4">No tienes sitios web registrados.</p>
                    <a href="{{ route('client.sites.create') }}"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-white text-sm font-medium transition">
                        Crear primer sitio
                    </a>
                </div>
            @endforelse
        </div>
    </div>
@endsection
