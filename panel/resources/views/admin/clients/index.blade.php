@extends('layouts.app')

@section('content')
    <div class="p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">Gestión de Clientes (Tenants)</h1>
                <p class="text-gray-400">Administra las cuentas de tus clientes y sus límites.</p>
            </div>
            <a href="{{ route('admin.clients.create') }}"
                class="px-6 py-3 bg-blue-600 hover:bg-blue-500 text-white font-semibold rounded-lg shadow-lg hover:shadow-blue-500/30 transition duration-200 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nuevo Cliente
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-500/10 border border-green-500/50 rounded-lg text-green-400">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-700/50 text-gray-400 text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-4">Empresa / Dominio</th>
                        <th class="px-6 py-4">Usuario Principal</th>
                        <th class="px-6 py-4">Plan</th>
                        <th class="px-6 py-4">Sitios</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($tenants as $tenant)
                        <tr class="hover:bg-gray-700/30 transition">
                            <td class="px-6 py-4">
                                <div class="font-bold text-white">{{ $tenant->name }}</div>
                                <div class="text-sm text-gray-500">{{ $tenant->domain }}</div>
                            </td>
                            <td class="px-6 py-4 text-gray-400 text-sm">
                                <div>{{ $tenant->user?->name ?? 'Sin asignar' }}</div>
                                <div class="text-xs">{{ $tenant->user?->email }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-300">
                                {{ $tenant->plan?->name ?? 'Sin plan' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-blue-500/10 text-blue-400 rounded text-xs font-bold">
                                    {{ $tenant->sites->count() }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 {{ $tenant->status === 'active' ? 'bg-green-500/10 text-green-400' : 'bg-red-500/10 text-red-400' }} rounded text-xs font-bold uppercase">
                                    {{ $tenant->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.clients.show', $tenant) }}" class="text-blue-400 hover:text-white transition">Ver detalle</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500 italic">No hay clientes registrados aún.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="p-4 border-t border-gray-700">
                {{ $tenants->links() }}
            </div>
        </div>
    </div>
@endsection
