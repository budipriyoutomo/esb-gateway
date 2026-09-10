<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalog of ESB operations, imported from the generated files in docs/esb/.
 *
 * Documentation/configuration rather than a runtime registry
 * (docs/04-functional-requirements.md): nothing dispatches from this table.
 * It exists so operators can search 401 operations without reading markdown.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_endpoints', function (Blueprint $table) {
            $table->id();

            // Deterministic: "{product}:{group}:{vendor name}". Re-importing
            // the same catalog updates rather than duplicates.
            $table->string('code')->unique();

            $table->string('product', 16)->index();
            $table->string('group')->index();
            $table->string('name');

            // Part of an operation's identity: the vendor ships two distinct
            // operations sharing a group and name but differing by version.
            $table->string('version', 16);

            // Null for the vendor's narrative "Flow" pages, which document a
            // process rather than a callable operation.
            $table->string('method', 10)->nullable()->index();
            $table->string('path')->nullable();
            $table->boolean('is_operation')->default(true)->index();

            $table->string('family', 32)->index();
            $table->string('docs_url');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_endpoints');
    }
};
