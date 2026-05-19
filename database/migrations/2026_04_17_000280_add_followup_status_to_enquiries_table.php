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
            if (!Schema::hasColumn('enquiries', 'followup_status')) {
                $table->string('followup_status', 20)->default('pending')->after('follow_time');
            }

            if (!Schema::hasColumn('enquiries', 'followup_marked_at')) {
                $table->timestamp('followup_marked_at')->nullable()->after('followup_status');
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

            if (Schema::hasColumn('enquiries', 'followup_marked_at')) {
                $dropColumns[] = 'followup_marked_at';
            }

            if (Schema::hasColumn('enquiries', 'followup_status')) {
                $dropColumns[] = 'followup_status';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
