<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XPanel Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-white text-black">
    <main class="grid min-h-screen lg:grid-cols-[1.1fr_0.9fr]">
        <section class="relative hidden overflow-hidden bg-black p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute inset-0 opacity-30" style="background-image: linear-gradient(rgba(255,255,255,.08) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.08) 1px, transparent 1px); background-size: 42px 42px;"></div>
            <div class="relative">
                <div class="text-3xl font-black">XPanel</div>
                <p class="mt-4 max-w-xl text-5xl font-black leading-tight tracking-tight">Admin global para operar tu plataforma de hosting.</p>
            </div>
            <div class="relative grid grid-cols-3 gap-3 text-sm text-gray-300">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">Clientes</div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">Servidores</div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">Sitios</div>
            </div>
        </section>

        <section class="flex items-center justify-center p-6">
            <div class="w-full max-w-md">
                <p class="text-sm font-bold uppercase tracking-[0.3em] text-gray-500">Acceso Admin</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight">Entrar a XPanel</h1>
                <p class="mt-3 text-gray-500">Usa las credenciales que entrega el instalador o `xpanel acceso`.</p>

                @if ($errors->any())
                    <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if (session('status'))
                    <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                <form action="{{ route('admin.login.post') }}" method="POST" class="mt-8 space-y-5">
                    @csrf
                    <div>
                        <label class="mb-2 block text-sm font-bold text-gray-700">Correo admin</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                            class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 outline-none transition focus:border-black focus:ring-4 focus:ring-black/5"
                            required autofocus>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-gray-700">Contraseña</label>
                        <input type="password" name="password"
                            class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 outline-none transition focus:border-black focus:ring-4 focus:ring-black/5"
                            required>
                    </div>

                    <button type="submit" class="w-full rounded-2xl bg-black px-5 py-4 font-black text-white transition hover:bg-gray-800">
                        Entrar al Admin
                    </button>
                </form>
            </div>
        </section>
    </main>
</body>

</html>
