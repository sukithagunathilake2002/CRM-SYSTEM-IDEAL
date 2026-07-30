<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'exchange_purchase_value')) {
                $table->decimal('exchange_purchase_value', 15, 2)->nullable()->after('exchange_type');
            }

            if (!Schema::hasColumn('bookings', 'exchange_ownership')) {
                $table->string('exchange_ownership', 50)->nullable()->after('exchange_manufacture_year');
            }

            if (!Schema::hasColumn('bookings', 'exchange_insurance_validity')) {
                $table->date('exchange_insurance_validity')->nullable()->after('exchange_ownership');
            }

            if (!Schema::hasColumn('bookings', 'exchange_tyre_replacements')) {
                $table->json('exchange_tyre_replacements')->nullable()->after('exchange_registration_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $columns = [];

            foreach ([
                'exchange_tyre_replacements',
                'exchange_insurance_validity',
                'exchange_ownership',
                'exchange_purchase_value',
            ] as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $columns[] = $column;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
