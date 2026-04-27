<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('event_category', 40)->nullable()->after('action');
            $table->string('severity', 16)->default('info')->after('description');
            $table->string('outcome', 16)->default('success')->after('severity');
            $table->unsignedTinyInteger('risk_score')->default(0)->after('outcome');
            $table->boolean('is_suspicious')->default(false)->after('risk_score');
            $table->json('detection_tags')->nullable()->after('is_suspicious');
            $table->string('correlation_id', 100)->nullable()->after('request_method');
            $table->string('request_id', 100)->nullable()->after('correlation_id');
            $table->string('session_id', 150)->nullable()->after('request_id');
            $table->string('actor_type', 20)->nullable()->after('user_id');
            $table->string('actor_identifier')->nullable()->after('actor_type');
            $table->string('actor_role', 40)->nullable()->after('actor_identifier');
            $table->string('target_identifier')->nullable()->after('auditable_id');
            $table->unsignedSmallInteger('http_status_code')->nullable()->after('request_method');
            $table->string('request_source', 20)->nullable()->after('http_status_code');

            $table->index('event_category');
            $table->index('severity');
            $table->index('outcome');
            $table->index('is_suspicious');
            $table->index('risk_score');
            $table->index('correlation_id');
            $table->index('request_id');
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['event_category']);
            $table->dropIndex(['severity']);
            $table->dropIndex(['outcome']);
            $table->dropIndex(['is_suspicious']);
            $table->dropIndex(['risk_score']);
            $table->dropIndex(['correlation_id']);
            $table->dropIndex(['request_id']);
            $table->dropIndex(['session_id']);

            $table->dropColumn([
                'event_category',
                'severity',
                'outcome',
                'risk_score',
                'is_suspicious',
                'detection_tags',
                'correlation_id',
                'request_id',
                'session_id',
                'actor_type',
                'actor_identifier',
                'actor_role',
                'target_identifier',
                'http_status_code',
                'request_source',
            ]);
        });
    }
};
