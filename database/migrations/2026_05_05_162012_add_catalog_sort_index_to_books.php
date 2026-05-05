<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Creates a perfectly pre-sorted index mapping exactly to Laravel's query
        DB::statement('CREATE INDEX idx_books_catalog_sort ON books (is_active, published_at DESC, id DESC);');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_books_catalog_sort;');
    }
};