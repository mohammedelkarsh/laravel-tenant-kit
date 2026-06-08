<?php

namespace App\Console\Commands;

use App\Services\TenantProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ProvisionTenantCommand extends Command
{
    protected $signature = 'tenant:provision
                            {subdomain : Workspace subdomain (also used as tenant ID)}
                            {name : Display name for the workspace}
                            {--admin= : Owner email address (creates a user inside the tenant DB)}
                            {--admin-name= : Owner display name}
                            {--password=password : Password for the owner account}';

    protected $description = 'Provision a new tenant workspace with database, migrations, roles, and optional owner user';

    public function handle(TenantProvisioner $provisioner): int
    {
        $subdomain = Str::lower($this->argument('subdomain'));

        if (! preg_match('/^[a-z0-9]([a-z0-9-]{1,61}[a-z0-9])?$/', $subdomain)) {
            $this->error('Invalid subdomain. Use lowercase letters, numbers, and hyphens (3–63 chars).');

            return self::FAILURE;
        }

        if (\App\Models\Tenant::query()->where('id', $subdomain)->exists()) {
            $this->error("Workspace [{$subdomain}] already exists.");

            return self::FAILURE;
        }

        $this->info("Provisioning workspace [{$subdomain}]...");

        $result = $provisioner->provision(
            subdomain: $subdomain,
            name: $this->argument('name'),
            adminEmail: $this->option('admin'),
            adminName: $this->option('admin-name'),
            adminPassword: $this->option('password'),
        );

        $tenant = $result['tenant'];

        $this->newLine();
        $this->components->info('Workspace provisioned successfully.');
        $this->table(
            ['Key', 'Value'],
            [
                ['ID', $tenant->id],
                ['Name', $tenant->name],
                ['URL', $result['url']],
                ['Database', $tenant->database()->getName()],
            ],
        );

        if ($this->option('admin')) {
            $this->line('  Owner: '.$this->option('admin').' / '.$this->option('password'));
        }

        $this->newLine();
        $this->comment('Tip: add to hosts → 127.0.0.1 '.$tenant->id.'.'.config('app.central_domain'));

        return self::SUCCESS;
    }
}
