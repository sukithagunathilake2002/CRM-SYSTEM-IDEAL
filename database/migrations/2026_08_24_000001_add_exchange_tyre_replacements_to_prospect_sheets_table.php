<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospect_sheets', function (Blueprint $table): void {
            if (!Schema::hasColumn('prospect_sheets', 'exchange_tyre_replacements')) {
                $table->json('exchange_tyre_replacements')->nullable()->after('exchange_registration_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prospect_sheets', function (Blueprint $table): void {
            if (Schema::hasColumn('prospect_sheets', 'exchange_tyre_replacements')) {
                $table->dropColumn('exchange_tyre_replacements');
            }
        });
    }
};
