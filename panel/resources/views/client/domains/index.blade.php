@extends('layouts.app')

@section('content')
    <section class="space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-gray-500">Panel Cliente</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight">Dominios</h1>
                <p class="mt-2 text-gray-400">Registra dominios, alias o subdominios asociados a tus sitios.</p>
            </div>
            <a href="{{ route('client.domains.create') }}"
                class="flex items-center gap-2 rounded-xl bg-gray-900 dark:bg-white px-5 py-3 text-sm font-bold text-white dark:text-black transition hover:bg-gray-700 dark:hover:bg-gray-200">
                Agregar dominio
            </a>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/[0.03] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-left">
                    <thead class="text-xs uppercase tracking-widest text-gray-500 border-b border-white/10">
                        <tr>
                            <th class="px-6 py-4">Dominio</th>
                            <th class="px-6 py-4">Tipo</th>
                            <th class="px-6 py-4">Sitio</th>
                            <th class="px-6 py-4">DNS</th>
                            <th class="px-6 py-4">SSL</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($domains as $domain)
                            <tr class="hover:bg-white/[0.02] transition">
                                <td class="px-6 py-4 font-semibold text-white">{{ $domain->domain }}</td>
                                <td class="px-6 py-4 text-gray-400">{{ ucfirst($domain->type) }}</td>
                                <td class="px-6 py-4 text-gray-400">{{ $domain->site?->domain ?? 'Sin asociar' }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full bg-yellow-500/10 px-3 py-1 text-xs font-bold text-yellow-300">{{ strtoupper($domain->dns_status) }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full bg-yellow-500/10 px-3 py-1 text-xs font-bold text-yellow-300">{{ strtoupper($domain->ssl_status) }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <x-confirm-modal
                                        action="{{ route('client.domains.destroy', $domain) }}"
                                        title="Eliminar dominio"
                                        message="Se eliminará '{{ $domain->domain }}'. Los registros DNS asociados también se eliminarán."
                                        btnText="Eliminar dominio"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    Todavía no registraste dominios adicionales.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $domains->links() }}
    </section>
@endsection
