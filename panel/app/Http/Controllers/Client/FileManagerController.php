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

    public function index(Request $request, Site $site)
    {
        $tenant = $request->attributes->get('tenant');
        if ($site->tenant_id !== $tenant->id) {
            abort(403);
        }
        return view('client.files.index', compact('site'));
    }

    public function list(Request $request, Site $site)
    {
        $tenant = $request->attributes->get('tenant');
        if ($site->tenant_id !== $tenant->id) {
            abort(403);
        }
        $path = $request->query('path', '/');
        try {
            return response()->json($this->daemon->fileList($site->domain, $path));
        } catch (\Throwable $e) {
            Log::warning('FileManager list failed', ['site' => $site->domain, 'path' => $path, 'error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function read(Request $request, Site $site)
    {
        $tenant = $request->attributes->get('tenant');
        if ($site->tenant_id !== $tenant->id) {
            abort(403);
        }
        $path = $request->query('path', '');
        if (empty($path)) {
            return response()->json(['error' => 'path required'], 400);
        }
        try {
            return response()->json($this->daemon->fileRead($site->domain, $path));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function write(Request $request, Site $site)
    {
        $tenant = $request->attributes->get('tenant');
        if ($site->tenant_id !== $tenant->id) {
            abort(403);
        }
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:2048'],
            'content' => ['present', 'string'],
        ]);
        try {
            return response()->json($this->daemon->fileWrite($site->domain, $validated['path'], $validated['content']));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function mkdir(Request $request, Site $site)
    {
        $tenant = $request->attributes->get('tenant');
        if ($site->tenant_id !== $tenant->id) {
            abort(403);
        }
        $validated = $request->validate(['path' => ['required', 'string', 'max:2048']]);
        try {
            return response()->json($this->daemon->fileMkdir($site->domain, $validated['path']));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete(Request $request, Site $site)
    {
        $tenant = $request->attributes->get('tenant');
        if ($site->tenant_id !== $tenant->id) {
            abort(403);
        }
        $validated = $request->validate(['path' => ['required', 'string', 'max:2048']]);
        try {
            return response()->json($this->daemon->fileDelete($site->domain, $validated['path']));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function rename(Request $request, Site $site)
    {
        $tenant = $request->attributes->get('tenant');
        if ($site->tenant_id !== $tenant->id) {
            abort(403);
        }
        $validated = $request->validate([
            'old_path' => ['required', 'string', 'max:2048'],
            'new_path' => ['required', 'string', 'max:2048'],
        ]);
        try {
            return response()->json($this->daemon->fileRename($site->domain, $validated['old_path'], $validated['new_path']));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function upload(Request $request, Site $site)
    {
        $tenant = $request->attributes->get('tenant');
        if ($site->tenant_id !== $tenant->id) {
            abort(403);
        }
        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'path' => ['required', 'string'],
        ]);
        try {
            return response()->json($this->daemon->fileUpload($site->domain, $request->input('path', '/'), $request->file('file')));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function download(Request $request, Site $site)
    {
        $tenant = $request->attributes->get('tenant');
        if ($site->tenant_id !== $tenant->id) {
            abort(403);
        }
        $path = $request->query('path', '');
        if (empty($path)) {
            abort(400, 'path required');
        }
        try {
            $response = $this->daemon->fileDownloadProxy($site->domain, $path);
            $filename = basename($path);
            return response($response->body(), 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        } catch (\Throwable $e) {
            abort(500, $e->getMessage());
        }
    }
}
