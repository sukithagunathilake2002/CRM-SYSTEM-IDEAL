<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospect_sheets', function (Blueprint $table) {
            if (!Schema::hasColumn('prospect_sheets', 'offer_remark')) {
                $table->text('offer_remark')->nullable()->after('offer_final_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prospect_sheets', function (Blueprint $table) {
            if (Schema::hasColumn('prospect_sheets', 'offer_remark')) {
                $table->dropColumn('offer_remark');
            }
        });
    }
};
