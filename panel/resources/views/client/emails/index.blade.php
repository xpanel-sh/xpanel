@extends('layouts.app')

@section('content')
    <section class="space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-gray-500">Panel Cliente</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight">Correos</h1>
                <p class="mt-2 text-gray-400">Crea y gestiona cuentas de correo para tus dominios.</p>
            </div>
            <a href="{{ route('client.emails.create') }}" class="rounded-xl bg-white px-5 py-3 text-sm font-bold text-black transition hover:bg-gray-200">
                Crear correo
            </a>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/[0.03]">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left">
                    <thead class="text-xs uppercase tracking-widest text-gray-500">
                        <tr>
                            <th class="px-6 py-4">Correo</th>
                            <th class="px-6 py-4">Dominio</th>
                            <th class="px-6 py-4">Cuota</th>
                            <th class="px-6 py-4">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($accounts as $account)
                            <tr>
                                <td class="px-6 py-4 font-semibold text-white">{{ $account->email }}</td>
                                <td class="px-6 py-4 text-gray-400">{{ $account->domain?->domain }}</td>
                                <td class="px-6 py-4 text-gray-400">{{ number_format($account->quota_mb / 1024, 1) }} GB</td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusClass = str_contains($account->status, 'error')
                                            ? 'border-red-500/30 bg-red-500/10 text-red-300'
                                            : ($account->status === 'provisioning'
                                                ? 'border-amber-500/30 bg-amber-500/10 text-amber-300'
                                                : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300');
                                    @endphp
                                    <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $statusClass }}">{{ strtoupper($account->status) }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <details class="inline-block text-left">
                                        <summary class="cursor-pointer text-sm font-semibold text-white">Gestionar</summary>
                                        <div class="mt-3 w-80 rounded-2xl border border-white/10 bg-black p-4 shadow-xl">
                                            <form action="{{ route('client.emails.reset-password', $account) }}" method="POST" class="space-y-3">
                                                @csrf
                                                <input type="password" name="password" required minlength="12" autocomplete="new-password" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white outline-none" placeholder="Nueva clave">
                                                <button class="w-full rounded-xl bg-white px-4 py-2 text-sm font-bold text-black">Resetear clave</button>
                                            </form>
                                            <form action="{{ route('client.emails.destroy', $account) }}" method="POST" onsubmit="return confirm('¿Eliminar correo?')" class="mt-3">
                                                @csrf
                                                @method('DELETE')
                                                <button class="w-full rounded-xl border border-red-500/20 px-4 py-2 text-sm font-bold text-red-300">Eliminar</button>
                                            </form>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    No tienes cuentas de correo todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $accounts->links() }}
    </section>
@endsection
