<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // gemini, ollama
            $table->string('feature'); // summarization, moderation
            $table->integer('tokens_used')->default(0);
            $table->decimal('cost_estimate', 10, 6)->default(0.00);
            $table->text('input_prompt')->nullable();
            $table->text('output_response')->nullable();
            $table->boolean('was_fallback')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('ai_usage_logs');
    }
};