@extends('layouts.client')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<section class="space-y-6">
        <div class="rounded-3xl border border-white/10 bg-white/[0.03] p-6 md:p-8">
            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-gray-500">Mi Cuenta</p>
            <h1 class="mt-3 text-3xl font-black tracking-tight md:text-5xl">{{ $tenant->name }}</h1>
            <p class="mt-4 text-gray-400">Consulta tu plan asignado, límites actuales y estado de servicio.</p>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-[0.8fr_1.2fr]">
            <div class="rounded-2xl border border-white/10 bg-black p-6">
                <div class="text-sm uppercase tracking-widest text-gray-500">Plan actual</div>
                <h2 class="mt-3 text-3xl font-black">{{ $tenant->plan?->name ?? 'Sin plan asignado' }}</h2>
                <p class="mt-3 text-gray-500">{{ $tenant->plan?->description ?? 'El administrador aún no asignó un paquete a esta cuenta.' }}</p>
                <div class="mt-6 rounded-xl border border-white/10 p-4 text-sm text-gray-400">
                    Estado: <span class="{{ $tenant->status === 'active' ? 'text-emerald-300' : 'text-red-300' }}">{{ ucfirst($tenant->status) }}</span>
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-xl font-bold">Límites y uso</h2>
                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-xl bg-black p-4">
                        <div class="text-xs uppercase tracking-widest text-gray-500">Sitios</div>
                        <div class="mt-2 text-2xl font-black">{{ $usage['sites'] }} / {{ $tenant->plan?->max_sites ?? 'sin límite definido' }}</div>
                    </div>
                    <div class="rounded-xl bg-black p-4">
                        <div class="text-xs uppercase tracking-widest text-gray-500">Bases de datos</div>
                        <div class="mt-2 text-2xl font-black">{{ $usage['databases'] }} / {{ $tenant->plan?->max_databases ?? 'sin límite definido' }}</div>
                    </div>
                    <div class="rounded-xl bg-black p-4">
                        <div class="text-xs uppercase tracking-widest text-gray-500">Dominios</div>
                        <div class="mt-2 text-2xl font-black">{{ $usage['domains'] }}</div>
                    </div>
                    <div class="rounded-xl bg-black p-4">
                        <div class="text-xs uppercase tracking-widest text-gray-500">Almacenamiento</div>
                        <div class="mt-2 text-2xl font-black">{{ $tenant->plan ? number_format($tenant->plan->storage_mb / 1024, 1) . ' GB' : 'sin plan' }}</div>
                    </div>
                    <div class="rounded-xl bg-black p-4">
                        <div class="text-xs uppercase tracking-widest text-gray-500">Correos</div>
                        <div class="mt-2 text-2xl font-black">{{ $usage['emails'] }} / {{ $tenant->plan?->email_accounts ?? 'sin plan' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
                    </div>
                </div>
            </main>

            @include('layouts.partials.client.footer')
        </div>
    </div>
@endsection
