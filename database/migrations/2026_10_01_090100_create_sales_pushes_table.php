<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per sales transaction a POS handed the gateway, and what the ESB
 * said about it (ADR-022).
 *
 * The payload is kept, encrypted, because the queue sends it later and an
 * operator may send it again. It carries customer names and payment details;
 * its retention is TBD (docs/07-database-design.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_pushes', function (Blueprint $table) {
            // The auto-increment id orders pushes of one salesNum; the ULID is
            // what the API and the URLs show, so ids do not leak volume.
            $table->id();
            $table->ulid('public_id')->unique();

            // Nulled rather than cascaded: deleting a client must not erase
            // the history of what it sent.
            $table->foreignId('api_client_id')->nullable()->constrained('api_clients')->nullOnDelete();

            $table->string('sales_num', 20);
            $table->string('branch_code', 20);
            $table->date('sales_date')->nullable();

            $table->text('payload');

            $table->string('status', 16);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('last_attempted_at')->nullable();

            // The last failure: its kind, the correlation id of the ESB call,
            // and the per-field messages the ESB listed.
            $table->string('failure', 32)->nullable();
            $table->uuid('correlation_id')->nullable();
            $table->json('errors')->nullable();

            $table->string('esb_status', 32)->nullable();
            $table->string('esb_sync_date', 32)->nullable();
            $table->timestamp('pushed_at')->nullable();

            $table->timestamps();

            $table->index(['sales_num', 'id']);
            $table->index(['status', 'created_at']);
            $table->index(['branch_code', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_pushes');
    }
};
