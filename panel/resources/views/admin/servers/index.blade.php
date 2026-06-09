@extends('layouts.app')

@section('content')
    <div class="p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">Servidores Conectados</h1>
                <p class="text-gray-400">Administra servidores donde corre el agente XPanel.</p>
            </div>
            <a href="{{ route('admin.servers.create') }}" class="px-6 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-lg font-semibold">
                Agregar Servidor
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
                        <th class="px-6 py-4">Nombre</th>
                        <th class="px-6 py-4">IP</th>
                        <th class="px-6 py-4">Puerto</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($nodes as $node)
                        <tr class="hover:bg-gray-700/30 transition">
                            <td class="px-6 py-4 text-white font-medium">{{ $node->name }}</td>
                            <td class="px-6 py-4 text-gray-300">{{ $node->ip_address }}</td>
                            <td class="px-6 py-4 text-gray-300">{{ $node->port }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded text-xs font-medium {{ $node->is_active ? 'bg-green-500/10 text-green-400' : 'bg-red-500/10 text-red-400' }}">
                                    {{ $node->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form action="{{ route('admin.servers.toggle', $node) }}" method="POST" class="inline">
                                    @csrf
                                    <button class="text-blue-400 hover:text-white">
                                        {{ $node->is_active ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">No hay servidores conectados registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4 border-t border-gray-700">
                {{ $nodes->links() }}
            </div>
        </div>
    </div>
@endsection
