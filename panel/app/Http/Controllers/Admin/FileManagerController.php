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
        $domain = $this->normalizeDomain($request->query('domain'));
        $path = $request->query('path', '/');

        try {
            return response()->json($this->daemon->fileList($domain, $path));
        } catch (\Throwable $e) {
            Log::warning('Admin FileManager list failed', ['domain' => $domain, 'path' => $path, 'error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function read(Request $request)
    {
        $domain = $this->normalizeDomain($request->query('domain'));
        $path = $request->query('path', '');
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
            'content' => ['present', 'string'],
        ]);
        try {
            return response()->json($this->daemon->fileWrite($this->normalizeDomain($validated['domain'] ?? null), $validated['path'], $validated['content']));
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
            return response()->json($this->daemon->fileMkdir($this->normalizeDomain($validated['domain'] ?? null), $validated['path']));
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
            return response()->json($this->daemon->fileDelete($this->normalizeDomain($validated['domain'] ?? null), $validated['path']));
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
            return response()->json($this->daemon->fileRename(
                $this->normalizeDomain($validated['domain'] ?? null),
                $validated['old_path'],
                $validated['new_path']
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
            return response()->json($this->daemon->fileUpload(
                $this->normalizeDomain($request->input('domain')),
                $request->input('path', '/'),
                $request->file('file')
            ));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function download(Request $request)
    {
        $domain = $this->normalizeDomain($request->query('domain'));
        $path = $request->query('path', '');
        if (empty($path)) {
            abort(400, 'path required');
        }
        try {
            $response = $this->daemon->fileDownloadProxy($domain, $path);
            $filename = basename($path);
            return response($response->body(), 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        } catch (\Throwable $e) {
            abort(500, $e->getMessage());
        }
    }

    private function normalizeDomain(?string $domain): ?string
    {
        $domain = trim((string) $domain);

        return $domain === '' ? null : strtolower($domain);
    }
}
