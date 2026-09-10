<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Runtime-editable ESB connections (ADR-018, superseding ADR-013).
 *
 * config/esb.php stays the bootstrap source and the fallback. A row here
 * overrides it for one family in one environment, so an ESB environment can be
 * repointed without a deploy.
 *
 * `credentials` is encrypted at rest by the model's cast. Nothing reads it back
 * out to a response, a log, or an audit row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esb_connections', function (Blueprint $table) {
            $table->id();

            // ADR-010's base URL families. Stored as a string rather than a
            // foreign key: the family set is code (EsbFamily), not data.
            $table->string('family');

            // Which ESB environment this row describes. A row only takes
            // effect when it matches the configured environment, so recording
            // a production connection on a staging install cannot silently
            // repoint it.
            $table->string('environment');

            $table->string('base_url');
            $table->string('auth_mechanism')->nullable();

            // Encrypted at rest. Never returned after storage.
            $table->text('credentials')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['family', 'environment']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esb_connections');
    }
};
