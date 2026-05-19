<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('enquiries')) {
            return;
        }

        Schema::table('enquiries', function (Blueprint $table) {
            if (!Schema::hasColumn('enquiries', 'followup_customer_comment')) {
                $table->text('followup_customer_comment')->nullable()->after('followup_result');
            }

            if (!Schema::hasColumn('enquiries', 'followup_conversion_year')) {
                $table->unsignedSmallInteger('followup_conversion_year')->nullable()->after('followup_customer_comment');
            }

            if (!Schema::hasColumn('enquiries', 'followup_conversion_month')) {
                $table->unsignedTinyInteger('followup_conversion_month')->nullable()->after('followup_conversion_year');
            }

            if (!Schema::hasColumn('enquiries', 'followup_test_drive_given')) {
                $table->string('followup_test_drive_given', 10)->nullable()->after('followup_conversion_month');
            }

            if (!Schema::hasColumn('enquiries', 'followup_test_drive_not_given_reason')) {
                $table->string('followup_test_drive_not_given_reason')->nullable()->after('followup_test_drive_given');
            }

            if (!Schema::hasColumn('enquiries', 'followup_test_drive_when')) {
                $table->date('followup_test_drive_when')->nullable()->after('followup_test_drive_not_given_reason');
            }

            if (!Schema::hasColumn('enquiries', 'followup_test_drive_vehicle_used')) {
                $table->string('followup_test_drive_vehicle_used')->nullable()->after('followup_test_drive_when');
            }

            if (!Schema::hasColumn('enquiries', 'followup_test_drive_to_whom')) {
                $table->string('followup_test_drive_to_whom')->nullable()->after('followup_test_drive_vehicle_used');
            }

            if (!Schema::hasColumn('enquiries', 'followup_first_time_buyer')) {
                $table->string('followup_first_time_buyer', 10)->nullable()->after('followup_test_drive_to_whom');
            }

            if (!Schema::hasColumn('enquiries', 'followup_first_time_buyer_reason')) {
                $table->string('followup_first_time_buyer_reason')->nullable()->after('followup_first_time_buyer');
            }

            if (!Schema::hasColumn('enquiries', 'followup_lead_temperature')) {
                $table->string('followup_lead_temperature', 20)->nullable()->after('followup_first_time_buyer_reason');
            }

            if (!Schema::hasColumn('enquiries', 'followup_next_type')) {
                $table->string('followup_next_type', 30)->nullable()->after('followup_lead_temperature');
            }

            if (!Schema::hasColumn('enquiries', 'followup_next_date')) {
                $table->date('followup_next_date')->nullable()->after('followup_next_type');
            }

            if (!Schema::hasColumn('enquiries', 'followup_next_time')) {
                $table->time('followup_next_time')->nullable()->after('followup_next_date');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('enquiries')) {
            return;
        }

        Schema::table('enquiries', function (Blueprint $table) {
            $dropColumns = [];

            foreach ([
                'followup_next_time',
                'followup_next_date',
                'followup_next_type',
                'followup_lead_temperature',
                'followup_first_time_buyer_reason',
                'followup_first_time_buyer',
                'followup_test_drive_to_whom',
                'followup_test_drive_vehicle_used',
                'followup_test_drive_when',
                'followup_test_drive_not_given_reason',
                'followup_test_drive_given',
                'followup_conversion_month',
                'followup_conversion_year',
                'followup_customer_comment',
            ] as $column) {
                if (Schema::hasColumn('enquiries', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
