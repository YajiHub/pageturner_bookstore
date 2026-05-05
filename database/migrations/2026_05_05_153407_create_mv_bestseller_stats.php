<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE MATERIALIZED VIEW mv_bestseller_stats AS
            SELECT
                category_id,
                COUNT(*) as total_books,
                AVG(price) as avg_price,
                SUM(stock_quantity) as total_inventory,
                COUNT(CASE WHEN stock_quantity > 500 THEN 1 END) as bestseller_count,
                MAX(published_at) as latest_publication
            FROM books
            WHERE is_active = true
            GROUP BY category_id;
        ");
    }

    public function down(): void
    {
        DB::statement("DROP MATERIALIZED VIEW IF EXISTS mv_bestseller_stats");
    }
};