<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'DWD',
            'email' => 'admin@dwdecor.co.id',
            'phone' => '+6281234567890',
            'password' => bcrypt('password'),
            'provider' => 'local',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create test customer
        User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'customer@example.com',
            'phone' => '+6281234567891',
            'password' => bcrypt('password'),
            'provider' => 'local',
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        // Call other seeders
        $this->call([
            SettingSeeder::class,
        ]);
    }
}
