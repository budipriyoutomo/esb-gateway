<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contract revision 3 as sync-api proposed it (ADR-032, P30): `sales` holds
 * one element per visit purpose with its own payments, so the separate
 * payments column goes. Each visit purpose becomes its own Push Sales
 * transaction, recorded on its closing_sales_pushes row, and the gateway maps
 * each payment method to its ERP account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('closing_recaps', function (Blueprint $table) {
            $table->dropColumn('payments');
        });

        Schema::table('closing_sales_pushes', function (Blueprint $table) {
            $table->string('visit_purpose_name', 100)->default('')->after('recap_sequence');
            // The digit that ends the salesNum: fixed per recap and purpose.
            $table->unsignedTinyInteger('slot')->default(1)->after('visit_purpose_name');
        });

        // payment method (as the POS names it) -> ERP account and MDR.
        Schema::create('closing_payment_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('payment_method', 100)->unique();
            $table->string('coa_no', 50);
            $table->string('coa_name', 255)->nullable();
            $table->decimal('merchant_discount_rate', 8, 4)->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('closing_payment_mappings');

        Schema::table('closing_sales_pushes', function (Blueprint $table) {
            $table->dropColumn(['visit_purpose_name', 'slot']);
        });

        Schema::table('closing_recaps', function (Blueprint $table) {
            $table->json('payments')->nullable()->after('sales');
        });
    }
};
