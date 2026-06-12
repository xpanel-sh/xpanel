@extends('layouts.client')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<section class="space-y-6">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-gray-500">Panel Cliente</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight">DNS</h1>
            <p class="mt-2 text-gray-400">Gestiona registros DNS si decides apuntar tu dominio a los nameservers de XPanel.</p>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-lg font-bold mb-3">Nameservers recomendados</h2>
            @if($nameservers && count($nameservers->records()))
                <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                    @foreach($nameservers->records() as $record)
                        <div class="rounded-xl bg-black/40 dark:bg-black px-4 py-3 font-mono text-sm text-white border border-white/5">{{ $record }}</div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">El administrador aún no configuró nameservers propios.</p>
            @endif
        </div>

        <form action="{{ route('client.dns.store') }}" method="POST"
            class="grid grid-cols-1 gap-4 rounded-2xl border border-white/10 bg-white/[0.03] p-6 lg:grid-cols-6">
            @csrf
            <div class="lg:col-span-2">
                <label class="mb-2 block text-sm text-gray-400">Dominio</label>
                <select name="domain_id" class="w-full rounded-xl border border-white/10 bg-black/40 dark:bg-black px-3 py-3 text-white outline-none focus:border-white/20">
                    @foreach($domains as $domain)
                        <option value="{{ $domain->id }}">{{ $domain->domain }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm text-gray-400">Tipo</label>
                <select name="type" class="w-full rounded-xl border border-white/10 bg-black/40 dark:bg-black px-3 py-3 text-white outline-none focus:border-white/20">
                    @foreach(['A','AAAA','CNAME','MX','TXT','NS','SRV','CAA'] as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm text-gray-400">Nombre</label>
                <input name="name" value="{{ old('name', '@') }}"
                    class="w-full rounded-xl border border-white/10 bg-black/40 dark:bg-black px-3 py-3 text-white outline-none focus:border-white/20">
            </div>
            <div class="lg:col-span-2">
                <label class="mb-2 block text-sm text-gray-400">Valor</label>
                <input name="value" value="{{ old('value') }}"
                    class="w-full rounded-xl border border-white/10 bg-black/40 dark:bg-black px-3 py-3 text-white outline-none focus:border-white/20">
            </div>
            <div>
                <label class="mb-2 block text-sm text-gray-400">TTL</label>
                <input type="number" name="ttl" value="{{ old('ttl', 3600) }}"
                    class="w-full rounded-xl border border-white/10 bg-black/40 dark:bg-black px-3 py-3 text-white outline-none focus:border-white/20">
            </div>
            <div>
                <label class="mb-2 block text-sm text-gray-400">Prioridad</label>
                <input type="number" name="priority" value="{{ old('priority') }}"
                    class="w-full rounded-xl border border-white/10 bg-black/40 dark:bg-black px-3 py-3 text-white outline-none focus:border-white/20">
            </div>
            <div class="lg:col-span-5">
                <button class="rounded-xl bg-gray-900 dark:bg-white px-5 py-3 text-sm font-bold text-white dark:text-black hover:bg-gray-700 dark:hover:bg-gray-200 transition">
                    Agregar registro
                </button>
            </div>
        </form>

        <div class="rounded-2xl border border-white/10 bg-white/[0.03] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left">
                    <thead class="text-xs uppercase tracking-widest text-gray-500 border-b border-white/10">
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
                            <tr class="hover:bg-white/[0.02] transition">
                                <td class="px-6 py-4 text-gray-300">{{ $record->domain?->domain }}</td>
                                <td class="px-6 py-4 font-bold text-white">{{ $record->type }}</td>
                                <td class="px-6 py-4 text-gray-300 font-mono text-sm">{{ $record->name }}</td>
                                <td class="px-6 py-4 text-gray-400 font-mono text-xs max-w-xs truncate">{{ $record->value }}</td>
                                <td class="px-6 py-4 text-gray-400 text-sm">{{ $record->ttl }}s</td>
                                <td class="px-6 py-4 text-right">
                                    <x-confirm-modal
                                        action="{{ route('client.dns.destroy', $record) }}"
                                        title="Eliminar registro DNS"
                                        message="Se eliminará el registro {{ $record->type }} '{{ $record->name }}' del dominio {{ $record->domain?->domain }}."
                                        btnText="Eliminar registro"
                                    />
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
                    </div>
                </div>
            </main>

            @include('layouts.partials.client.footer')
        </div>
    </div>
@endsection
