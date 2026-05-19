<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospect_sheets', function (Blueprint $table) {
            $table->string('source_of_information')->nullable()->after('interested_vehicle_color');
        });
    }

    public function down(): void
    {
        Schema::table('prospect_sheets', function (Blueprint $table) {
            $table->dropColumn('source_of_information');
        });
    }
};

