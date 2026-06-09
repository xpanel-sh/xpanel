<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XPanel Cliente</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-[#f6f6f3] text-black">
    <main class="flex min-h-screen items-center justify-center p-6">
        <div class="grid w-full max-w-5xl overflow-hidden rounded-[2rem] border border-black/10 bg-white shadow-2xl shadow-black/10 lg:grid-cols-[0.9fr_1.1fr]">
            <section class="bg-black p-8 text-white md:p-10">
                <div class="text-2xl font-black">XPanel</div>
                <h1 class="mt-10 text-4xl font-black leading-tight tracking-tight">Tu hosting, sin ruido.</h1>
                <p class="mt-4 text-gray-400">
                    Administra sitios, bases de datos, dominios y servicios desde tu panel cliente.
                </p>
                <div class="mt-10 space-y-3 text-sm text-gray-300">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">Sitios web por proyecto</div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">Bases de datos aisladas</div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">SSL y backups en camino</div>
                </div>
            </section>

            <section class="p-8 md:p-10">
                <p class="text-sm font-bold uppercase tracking-[0.3em] text-gray-500">Panel Cliente</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight">Iniciar sesión</h2>

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

                <form action="{{ route('client.login.post') }}" method="POST" class="mt-8 space-y-5">
                    @csrf
                    <div>
                        <label class="mb-2 block text-sm font-bold text-gray-700">Correo electrónico</label>
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
                        Entrar al Panel
                    </button>
                </form>
            </section>
        </div>
    </main>
</body>

</html>
