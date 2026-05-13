<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Admin
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@pageturner.com',
            'role' => 'admin',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // 2. Create Customers (Needed for reviews)
        User::factory(10)->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        // 3. Create English Categories
        $categories = ['Fiction', 'Sci-Fi & Fantasy', 'Mystery & Thriller', 'Biography', 'History', 'Technology', 'Science', 'Romance'];
        foreach ($categories as $categoryName) {
            Category::factory()->create(['name' => $categoryName]);
        }

        // 4. Run the 1-Million Book English Seeder
        $this->call([
            MassBookSeeder::class,
        ]);

        // 5. Run the new AI Showcase Seeder!
        $this->call([
            AiShowcaseSeeder::class,
        ]);
    }
}