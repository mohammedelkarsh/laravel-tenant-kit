<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class TenantRolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['owner', 'admin', 'member'] as $role) {
            Role::query()->firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }
    }
}
