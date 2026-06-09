@extends('layouts.app')

@section('content')
    <section class="space-y-6">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-gray-500">Panel Cliente</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight">DNS</h1>
            <p class="mt-2 text-gray-400">Gestiona registros DNS si decides apuntar tu dominio a los nameservers de XPanel.</p>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-xl font-bold">Nameservers recomendados</h2>
            @if($nameservers && count($nameservers->records()))
                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                    @foreach($nameservers->records() as $record)
                        <div class="rounded-xl bg-black px-4 py-3 font-mono text-sm text-white">{{ $record }}</div>
                    @endforeach
                </div>
            @else
                <p class="mt-3 text-sm text-gray-500">El administrador aún no configuró nameservers propios.</p>
            @endif
        </div>

        <form action="{{ route('client.dns.store') }}" method="POST" class="grid grid-cols-1 gap-4 rounded-2xl border border-white/10 bg-white/[0.03] p-6 lg:grid-cols-6">
            @csrf
            <div class="lg:col-span-2">
                <label class="mb-2 block text-sm text-gray-300">Dominio</label>
                <select name="domain_id" class="w-full rounded-xl border border-white/10 bg-black px-3 py-3 text-white">
                    @foreach($domains as $domain)
                        <option value="{{ $domain->id }}">{{ $domain->domain }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm text-gray-300">Tipo</label>
                <select name="type" class="w-full rounded-xl border border-white/10 bg-black px-3 py-3 text-white">
                    @foreach(['A','AAAA','CNAME','MX','TXT','NS','SRV','CAA'] as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm text-gray-300">Nombre</label>
                <input name="name" value="{{ old('name', '@') }}" class="w-full rounded-xl border border-white/10 bg-black px-3 py-3 text-white">
            </div>
            <div class="lg:col-span-2">
                <label class="mb-2 block text-sm text-gray-300">Valor</label>
                <input name="value" value="{{ old('value') }}" class="w-full rounded-xl border border-white/10 bg-black px-3 py-3 text-white">
            </div>
            <div>
                <label class="mb-2 block text-sm text-gray-300">TTL</label>
                <input type="number" name="ttl" value="{{ old('ttl', 3600) }}" class="w-full rounded-xl border border-white/10 bg-black px-3 py-3 text-white">
            </div>
            <div>
                <label class="mb-2 block text-sm text-gray-300">Prioridad</label>
                <input type="number" name="priority" value="{{ old('priority') }}" class="w-full rounded-xl border border-white/10 bg-black px-3 py-3 text-white">
            </div>
            <div class="lg:col-span-5">
                <button class="rounded-xl bg-white px-5 py-3 text-sm font-bold text-black">Agregar registro</button>
            </div>
        </form>

        <div class="rounded-2xl border border-white/10 bg-white/[0.03]">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left">
                    <thead class="text-xs uppercase tracking-widest text-gray-500">
                        <tr>
                            <th class="px-6 py-4">Dominio</th>
                            <th class="px-6 py-4">Tipo</th>
                            <th class="px-6 py-4">Nombre</th>
                            <th class="px-6 py-4">Valor</th>
                            <th class="px-6 py-4">TTL</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($records as $record)
                            <tr>
                                <td class="px-6 py-4 text-gray-300">{{ $record->domain?->domain }}</td>
                                <td class="px-6 py-4 font-bold text-white">{{ $record->type }}</td>
                                <td class="px-6 py-4 text-gray-300">{{ $record->name }}</td>
                                <td class="px-6 py-4 text-gray-300">{{ $record->value }}</td>
                                <td class="px-6 py-4 text-gray-300">{{ $record->ttl }}</td>
                                <td class="px-6 py-4 text-right">
                                    <form action="{{ route('client.dns.destroy', $record) }}" method="POST" onsubmit="return confirm('¿Eliminar registro DNS?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-sm font-semibold text-red-300 hover:text-red-100">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">No tienes registros DNS aún.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $records->links() }}
    </section>
@endsection
