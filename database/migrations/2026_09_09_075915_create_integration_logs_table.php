<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operational history of ESB calls.
 *
 * Fields follow docs/11-logging-monitoring.md. No request or response payloads
 * are stored: the vendor's payloads carry personal and financial data, and
 * retention for this table is still TBD.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();

            $table->uuid('correlation_id')->index();
            $table->string('operation')->index();

            $table->string('family', 32);
            $table->string('environment', 32);
            $table->string('method', 10);

            // Path only -- never the full URL, which would record the ESB host
            // on every row.
            $table->string('path');

            $table->string('outcome', 32)->index();
            $table->string('esb_code', 32)->nullable()->index();
            $table->text('safe_error_message')->nullable();
            $table->unsignedSmallInteger('upstream_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);

            // Who triggered it, where a user did. Null for scheduled or queued
            // work. Nulled rather than cascaded so deleting a user does not
            // erase operational history.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('started_at')->nullable();
            $table->timestamps();

            // Supports the log viewer's default "recent failures" view.
            $table->index(['outcome', 'created_at']);
            $table->index(['operation', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};
