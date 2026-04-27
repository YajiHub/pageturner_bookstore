<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_transfer_jobs', function (Blueprint $table) {
            $table->unsignedTinyInteger('progress_percentage')->default(0)->after('failed_rows');
        });
    }

    public function down(): void
    {
        Schema::table('data_transfer_jobs', function (Blueprint $table) {
            $table->dropColumn('progress_percentage');
        });
    }
};
