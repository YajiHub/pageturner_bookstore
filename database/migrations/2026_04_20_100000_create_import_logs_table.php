<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('data_transfer_job_id')->nullable()->constrained('data_transfer_jobs')->nullOnDelete();
            $table->string('filename');
            $table->string('status', 20)->default('queued');
            $table->unsignedInteger('rows_processed')->default(0);
            $table->unsignedInteger('rows_failed')->default(0);
            $table->json('failure_report')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
