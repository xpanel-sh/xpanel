<?php

namespace App\Http\Controllers\Client\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Site;
use App\Services\SiteProvisioner;
use App\Services\DaemonClient;
use Illuminate\Support\Facades\Log;

class SiteController extends Controller
{
    /**
     * Display a listing of the sites.
     */
    public function index()
    {
        $tenant = request()->attributes->get('tenant');
        $sites = Site::where('tenant_id', $tenant->id)->latest()->get();

        return view('client.web.index', compact('sites'));
    }

    /**
     * Show the form for creating a new site.
     */
    public function create()
    {
        return view('client.web.create');
    }

    /**
     * Store a newly created site in storage.
     */
    public function store(Request $request, SiteProvisioner $provisioner)
    {
        $tenant = $request->attributes->get('tenant');

        $validated = $request->validate([
            'domain' => 'required|regex:/^(?!:\/\/)(?=.{1,255}$)((.{1,63}\.){1,127}(?![0-9]*$)[a-z0-9-]+\.?)$/i|unique:sites,domain',
            'project_type' => 'required|in:php,node,static,python',
            'web_server' => 'required_if:project_type,php|in:apache,nginx',
            'php_version' => 'required_if:project_type,php|in:8.0,8.1,8.2,8.3',
        ]);

        try {
            $provisioner->provisionForTenant($tenant, $validated);
        } catch (\Throwable $e) {
            Log::warning('Site provisioning failed', ['tenant_id' => $tenant->id, 'domain' => $validated['domain'] ?? null, 'exception' => $e]);
            return back()->withErrors(['domain' => 'No se pudo provisionar el sitio. Revisa operaciones del agente o contacta soporte.'])->withInput();
        }

        return redirect()->route('client.sites.index')->with('success', 'Sitio enviado a provisión correctamente.');
    }

    public function restart(Request $request, Site $site, DaemonClient $daemon)
    {
        $tenant = $request->attributes->get('tenant');
        if ($site->tenant_id !== $tenant->id) {
            abort(403);
        }

        $containerName = 'xpanel-site-' . str_replace('.', '-', strtolower($site->domain));
        try {
            $daemon->restartSite($containerName);
        } catch (\Throwable $e) {
            Log::warning('Site restart failed', ['site_id' => $site->id, 'exception' => $e]);
            return redirect()->route('client.sites.index')
                ->withErrors(['site' => 'El agente no pudo reiniciar el sitio. Revisa operaciones del agente o contacta soporte.']);
        }

        return redirect()->route('client.sites.index')->with('success', 'Sitio reiniciado correctamente.');
    }

    public function destroy(Request $request, Site $site, DaemonClient $daemon)
    {
        $tenant = $request->attributes->get('tenant');
        if ($site->tenant_id !== $tenant->id) {
            abort(403);
        }

        $containerName = 'xpanel-site-' . str_replace('.', '-', strtolower($site->domain));
        try {
            $daemon->deleteSite($containerName);
        } catch (\Throwable $e) {
            Log::warning('Site deletion failed', ['site_id' => $site->id, 'exception' => $e]);
            $site->update(['status' => 'delete_error']);
            return redirect()->route('client.sites.index')
                ->withErrors(['site' => 'El agente no pudo eliminar el sitio. Revisa operaciones del agente o contacta soporte.']);
        }

        $site->delete();

        return redirect()->route('client.sites.index')->with('success', 'Sitio eliminado correctamente.');
    }
}
