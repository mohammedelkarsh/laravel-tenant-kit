<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@laravel-tenant-kit.test',
            'password' => 'password',
        ]);

        $this->call(DemoTenantSeeder::class);
    }
}
