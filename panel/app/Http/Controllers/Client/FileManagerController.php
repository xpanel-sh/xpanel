<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\DaemonClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FileManagerController extends Controller
{
    public function __construct(private DaemonClient $daemon) {}

    public function index(Request $request, ?string $domain = null)
    {
        $tenant = $request->attributes->get('tenant');
        $domain = $this->normalizeDomain($domain);
        $site = $domain ? $this->siteForTenant($tenant->id, $domain) : null;
        $sites = Site::where('tenant_id', $tenant->id)->orderBy('domain')->get(['id', 'tenant_id', 'domain']);

        return view('client.web.files.ikode', compact('site', 'sites', 'domain'));
    }

    public function list(Request $request)
    {
        $tenant = $request->attributes->get('tenant');
        $domain = $this->normalizeDomain($request->query('domain'));
        $path = $request->query('path', '/');
        $prefixEntries = false;

        if (!$domain) {
            if ($path === '/' || $path === '') {
                return response()->json($this->tenantRootList($tenant->id));
            }
            [$domain, $path] = $this->domainAndPathFromRoot($tenant->id, $path);
            $prefixEntries = true;
        } else {
            $this->siteForTenant($tenant->id, $domain);
        }

        try {
            $payload = $this->daemon->fileList($domain, $path);

            if ($prefixEntries) {
                $payload['path'] = '/' . $domain . ($path === '/' ? '' : $path);
                $payload['entries'] = collect($payload['entries'] ?? [])
                    ->map(function (array $entry) use ($domain) {
                        $entry['path'] = '/' . $domain . ($entry['path'] ?? '/');
                        return $entry;
                    })
                    ->all();
            }

            return response()->json($payload);
        } catch (\Throwable $e) {
            Log::warning('FileManager list failed', ['domain' => $domain, 'path' => $path, 'error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function read(Request $request)
    {
        [$domain, $path] = $this->resolveOperationTarget($request, $request->query('path', ''));
        if (empty($path)) {
            return response()->json(['error' => 'path required'], 400);
        }
        try {
            return response()->json($this->daemon->fileRead($domain, $path));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function write(Request $request)
    {
        $validated = $request->validate([
            'domain' => ['nullable', 'string', 'max:255'],
            'path' => ['required', 'string', 'max:2048'],
            'content' => ['present', 'nullable', 'string'],
        ]);
        [$domain, $path] = $this->resolveOperationTarget($request, $validated['path'], $validated['domain'] ?? null);

        try {
            return response()->json($this->daemon->fileWrite($domain, $path, $validated['content'] ?? ''));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function mkdir(Request $request)
    {
        $validated = $request->validate([
            'domain' => ['nullable', 'string', 'max:255'],
            'path' => ['required', 'string', 'max:2048'],
        ]);
        [$domain, $path] = $this->resolveOperationTarget($request, $validated['path'], $validated['domain'] ?? null);

        try {
            return response()->json($this->daemon->fileMkdir($domain, $path));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'domain' => ['nullable', 'string', 'max:255'],
            'path' => ['required', 'string', 'max:2048'],
        ]);
        [$domain, $path] = $this->resolveOperationTarget($request, $validated['path'], $validated['domain'] ?? null);

        try {
            return response()->json($this->daemon->fileDelete($domain, $path));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function rename(Request $request)
    {
        $validated = $request->validate([
            'domain' => ['nullable', 'string', 'max:255'],
            'old_path' => ['required', 'string', 'max:2048'],
            'new_path' => ['required', 'string', 'max:2048'],
        ]);
        [$domain, $oldPath] = $this->resolveOperationTarget($request, $validated['old_path'], $validated['domain'] ?? null);
        [$newDomain, $newPath] = $this->resolveOperationTarget($request, $validated['new_path'], $validated['domain'] ?? null);

        if ($domain !== $newDomain) {
            return response()->json(['error' => 'cannot rename across site roots'], 422);
        }

        try {
            return response()->json($this->daemon->fileRename($domain, $oldPath, $newPath));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function upload(Request $request)
    {
        $request->validate([
            'domain' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:51200'],
            'path' => ['required', 'string'],
        ]);
        [$domain, $path] = $this->resolveOperationTarget($request, $request->input('path', '/'), $request->input('domain'));

        try {
            return response()->json($this->daemon->fileUpload($domain, $path, $request->file('file')));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function extract(Request $request)
    {
        $request->validate([
            'domain' => ['nullable', 'string', 'max:255'],
            'path' => ['required', 'string', 'max:2048'],
        ]);
        [$domain, $path] = $this->resolveOperationTarget($request, $request->input('path'), $request->input('domain'));

        try {
            return response()->json($this->daemon->fileExtract($domain, $path));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function search(Request $request)
    {
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validate([
            'domain' => ['nullable', 'string', 'max:255'],
            'path' => ['nullable', 'string', 'max:2048'],
            'query' => ['required', 'string', 'max:255'],
            'include_content' => ['nullable', 'boolean'],
            'case_sensitive' => ['nullable', 'boolean'],
        ]);

        $domain = $this->normalizeDomain($validated['domain'] ?? null);
        $path = $validated['path'] ?? '/';
        $includeContent = (bool) ($validated['include_content'] ?? true);
        $caseSensitive = (bool) ($validated['case_sensitive'] ?? false);

        try {
            if (!$domain && ($path === '/' || $path === '')) {
                $payloads = Site::where('tenant_id', $tenant->id)
                    ->orderBy('domain')
                    ->get(['domain'])
                    ->map(function (Site $site) use ($validated, $includeContent, $caseSensitive) {
                        $payload = $this->daemon->fileSearch($site->domain, '/', $validated['query'], $includeContent, $caseSensitive);
                        $payload['results'] = collect($payload['results'] ?? [])
                            ->map(function (array $result) use ($site) {
                                $result['path'] = '/' . $site->domain . ($result['path'] ?? '/');
                                return $result;
                            })
                            ->all();
                        return $payload;
                    });

                return response()->json([
                    'query' => $validated['query'],
                    'path' => '/',
                    'results' => $payloads->flatMap(fn (array $payload) => $payload['results'] ?? [])->take(200)->values()->all(),
                    'truncated' => $payloads->contains(fn (array $payload) => (bool) ($payload['truncated'] ?? false)),
                    'scanned' => $payloads->sum(fn (array $payload) => (int) ($payload['scanned'] ?? 0)),
                ]);
            }

            $prefixEntries = false;
            if (!$domain) {
                [$domain, $path] = $this->domainAndPathFromRoot($tenant->id, $path);
                $prefixEntries = true;
            } else {
                $this->siteForTenant($tenant->id, $domain);
            }

            $payload = $this->daemon->fileSearch($domain, $path ?: '/', $validated['query'], $includeContent, $caseSensitive);
            if ($prefixEntries) {
                $payload['path'] = '/' . $domain . (($path === '/' || $path === '') ? '' : $path);
                $payload['results'] = collect($payload['results'] ?? [])
                    ->map(function (array $result) use ($domain) {
                        $result['path'] = '/' . $domain . ($result['path'] ?? '/');
                        return $result;
                    })
                    ->all();
            }

            return response()->json($payload);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function download(Request $request)
    {
        [$domain, $path] = $this->resolveOperationTarget($request, $request->query('path', ''), $request->query('domain'));
        if (empty($path)) {
            abort(400, 'path required');
        }
        try {
            $response = $this->daemon->fileDownloadProxy($domain, $path);
            $filename = basename($path);
            $disposition = $request->boolean('inline') ? 'inline' : 'attachment';
            return response($response->body(), 200, [
                'Content-Type' => $this->contentTypeFor($filename),
                'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
            ]);
        } catch (\Throwable $e) {
            abort(500, $e->getMessage());
        }
    }

    private function contentTypeFor(string $filename): string
    {
        return match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'bmp' => 'image/bmp',
            'pdf' => 'application/pdf',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
            'mov' => 'video/quicktime',
            'm4v' => 'video/x-m4v',
            default => 'application/octet-stream',
        };
    }

    private function tenantRootList(int $tenantId): array
    {
        $entries = Site::where('tenant_id', $tenantId)
            ->orderBy('domain')
            ->get(['domain', 'updated_at'])
            ->map(fn (Site $site) => [
                'name' => $site->domain,
                'path' => '/' . $site->domain,
                'is_dir' => true,
                'size' => 0,
                'mode' => 'drwxr-xr-x',
                'mod_time' => optional($site->updated_at)->toIso8601String(),
            ])
            ->values()
            ->all();

        return ['path' => '/', 'entries' => $entries];
    }

    private function resolveOperationTarget(Request $request, string $path, ?string $domain = null): array
    {
        $tenant = $request->attributes->get('tenant');
        $domain = $this->normalizeDomain($domain ?? $request->input('domain') ?? $request->query('domain'));

        if (!$domain) {
            return $this->domainAndPathFromRoot($tenant->id, $path);
        }

        $this->siteForTenant($tenant->id, $domain);

        return [$domain, $path];
    }

    private function domainAndPathFromRoot(int $tenantId, string $path): array
    {
        $parts = array_values(array_filter(explode('/', trim($path, '/')), fn ($part) => $part !== ''));
        if (!$parts) {
            abort(422, 'select a site first');
        }

        $domain = $this->normalizeDomain(array_shift($parts));
        $this->siteForTenant($tenantId, $domain);
        $sitePath = '/' . implode('/', $parts);

        return [$domain, $sitePath === '/' ? '/' : $sitePath];
    }

    private function siteForTenant(int $tenantId, string $domain): Site
    {
        return Site::where('tenant_id', $tenantId)->where('domain', $domain)->firstOrFail();
    }

    private function normalizeDomain(?string $domain): ?string
    {
        $domain = trim((string) $domain);

        return $domain === '' ? null : strtolower($domain);
    }
}
