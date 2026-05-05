<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@pageturner.com',
            'role' => 'admin',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // 2. Create customer users
        User::factory(10)->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        // 3. Create categories (crucial, since books need category_ids)
        Category::factory(8)->create();

        // 4. Call the new Lab 7 MassBookSeeder!
        // This will automatically read your .env SEED_BOOKS_COUNT and use the correct fields.
        $this->call([
            MassBookSeeder::class,
        ]);
    }
}