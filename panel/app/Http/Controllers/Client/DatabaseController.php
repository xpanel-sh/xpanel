<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ManagedDatabase;
use App\Models\Site;
use App\Services\DaemonClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DatabaseController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $databases = ManagedDatabase::query()
            ->with('site')
            ->where('tenant_id', $tenant->id)
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->latest()
            ->paginate(15);

        return view('client.db.index', compact('databases'));
    }

    public function create(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $sites = Site::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('domain')
            ->get(['id', 'domain']);

        return view('client.db.create', compact('sites'));
    }

    public function store(Request $request, DaemonClient $daemon)
    {
        $tenant = $request->attributes->get('tenant');
        $plan = $tenant->plan;

        if ($plan && $plan->max_databases > 0) {
            $currentDatabases = ManagedDatabase::where('tenant_id', $tenant->id)->count();
            if ($currentDatabases >= $plan->max_databases) {
                return back()->withErrors([
                    'name' => 'Database limit reached for the assigned plan.',
                ])->withInput();
            }
        }

        $prefixSite = null;

        if ($request->filled('site_id')) {
            $prefixSite = Site::where('id', $request->input('site_id'))
                ->where('tenant_id', $tenant->id)
                ->first();
        }

        if ($prefixSite) {
            $prefix = $tenant->databasePrefix();

            if ($request->filled('name_suffix')) {
                $request->merge(['name' => $prefix . trim((string) $request->input('name_suffix'))]);
            }

            if ($request->filled('username_suffix')) {
                $request->merge(['username' => $prefix . trim((string) $request->input('username_suffix'))]);
            }
        }

        $validated = $request->validate([
            'name' => ['required', 'regex:/^[A-Za-z0-9_]+$/', 'min:3', 'max:32', 'unique:managed_databases,name'],
            'username' => ['required', 'regex:/^[A-Za-z0-9_]+$/', 'min:3', 'max:32', 'unique:managed_databases,username'],
            'password' => ['required', 'string', 'min:16', 'max:128'],
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
            'engine' => ['required', 'in:mariadb,mysql'],
        ]);

        $site = null;

        if (!empty($validated['site_id'])) {
            $site = Site::where('id', $validated['site_id'])
                ->where('tenant_id', $tenant->id)
                ->first();

            if (!$site) {
                return back()->withErrors(['site_id' => 'Selected site does not belong to your tenant.'])->withInput();
            }
        }

        $database = ManagedDatabase::create([
            'tenant_id' => $tenant->id,
            'site_id' => $validated['site_id'] ?? null,
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => bcrypt($validated['password']),
            'engine' => $validated['engine'],
            'status' => 'provisioning',
        ]);

        try {
            $daemon->createDatabase($database->name, $database->username, $validated['password'], $database->engine);
            $database->update(['status' => 'active']);
        } catch (\Throwable $e) {
            Log::warning('Database provisioning failed', ['database_id' => $database->id, 'exception' => $e]);
            $database->update(['status' => 'provision_error']);
            return redirect()->route($site ? 'client.websites.module' : 'client.databases.index', $site ? [
                'domain' => $site->domain,
                'section' => 'databases',
                'page' => 'my-sql-databases',
            ] : [])
                ->withErrors(['database' => 'Base registrada, pero el agente no pudo provisionarla. Revisa operaciones del agente o contacta soporte.']);
        }

        return redirect()->route($site ? 'client.websites.module' : 'client.databases.index', $site ? [
            'domain' => $site->domain,
            'section' => 'databases',
            'page' => 'my-sql-databases',
        ] : [])
            ->with('success', 'Database created. Guarda la contraseña que ingresaste; XPanel no la muestra ni la recupera.');
    }

    public function phpMyAdmin(Request $request, ManagedDatabase $database)
    {
        $tenant = $request->attributes->get('tenant');

        if ($database->tenant_id !== $tenant->id) {
            abort(403);
        }

        $baseUrl = rtrim((string) config('services.phpmyadmin.url', '/phpmyadmin'), '/');
        if ($baseUrl === '') {
            $baseUrl = '/phpmyadmin';
        }

        $query = http_build_query([
            'server' => 1,
            'db' => $database->name,
        ]);

        $url = $baseUrl . '/index.php?route=/database/structure&' . $query;

        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://')
            ? redirect()->away($url)
            : redirect($url);
    }

    public function updatePermissions(Request $request, ManagedDatabase $database, DaemonClient $daemon)
    {
        $tenant = $request->attributes->get('tenant');

        if ($database->tenant_id !== $tenant->id) {
            abort(403);
        }

        $allowed = ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'CREATE', 'DROP', 'INDEX', 'ALTER', 'REFERENCES', 'ALL PRIVILEGES'];

        $validated = $request->validate([
            'privileges'   => ['required', 'array', 'min:1'],
            'privileges.*' => ['required', 'string', 'in:' . implode(',', $allowed)],
        ]);

        try {
            $daemon->updateDatabasePermissions(
                $database->name,
                $database->username,
                $database->engine,
                $validated['privileges']
            );
        } catch (\Throwable $e) {
            Log::warning('Database permissions update failed', ['database_id' => $database->id, 'exception' => $e]);
            return back()->withErrors(['permissions' => 'No se pudieron actualizar los permisos: ' . $e->getMessage()]);
        }

        return back()->with('success', 'Permisos actualizados correctamente.');
    }

    public function destroy(Request $request, ManagedDatabase $database, DaemonClient $daemon)
    {
        $tenant = $request->attributes->get('tenant');

        if ($database->tenant_id !== $tenant->id) {
            abort(403);
        }

        try {
            $daemon->deleteDatabase($database->name, $database->username, $database->engine);
        } catch (\Throwable $e) {
            Log::warning('Database deletion failed', ['database_id' => $database->id, 'exception' => $e]);
            $database->update(['status' => 'delete_error']);
            return redirect()->route('client.databases.index')
                ->withErrors(['database' => 'El agente no pudo eliminar la base. Revisa operaciones del agente o contacta soporte.']);
        }

        $database->delete();

        return redirect()->route('client.databases.index')->with('success', 'Database deleted successfully.');
    }
}
