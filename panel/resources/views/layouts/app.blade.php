<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XPanel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-[#0b0c0f] text-white">
    @php
        $user = auth()->user();
        $isAdmin = $user?->role === 'admin';
        $navItem = 'block rounded-xl px-4 py-3 text-sm transition border';
        $activeItem = 'bg-white text-black border-white shadow-lg shadow-white/10';
        $idleItem = 'text-gray-300 border-transparent hover:bg-white/10 hover:border-white/10 hover:text-white';
    @endphp

    <div class="flex min-h-screen">
        <aside class="hidden w-72 shrink-0 border-r border-white/10 bg-black md:flex md:flex-col">
            <div class="border-b border-white/10 p-6">
                <div class="text-2xl font-black tracking-tight">XPanel</div>
                <div class="mt-2 text-xs uppercase tracking-[0.25em] text-gray-500">
                    {{ $isAdmin ? 'Admin Global' : 'Panel Cliente' }}
                </div>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto p-4">
                @if($isAdmin)
                    <div>
                        <div class="mb-2 px-4 text-xs font-semibold uppercase tracking-widest text-gray-500">Control</div>
                        <div class="space-y-2">
                            <a href="{{ route('admin.dashboard') }}" class="{{ $navItem }} {{ request()->routeIs('admin.dashboard') ? $activeItem : $idleItem }}">Resumen</a>
                            <a href="{{ route('admin.clients.index') }}" class="{{ $navItem }} {{ request()->routeIs('admin.clients.*') ? $activeItem : $idleItem }}">Clientes</a>
                            <a href="{{ route('admin.plans.index') }}" class="{{ $navItem }} {{ request()->routeIs('admin.plans.*') ? $activeItem : $idleItem }}">Planes</a>
                            <a href="{{ route('admin.sites.index') }}" class="{{ $navItem }} {{ request()->routeIs('admin.sites.*') ? $activeItem : $idleItem }}">Sitios Globales</a>
                            <a href="{{ route('admin.domains.index') }}" class="{{ $navItem }} {{ request()->routeIs('admin.domains.*') ? $activeItem : $idleItem }}">Dominios</a>
                            <a href="{{ route('admin.dns.nameservers') }}" class="{{ $navItem }} {{ request()->routeIs('admin.dns.*') ? $activeItem : $idleItem }}">Nameservers</a>
                            <a href="{{ route('admin.servers.index') }}" class="{{ $navItem }} {{ request()->routeIs('admin.servers.*') ? $activeItem : $idleItem }}">Servidores Conectados</a>
                            <a href="{{ route('admin.daemon.operations') }}" class="{{ $navItem }} {{ request()->routeIs('admin.daemon.*') ? $activeItem : $idleItem }}">Operaciones Agente</a>
                        </div>
                    </div>

                    <div>
                        <div class="mb-2 px-4 text-xs font-semibold uppercase tracking-widest text-gray-500">Próximos módulos</div>
                        <div class="space-y-2">
                            <span class="{{ $navItem }} border-white/5 text-gray-600">Backups globales</span>
                            <span class="{{ $navItem }} border-white/5 text-gray-600">Auditoría avanzada</span>
                        </div>
                    </div>
                @else
                    <div>
                        <div class="mb-2 px-4 text-xs font-semibold uppercase tracking-widest text-gray-500">Mi hosting</div>
                        <div class="space-y-2">
                            <a href="{{ route('client.dashboard') }}" class="{{ $navItem }} {{ request()->routeIs('client.dashboard') ? $activeItem : $idleItem }}">Resumen</a>
                            <a href="{{ route('client.sites.index') }}" class="{{ $navItem }} {{ request()->routeIs('client.sites.*') ? $activeItem : $idleItem }}">Sitios Web</a>
                            <a href="{{ route('client.domains.index') }}" class="{{ $navItem }} {{ request()->routeIs('client.domains.*') ? $activeItem : $idleItem }}">Dominios</a>
                            <a href="{{ route('client.dns.index') }}" class="{{ $navItem }} {{ request()->routeIs('client.dns.*') ? $activeItem : $idleItem }}">DNS</a>
                            <a href="{{ route('client.emails.index') }}" class="{{ $navItem }} {{ request()->routeIs('client.emails.*') ? $activeItem : $idleItem }}">Correos</a>
                            <a href="{{ route('client.databases.index') }}" class="{{ $navItem }} {{ request()->routeIs('client.databases.*') ? $activeItem : $idleItem }}">Bases de Datos</a>
                            <a href="{{ route('client.account.show') }}" class="{{ $navItem }} {{ request()->routeIs('client.account.*') ? $activeItem : $idleItem }}">Mi Cuenta</a>
                        </div>
                    </div>

                    <div>
                        <div class="mb-2 px-4 text-xs font-semibold uppercase tracking-widest text-gray-500">Próximos módulos</div>
                        <div class="space-y-2">
                            <span class="{{ $navItem }} border-white/5 text-gray-600">Archivos</span>
                            <span class="{{ $navItem }} border-white/5 text-gray-600">SSL y seguridad</span>
                        </div>
                    </div>
                @endif
            </nav>

            <div class="border-t border-white/10 p-4">
                <div class="mb-3 rounded-xl bg-white/5 p-3">
                    <div class="text-sm font-semibold">{{ $user?->name }}</div>
                    <div class="truncate text-xs text-gray-500">{{ $user?->email }}</div>
                </div>
                <form action="{{ $isAdmin ? route('admin.logout') : route('client.logout') }}" method="POST">
                    @csrf
                    <button class="w-full rounded-xl border border-red-500/20 px-4 py-2 text-left text-sm text-red-300 transition hover:bg-red-500/10">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>

        <main class="flex-1 overflow-y-auto">
            <header class="sticky top-0 z-20 border-b border-white/10 bg-[#0b0c0f]/90 px-5 py-4 backdrop-blur md:hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-black">XPanel</div>
                        <div class="text-xs text-gray-500">{{ $isAdmin ? 'Admin Global' : 'Panel Cliente' }}</div>
                    </div>
                    <form action="{{ $isAdmin ? route('admin.logout') : route('client.logout') }}" method="POST">
                        @csrf
                        <button class="rounded-lg border border-white/10 px-3 py-2 text-xs text-gray-300">Salir</button>
                    </form>
                </div>
            </header>

            <div class="mx-auto max-w-7xl p-5 md:p-8">
                @if (session('success'))
                    <div class="mb-6 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-5 py-4 text-sm text-emerald-100">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-red-500/30 bg-red-500/10 px-5 py-4 text-sm text-red-100">
                        {{ $errors->first() }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>

</html>
