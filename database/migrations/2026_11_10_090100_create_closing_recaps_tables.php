<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Closing recaps from sync-api Maharasa, held before anything reaches the ESB
 * (ADR-032).
 *
 * - closing_submissions: every accepted request, with its raw body, for audit
 *   and idempotency (unique message_id).
 * - closing_recaps: the current state per outlet and production date.
 * - closing_recap_items: the current version's items, replaced in full by a
 *   newer sequence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('closing_submissions', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();

            // Nulled rather than cascaded: revoking a client keeps its history.
            $table->foreignId('api_client_id')->nullable()->constrained('api_clients')->nullOnDelete();

            $table->uuid('message_id')->unique();
            $table->char('body_hash', 64);
            $table->string('outlet_code', 20);
            $table->date('production_date');
            $table->unsignedInteger('sequence');
            $table->timestamp('sent_at');

            // The body exactly as received, to compare with the sender's own copy.
            $table->longText('body');

            $table->timestamps();

            $table->index(['outlet_code', 'production_date']);
        });

        Schema::create('closing_recaps', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();

            $table->string('outlet_code', 20);
            $table->date('production_date');
            $table->unsignedInteger('sequence');
            $table->foreignId('closing_submission_id')->nullable()->constrained('closing_submissions')->nullOnDelete();

            $table->string('status', 24);
            $table->timestamp('last_received_at')->nullable();

            $table->timestamps();

            $table->unique(['outlet_code', 'production_date']);
            $table->index(['status', 'production_date']);
        });

        Schema::create('closing_recap_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('closing_recap_id')->constrained('closing_recaps')->cascadeOnDelete();

            $table->string('source', 10); // closing | pos_only

            $table->uuid('menu_id')->nullable();
            $table->string('menu_code', 50)->nullable();
            $table->string('menu_name', 255)->nullable();

            $table->string('product_id', 50)->nullable();
            $table->string('product_code', 50)->nullable();
            $table->string('product_name', 255)->nullable();

            // NUMERIC, not integer: POS quantities can be fractions and the
            // adjustment can be negative.
            $table->decimal('colorplate_sold', 18, 6)->default(0);
            $table->decimal('pos_qty', 18, 6)->default(0);
            $table->decimal('total_sold', 18, 6)->default(0);
            $table->decimal('waste', 18, 6)->default(0);
            $table->decimal('adjustment', 18, 6)->default(0);
            $table->decimal('compensation', 18, 6)->default(0);

            $table->timestamps();

            $table->index(['closing_recap_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('closing_recap_items');
        Schema::dropIfExists('closing_recaps');
        Schema::dropIfExists('closing_submissions');
    }
};
