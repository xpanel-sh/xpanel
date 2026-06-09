@extends('layouts.app')

@section('content')
    <section class="space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-gray-500">Admin Global</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight">Planes de Hosting</h1>
                <p class="mt-2 text-gray-400">Define límites base para clientes: sitios, bases de datos, almacenamiento y correo.</p>
            </div>
            <a href="{{ route('admin.plans.create') }}" class="rounded-xl bg-white px-5 py-3 text-sm font-bold text-black transition hover:bg-gray-200">
                Crear plan
            </a>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            @forelse($plans as $plan)
                <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-black">{{ $plan->name }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ $plan->slug }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $plan->is_active ? 'bg-emerald-500/10 text-emerald-300' : 'bg-red-500/10 text-red-300' }}">
                            {{ $plan->is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>

                    <div class="mt-5 text-3xl font-black">
                        ${{ $plan->monthly_price }}
                        <span class="text-sm font-medium text-gray-500">/ mes</span>
                    </div>

                    <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-xl bg-black p-3">
                            <dt class="text-gray-500">Sitios</dt>
                            <dd class="mt-1 font-bold">{{ $plan->max_sites }}</dd>
                        </div>
                        <div class="rounded-xl bg-black p-3">
                            <dt class="text-gray-500">DB</dt>
                            <dd class="mt-1 font-bold">{{ $plan->max_databases }}</dd>
                        </div>
                        <div class="rounded-xl bg-black p-3">
                            <dt class="text-gray-500">Storage</dt>
                            <dd class="mt-1 font-bold">{{ number_format($plan->storage_mb / 1024, 1) }} GB</dd>
                        </div>
                        <div class="rounded-xl bg-black p-3">
                            <dt class="text-gray-500">Clientes</dt>
                            <dd class="mt-1 font-bold">{{ $plan->tenants_count }}</dd>
                        </div>
                    </dl>

                    @if($plan->description)
                        <p class="mt-4 text-sm text-gray-400">{{ $plan->description }}</p>
                    @endif

                    <div class="mt-6 flex gap-2">
                        <a href="{{ route('admin.plans.edit', $plan) }}" class="flex-1 rounded-xl border border-white/10 px-4 py-2 text-center text-sm font-bold text-white hover:bg-white/10">
                            Editar
                        </a>
                        <form action="{{ route('admin.plans.toggle', $plan) }}" method="POST">
                            @csrf
                            <button class="rounded-xl border border-white/10 px-4 py-2 text-sm font-bold text-gray-300 hover:bg-white/10">
                                {{ $plan->is_active ? 'Pausar' : 'Activar' }}
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-white/10 p-10 text-center text-gray-500 lg:col-span-3">
                    Todavía no hay planes. Crea el primer paquete para empezar a organizar clientes.
                </div>
            @endforelse
        </div>

        {{ $plans->links() }}
    </section>
@endsection
