<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('deliveries')) {
            return;
        }

        Schema::table('deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('deliveries', 'reference_taken')) {
                $table->boolean('reference_taken')->default(false)->after('delivery_receipts');
            }

            if (!Schema::hasColumn('deliveries', 'selecting_brand_reasons')) {
                $table->json('selecting_brand_reasons')->nullable()->after('reference_taken');
            }

            if (!Schema::hasColumn('deliveries', 'date_of_delivery')) {
                $table->date('date_of_delivery')->nullable()->after('selecting_brand_reasons');
            }

            if (!Schema::hasColumn('deliveries', 'chassis_number')) {
                $table->string('chassis_number')->nullable()->after('date_of_delivery');
            }

            if (!Schema::hasColumn('deliveries', 'pending_commitments')) {
                $table->text('pending_commitments')->nullable()->after('chassis_number');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('deliveries')) {
            return;
        }

        Schema::table('deliveries', function (Blueprint $table) {
            $dropColumns = [];

            foreach ([
                'pending_commitments',
                'chassis_number',
                'date_of_delivery',
                'selecting_brand_reasons',
                'reference_taken',
            ] as $column) {
                if (Schema::hasColumn('deliveries', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
