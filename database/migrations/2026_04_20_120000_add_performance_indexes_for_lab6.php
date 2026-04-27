<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'created_at'], 'users_role_created_at_idx');
        });

        Schema::table('books', function (Blueprint $table) {
            $table->index(['category_id', 'price'], 'books_category_price_idx');
            $table->index(['stock_quantity'], 'books_stock_quantity_idx');
            $table->index(['created_at'], 'books_created_at_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'orders_user_created_at_idx');
            $table->index(['status', 'updated_at'], 'orders_status_updated_at_idx');
            $table->index(['created_at'], 'orders_created_at_idx');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['book_id'], 'order_items_book_id_idx');
            $table->index(['order_id'], 'order_items_order_id_idx');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['book_id', 'created_at'], 'reviews_book_created_at_idx');
            $table->index(['rating'], 'reviews_rating_idx');
        });

        Schema::table('data_transfer_jobs', function (Blueprint $table) {
            $table->index(['status', 'started_at'], 'transfer_jobs_status_started_at_idx');
            $table->index(['type', 'created_at'], 'transfer_jobs_type_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_created_at_idx');
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex('books_category_price_idx');
            $table->dropIndex('books_stock_quantity_idx');
            $table->dropIndex('books_created_at_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_user_created_at_idx');
            $table->dropIndex('orders_status_updated_at_idx');
            $table->dropIndex('orders_created_at_idx');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_book_id_idx');
            $table->dropIndex('order_items_order_id_idx');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_book_created_at_idx');
            $table->dropIndex('reviews_rating_idx');
        });

        Schema::table('data_transfer_jobs', function (Blueprint $table) {
            $table->dropIndex('transfer_jobs_status_started_at_idx');
            $table->dropIndex('transfer_jobs_type_created_at_idx');
        });
    }
};
