<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MassBookSeeder extends Seeder
{
    private const CHUNK_SIZE = 5000; // Optimal batch size for PostgreSQL

    public function run(): void
    {
        // Dynamically fetch the record count from .env, fallback to 1M if missing
        $totalRecords = (int) env('SEED_BOOKS_COUNT', 1000000);
        
        $this->command->info("Starting mass book seeding ({$totalRecords} Records)...");
        $inserted = 0;

        while ($inserted < $totalRecords) {
            $batchSize = min(self::CHUNK_SIZE, $totalRecords - $inserted);

            // make() generates arrays WITHOUT instantiating bloated Eloquent objects
            $books = Book::factory()->count($batchSize)->make()->toArray();
            
            // Raw batch insert
            DB::table('books')->insert($books);

            $inserted += $batchSize;

            // Force Garbage Collection every 10 chunks to stay under 512MB RAM
            if ($inserted % (self::CHUNK_SIZE * 10) === 0) {
                unset($books);
                gc_collect_cycles();
                $this->command->info("Inserted " . number_format($inserted) . " books...");
            }
        }
        
        $this->command->info('Successfully seeded ' . number_format($totalRecords) . ' books!');
    }
}