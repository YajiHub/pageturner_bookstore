<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 20);
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'event_type', 'last_seen_at'], 'reading_histories_user_event_last_seen_idx');
            $table->index(['book_id', 'event_type'], 'reading_histories_book_event_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_histories');
    }
};