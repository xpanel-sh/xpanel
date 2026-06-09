<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Admin users are still authenticated by the `auth` middleware.
        // We only skip tenant binding for admins because they manage all tenants globally.
        if ($user->role === 'admin') {
            return $next($request);
        }

        $tenant = $user->tenant;
        if (!$tenant) {
            abort(403, 'No tenant assigned to this account.');
        }

        if ($tenant->status !== 'active') {
            abort(403, 'This account is currently suspended.');
        }

        $request->attributes->set('tenant', $tenant);
        return $next($request);
    }
}
