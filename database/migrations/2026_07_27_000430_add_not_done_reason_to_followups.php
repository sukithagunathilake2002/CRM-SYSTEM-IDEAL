<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('enquiries')) {
            Schema::table('enquiries', function (Blueprint $table): void {
                if (!Schema::hasColumn('enquiries', 'followup_not_done_reason')) {
                    $table->string('followup_not_done_reason')->nullable()->after('followup_lost_reject_other_text');
                }

                if (!Schema::hasColumn('enquiries', 'followup_not_done_reason_other')) {
                    $table->string('followup_not_done_reason_other')->nullable()->after('followup_not_done_reason');
                }
            });
        }

        if (Schema::hasTable('followup_attempts')) {
            Schema::table('followup_attempts', function (Blueprint $table): void {
                if (!Schema::hasColumn('followup_attempts', 'not_done_reason')) {
                    $table->string('not_done_reason')->nullable()->after('followup_status');
                }

                if (!Schema::hasColumn('followup_attempts', 'not_done_reason_other')) {
                    $table->string('not_done_reason_other')->nullable()->after('not_done_reason');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('followup_attempts')) {
            Schema::table('followup_attempts', function (Blueprint $table): void {
                if (Schema::hasColumn('followup_attempts', 'not_done_reason_other')) {
                    $table->dropColumn('not_done_reason_other');
                }

                if (Schema::hasColumn('followup_attempts', 'not_done_reason')) {
                    $table->dropColumn('not_done_reason');
                }
            });
        }

        if (Schema::hasTable('enquiries')) {
            Schema::table('enquiries', function (Blueprint $table): void {
                if (Schema::hasColumn('enquiries', 'followup_not_done_reason_other')) {
                    $table->dropColumn('followup_not_done_reason_other');
                }

                if (Schema::hasColumn('enquiries', 'followup_not_done_reason')) {
                    $table->dropColumn('followup_not_done_reason');
                }
            });
        }
    }
};
