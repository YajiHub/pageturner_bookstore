<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Category;

class MassBookSeeder extends Seeder
{
    public function run(): void
    {
        $seedBooksCount = env('SEED_BOOKS_COUNT', 1000000);
        $categories = Category::pluck('id')->toArray();
        
        if (empty($categories)) {
            $this->command->error("No categories found. Please seed categories first.");
            return;
        }

        $this->command->info("Seeding {$seedBooksCount} English books in chunks...");

        // CHANGED: Lowered from 5000 to 3000 to prevent the 65,535 parameter PDO limit crash!
        $chunkSize = 3000; 
        $now = now()->format('Y-m-d H:i:s');

        // High-speed English generation arrays
        $adjectives = ['The Silent', 'Hidden', 'Lost', 'Golden', 'Dark', 'Shattered', 'Quantum', 'Fallen', 'Eternal', 'Crimson'];
        $nouns = ['Journey', 'Mystery', 'Secret', 'Legacy', 'Chronicles', 'Shadow', 'Light', 'Echo', 'Kingdom', 'Paradox'];
        $formats = ['Hardcover', 'Paperback', 'E-Book', 'Audiobook'];

        for ($i = 0; $i < $seedBooksCount; $i += $chunkSize) {
            $currentChunkSize = min($chunkSize, $seedBooksCount - $i);
            $booksData = [];

            for ($j = 0; $j < $currentChunkSize; $j++) {
                $booksData[] = [
                    'category_id' => $categories[array_rand($categories)],
                    'title' => $adjectives[array_rand($adjectives)] . ' ' . $nouns[array_rand($nouns)] . ' ' . rand(1, 999),
                    'author' => 'Author ' . Str::random(6),
                    'publisher' => 'PageTurner Press',
                    'isbn' => '978' . str_pad((string)($i + $j + 1), 10, '0', STR_PAD_LEFT),
                    'description' => 'A thrilling and deeply moving English masterpiece about adventure, discovery, and the human spirit.',
                    'price' => rand(15000, 199999) / 100,
                    'stock_quantity' => rand(5, 100),
                    'format' => $formats[array_rand($formats)],
                    
                    'cover_image' => 'covers/cover_' . rand(1, 27) . '.jpg', 
                    
                    'is_active' => true,
                    'published_at' => now()->subDays(rand(1, 10000))->format('Y-m-d'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('books')->insert($booksData);
            $this->command->info("Inserted " . ($i + $currentChunkSize) . " / {$seedBooksCount} English books");
            
            gc_collect_cycles(); // Keep RAM under 512MB
        }
    }
}