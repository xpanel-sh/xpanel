@extends('layouts.client')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<section class="mx-auto max-w-3xl">
        <div class="mb-8">
            <a href="{{ route('client.domains.index') }}" class="text-sm text-gray-400 hover:text-white">Volver a dominios</a>
            <h1 class="mt-4 text-3xl font-black">Agregar Dominio</h1>
            <p class="mt-2 text-gray-400">Asocia un dominio, alias o subdominio a tu cuenta.</p>
        </div>

        <form action="{{ route('client.domains.store') }}" method="POST" class="space-y-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            @csrf

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-300">Dominio</label>
                <input type="text" name="domain" value="{{ old('domain') }}" required
                    class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white"
                    placeholder="example.com">
                <p class="mt-2 text-xs text-gray-500">Sin http:// ni https://.</p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-300">Tipo</label>
                <select name="type" class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white">
                    <option value="primary" @selected(old('type') === 'primary')>Principal</option>
                    <option value="alias" @selected(old('type') === 'alias')>Alias</option>
                    <option value="subdomain" @selected(old('type') === 'subdomain')>Subdominio</option>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-300">Sitio asociado</label>
                <select name="site_id" class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white">
                    <option value="">Sin asociar todavía</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected(old('site_id') == $site->id)>{{ $site->domain }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <button class="rounded-xl bg-white px-6 py-3 font-bold text-black transition hover:bg-gray-200">Guardar dominio</button>
                <a href="{{ route('client.domains.index') }}" class="rounded-xl border border-white/10 px-6 py-3 text-center font-bold text-white transition hover:bg-white/10">Cancelar</a>
            </div>
        </form>
    </section>
                    </div>
                </div>
            </main>

            @include('layouts.partials.client.footer')
        </div>
    </div>
@endsection
