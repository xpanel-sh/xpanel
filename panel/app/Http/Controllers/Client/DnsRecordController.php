<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\DnsRecord;
use App\Models\Domain;
use App\Models\NameserverSetting;
use Illuminate\Http\Request;
use App\Services\DaemonClient;
use Illuminate\Support\Facades\Log;

class DnsRecordController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');
        $domains = Domain::query()->where('tenant_id', $tenant->id)->orderBy('domain')->get();
        $records = DnsRecord::query()
            ->with('domain')
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->paginate(20);
        $nameservers = NameserverSetting::query()->where('is_active', true)->first();

        return view('client.domains.dns', compact('domains', 'records', 'nameservers'));
    }

    public function store(Request $request, DaemonClient $daemon)
    {
        $tenant = $request->attributes->get('tenant');

        $validated = $request->validate([
            'domain_id' => ['required', 'integer', 'exists:domains,id'],
            'type' => ['required', 'in:A,AAAA,CNAME,MX,TXT,NS,SRV,CAA'],
            'name' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string', 'max:2000'],
            'ttl' => ['required', 'integer', 'min:60', 'max:86400'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $domain = Domain::where('id', $validated['domain_id'])
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $record = DnsRecord::create([
            'tenant_id' => $tenant->id,
            'domain_id' => $domain->id,
            'type' => $validated['type'],
            'name' => $validated['name'],
            'value' => $validated['value'],
            'ttl' => $validated['ttl'],
            'priority' => $validated['priority'] ?? null,
            'is_active' => true,
        ]);

        try {
            $daemon->upsertDnsRecord([
                'domain' => $domain->domain,
                'type' => $record->type,
                'name' => $record->name,
                'value' => $record->value,
                'ttl' => $record->ttl,
                'priority' => $record->priority,
            ]);
            $domain->update(['dns_status' => 'managed']);
        } catch (\Throwable $e) {
            Log::warning('DNS record upsert failed', ['record_id' => $record->id, 'exception' => $e]);
            $domain->update(['dns_status' => 'provision_error']);
            return redirect()->route('client.dns.index')
                ->withErrors(['dns' => 'Registro guardado, pero el agente no pudo aplicarlo. Revisa operaciones del agente o contacta soporte.']);
        }

        return redirect()->route('client.dns.index')->with('success', 'Registro DNS agregado correctamente.');
    }

    public function destroy(Request $request, DnsRecord $record, DaemonClient $daemon)
    {
        $tenant = $request->attributes->get('tenant');

        if ($record->tenant_id !== $tenant->id) {
            abort(403);
        }

        try {
            $daemon->deleteDnsRecord([
                'domain' => $record->domain?->domain,
                'type' => $record->type,
                'name' => $record->name,
                'value' => $record->value,
            ]);
        } catch (\Throwable $e) {
            Log::warning('DNS record deletion failed', ['record_id' => $record->id, 'exception' => $e]);
            return redirect()->route('client.dns.index')
                ->withErrors(['dns' => 'El agente no pudo eliminar el registro. Revisa operaciones del agente o contacta soporte.']);
        }

        $record->delete();

        return redirect()->route('client.dns.index')->with('success', 'Registro DNS eliminado correctamente.');
    }
}
