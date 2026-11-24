<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\TenantRolesSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantProvisioner
{
    /**
     * @return array{tenant: Tenant, url: string}
     */
    public function provision(
        string $subdomain,
        string $name,
        ?string $adminEmail = null,
        ?string $adminName = null,
        ?string $adminPassword = null,
    ): array {
        $id = Str::lower($subdomain);

        $tenant = Tenant::query()->create([
            'id' => $id,
            'name' => $name,
        ]);

        $tenant->domains()->create([
            'domain' => $id,
        ]);

        if ($adminEmail) {
            $this->createOwnerUser(
                $tenant,
                $adminEmail,
                $adminName ?? 'Workspace Owner',
                $adminPassword ?? 'password',
            );
        }

        $tenant = $tenant->fresh();

        app(PlatformWebhookDispatcher::class)->dispatch('workspace.created', [
            'workspace_id' => $tenant->id,
            'name' => $tenant->name,
            'url' => $tenant->url(),
            'admin_email' => $adminEmail ? Str::lower($adminEmail) : null,
        ]);

        return [
            'tenant' => $tenant,
            'url' => $tenant->url(),
        ];
    }

    public function createOwnerUser(
        Tenant $tenant,
        string $email,
        string $name,
        string $password,
    ): User {
        tenancy()->initialize($tenant);

        (new TenantRolesSeeder)->run();

        $user = User::query()->firstOrCreate(
            ['email' => Str::lower($email)],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        if (! $user->hasRole('owner')) {
            $user->assignRole('owner');
        }

        tenancy()->end();

        return $user;
    }
}
