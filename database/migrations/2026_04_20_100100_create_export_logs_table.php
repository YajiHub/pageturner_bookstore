<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('data_transfer_job_id')->nullable()->constrained('data_transfer_jobs')->nullOnDelete();
            $table->string('format', 10)->default('xlsx');
            $table->json('filters')->nullable();
            $table->json('selected_columns')->nullable();
            $table->string('status', 20)->default('queued');
            $table->string('download_link')->nullable();
            $table->unsignedInteger('rows_exported')->default(0);
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_logs');
    }
};
