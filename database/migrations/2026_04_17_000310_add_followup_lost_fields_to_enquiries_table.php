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
            if (!Schema::hasColumn('enquiries', 'followup_lost_to')) {
                $table->string('followup_lost_to', 20)->nullable()->after('followup_next_time');
            }

            if (!Schema::hasColumn('enquiries', 'followup_lost_competition_brand')) {
                $table->string('followup_lost_competition_brand')->nullable()->after('followup_lost_to');
            }

            if (!Schema::hasColumn('enquiries', 'followup_lost_competition_model')) {
                $table->string('followup_lost_competition_model')->nullable()->after('followup_lost_competition_brand');
            }

            if (!Schema::hasColumn('enquiries', 'followup_lost_codealer_name')) {
                $table->string('followup_lost_codealer_name')->nullable()->after('followup_lost_competition_model');
            }

            if (!Schema::hasColumn('enquiries', 'followup_lost_reject_reasons')) {
                $table->json('followup_lost_reject_reasons')->nullable()->after('followup_lost_codealer_name');
            }

            if (!Schema::hasColumn('enquiries', 'followup_lost_reject_other_text')) {
                $table->string('followup_lost_reject_other_text')->nullable()->after('followup_lost_reject_reasons');
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
                'followup_lost_reject_other_text',
                'followup_lost_reject_reasons',
                'followup_lost_codealer_name',
                'followup_lost_competition_model',
                'followup_lost_competition_brand',
                'followup_lost_to',
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

