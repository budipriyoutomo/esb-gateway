<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The item journals a closing recap becomes in the ERP, one per stock kind
 * and prepared version (ADR-032, P28). A row is kept after it is replaced or
 * deleted in the ERP, so the history of what was written stays.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('closing_item_journals', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('closing_recap_id')->constrained('closing_recaps')->cascadeOnDelete();

            // The recap version it was prepared from.
            $table->unsignedInteger('recap_sequence');
            $table->string('kind', 16);

            // What is sent, copied at preparation.
            $table->unsignedBigInteger('purpose_id');
            $table->string('purpose_name', 255);
            $table->smallInteger('direction');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('location_id');
            $table->date('journal_date');
            $table->string('notes', 1000);
            $table->json('lines');
            // The menus and BOM lines (as read from the ERP) each line came from.
            $table->json('breakdown');

            $table->string('status', 24);
            $table->string('item_journal_num', 50)->nullable();

            // The last failure: its kind, the correlation id, the ERP's messages.
            $table->string('failure', 32)->nullable();
            $table->uuid('correlation_id')->nullable();
            $table->json('errors')->nullable();

            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('deleted_in_erp_at')->nullable();
            $table->timestamps();

            $table->index(['closing_recap_id', 'status']);
            $table->index('item_journal_num');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('closing_item_journals');
    }
};
