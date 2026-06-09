@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-4xl space-y-6">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-gray-500">Admin Global</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight">Nameservers</h1>
            <p class="mt-2 text-gray-400">Define los NS que tus clientes podrán usar si no conectan Cloudflare.</p>
        </div>

        <form action="{{ route('admin.dns.nameservers.update') }}" method="POST" class="space-y-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-300">Proveedor DNS principal</label>
                <select name="provider" class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white">
                    <option value="xpanel" @selected($settings->provider === 'xpanel')>XPanel NS propio</option>
                    <option value="cloudflare" @selected($settings->provider === 'cloudflare')>Cloudflare</option>
                    <option value="external" @selected($settings->provider === 'external')>Externo/manual</option>
                </select>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach(['ns1', 'ns2', 'ns3', 'ns4'] as $ns)
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-300">{{ strtoupper($ns) }}</label>
                        <input type="text" name="{{ $ns }}" value="{{ old($ns, $settings->{$ns}) }}"
                            class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white"
                            placeholder="{{ $ns }}.xpanel.sh">
                    </div>
                @endforeach
            </div>

            <label class="flex items-center gap-3">
                <input type="checkbox" name="is_active" value="1" @checked($settings->is_active)>
                <span class="text-sm text-gray-300">Mostrar estos NS a clientes</span>
            </label>

            <div class="rounded-xl border border-yellow-500/20 bg-yellow-500/10 p-4 text-sm text-yellow-100">
                Esto configura la capa del panel. Para DNS autoritativo real se conectará luego el agente con PowerDNS/CoreDNS o proveedor equivalente.
            </div>

            <button class="rounded-xl bg-white px-6 py-3 font-bold text-black transition hover:bg-gray-200">Guardar Nameservers</button>
        </form>
    </section>
@endsection
