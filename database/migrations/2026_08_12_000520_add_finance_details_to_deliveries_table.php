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

        Schema::table('deliveries', function (Blueprint $table): void {
            if (!Schema::hasColumn('deliveries', 'payment_finance_bank')) {
                $table->string('payment_finance_bank')->nullable()->after('payment_finance_provider');
            }

            if (!Schema::hasColumn('deliveries', 'payment_finance_disbursal_amount')) {
                $table->decimal('payment_finance_disbursal_amount', 15, 2)->nullable()->after('payment_finance_bank');
            }

            if (!Schema::hasColumn('deliveries', 'payment_finance_other_reason')) {
                $table->string('payment_finance_other_reason')->nullable()->after('payment_finance_disbursal_amount');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('deliveries')) {
            return;
        }

        Schema::table('deliveries', function (Blueprint $table): void {
            foreach ([
                'payment_finance_other_reason',
                'payment_finance_disbursal_amount',
                'payment_finance_bank',
            ] as $column) {
                if (Schema::hasColumn('deliveries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
