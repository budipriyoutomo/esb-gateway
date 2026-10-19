<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Document templates imported from a spreadsheet, held in the gateway before
 * and after they are sent to the ESB (ADR-023).
 *
 * One import per uploaded file, one entry per template in it, one line per
 * product row. The file itself is not kept: only its name, its SHA-256, and
 * what was read from it. Nothing here is personal or financial data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_template_imports', function (Blueprint $table) {
            $table->id();

            // Nulled rather than cascaded: deleting a user must not erase
            // what was imported.
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('filename', 255);
            $table->char('file_sha256', 64);

            // idle, validating, or syncing: at most one job per import.
            $table->string('status', 16);

            // Rows that belong to no template, from the sheet.
            $table->json('problems');

            // Why the last validate or sync run stopped early, if it did.
            $table->string('last_error', 500)->nullable();

            $table->timestamp('validated_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('document_template_import_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_template_import_id')->constrained()->cascadeOnDelete();

            // Order in the file; templates are sent in this order.
            $table->unsignedInteger('position');

            $table->string('name', 255);
            // DocumentTemplateType; null when the sheet's text matched none.
            $table->unsignedTinyInteger('type')->nullable();

            // Codes as written (merged across rows); ids once validated.
            $table->json('branch_codes');
            $table->json('branch_ids')->nullable();

            $table->string('status', 16);
            // Why it is invalid, blocked, failed, or unconfirmed.
            $table->json('problems');

            $table->unsignedBigInteger('esb_template_id')->nullable();
            // Integration log correlation id of the last create attempt.
            $table->uuid('correlation_id')->nullable();
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            $table->index(['document_template_import_id', 'position']);
            $table->index(['document_template_import_id', 'status']);
        });

        Schema::create('document_template_import_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_template_import_entry_id')->constrained()->cascadeOnDelete();

            // Row in the uploaded sheet, so a problem can point at it.
            $table->unsignedInteger('row_number');

            $table->string('product_code', 100);
            $table->string('product_name', 255);
            $table->string('unit', 100);
            // Sent as the ESB's requestTemplateDetailName.
            $table->string('category', 255);

            // Resolved from product code + unit when validated.
            $table->unsignedBigInteger('product_detail_id')->nullable();

            $table->index(['document_template_import_entry_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_template_import_lines');
        Schema::dropIfExists('document_template_import_entries');
        Schema::dropIfExists('document_template_imports');
    }
};
