<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create ALL missing columns FIRST with safe defaults for existing data
        Schema::table('books', function (Blueprint $table) {
            if (!Schema::hasColumn('books', 'published_at')) {
                $table->date('published_at')->nullable();
            }
            if (!Schema::hasColumn('books', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (!Schema::hasColumn('books', 'publisher')) {
                $table->string('publisher')->default('Self-Published');
            }
            if (!Schema::hasColumn('books', 'format')) {
                $table->string('format')->default('Paperback');
            }
        });

        // 2. Add the performance indexes
        Schema::table('books', function (Blueprint $table) {
            $table->index(['category_id', 'published_at', 'is_active'], 'idx_books_catalog_filter');
            $table->index(['price', 'stock_quantity', 'id'], 'idx_books_price_stock');
            
            // PostgreSQL Full-Text Search index
            $table->fullText(['title', 'description'], 'idx_books_fulltext');
            
            $table->index('is_active', 'idx_books_active');
            $table->index('isbn', 'idx_books_isbn_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex('idx_books_catalog_filter');
            $table->dropIndex('idx_books_price_stock');
            $table->dropFullText('idx_books_fulltext');
            $table->dropIndex('idx_books_active');
            $table->dropIndex('idx_books_isbn_lookup');
            
            $columnsToDrop = [];
            foreach (['published_at', 'is_active', 'publisher', 'format'] as $col) {
                if (Schema::hasColumn('books', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};