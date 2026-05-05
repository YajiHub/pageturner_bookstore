<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MassBookSeeder extends Seeder
{
    private const CHUNK_SIZE = 5000; // Optimal batch size for MySQL
    private const TOTAL_RECORDS = 1000000;

    public function run(): void
    {
        $this->command->info('Starting mass book seeding (1 Million Records)...');
        $inserted = 0;

        while ($inserted < self::TOTAL_RECORDS) {
            $batchSize = min(self::CHUNK_SIZE, self::TOTAL_RECORDS - $inserted);

            // make() generates arrays WITHOUT instantiating bloated Eloquent objects
            $books = Book::factory()->count($batchSize)->make()->toArray();
            
            // Raw batch insert
            DB::table('books')->insert($books);

            $inserted += $batchSize;

            // Force Garbage Collection every 10 chunks to stay under 512MB RAM
            if ($inserted % (self::CHUNK_SIZE * 10) === 0) {
                unset($books);
                gc_collect_cycles();
                $this->command->info("Inserted {$inserted} books...");
            }
        }
        
        $this->command->info('Successfully seeded ' . self::TOTAL_RECORDS . ' books!');
    }
}