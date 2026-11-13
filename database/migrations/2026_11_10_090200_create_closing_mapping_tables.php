<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How closing recaps meet the ERP (ADR-032), kept by an operator.
 *
 * ERP names and codes are copied from the picker so the screens can show
 * them without calling the ESB. Preparing a recap reads the ERP again.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Outlet code -> the branch for sales, the branch and location for
        // item journals.
        Schema::create('closing_outlet_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('outlet_code', 20)->unique();
            $table->unsignedBigInteger('branch_id');
            // CL + branch code + YYYYMMDD is the sales number: at most 10 here.
            $table->string('branch_code', 10);
            $table->string('branch_name', 255);
            $table->unsignedBigInteger('location_id');
            $table->string('location_name', 255);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // A closing menu (key: menuId) or a POS product (key: productCode,
        // upper-cased) -> the ERP menu its sales go to, and for a closing
        // menu the bill of material its stock kinds are expanded through.
        Schema::create('closing_item_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('source', 10);
            $table->string('item_key', 50);
            $table->unsignedBigInteger('esb_menu_id')->nullable();
            $table->string('esb_menu_code', 50)->nullable();
            $table->string('esb_menu_name', 255)->nullable();
            $table->unsignedBigInteger('bom_id')->nullable();
            $table->string('bom_name', 255)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['source', 'item_key']);
        });

        // waste | adjustment | compensation -> the item journal purpose, and
        // the sign its quantity takes (-1 stock out, +1 stock in).
        Schema::create('closing_purpose_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 16)->unique();
            $table->unsignedBigInteger('purpose_id');
            $table->string('purpose_name', 255);
            $table->smallInteger('direction');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('closing_purpose_mappings');
        Schema::dropIfExists('closing_item_mappings');
        Schema::dropIfExists('closing_outlet_mappings');
    }
};
