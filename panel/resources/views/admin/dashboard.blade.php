@extends('layouts.app')

@section('content')
    <section class="space-y-8">
        <div class="rounded-3xl border border-white/10 bg-white/[0.03] p-6 md:p-8">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-gray-500">Admin Global</p>
                <h1 class="mt-3 text-3xl font-black tracking-tight md:text-5xl">Centro de control XPanel</h1>
                <p class="mt-4 text-gray-400">
                    Gestiona clientes, sitios y servidores conectados desde un solo panel. El cliente tiene su propio panel separado; aquí controlas la plataforma completa.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-4">
            <div class="rounded-2xl border border-white/10 bg-black p-6">
                <div class="text-sm uppercase tracking-widest text-gray-500">Clientes</div>
                <div class="mt-3 text-4xl font-black text-white">{{ $clientCount }}</div>
                <p class="mt-2 text-sm text-gray-500">Cuentas tenant activas o registradas.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-black p-6">
                <div class="text-sm uppercase tracking-widest text-gray-500">Sitios</div>
                <div class="mt-3 text-4xl font-black text-white">{{ $siteCount }}</div>
                <p class="mt-2 text-sm text-gray-500">Proyectos web creados desde Admin o Cliente.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-black p-6">
                <div class="text-sm uppercase tracking-widest text-gray-500">Servidores</div>
                <div class="mt-3 text-4xl font-black text-white">{{ $nodeCount }}</div>
                <p class="mt-2 text-sm text-gray-500">Servidores conectados al agente XPanel.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-black p-6">
                <div class="text-sm uppercase tracking-widest text-gray-500">Planes</div>
                <div class="mt-3 text-4xl font-black text-white">{{ $planCount }}</div>
                <p class="mt-2 text-sm text-gray-500">Paquetes disponibles para clientes.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-xl font-bold">Acciones rápidas</h2>
                <p class="mt-2 text-sm text-gray-500">Flujo Admin: primero cliente, luego recursos asignados.</p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('admin.clients.create') }}" class="rounded-xl bg-white px-5 py-3 text-sm font-bold text-black transition hover:bg-gray-200">
                        Crear Cliente
                    </a>
                    <a href="{{ route('admin.sites.index') }}" class="rounded-xl border border-white/10 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">
                        Ver Sitios
                    </a>
                    <a href="{{ route('admin.plans.create') }}" class="rounded-xl border border-white/10 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">
                        Crear Plan
                    </a>
                    <a href="{{ route('admin.servers.create') }}" class="rounded-xl border border-white/10 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">
                        Agregar Servidor
                    </a>
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-xl font-bold">Modelo del sistema</h2>
                <div class="mt-5 space-y-4 text-sm text-gray-400">
                    <div class="rounded-xl border border-white/10 bg-black p-4">
                        <span class="font-bold text-white">Admin:</span> opera clientes, servidores, sitios globales, políticas y salud.
                    </div>
                    <div class="rounded-xl border border-white/10 bg-black p-4">
                        <span class="font-bold text-white">Cliente:</span> administra solo sus sitios, bases de datos y recursos asignados.
                    </div>
                    <div class="rounded-xl border border-white/10 bg-black p-4">
                        <span class="font-bold text-white">Agente:</span> ejecuta acciones del sistema de forma segura fuera de Laravel.
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
