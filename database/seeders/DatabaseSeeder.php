<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@pageturner.com',
            'role' => 'admin',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        // Create customer users
        $customers = User::factory(10)->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        // Create categories
        $categories = Category::factory(8)->create();

        // Create books for each category (3 per category = 24 total)
        $categories->each(function ($category) {
            Book::factory(3)->create(['category_id' => $category->id]);
        });

        // Create reviews
        $books = Book::all();
        $customers->each(function ($customer) use ($books) {
            // Each customer reviews 3-5 random books
            $books->random(rand(3, 5))->each(function ($book) use ($customer) {
                Review::factory()->create([
                    'user_id' => $customer->id,
                    'book_id' => $book->id,
                ]);
            });
        });
    }
}
