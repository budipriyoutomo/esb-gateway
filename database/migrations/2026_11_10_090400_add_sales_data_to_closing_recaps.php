<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sales data for Push Sales (ADR-032, P29), as contract revision 3 proposes
 * it (TBD: not yet confirmed by sync-api), and the Push Sales transaction a
 * recap becomes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('closing_recaps', function (Blueprint $table) {
            // The header totals and payments of the current version; null when
            // sync-api sent none (revision 2).
            $table->json('sales')->nullable()->after('status');
            $table->json('payments')->nullable()->after('sales');
        });

        Schema::table('closing_recap_items', function (Blueprint $table) {
            // Price lines whose quantities add up to total_sold.
            $table->json('prices')->nullable()->after('compensation');
        });

        // One per prepared version. Sending hands the payload to sales_pushes
        // (ADR-022), which queues it and records what the ESB said.
        Schema::create('closing_sales_pushes', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('closing_recap_id')->constrained('closing_recaps')->cascadeOnDelete();
            $table->unsignedInteger('recap_sequence');
            $table->string('sales_num', 20);
            $table->json('payload');
            $table->string('status', 16);
            $table->foreignId('sales_push_id')->nullable()->constrained('sales_pushes')->nullOnDelete();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['closing_recap_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('closing_sales_pushes');

        Schema::table('closing_recap_items', function (Blueprint $table) {
            $table->dropColumn('prices');
        });

        Schema::table('closing_recaps', function (Blueprint $table) {
            $table->dropColumn(['sales', 'payments']);
        });
    }
};
