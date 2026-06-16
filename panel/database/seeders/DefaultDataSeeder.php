<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServerNode;
use App\Models\HostingPlan;
use App\Models\NameserverSetting;
use App\Models\SystemSetting;

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

    }
}
