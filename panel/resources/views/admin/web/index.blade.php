@extends('layouts.app')

@section('content')
    <div class="p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">Administración de Sitios</h1>
                <p class="text-gray-400">Vista global de todos los sitios alojados en el servidor.</p>
            </div>
        </div>

        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
            <table class="w-full text-left text-gray-400">
                <thead class="bg-gray-700/50 text-gray-300 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-6 py-4">Dominio</th>
                        <th class="px-6 py-4">Cliente (Tenant)</th>
                        <th class="px-6 py-4">Versión PHP</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4">Fecha Creación</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($sites as $site)
                        <tr class="hover:bg-gray-700/30 transition">
                            <td class="px-6 py-4 text-white font-medium">{{ $site->domain }}</td>
                            <td class="px-6 py-4">{{ $site->tenant->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4"><span
                                    class="px-2 py-1 bg-blue-500/10 text-blue-400 rounded text-xs font-bold">{{ $site->php_version }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @if($site->status === 'active')
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-500/10 text-green-400">Activo</span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-500/10 text-yellow-400">{{ ucfirst($site->status) }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $site->created_at->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                <button class="text-blue-400 hover:text-white transition">Editar</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                No hay sitios registrados en el sistema.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="p-4 border-t border-gray-700">
                {{ $sites->links() }}
            </div>
        </div>
    </div>
@endsection