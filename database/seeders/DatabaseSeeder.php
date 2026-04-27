<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@pageturner.com',
            'role' => 'admin',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // Create customer users
        $customers = User::factory(10)->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        // Create categories
        $categories = Category::factory(8)->create();

        // FORCED to 1,000,000 to bypass Laravel's env() cache issues.
        $seedBooksCount = 1000000; 
        
        if ($seedBooksCount > 100) {
            $this->command->info("Seeding {$seedBooksCount} books in chunks. This might take a while...");
            
            // Bulk insert via DB::table for massive performance gains
            $chunkSize = 5000;
            $now = now()->format('Y-m-d H:i:s');
            $categoryIds = $categories->pluck('id')->toArray();

            for ($i = 0; $i < $seedBooksCount; $i += $chunkSize) {
                $currentChunkSize = min($chunkSize, $seedBooksCount - $i);
                $booksData = [];

                for ($j = 0; $j < $currentChunkSize; $j++) {
                    $booksData[] = [
                        'category_id' => $categoryIds[array_rand($categoryIds)],
                        'title' => 'Book ' . ($i + $j + 1) . ' ' . Str::random(8),
                        'author' => 'Author ' . Str::random(6),
                        // Fast mathematical ISBN generation to prevent out-of-memory errors
                        'isbn' => '978' . str_pad((string)($i + $j + 1), 10, '0', STR_PAD_LEFT),
                        'price' => rand(15000, 199999) / 100,
                        'stock_quantity' => rand(5, 100),
                        'description' => 'Automatically generated book description for load testing.',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                DB::table('books')->insert($booksData);
                $this->command->info("Inserted " . ($i + $currentChunkSize) . " / {$seedBooksCount} books");
            }
        } else {
            // Standard Eloquent seeding for normal local dev
            $categories->each(function ($category) {
                Book::factory(3)->create(['category_id' => $category->id]);
            });

            // Create reviews
            $books = Book::inRandomOrder()->limit(50)->get();
            if ($books->isNotEmpty()) {
                $customers->each(function ($customer) use ($books) {
                    $books->random(rand(3, 5))->each(function ($book) use ($customer) {
                        Review::factory()->create([
                            'user_id' => $customer->id,
                            'book_id' => $book->id,
                        ]);
                    });
                });
            }
        }
    }
}