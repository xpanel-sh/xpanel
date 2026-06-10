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

        return view('client.databases.index', compact('databases'));
    }

    public function create(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $sites = Site::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('domain')
            ->get(['id', 'domain']);

        return view('client.databases.create', compact('sites'));
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

        $validated = $request->validate([
            'name' => ['required', 'regex:/^[A-Za-z0-9_]+$/', 'min:3', 'max:32', 'unique:managed_databases,name'],
            'username' => ['required', 'regex:/^[A-Za-z0-9_]+$/', 'min:3', 'max:32', 'unique:managed_databases,username'],
            'password' => ['required', 'string', 'min:16', 'max:128'],
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
            'engine' => ['required', 'in:mariadb,mysql'],
        ]);

        if (!empty($validated['site_id'])) {
            $siteBelongsToTenant = Site::where('id', $validated['site_id'])
                ->where('tenant_id', $tenant->id)
                ->exists();

            if (!$siteBelongsToTenant) {
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
            return redirect()->route('client.databases.index')
                ->withErrors(['database' => 'Base registrada, pero el agente no pudo provisionarla. Revisa operaciones del agente o contacta soporte.']);
        }

        return redirect()->route('client.databases.index')
            ->with('success', 'Database created. Guarda la contraseña que ingresaste; XPanel no la muestra ni la recupera.');
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
