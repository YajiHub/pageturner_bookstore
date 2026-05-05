<?php

namespace App\Console\Commands;

use App\Models\Book;
use Illuminate\Console\Command;

class BenchmarkBookQueries extends Command
{
    protected $signature = 'benchmark:books {--iterations=100}';
    protected $description = 'Run performance benchmarks on critical catalog queries';

    public function handle()
    {
        $iterations = (int) $this->option('iterations');
        $this->info("Running benchmarks for {$iterations} iterations...");

        // Warm up connection
        Book::first();

        // 1. Cursor Paginated Catalog Listing
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            Book::select(['id', 'isbn', 'title', 'author', 'price', 'stock_quantity', 'published_at', 'category_id'])
                ->with(['category:id,name'])
                ->where('is_active', true)
                ->orderBy('published_at', 'desc')
                ->orderBy('id', 'desc')
                ->cursorPaginate(100);
        }
        $avgListing = ((microtime(true) - $start) * 1000) / $iterations;
        $this->info("Catalog Listing Avg: " . number_format($avgListing, 2) . " ms");

        // 2. Exact ISBN Match (Indexed)
        $isbn = Book::inRandomOrder()->value('isbn') ?? '9780000000000';
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            Book::where('isbn', $isbn)->first();
        }
        $avgIsbn = ((microtime(true) - $start) * 1000) / $iterations;
        $this->info("ISBN Lookup Avg: " . number_format($avgIsbn, 2) . " ms");

        // Verification & CI Exit Code
        $failed = false;
        if ($avgListing > 100) {
            $this->error("FAILED: Catalog listing missed target (<100ms)");
            $failed = true;
        }
        if ($avgIsbn > 50) {
            $this->error("FAILED: ISBN lookup missed target (<50ms)");
            $failed = true;
        }

        if (!$failed) {
            $this->info("SUCCESS: All benchmarks passed!");
        }

        return $failed ? 1 : 0;
    }
}