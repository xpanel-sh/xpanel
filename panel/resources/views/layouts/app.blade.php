<!DOCTYPE html>
<html lang="es" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>XPanel</title>
    <script>
        // Anti-flash: aplicar tema desde localStorage antes de pintar
        (function() {
            const t = localStorage.getItem('xpanel-theme');
            if (t === 'light') {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
</head>

<body class="min-h-screen bg-gray-50 text-gray-900 dark:bg-[#0b0c0f] dark:text-white transition-colors duration-200"
      x-data="themeManager()" x-init="init()">
    @php
        $user = \Illuminate\Support\Facades\Auth::guard('admin')->user() ?? auth()->user();
        $isAdmin = \Illuminate\Support\Facades\Auth::guard('admin')->check() && $user?->role === 'admin';
        $navItem = 'block rounded-xl px-4 py-3 text-sm transition border';
        $activeItem = 'bg-gray-900 text-white border-gray-900 shadow-lg dark:bg-white dark:text-black dark:border-white dark:shadow-white/10';
        $idleItem = 'text-gray-600 border-transparent hover:bg-gray-100 hover:border-gray-200 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:border-white/10 dark:hover:text-white';
    @endphp

    <div class="flex min-h-screen">
        {{-- Sidebar desktop --}}
        <aside class="hidden w-72 shrink-0 border-r border-gray-200 bg-white dark:border-white/10 dark:bg-black md:flex md:flex-col">
            <div class="border-b border-gray-200 dark:border-white/10 p-6">
                <div class="text-2xl font-black tracking-tight">XPanel</div>
                <div class="mt-2 text-xs uppercase tracking-[0.25em] text-gray-400 dark:text-gray-500">
                    {{ $isAdmin ? 'Admin Global' : 'Panel Cliente' }}
                </div>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto p-4">
                @if($isAdmin)
                    <div>
                        <div class="mb-2 px-4 text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">Control</div>
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
                        <div class="mb-2 px-4 text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">Próximos módulos</div>
                        <div class="space-y-2">
                            <span class="{{ $navItem }} border-gray-100 text-gray-400 dark:border-white/5 dark:text-gray-600">Backups globales</span>
                            <span class="{{ $navItem }} border-gray-100 text-gray-400 dark:border-white/5 dark:text-gray-600">Auditoría avanzada</span>
                        </div>
                    </div>
                @else
                    <div>
                        <div class="mb-2 px-4 text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">Mi hosting</div>
                        <div class="space-y-2">
                            <a href="{{ route('client.dashboard') }}" class="{{ $navItem }} {{ request()->routeIs('client.dashboard') ? $activeItem : $idleItem }}">Resumen</a>
                            <a href="{{ route('client.sites.index') }}" class="{{ $navItem }} {{ request()->routeIs('client.sites.*') || request()->routeIs('client.files.*') ? $activeItem : $idleItem }}">Sitios Web</a>
                            <a href="{{ route('client.domains.index') }}" class="{{ $navItem }} {{ request()->routeIs('client.domains.*') ? $activeItem : $idleItem }}">Dominios</a>
                            <a href="{{ route('client.dns.index') }}" class="{{ $navItem }} {{ request()->routeIs('client.dns.*') ? $activeItem : $idleItem }}">DNS</a>
                            <a href="{{ route('client.emails.index') }}" class="{{ $navItem }} {{ request()->routeIs('client.emails.*') ? $activeItem : $idleItem }}">Correos</a>
                            <a href="{{ route('client.databases.index') }}" class="{{ $navItem }} {{ request()->routeIs('client.databases.*') ? $activeItem : $idleItem }}">Bases de Datos</a>
                            <a href="{{ route('client.account.show') }}" class="{{ $navItem }} {{ request()->routeIs('client.account.*') ? $activeItem : $idleItem }}">Mi Cuenta</a>
                        </div>
                    </div>

                    <div>
                        <div class="mb-2 px-4 text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">Próximos módulos</div>
                        <div class="space-y-2">
                            <span class="{{ $navItem }} border-gray-100 text-gray-400 dark:border-white/5 dark:text-gray-600">SSL y seguridad</span>
                        </div>
                    </div>
                @endif
            </nav>

            {{-- Footer sidebar: usuario + logout + toggle tema --}}
            <div class="border-t border-gray-200 dark:border-white/10 p-4 space-y-3">
                {{-- Toggle tema --}}
                <button @click="toggle()"
                    class="w-full flex items-center gap-3 rounded-xl border border-gray-200 dark:border-white/10 px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5 transition">
                    <span x-show="!isDark">
                        <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
                        </svg>
                    </span>
                    <span x-show="isDark">
                        <svg class="w-4 h-4 text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                        </svg>
                    </span>
                    <span x-text="isDark ? 'Modo claro' : 'Modo oscuro'"></span>
                </button>

                <div class="rounded-xl bg-gray-100 dark:bg-white/5 p-3">
                    <div class="text-sm font-semibold">{{ $user?->name }}</div>
                    <div class="truncate text-xs text-gray-500">{{ $user?->email }}</div>
                </div>
                <form action="{{ $isAdmin ? route('admin.logout') : route('client.logout') }}" method="POST">
                    @csrf
                    <button class="w-full rounded-xl border border-red-200 dark:border-red-500/20 px-4 py-2 text-left text-sm text-red-500 dark:text-red-300 transition hover:bg-red-50 dark:hover:bg-red-500/10">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>

        <main class="flex-1 overflow-y-auto">
            <header class="sticky top-0 z-20 border-b border-gray-200 dark:border-white/10 bg-gray-50/90 dark:bg-[#0b0c0f]/90 px-5 py-4 backdrop-blur md:hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-black">XPanel</div>
                        <div class="text-xs text-gray-500">{{ $isAdmin ? 'Admin Global' : 'Panel Cliente' }}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="toggle()" class="rounded-lg border border-gray-200 dark:border-white/10 px-3 py-2 text-xs text-gray-500 dark:text-gray-300">
                            <span x-show="isDark">☀️</span>
                            <span x-show="!isDark">🌙</span>
                        </button>
                        <form action="{{ $isAdmin ? route('admin.logout') : route('client.logout') }}" method="POST">
                            @csrf
                            <button class="rounded-lg border border-gray-200 dark:border-white/10 px-3 py-2 text-xs text-gray-600 dark:text-gray-300">Salir</button>
                        </form>
                    </div>
                </div>
            </header>

            <div class="mx-auto max-w-7xl p-5 md:p-8">
                @if (session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                         x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         class="mb-6 flex items-center justify-between rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-5 py-4 text-sm text-emerald-700 dark:text-emerald-100">
                        <span>{{ session('success') }}</span>
                        <button @click="show = false" class="ml-4 text-emerald-500 hover:text-emerald-300">✕</button>
                    </div>
                @endif

                @if ($errors->any())
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 7000)"
                         x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         class="mb-6 flex items-center justify-between rounded-2xl border border-red-500/30 bg-red-500/10 px-5 py-4 text-sm text-red-700 dark:text-red-100">
                        <span>{{ $errors->first() }}</span>
                        <button @click="show = false" class="ml-4 text-red-400 hover:text-red-300">✕</button>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <script>
        function themeManager() {
            return {
                isDark: true,
                init() {
                    const saved = localStorage.getItem('xpanel-theme');
                    this.isDark = saved !== 'light';
                    this.$watch('isDark', val => {
                        if (val) {
                            document.documentElement.classList.add('dark');
                            localStorage.setItem('xpanel-theme', 'dark');
                        } else {
                            document.documentElement.classList.remove('dark');
                            localStorage.setItem('xpanel-theme', 'light');
                        }
                    });
                },
                toggle() {
                    this.isDark = !this.isDark;
                }
            }
        }
    </script>

    @stack('scripts')
</body>

</html>
