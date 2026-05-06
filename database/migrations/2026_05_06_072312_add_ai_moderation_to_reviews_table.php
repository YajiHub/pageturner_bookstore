<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('reviews', function (Blueprint $table) {
            $table->boolean('is_flagged_by_ai')->default(false);
            $table->string('ai_moderation_reason')->nullable();
        });
    }

    public function down(): void {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['is_flagged_by_ai', 'ai_moderation_reason']);
        });
    }
};