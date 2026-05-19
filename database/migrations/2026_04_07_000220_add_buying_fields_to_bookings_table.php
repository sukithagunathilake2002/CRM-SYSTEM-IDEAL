<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('interested_vehicle_color', 50)->nullable()->after('date_of_birth');
            $table->string('quote_taken', 20)->nullable()->after('interested_vehicle_color');
            $table->date('quote_date')->nullable()->after('quote_taken');
            $table->string('test_drive_given', 20)->nullable()->after('quote_date');
            $table->date('test_drive_date')->nullable()->after('test_drive_given');
            $table->string('test_drive_vehicle_model')->nullable()->after('test_drive_date');
            $table->string('test_drive_to_whom')->nullable()->after('test_drive_vehicle_model');
            $table->string('test_drive_not_given_reason')->nullable()->after('test_drive_to_whom');
            $table->string('purchase_mode', 20)->nullable()->after('test_drive_not_given_reason');
            $table->string('finance_form', 20)->nullable()->after('purchase_mode');
            $table->string('interested_in_competition', 20)->nullable()->after('finance_form');
            $table->string('competition_brand')->nullable()->after('interested_in_competition');
            $table->string('competition_model')->nullable()->after('competition_brand');
            $table->string('first_time_buyer', 20)->nullable()->after('competition_model');
            $table->string('existing_vehicle_brand')->nullable()->after('first_time_buyer');
            $table->string('existing_vehicle_model')->nullable()->after('existing_vehicle_brand');
            $table->unsignedSmallInteger('existing_vehicle_year')->nullable()->after('existing_vehicle_model');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'interested_vehicle_color',
                'quote_taken',
                'quote_date',
                'test_drive_given',
                'test_drive_date',
                'test_drive_vehicle_model',
                'test_drive_to_whom',
                'test_drive_not_given_reason',
                'purchase_mode',
                'finance_form',
                'interested_in_competition',
                'competition_brand',
                'competition_model',
                'first_time_buyer',
                'existing_vehicle_brand',
                'existing_vehicle_model',
                'existing_vehicle_year',
            ]);
        });
    }
};
