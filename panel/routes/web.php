<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::group(['middleware' => ['web']], function () {
    $adminLoginPath = config('xpanel.routes.admin_login_path', 'admin/login');
    $clientLoginPath = config('xpanel.routes.client_login_path', 'login');

    // Autenticación Admin
    Route::get($adminLoginPath, [\App\Http\Controllers\Admin\AuthController::class, 'showLogin'])->name('admin.login');
    Route::post($adminLoginPath, [\App\Http\Controllers\Admin\AuthController::class, 'login'])->name('admin.login.post');
    Route::post('/admin/logout', [\App\Http\Controllers\Admin\AuthController::class, 'logout'])->name('admin.logout');

    // Autenticación Client (por defecto /login)
    Route::get($clientLoginPath, [\App\Http\Controllers\Client\AuthController::class, 'showLogin'])->name('client.login');
    Route::post($clientLoginPath, [\App\Http\Controllers\Client\AuthController::class, 'login'])->name('client.login.post');
    Route::post('/logout', [\App\Http\Controllers\Client\AuthController::class, 'logout'])->name('client.logout');

    // Redirección raíz
    Route::get('/', function () {
        return redirect()->route('client.login');
    });

    // Rutas Protegidas y Multi-tenant
    Route::middleware(['auth', \App\Http\Middleware\ResolveTenant::class])->group(function () {

        Route::get('/dashboard', function (Illuminate\Http\Request $request) {
            if ($request->user() && $request->user()->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            $tenant = $request->attributes->get('tenant');
            if (!$tenant) {
                return redirect()->route('client.login')->withErrors([
                    'email' => 'No tenant assigned to this account.',
                ]);
            }

            $siteCount = \App\Models\Site::where('tenant_id', $tenant->id)->count();
            $sites = \App\Models\Site::where('tenant_id', $tenant->id)->latest()->take(5)->get();
            $databaseCount = \App\Models\ManagedDatabase::where('tenant_id', $tenant->id)->count();
            $domainCount = \App\Models\Domain::where('tenant_id', $tenant->id)->count();
            $emailCount = \App\Models\EmailAccount::where('tenant_id', $tenant->id)->count();
            return view('client.dashboard', compact('sites', 'tenant', 'siteCount', 'databaseCount', 'domainCount', 'emailCount'));
        })->name('client.dashboard');

        // Rutas solo Admin
        Route::middleware('can:admin')->group(function () {
            Route::get('/admin/dashboard', function () {
                $clientCount = \App\Models\Tenant::count();
                $siteCount = \App\Models\Site::count();
                $nodeCount = \App\Models\ServerNode::count();
                $planCount = \App\Models\HostingPlan::count();
                return view('admin.dashboard', compact('clientCount', 'siteCount', 'nodeCount', 'planCount'));
            })->name('admin.dashboard');

            Route::get('/admin/plans', [\App\Http\Controllers\Admin\HostingPlanController::class, 'index'])->name('admin.plans.index');
            Route::get('/admin/plans/create', [\App\Http\Controllers\Admin\HostingPlanController::class, 'create'])->name('admin.plans.create');
            Route::post('/admin/plans', [\App\Http\Controllers\Admin\HostingPlanController::class, 'store'])->name('admin.plans.store');
            Route::get('/admin/plans/{plan}/edit', [\App\Http\Controllers\Admin\HostingPlanController::class, 'edit'])->name('admin.plans.edit');
            Route::put('/admin/plans/{plan}', [\App\Http\Controllers\Admin\HostingPlanController::class, 'update'])->name('admin.plans.update');
            Route::post('/admin/plans/{plan}/toggle', [\App\Http\Controllers\Admin\HostingPlanController::class, 'toggle'])->name('admin.plans.toggle');

            // Gestión de Clientes
            Route::prefix('admin/clients')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\TenantController::class, 'index'])->name('admin.clients.index');
                Route::get('/create', [\App\Http\Controllers\Admin\TenantController::class, 'create'])->name('admin.clients.create');
                Route::post('/', [\App\Http\Controllers\Admin\TenantController::class, 'store'])->name('admin.clients.store');
                Route::get('/{tenant}', [\App\Http\Controllers\Admin\TenantController::class, 'show'])->name('admin.clients.show');
                Route::get('/{tenant}/edit', [\App\Http\Controllers\Admin\TenantController::class, 'edit'])->name('admin.clients.edit');
                Route::put('/{tenant}', [\App\Http\Controllers\Admin\TenantController::class, 'update'])->name('admin.clients.update');
                Route::post('/{tenant}/toggle-status', [\App\Http\Controllers\Admin\TenantController::class, 'toggleStatus'])->name('admin.clients.toggle-status');
            });

            Route::get('/admin/servers', [\App\Http\Controllers\Admin\ServerNodeController::class, 'index'])->name('admin.servers.index');
            Route::get('/admin/servers/create', [\App\Http\Controllers\Admin\ServerNodeController::class, 'create'])->name('admin.servers.create');
            Route::post('/admin/servers', [\App\Http\Controllers\Admin\ServerNodeController::class, 'store'])->name('admin.servers.store');
            Route::post('/admin/servers/{server}/toggle', [\App\Http\Controllers\Admin\ServerNodeController::class, 'toggle'])->name('admin.servers.toggle');

            // Web Module (Admin)
            Route::get('/admin/sites', [\App\Http\Controllers\Admin\Web\SiteController::class, 'index'])->name('admin.sites.index');
            Route::get('/admin/domains', [\App\Http\Controllers\Admin\DomainController::class, 'index'])->name('admin.domains.index');
            Route::get('/admin/dns/nameservers', [\App\Http\Controllers\Admin\NameserverController::class, 'edit'])->name('admin.dns.nameservers');
            Route::put('/admin/dns/nameservers', [\App\Http\Controllers\Admin\NameserverController::class, 'update'])->name('admin.dns.nameservers.update');
            Route::get('/admin/daemon/operations', [\App\Http\Controllers\Admin\DaemonOperationController::class, 'index'])->name('admin.daemon.operations');
        });

        // ==========================
        // MÓDULO WEB (CLIENTE)
        // ==========================
        Route::prefix('sites')->name('client.sites.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\Web\SiteController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Client\Web\SiteController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Client\Web\SiteController::class, 'store'])->name('store');
            Route::post('/{site}/restart', [\App\Http\Controllers\Client\Web\SiteController::class, 'restart'])->name('restart');
            Route::delete('/{site}', [\App\Http\Controllers\Client\Web\SiteController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('databases')->name('client.databases.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\DatabaseController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Client\DatabaseController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Client\DatabaseController::class, 'store'])->name('store');
            Route::delete('/{database}', [\App\Http\Controllers\Client\DatabaseController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('domains')->name('client.domains.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\DomainController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Client\DomainController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Client\DomainController::class, 'store'])->name('store');
            Route::delete('/{domain}', [\App\Http\Controllers\Client\DomainController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('emails')->name('client.emails.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\EmailAccountController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Client\EmailAccountController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Client\EmailAccountController::class, 'store'])->name('store');
            Route::post('/{emailAccount}/reset-password', [\App\Http\Controllers\Client\EmailAccountController::class, 'resetPassword'])->name('reset-password');
            Route::delete('/{emailAccount}', [\App\Http\Controllers\Client\EmailAccountController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('dns')->name('client.dns.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\DnsRecordController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Client\DnsRecordController::class, 'store'])->name('store');
            Route::delete('/{record}', [\App\Http\Controllers\Client\DnsRecordController::class, 'destroy'])->name('destroy');
        });

        Route::get('/account', [\App\Http\Controllers\Client\AccountController::class, 'show'])->name('client.account.show');

    });
});
