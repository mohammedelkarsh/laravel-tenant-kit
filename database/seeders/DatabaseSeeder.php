<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

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
