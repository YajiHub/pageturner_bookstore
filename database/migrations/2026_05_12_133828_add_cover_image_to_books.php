<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Safely adds the column to the partitioned table at the end of the chain
        Schema::table('books', function (Blueprint $table) {
            $table->string('cover_image')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('cover_image');
        });
    }
};