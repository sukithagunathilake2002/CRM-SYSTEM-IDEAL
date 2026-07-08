<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('deliveries', 'interested_in_exchange')) {
                $table->string('interested_in_exchange', 10)->nullable()->after('existing_vehicle_year');
            }

            if (!Schema::hasColumn('deliveries', 'exchange_type')) {
                $table->string('exchange_type', 20)->nullable()->after('interested_in_exchange');
            }

            if (!Schema::hasColumn('deliveries', 'exchange_vehicle_brand')) {
                $table->string('exchange_vehicle_brand')->nullable()->after('exchange_type');
            }

            if (!Schema::hasColumn('deliveries', 'exchange_vehicle_model')) {
                $table->string('exchange_vehicle_model')->nullable()->after('exchange_vehicle_brand');
            }

            if (!Schema::hasColumn('deliveries', 'exchange_manufacture_year')) {
                $table->unsignedSmallInteger('exchange_manufacture_year')->nullable()->after('exchange_vehicle_model');
            }

            if (!Schema::hasColumn('deliveries', 'exchange_color')) {
                $table->string('exchange_color')->nullable()->after('exchange_manufacture_year');
            }

            if (!Schema::hasColumn('deliveries', 'exchange_mileage_km')) {
                $table->unsignedInteger('exchange_mileage_km')->nullable()->after('exchange_color');
            }

            if (!Schema::hasColumn('deliveries', 'exchange_registration_no')) {
                $table->string('exchange_registration_no', 50)->nullable()->after('exchange_mileage_km');
            }

            if (!Schema::hasColumn('deliveries', 'exchange_expected_price')) {
                $table->decimal('exchange_expected_price', 12, 2)->nullable()->after('exchange_registration_no');
            }

            if (!Schema::hasColumn('deliveries', 'exchange_quoted_price')) {
                $table->decimal('exchange_quoted_price', 12, 2)->nullable()->after('exchange_expected_price');
            }

            if (!Schema::hasColumn('deliveries', 'exchange_price_difference')) {
                $table->decimal('exchange_price_difference', 12, 2)->nullable()->after('exchange_quoted_price');
            }

            if (!Schema::hasColumn('deliveries', 'blue_book_image')) {
                $table->string('blue_book_image')->nullable()->after('exchange_price_difference');
            }

            if (!Schema::hasColumn('deliveries', 'lot_no_image')) {
                $table->string('lot_no_image')->nullable()->after('blue_book_image');
            }

            if (!Schema::hasColumn('deliveries', 'car_pic_1_image')) {
                $table->string('car_pic_1_image')->nullable()->after('lot_no_image');
            }

            if (!Schema::hasColumn('deliveries', 'car_pic_2_image')) {
                $table->string('car_pic_2_image')->nullable()->after('car_pic_1_image');
            }

            if (!Schema::hasColumn('deliveries', 'exchange_extra_images')) {
                $table->json('exchange_extra_images')->nullable()->after('car_pic_2_image');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            foreach ([
                'exchange_extra_images',
                'car_pic_2_image',
                'car_pic_1_image',
                'lot_no_image',
                'blue_book_image',
                'exchange_price_difference',
                'exchange_quoted_price',
                'exchange_expected_price',
                'exchange_registration_no',
                'exchange_mileage_km',
                'exchange_color',
                'exchange_manufacture_year',
                'exchange_vehicle_model',
                'exchange_vehicle_brand',
                'exchange_type',
                'interested_in_exchange',
            ] as $column) {
                if (Schema::hasColumn('deliveries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
