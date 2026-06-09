<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DaemonClient
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.xpanel_daemon.url', 'http://127.0.0.1:7070'), '/');
        $this->token = config('services.xpanel_daemon.token');
    }

    /**
     * Check daemon status
     */
    public function getStatus()
    {
        try {
            return Http::get("{$this->baseUrl}/status");
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Daemon Unreachable'], 503);
        }
    }

    /**
     * Provision a new site
     */
    public function createSite($domain, $projectType, $webServer, $phpVersion)
    {
        $response = $this->http()->post("{$this->baseUrl}/api/site/create", [
            'name' => 'xpanel-site-' . str_replace('.', '-', strtolower($domain)),
            'domain' => $domain,
            'type' => $projectType,
            'web_server' => $webServer,
            'php_version' => $phpVersion,
        ]);

        if (!$response->successful()) {
            Log::warning('Daemon createSite failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Daemon createSite failed.');
        }

        return $response;
    }

    public function restartSite(string $containerName): array
    {
        return $this->post('/api/site/restart', [
            'name' => $containerName,
        ], 'Daemon restartSite failed');
    }

    public function deleteSite(string $containerName): array
    {
        return $this->post('/api/site/delete', [
            'name' => $containerName,
        ], 'Daemon deleteSite failed');
    }

    public function createDatabase(string $name, string $username, string $password, string $engine): array
    {
        return $this->post('/api/database/create', [
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'engine' => $engine,
        ], 'Daemon createDatabase failed');
    }

    public function deleteDatabase(string $name, string $username, string $engine): array
    {
        return $this->post('/api/database/delete', [
            'name' => $name,
            'username' => $username,
            'engine' => $engine,
        ], 'Daemon deleteDatabase failed');
    }

    public function createEmailAccount(string $email, string $domain, int $quotaMb, string $password): array
    {
        return $this->post('/api/mail/account/create', [
            'email' => $email,
            'domain' => $domain,
            'quota_mb' => $quotaMb,
            'password' => $password,
        ], 'Daemon createEmailAccount failed');
    }

    public function deleteEmailAccount(string $email): array
    {
        return $this->post('/api/mail/account/delete', [
            'email' => $email,
        ], 'Daemon deleteEmailAccount failed');
    }

    public function resetEmailPassword(string $email, string $password): array
    {
        return $this->post('/api/mail/account/reset-password', [
            'email' => $email,
            'password' => $password,
        ], 'Daemon resetEmailPassword failed');
    }

    public function upsertDnsRecord(array $record): array
    {
        return $this->post('/api/dns/record/upsert', $record, 'Daemon upsertDnsRecord failed');
    }

    public function deleteDnsRecord(array $record): array
    {
        return $this->post('/api/dns/record/delete', $record, 'Daemon deleteDnsRecord failed');
    }

    public function applyNameservers(array $nameservers): array
    {
        return $this->post('/api/dns/nameservers/apply', [
            'nameservers' => $nameservers,
        ], 'Daemon applyNameservers failed');
    }

    public function operations(): array
    {
        $response = $this->http()->get("{$this->baseUrl}/api/operations");

        if (!$response->successful()) {
            Log::warning('Daemon operations failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Daemon operations failed.');
        }

        return $response->json() ?? [];
    }

    public function runtimeStatus(): array
    {
        $response = $this->http()->get("{$this->baseUrl}/api/runtime/status");

        if (!$response->successful()) {
            Log::warning('Daemon runtime status failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Daemon runtime status failed.');
        }

        return $response->json() ?? [];
    }

    private function post(string $path, array $payload, string $errorPrefix): array
    {
        $response = $this->http()->post("{$this->baseUrl}{$path}", $payload);

        if (!$response->successful()) {
            Log::warning($errorPrefix, [
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException($errorPrefix . '.');
        }

        return $response->json() ?? [];
    }

    private function http()
    {
        $client = Http::timeout(15);

        if ($this->token) {
            $client = $client->withToken($this->token)->withHeaders([
                'X-XPanel-Token' => $this->token,
            ]);
        }

        return $client;
    }
}
