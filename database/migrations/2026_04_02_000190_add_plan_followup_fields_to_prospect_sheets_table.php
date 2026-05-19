<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospect_sheets', function (Blueprint $table) {
            $table->boolean('reschedule_followup')->default(false)->after('current_step');
            $table->string('lead_status', 20)->nullable()->after('reschedule_followup');
            $table->text('customer_remark')->nullable()->after('lead_status');
        });
    }

    public function down(): void
    {
        Schema::table('prospect_sheets', function (Blueprint $table) {
            $table->dropColumn(['reschedule_followup', 'lead_status', 'customer_remark']);
        });
    }
};
