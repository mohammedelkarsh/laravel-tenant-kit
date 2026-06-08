<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->firstOrCreate(
            ['id' => 'demo'],
            ['name' => 'Demo Workspace'],
        );

        if (! $tenant->domains()->where('domain', 'demo')->exists()) {
            $tenant->domains()->create(['domain' => 'demo']);
        }

        Artisan::call('tenants:migrate', [
            '--tenants' => [$tenant->id],
            '--force' => true,
        ]);

        Artisan::call('tenants:seed', [
            '--tenants' => [$tenant->id],
            '--force' => true,
        ]);

        tenancy()->initialize($tenant);

        $demoUser = User::query()->firstOrCreate(
            ['email' => 'demo@demo.test'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        if (! $demoUser->hasRole('owner')) {
            $demoUser->assignRole('owner');
        }

        tenancy()->end();
    }
}
