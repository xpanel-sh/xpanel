<?php

namespace App\Services;

use App\Models\ServerNode;
use App\Models\Site;
use App\Models\Tenant;
use RuntimeException;

class SiteProvisioner
{
    public function __construct(private DaemonClient $daemon)
    {
    }

    public function provisionForTenant(Tenant $tenant, array $data): Site
    {
        $plan = $tenant->plan;
        if ($plan && $plan->max_sites > 0 && $tenant->sites()->count() >= $plan->max_sites) {
            throw new RuntimeException('Site limit reached for the assigned plan.');
        }

        $node = ServerNode::query()->where('is_active', true)->first();

        if (!$node) {
            throw new RuntimeException('No active server connected to provision this site.');
        }

        $site = Site::create([
            'tenant_id' => $tenant->id,
            'server_node_id' => $node->id,
            'domain' => strtolower($data['domain']),
            'project_type' => $data['project_type'],
            'web_server' => $data['web_server'] ?? 'apache',
            'php_version' => $data['php_version'] ?? '8.2',
            'status' => 'provisioning',
        ]);

        try {
            $this->daemon->createSite(
                $site->domain,
                $site->project_type,
                $site->web_server,
                $site->php_version
            );

            // Status stays 'provisioning' — the panel polls /client/sites/{site}/status
            // to confirm the container is actually running before marking it active.
            // This supports future multi-server/multi-node scenarios where the daemon
            // may be on a remote node and container health is confirmed asynchronously.
            return $site->refresh();
        } catch (\Throwable $e) {
            $site->delete();
            throw new RuntimeException('Could not provision the site on the connected server.', previous: $e);
        }
    }
}
