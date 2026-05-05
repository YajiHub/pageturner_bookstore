<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // Composite index for common filtering patterns
            $table->index(['category_id', 'published_at', 'is_active'], 'idx_books_catalog_filter');
            
            // Covering index for price range queries (Index-only scans)
            $table->index(['price', 'stock_quantity', 'id'], 'idx_books_price_stock');
            
            // Full-text index (Requires MySQL 5.7+ or PostgreSQL)
            $table->fullText(['title', 'description'], 'idx_books_fulltext');
            
            // Index for active-book filtering
            $table->index('is_active', 'idx_books_active');
            
            // ISBN lookup index
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
        });
    }
};