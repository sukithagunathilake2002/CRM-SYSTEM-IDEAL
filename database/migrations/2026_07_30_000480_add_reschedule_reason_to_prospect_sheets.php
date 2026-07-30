<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospect_sheets', function (Blueprint $table): void {
            if (!Schema::hasColumn('prospect_sheets', 'reschedule_reason')) {
                $table->text('reschedule_reason')->nullable()->after('reschedule_followup');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prospect_sheets', function (Blueprint $table): void {
            if (Schema::hasColumn('prospect_sheets', 'reschedule_reason')) {
                $table->dropColumn('reschedule_reason');
            }
        });
    }
};
