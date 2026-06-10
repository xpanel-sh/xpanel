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

    // ===========================
    // File Manager Methods
    // ===========================

    public function fileList(string $domain, string $path = '/'): array
    {
        $response = $this->http()->get("{$this->baseUrl}/api/files/list", [
            'domain' => $domain,
            'path' => $path,
        ]);
        if (!$response->successful()) {
            Log::warning('Daemon fileList failed', ['domain' => $domain, 'path' => $path, 'body' => $response->body()]);
            throw new \RuntimeException('Daemon fileList failed: ' . $response->body());
        }
        return $response->json() ?? [];
    }

    public function fileRead(string $domain, string $path): array
    {
        $response = $this->http()->get("{$this->baseUrl}/api/files/read", [
            'domain' => $domain,
            'path' => $path,
        ]);
        if (!$response->successful()) {
            Log::warning('Daemon fileRead failed', ['domain' => $domain, 'path' => $path, 'body' => $response->body()]);
            throw new \RuntimeException('Daemon fileRead failed: ' . $response->body());
        }
        return $response->json() ?? [];
    }

    public function fileWrite(string $domain, string $path, string $content): array
    {
        return $this->post('/api/files/write', ['domain' => $domain, 'path' => $path, 'content' => $content], 'Daemon fileWrite failed');
    }

    public function fileMkdir(string $domain, string $path): array
    {
        return $this->post('/api/files/mkdir', ['domain' => $domain, 'path' => $path], 'Daemon fileMkdir failed');
    }

    public function fileDelete(string $domain, string $path): array
    {
        return $this->post('/api/files/delete', ['domain' => $domain, 'path' => $path], 'Daemon fileDelete failed');
    }

    public function fileRename(string $domain, string $oldPath, string $newPath): array
    {
        return $this->post('/api/files/rename', ['domain' => $domain, 'old_path' => $oldPath, 'new_path' => $newPath], 'Daemon fileRename failed');
    }

    public function fileUpload(string $domain, string $destDir, \Illuminate\Http\UploadedFile $file): array
    {
        $response = $this->http()
            ->attach('file', $file->getContent(), $file->getClientOriginalName())
            ->post("{$this->baseUrl}/api/files/upload", [
                'domain' => $domain,
                'path' => $destDir,
            ]);
        if (!$response->successful()) {
            Log::warning('Daemon fileUpload failed', ['domain' => $domain, 'body' => $response->body()]);
            throw new \RuntimeException('Daemon fileUpload failed: ' . $response->body());
        }
        return $response->json() ?? [];
    }

    public function fileDownloadProxy(string $domain, string $path): \Illuminate\Http\Client\Response
    {
        $response = $this->http()->get("{$this->baseUrl}/api/files/download", [
            'domain' => $domain,
            'path' => $path,
        ]);
        if (!$response->successful()) {
            throw new \RuntimeException('Daemon fileDownload failed: ' . $response->body());
        }
        return $response;
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
