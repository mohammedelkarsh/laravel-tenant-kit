<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantProvisioner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Stancl\Tenancy\Contracts\TenantDatabaseManager;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        if (! Tenant::query()->where('id', 'demo')->exists()) {
            $this->dropOrphanTenantDatabase('demo');

            app(TenantProvisioner::class)->provision(
                subdomain: 'demo',
                name: 'Demo Workspace',
                adminEmail: 'demo@demo.test',
                adminName: 'Demo User',
            );

            return;
        }

        $tenant = Tenant::query()->findOrFail('demo');

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

    /**
     * migrate:fresh drops central tables but leaves tenant databases behind.
     */
    private function dropOrphanTenantDatabase(string $tenantId): void
    {
        $dbName = config('tenancy.database.prefix').$tenantId.config('tenancy.database.suffix', '');
        $connection = config('tenancy.database.central_connection', config('database.default'));
        $driver = config("database.connections.{$connection}.driver");
        $managerClass = config("tenancy.database.managers.{$driver}");

        if (! $managerClass) {
            return;
        }

        /** @var TenantDatabaseManager $manager */
        $manager = app($managerClass);
        $manager->setConnection($connection);

        if (! $manager->databaseExists($dbName)) {
            return;
        }

        if ($driver === 'sqlite') {
            $path = database_path($dbName);
            if (is_file($path)) {
                unlink($path);
            }

            return;
        }

        DB::connection($connection)->statement(
            $driver === 'pgsql'
                ? "DROP DATABASE \"{$dbName}\" WITH (FORCE)"
                : "DROP DATABASE `{$dbName}`"
        );
    }
}
