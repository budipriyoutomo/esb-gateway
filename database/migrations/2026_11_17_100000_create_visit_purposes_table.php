<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visit purposes kept in the gateway: the official list of the
 * visitPurposeName values closing recaps carry ([ESB-052]). name_key is the
 * name trimmed and upper-cased, as the closing recaps match it, so that
 * uniqueness does not depend on the database's collation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_purposes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('name_key', 100)->unique();
            $table->string('notes', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_purposes');
    }
};
