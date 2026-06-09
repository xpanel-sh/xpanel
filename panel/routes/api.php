<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DaemonController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas de comunicación con el Daemon
Route::get('/daemon/status', function () {
    $client = new \App\Services\DaemonClient();
    return $client->getStatus()->json();
});

// Admin API
Route::prefix('admin')->group(function () {
    // Route::apiResource('tenants', \App\Http\Controllers\Admin\TenantController::class);
    // Route::apiResource('nodes', \App\Http\Controllers\Admin\NodeController::class);
});

// Client API
Route::prefix('client')->group(function () {
    // Route::apiResource('sites', \App\Http\Controllers\Client\SiteController::class);
});
