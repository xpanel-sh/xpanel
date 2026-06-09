@csrf

<div class="grid grid-cols-1 gap-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6 md:grid-cols-2">
    <div>
        <label class="mb-2 block text-sm font-semibold text-gray-300">Nombre del plan</label>
        <input type="text" name="name" value="{{ old('name', $plan->name) }}" required
            class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white"
            placeholder="Starter">
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-gray-300">Slug</label>
        <input type="text" name="slug" value="{{ old('slug', $plan->slug) }}"
            class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white"
            placeholder="starter">
        <p class="mt-2 text-xs text-gray-500">Si lo dejas vacío, se genera desde el nombre.</p>
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-gray-300">Sitios web</label>
        <input type="number" name="max_sites" value="{{ old('max_sites', $plan->max_sites ?? 1) }}" min="0" required
            class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white">
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-gray-300">Bases de datos</label>
        <input type="number" name="max_databases" value="{{ old('max_databases', $plan->max_databases ?? 1) }}" min="0" required
            class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white">
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-gray-300">Almacenamiento MB</label>
        <input type="number" name="storage_mb" value="{{ old('storage_mb', $plan->storage_mb ?? 1024) }}" min="0" required
            class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white">
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-gray-300">Transferencia GB</label>
        <input type="number" name="bandwidth_gb" value="{{ old('bandwidth_gb', $plan->bandwidth_gb ?? 10) }}" min="0" required
            class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white">
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-gray-300">Cuentas de correo</label>
        <input type="number" name="email_accounts" value="{{ old('email_accounts', $plan->email_accounts ?? 0) }}" min="0" required
            class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white">
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-gray-300">Precio mensual</label>
        <input type="number" step="0.01" name="monthly_price" value="{{ old('monthly_price', $plan->monthly_price ?? 0) }}" min="0" required
            class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white">
    </div>

    <div class="md:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-gray-300">Descripción</label>
        <textarea name="description" rows="4"
            class="w-full rounded-xl border border-white/10 bg-black px-4 py-3 text-white outline-none focus:border-white"
            placeholder="Plan base para sitios pequeños...">{{ old('description', $plan->description) }}</textarea>
    </div>

    <label class="flex items-center gap-3 md:col-span-2">
        <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-white/20 bg-black"
            @checked(old('is_active', $plan->exists ? $plan->is_active : true))>
        <span class="text-sm text-gray-300">Plan activo para nuevas asignaciones</span>
    </label>
</div>

<div class="mt-6 flex flex-col gap-3 sm:flex-row">
    <button class="rounded-xl bg-white px-6 py-3 font-bold text-black transition hover:bg-gray-200">
        Guardar plan
    </button>
    <a href="{{ route('admin.plans.index') }}" class="rounded-xl border border-white/10 px-6 py-3 text-center font-bold text-white transition hover:bg-white/10">
        Cancelar
    </a>
</div>
