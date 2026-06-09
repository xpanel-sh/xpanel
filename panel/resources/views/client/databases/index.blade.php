@extends('layouts.app')

@section('content')
    <div class="p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">Mis Bases de Datos</h1>
                <p class="text-gray-400">Gestiona las credenciales de tus bases de datos por sitio.</p>
            </div>
            <a href="{{ route('client.databases.create') }}"
                class="px-6 py-3 bg-blue-600 hover:bg-blue-500 text-white font-semibold rounded-lg shadow-lg transition duration-200">
                Nueva Base de Datos
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
                        <th class="px-6 py-4">Base</th>
                        <th class="px-6 py-4">Usuario</th>
                        <th class="px-6 py-4">Engine</th>
                        <th class="px-6 py-4">Sitio</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($databases as $database)
                        <tr class="hover:bg-gray-700/30 transition">
                            <td class="px-6 py-4 text-white font-medium">{{ $database->name }}</td>
                            <td class="px-6 py-4 text-gray-300">{{ $database->username }}</td>
                            <td class="px-6 py-4 text-gray-300 uppercase">{{ $database->engine }}</td>
                            <td class="px-6 py-4 text-gray-400">{{ $database->site?->domain ?? 'Sin sitio' }}</td>
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
                                <form action="{{ route('client.databases.destroy', $database) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-400 hover:text-white transition">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">No tienes bases de datos registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="p-4 border-t border-gray-700">
                {{ $databases->links() }}
            </div>
        </div>
    </div>
@endsection
