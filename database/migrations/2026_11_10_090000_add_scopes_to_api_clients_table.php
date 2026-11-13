<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which /api endpoints a client may call (ADR-032).
 *
 * Null for clients issued before scopes existed: those are POS systems and
 * keep `sales-data` only (ApiClient::scopes()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            $table->json('scopes')->nullable()->after('token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            $table->dropColumn('scopes');
        });
    }
};
