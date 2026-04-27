<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->char('checksum', 64)->nullable()->after('request_method');
            $table->char('previous_checksum', 64)->nullable()->after('checksum');
            $table->timestamp('archived_at')->nullable()->after('created_at');

            $table->index('checksum');
            $table->index('archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['checksum']);
            $table->dropIndex(['archived_at']);
            $table->dropColumn(['checksum', 'previous_checksum', 'archived_at']);
        });
    }
};
