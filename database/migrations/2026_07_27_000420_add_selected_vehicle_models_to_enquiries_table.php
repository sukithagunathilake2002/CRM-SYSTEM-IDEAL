<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('enquiries') || Schema::hasColumn('enquiries', 'selected_vehicle_models')) {
            return;
        }

        Schema::table('enquiries', function (Blueprint $table): void {
            $table->json('selected_vehicle_models')->nullable()->after('vehicle_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('enquiries') || !Schema::hasColumn('enquiries', 'selected_vehicle_models')) {
            return;
        }

        Schema::table('enquiries', function (Blueprint $table): void {
            $table->dropColumn('selected_vehicle_models');
        });
    }
};
