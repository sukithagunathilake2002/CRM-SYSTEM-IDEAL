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
            if (!Schema::hasColumn('enquiries', 'followup_visit_date')) {
                $table->date('followup_visit_date')->nullable()->after('followup_marked_at');
            }

            if (!Schema::hasColumn('enquiries', 'followup_met_whom')) {
                $table->string('followup_met_whom')->nullable()->after('followup_visit_date');
            }

            if (!Schema::hasColumn('enquiries', 'followup_picture_1')) {
                $table->string('followup_picture_1')->nullable()->after('followup_met_whom');
            }

            if (!Schema::hasColumn('enquiries', 'followup_picture_2')) {
                $table->string('followup_picture_2')->nullable()->after('followup_picture_1');
            }

            if (!Schema::hasColumn('enquiries', 'followup_result')) {
                $table->string('followup_result', 20)->nullable()->after('followup_picture_2');
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

            foreach (['followup_result', 'followup_picture_2', 'followup_picture_1', 'followup_met_whom', 'followup_visit_date'] as $column) {
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
