<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('deliveries', 'interested_model')) {
                $table->string('interested_model')->nullable()->after('profession');
            }

            if (!Schema::hasColumn('deliveries', 'interested_engine')) {
                $table->string('interested_engine')->nullable()->after('interested_model');
            }

            if (!Schema::hasColumn('deliveries', 'interested_variant')) {
                $table->string('interested_variant')->nullable()->after('interested_engine');
            }

            if (!Schema::hasColumn('deliveries', 'interested_vehicle_color')) {
                $table->string('interested_vehicle_color', 50)->nullable()->after('interested_variant');
            }

            if (!Schema::hasColumn('deliveries', 'quote_taken')) {
                $table->string('quote_taken', 10)->nullable()->after('interested_vehicle_color');
            }

            if (!Schema::hasColumn('deliveries', 'quote_date')) {
                $table->date('quote_date')->nullable()->after('quote_taken');
            }

            if (!Schema::hasColumn('deliveries', 'test_drive_given')) {
                $table->string('test_drive_given', 10)->nullable()->after('quote_date');
            }

            if (!Schema::hasColumn('deliveries', 'test_drive_date')) {
                $table->date('test_drive_date')->nullable()->after('test_drive_given');
            }

            if (!Schema::hasColumn('deliveries', 'test_drive_vehicle_model')) {
                $table->string('test_drive_vehicle_model')->nullable()->after('test_drive_date');
            }

            if (!Schema::hasColumn('deliveries', 'test_drive_to_whom')) {
                $table->string('test_drive_to_whom')->nullable()->after('test_drive_vehicle_model');
            }

            if (!Schema::hasColumn('deliveries', 'test_drive_not_given_reason')) {
                $table->string('test_drive_not_given_reason')->nullable()->after('test_drive_to_whom');
            }

            if (!Schema::hasColumn('deliveries', 'purchase_mode')) {
                $table->string('purchase_mode', 20)->nullable()->after('test_drive_not_given_reason');
            }

            if (!Schema::hasColumn('deliveries', 'finance_form')) {
                $table->string('finance_form', 20)->nullable()->after('purchase_mode');
            }

            if (!Schema::hasColumn('deliveries', 'interested_in_competition')) {
                $table->string('interested_in_competition', 20)->nullable()->after('finance_form');
            }

            if (!Schema::hasColumn('deliveries', 'competition_brand')) {
                $table->string('competition_brand')->nullable()->after('interested_in_competition');
            }

            if (!Schema::hasColumn('deliveries', 'competition_model')) {
                $table->string('competition_model')->nullable()->after('competition_brand');
            }

            if (!Schema::hasColumn('deliveries', 'first_time_buyer')) {
                $table->string('first_time_buyer', 10)->nullable()->after('competition_model');
            }

            if (!Schema::hasColumn('deliveries', 'existing_vehicle_brand')) {
                $table->string('existing_vehicle_brand')->nullable()->after('first_time_buyer');
            }

            if (!Schema::hasColumn('deliveries', 'existing_vehicle_model')) {
                $table->string('existing_vehicle_model')->nullable()->after('existing_vehicle_brand');
            }

            if (!Schema::hasColumn('deliveries', 'existing_vehicle_year')) {
                $table->unsignedSmallInteger('existing_vehicle_year')->nullable()->after('existing_vehicle_model');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $columns = [
                'existing_vehicle_year',
                'existing_vehicle_model',
                'existing_vehicle_brand',
                'first_time_buyer',
                'competition_model',
                'competition_brand',
                'interested_in_competition',
                'finance_form',
                'purchase_mode',
                'test_drive_not_given_reason',
                'test_drive_to_whom',
                'test_drive_vehicle_model',
                'test_drive_date',
                'test_drive_given',
                'quote_date',
                'quote_taken',
                'interested_vehicle_color',
                'interested_variant',
                'interested_engine',
                'interested_model',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('deliveries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
