@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto p-8">
        <div class="mb-8">
            <a href="{{ route('client.sites.index') }}"
                class="text-gray-400 hover:text-white mb-4 inline-flex items-center gap-2 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
                Volver a mis sitios
            </a>
            <h1 class="text-3xl font-bold text-white">Nuevo Sitio Web</h1>
            <p class="text-gray-400 mt-2">Configura un nuevo dominio para tu proyecto.</p>
        </div>

        <div class="bg-gray-800 rounded-xl border border-gray-700 p-8 shadow-xl">
            <form action="{{ route('client.sites.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Domain Input -->
                <div>
                    <label for="domain" class="block text-sm font-medium text-gray-300 mb-2">Dominio</label>
                    <div class="relative">
                        <input type="text" name="domain" id="domain" placeholder="ejemplo.com"
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition placeholder-gray-600"
                            required>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <span class="text-gray-500 text-sm">HTTPS</span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">No incluyas "http://" o "https://". Solo el nombre del dominio.
                    </p>
                </div>

                <!-- Project Type Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-3">Tipo de Proyecto</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <label class="cursor-pointer relative">
                            <input type="radio" name="project_type" value="php" class="peer sr-only" checked
                                onchange="toggleOptions('php')">
                            <div
                                class="p-4 bg-gray-900 border border-gray-700 rounded-lg hover:border-blue-500 peer-checked:border-blue-500 peer-checked:bg-blue-500/10 peer-checked:text-blue-400 transition text-center h-full flex flex-col items-center justify-center">
                                <span class="text-2xl mb-1">🐘</span>
                                <span class="block font-bold">PHP</span>
                            </div>
                        </label>
                        <label class="cursor-pointer relative">
                            <input type="radio" name="project_type" value="node" class="peer sr-only"
                                onchange="toggleOptions('node')">
                            <div
                                class="p-4 bg-gray-900 border border-gray-700 rounded-lg hover:border-blue-500 peer-checked:border-blue-500 peer-checked:bg-blue-500/10 peer-checked:text-blue-400 transition text-center h-full flex flex-col items-center justify-center">
                                <span class="text-2xl mb-1">🟢</span>
                                <span class="block font-bold">Node.js</span>
                            </div>
                        </label>
                        <label class="cursor-pointer relative">
                            <input type="radio" name="project_type" value="static" class="peer sr-only"
                                onchange="toggleOptions('static')">
                            <div
                                class="p-4 bg-gray-900 border border-gray-700 rounded-lg hover:border-blue-500 peer-checked:border-blue-500 peer-checked:bg-blue-500/10 peer-checked:text-blue-400 transition text-center h-full flex flex-col items-center justify-center">
                                <span class="text-2xl mb-1">⚡</span>
                                <span class="block font-bold">HTML/Static</span>
                            </div>
                        </label>
                        <label class="cursor-pointer relative">
                            <input type="radio" name="project_type" value="python" class="peer sr-only"
                                onchange="toggleOptions('python')">
                            <div
                                class="p-4 bg-gray-900 border border-gray-700 rounded-lg hover:border-blue-500 peer-checked:border-blue-500 peer-checked:bg-blue-500/10 peer-checked:text-blue-400 transition text-center h-full flex flex-col items-center justify-center">
                                <span class="text-2xl mb-1">🐍</span>
                                <span class="block font-bold">Python</span>
                            </div>
                        </label>
                    </div>

                    <!-- PHP Options (Condensed) -->
                    <div id="php-options">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Servidor web</label>
                                <select name="web_server"
                                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="apache" selected>Apache</option>
                                    <option value="nginx">Nginx + PHP-FPM</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Versión de PHP</label>
                                <select name="php_version"
                                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="8.0">PHP 8.0</option>
                                    <option value="8.1">PHP 8.1</option>
                                    <option value="8.2" selected>PHP 8.2 (Recomendado)</option>
                                    <option value="8.3">PHP 8.3</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    function toggleOptions(type) {
                        const phpOptions = document.getElementById('php-options');
                        if (type === 'php') {
                            phpOptions.classList.remove('hidden');
                        } else {
                            phpOptions.classList.add('hidden');
                        }
                    }
                </script>

                <div class="pt-4 border-t border-gray-700">
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white font-bold py-3 px-8 rounded-lg shadow-lg hover:shadow-blue-500/30 transition duration-200 transform hover:-translate-y-0.5">
                        Crear Sitio Web
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
