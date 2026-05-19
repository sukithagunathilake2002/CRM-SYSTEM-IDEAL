<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospect_sheets', function (Blueprint $table) {
            $table->string('interested_in_exchange', 20)->nullable()->after('purchase_mode');
            $table->string('exchange_vehicle_brand')->nullable()->after('interested_in_exchange');
            $table->string('exchange_vehicle_model')->nullable()->after('exchange_vehicle_brand');
            $table->unsignedSmallInteger('exchange_manufacture_year')->nullable()->after('exchange_vehicle_model');
            $table->string('exchange_color')->nullable()->after('exchange_manufacture_year');
            $table->unsignedInteger('exchange_mileage_km')->nullable()->after('exchange_color');
            $table->string('exchange_registration_no', 50)->nullable()->after('exchange_mileage_km');
            $table->decimal('exchange_expected_price', 15, 2)->nullable()->after('exchange_registration_no');
            $table->decimal('exchange_quoted_price', 15, 2)->nullable()->after('exchange_expected_price');
            $table->decimal('exchange_price_difference', 15, 2)->nullable()->after('exchange_quoted_price');

            $table->string('blue_book_image')->nullable()->after('exchange_price_difference');
            $table->string('lot_no_image')->nullable()->after('blue_book_image');
            $table->string('car_pic_1_image')->nullable()->after('lot_no_image');
            $table->string('car_pic_2_image')->nullable()->after('car_pic_1_image');
            $table->json('exchange_extra_images')->nullable()->after('car_pic_2_image');
        });
    }

    public function down(): void
    {
        Schema::table('prospect_sheets', function (Blueprint $table) {
            $table->dropColumn([
                'interested_in_exchange',
                'exchange_vehicle_brand',
                'exchange_vehicle_model',
                'exchange_manufacture_year',
                'exchange_color',
                'exchange_mileage_km',
                'exchange_registration_no',
                'exchange_expected_price',
                'exchange_quoted_price',
                'exchange_price_difference',
                'blue_book_image',
                'lot_no_image',
                'car_pic_1_image',
                'car_pic_2_image',
                'exchange_extra_images',
            ]);
        });
    }
};

