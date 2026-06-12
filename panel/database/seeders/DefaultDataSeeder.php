<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ServerNode;
use App\Models\Tenant;
use App\Models\HostingPlan;
use App\Models\NameserverSetting;
use App\Models\SystemSetting;
use RuntimeException;

class DefaultDataSeeder extends Seeder
{
    public function run(): void
    {
        HostingPlan::updateOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'max_sites' => 1,
                'max_databases' => 1,
                'storage_mb' => 1024,
                'bandwidth_gb' => 10,
                'email_accounts' => 0,
                'monthly_price' => 0,
                'is_active' => true,
                'description' => 'Plan base para instalaciones pequeñas y primeras pruebas.'
            ]
        );

        HostingPlan::updateOrCreate(
            ['slug' => 'growth'],
            [
                'name' => 'Growth',
                'max_sites' => 5,
                'max_databases' => 5,
                'storage_mb' => 10240,
                'bandwidth_gb' => 100,
                'email_accounts' => 10,
                'monthly_price' => 9.99,
                'is_active' => true,
                'description' => 'Plan para clientes con varios sitios y mayor capacidad.'
            ]
        );

        SystemSetting::firstOrCreate(
            ['key' => 'app_name'],
            ['value' => 'XPanel']
        );

        NameserverSetting::firstOrCreate(
            ['name' => 'default'],
            [
                'provider' => 'xpanel',
                'is_active' => false,
            ]
        );

        ServerNode::where('auth_token', implode('_', ['secret', 'token']))->delete();

        $daemonToken = trim((string) env('XPANEL_DAEMON_TOKEN', ''));
        if ($daemonToken !== '') {
            ServerNode::updateOrCreate(
                ['name' => 'Local Node'],
                [
                    'ip_address' => '127.0.0.1',
                    'port' => 7070,
                    'auth_token' => $daemonToken,
                    'is_active' => true
                ]
            );
        }

        if (!filter_var(env('XPANEL_SEED_DEMO_USERS', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $adminPassword = trim((string) env('XPANEL_DEMO_ADMIN_PASSWORD', ''));
        $clientPassword = trim((string) env('XPANEL_DEMO_CLIENT_PASSWORD', ''));

        if ($adminPassword === '' || $clientPassword === '') {
            throw new RuntimeException('Demo users require XPANEL_DEMO_ADMIN_PASSWORD and XPANEL_DEMO_CLIENT_PASSWORD.');
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@xpanel.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt($adminPassword),
                'role' => 'admin'
            ]
        );
        $admin->name = 'Admin';
        $admin->role = 'admin';
        $admin->save();

        $client = User::firstOrCreate(
            ['email' => 'client@xpanel.com'],
            [
                'name' => 'Client User',
                'password' => bcrypt($clientPassword),
                'role' => 'client'
            ]
        );
        $client->name = 'Client User';
        $client->role = 'client';
        $client->save();

        Tenant::updateOrCreate(
            ['domain' => 'client.com'],
            [
                'name' => 'Default Client',
                'user_id' => $client->id,
                'status' => 'active'
            ]
        );
    }
}
