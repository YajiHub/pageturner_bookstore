<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('data_transfer_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20); // import|export
            $table->string('status', 20)->default('queued'); // queued|processing|completed|failed
            $table->string('original_filename')->nullable();
            $table->string('stored_path')->nullable();
            $table->string('result_path')->nullable();
            $table->unsignedInteger('total_rows')->nullable();
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->json('failures')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_transfer_jobs');
    }
};
