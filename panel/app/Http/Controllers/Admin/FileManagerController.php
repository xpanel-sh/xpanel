<?php

namespace App\Http\Controllers\Admin;

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
        $domain = $this->normalizeDomain($domain);
        $site = $domain ? Site::where('domain', $domain)->firstOrFail() : null;
        $sites = Site::with('tenant')->orderBy('domain')->get(['id', 'tenant_id', 'domain']);

        return view('admin.files.index', compact('site', 'sites', 'domain'));
    }

    public function list(Request $request)
    {
        [$domain, $path, $virtualPrefix] = $this->resolveOperationTarget(
            $this->normalizeDomain($request->query('domain')),
            $request->query('path', '/')
        );

        try {
            $payload = $this->daemon->fileList($domain, $path);
            if ($virtualPrefix) {
                $payload['path'] = $virtualPrefix . (($path === '/' || $path === '') ? '' : $path);
                $payload['entries'] = collect($payload['entries'] ?? [])
                    ->map(function (array $entry) use ($virtualPrefix) {
                        $entry['path'] = $virtualPrefix . ($entry['path'] ?? '/');
                        return $entry;
                    })
                    ->values()
                    ->all();
            }

            return response()->json($payload);
        } catch (\Throwable $e) {
            Log::warning('Admin FileManager list failed', ['domain' => $domain, 'path' => $path, 'error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function read(Request $request)
    {
        [$domain, $path] = $this->resolveOperationTarget(
            $this->normalizeDomain($request->query('domain')),
            $request->query('path', '')
        );
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
        try {
            [$domain, $path] = $this->resolveOperationTarget($this->normalizeDomain($validated['domain'] ?? null), $validated['path']);
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
        try {
            [$domain, $path] = $this->resolveOperationTarget($this->normalizeDomain($validated['domain'] ?? null), $validated['path']);
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
        try {
            [$domain, $path] = $this->resolveOperationTarget($this->normalizeDomain($validated['domain'] ?? null), $validated['path']);
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
        try {
            [$domain, $oldPath] = $this->resolveOperationTarget($this->normalizeDomain($validated['domain'] ?? null), $validated['old_path']);
            [$newDomain, $newPath] = $this->resolveOperationTarget($this->normalizeDomain($validated['domain'] ?? null), $validated['new_path']);
            if ($domain !== $newDomain) {
                return response()->json(['error' => 'cross-site moves are not supported'], 422);
            }

            return response()->json($this->daemon->fileRename(
                $domain,
                $oldPath,
                $newPath
            ));
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
        try {
            [$domain, $path] = $this->resolveOperationTarget(
                $this->normalizeDomain($request->input('domain')),
                $request->input('path', '/')
            );

            return response()->json($this->daemon->fileUpload(
                $domain,
                $path,
                $request->file('file')
            ));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function extract(Request $request)
    {
        $validated = $request->validate([
            'domain' => ['nullable', 'string', 'max:255'],
            'path' => ['required', 'string', 'max:2048'],
        ]);
        try {
            [$domain, $path] = $this->resolveOperationTarget($this->normalizeDomain($validated['domain'] ?? null), $validated['path']);
            return response()->json($this->daemon->fileExtract(
                $domain,
                $path
            ));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'domain' => ['nullable', 'string', 'max:255'],
            'path' => ['nullable', 'string', 'max:2048'],
            'query' => ['required', 'string', 'max:255'],
            'include_content' => ['nullable', 'boolean'],
            'case_sensitive' => ['nullable', 'boolean'],
        ]);

        try {
            [$domain, $path, $virtualPrefix] = $this->resolveOperationTarget(
                $this->normalizeDomain($validated['domain'] ?? null),
                $validated['path'] ?? '/'
            );
            $payload = $this->daemon->fileSearch(
                $domain,
                $path,
                $validated['query'],
                (bool) ($validated['include_content'] ?? true),
                (bool) ($validated['case_sensitive'] ?? false)
            );
            if ($virtualPrefix) {
                $payload['path'] = $virtualPrefix . (($path === '/' || $path === '') ? '' : $path);
                $payload['results'] = collect($payload['results'] ?? [])
                    ->map(function (array $result) use ($virtualPrefix) {
                        $result['path'] = $virtualPrefix . ($result['path'] ?? '/');
                        return $result;
                    })
                    ->values()
                    ->all();
            }

            return response()->json($payload);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function download(Request $request)
    {
        [$domain, $path] = $this->resolveOperationTarget(
            $this->normalizeDomain($request->query('domain')),
            $request->query('path', '')
        );
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

    private function normalizeDomain(?string $domain): ?string
    {
        $domain = trim((string) $domain);

        return $domain === '' ? null : strtolower($domain);
    }

    private function resolveOperationTarget(?string $domain, string $path): array
    {
        if ($domain) {
            Site::where('domain', $domain)->firstOrFail();
            return [$domain, $this->normalizeSitePath($path), null];
        }

        $parts = array_values(array_filter(explode('/', str_replace('\\', '/', trim($path, '/'))), fn ($part) => $part !== ''));
        if (!$parts) {
            return [null, '/', null];
        }

        $candidateDomain = $this->normalizeDomain(array_shift($parts));
        if (!$candidateDomain || !Site::where('domain', $candidateDomain)->exists()) {
            return [null, $this->normalizeSitePath($path), null];
        }

        return [$candidateDomain, $this->normalizeSitePath('/' . implode('/', $parts)), '/' . $candidateDomain];
    }

    private function normalizeSitePath(string $path): string
    {
        $parts = explode('/', str_replace('\\', '/', $path));
        $clean = [];
        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($clean);
                continue;
            }
            $clean[] = $part;
        }

        return $clean ? '/' . implode('/', $clean) : '/';
    }
}
