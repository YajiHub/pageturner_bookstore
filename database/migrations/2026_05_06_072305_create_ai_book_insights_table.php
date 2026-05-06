<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ai_book_insights', function (Blueprint $table) {
            $table->id();
            
            // Lab 7 Fix: Use a standard indexed integer instead of a strict foreign key 
            // because the 'books' table is now partitioned.
            $table->unsignedBigInteger('book_id');
            $table->index('book_id'); 
            
            $table->text('ai_summary');
            $table->enum('overall_sentiment', ['Positive', 'Neutral', 'Negative', 'Mixed']);
            $table->integer('reviews_analyzed_count');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('ai_book_insights');
    }
};