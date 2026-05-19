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
            if (!Schema::hasColumn('enquiries', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('finance');
            }

            if (!Schema::hasColumn('enquiries', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }

            if (!Schema::hasColumn('enquiries', 'location_captured_at')) {
                $table->timestamp('location_captured_at')->nullable()->after('longitude');
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

            if (Schema::hasColumn('enquiries', 'location_captured_at')) {
                $dropColumns[] = 'location_captured_at';
            }

            if (Schema::hasColumn('enquiries', 'longitude')) {
                $dropColumns[] = 'longitude';
            }

            if (Schema::hasColumn('enquiries', 'latitude')) {
                $dropColumns[] = 'latitude';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
