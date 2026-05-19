<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospect_sheets', function (Blueprint $table) {
            $table->decimal('offer_unit_price', 15, 2)->nullable()->after('exchange_extra_images');
            $table->decimal('offer_unit_price_discount', 15, 2)->nullable()->after('offer_unit_price');
            $table->boolean('offer_unit_price_free')->default(false)->after('offer_unit_price_discount');

            $table->decimal('offer_vat_amount', 15, 2)->nullable()->after('offer_unit_price_free');
            $table->decimal('offer_vat_discount', 15, 2)->nullable()->after('offer_vat_amount');
            $table->boolean('offer_vat_free')->default(false)->after('offer_vat_discount');

            $table->decimal('offer_total_cost', 15, 2)->nullable()->after('offer_vat_free');
            $table->decimal('offer_total_discount', 15, 2)->nullable()->after('offer_total_cost');
            $table->decimal('offer_final_price', 15, 2)->nullable()->after('offer_total_discount');
        });
    }

    public function down(): void
    {
        Schema::table('prospect_sheets', function (Blueprint $table) {
            $table->dropColumn([
                'offer_unit_price',
                'offer_unit_price_discount',
                'offer_unit_price_free',
                'offer_vat_amount',
                'offer_vat_discount',
                'offer_vat_free',
                'offer_total_cost',
                'offer_total_discount',
                'offer_final_price',
            ]);
        });
    }
};
