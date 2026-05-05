<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop foreign keys from child tables that rely on the old single-column primary key
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropForeign(['book_id']);
            });
        }
        if (Schema::hasTable('reviews')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropForeign(['book_id']);
            });
        }

        // 2. Rename the unpartitioned table
        DB::statement('ALTER TABLE books RENAME TO books_unpartitioned');

        // 3. Create the new partitioned table
        // Note: PostgreSQL requires the partition key to be part of the Primary Key.
        DB::statement("
            CREATE TABLE books (
                id bigserial,
                isbn varchar(255) NOT NULL,
                title varchar(255) NOT NULL,
                author varchar(255) NOT NULL,
                publisher varchar(255) NOT NULL,
                description text,
                price numeric(8, 2) NOT NULL,
                stock_quantity integer NOT NULL,
                category_id bigint NOT NULL,
                format varchar(255) NOT NULL,
                published_at date NOT NULL DEFAULT '1999-01-01',
                is_active boolean DEFAULT true NOT NULL,
                created_at timestamp(0) without time zone,
                updated_at timestamp(0) without time zone,
                PRIMARY KEY (id, published_at) 
            ) PARTITION BY RANGE (published_at);
        ");

        // 4. Create the Partitions
        DB::statement("CREATE TABLE books_p_old PARTITION OF books FOR VALUES FROM (MINVALUE) TO ('2000-01-01')");
        DB::statement("CREATE TABLE books_p2000 PARTITION OF books FOR VALUES FROM ('2000-01-01') TO ('2005-01-01')");
        DB::statement("CREATE TABLE books_p2005 PARTITION OF books FOR VALUES FROM ('2005-01-01') TO ('2010-01-01')");
        DB::statement("CREATE TABLE books_p2010 PARTITION OF books FOR VALUES FROM ('2010-01-01') TO ('2015-01-01')");
        DB::statement("CREATE TABLE books_p2015 PARTITION OF books FOR VALUES FROM ('2015-01-01') TO ('2020-01-01')");
        DB::statement("CREATE TABLE books_p2020 PARTITION OF books FOR VALUES FROM ('2020-01-01') TO ('2025-01-01')");
        DB::statement("CREATE TABLE books_p_future PARTITION OF books FOR VALUES FROM ('2025-01-01') TO (MAXVALUE)");

        // 5. Migrate Data from the old table to the new partitioned table
        DB::statement("
            INSERT INTO books (id, isbn, title, author, publisher, description, price, stock_quantity, category_id, format, published_at, is_active, created_at, updated_at)
            SELECT id, isbn, title, author, publisher, description, price, stock_quantity, category_id, format, COALESCE(published_at, '1999-01-01'), is_active, created_at, updated_at
            FROM books_unpartitioned
        ");

        // 6. Reset the PostgreSQL sequence so new inserts don't fail with ID conflicts
        DB::statement("SELECT setval('books_id_seq', COALESCE((SELECT MAX(id) FROM books), 1))");

        // 7. Drop the old table
        DB::statement('DROP TABLE books_unpartitioned CASCADE');
        
        // Note: Eloquent models and relations will continue to work perfectly at the application layer 
        // even without the strict database-level foreign keys we dropped in Step 1.
    }

    public function down(): void
    {
        DB::statement('DROP TABLE books CASCADE');
    }
};