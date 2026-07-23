<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('deliveries', 'payment_receipt_amount_booking')) {
                $table->decimal('payment_receipt_amount_booking', 15, 2)->nullable()->after('extra_images');
            }

            if (!Schema::hasColumn('deliveries', 'payment_pre_delivery_amount')) {
                $table->decimal('payment_pre_delivery_amount', 15, 2)->nullable()->after('payment_receipt_amount_booking');
            }

            if (!Schema::hasColumn('deliveries', 'payment_delivery_amount')) {
                $table->decimal('payment_delivery_amount', 15, 2)->nullable()->after('payment_pre_delivery_amount');
            }

            if (!Schema::hasColumn('deliveries', 'payment_finance_provider')) {
                $table->string('payment_finance_provider')->nullable()->after('payment_delivery_amount');
            }

            if (!Schema::hasColumn('deliveries', 'payment_pending_reason')) {
                $table->string('payment_pending_reason')->nullable()->after('payment_finance_provider');
            }

            if (!Schema::hasColumn('deliveries', 'payment_pending_amount')) {
                $table->decimal('payment_pending_amount', 15, 2)->nullable()->after('payment_pending_reason');
            }

            if (!Schema::hasColumn('deliveries', 'payment_agent_name')) {
                $table->string('payment_agent_name')->nullable()->after('payment_pending_amount');
            }

            if (!Schema::hasColumn('deliveries', 'payment_agent_number')) {
                $table->string('payment_agent_number', 50)->nullable()->after('payment_agent_name');
            }

            if (!Schema::hasColumn('deliveries', 'payment_expected_date')) {
                $table->date('payment_expected_date')->nullable()->after('payment_agent_number');
            }

            if (!Schema::hasColumn('deliveries', 'payment_credit_given_to_customer')) {
                $table->string('payment_credit_given_to_customer')->nullable()->after('payment_expected_date');
            }

            if (!Schema::hasColumn('deliveries', 'payment_credit_amount_pending')) {
                $table->decimal('payment_credit_amount_pending', 15, 2)->nullable()->after('payment_credit_given_to_customer');
            }

            if (!Schema::hasColumn('deliveries', 'payment_credit_permitted_by')) {
                $table->string('payment_credit_permitted_by')->nullable()->after('payment_credit_amount_pending');
            }

            if (!Schema::hasColumn('deliveries', 'payment_credit_expected_date')) {
                $table->date('payment_credit_expected_date')->nullable()->after('payment_credit_permitted_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $columns = [
                'payment_credit_expected_date',
                'payment_credit_permitted_by',
                'payment_credit_amount_pending',
                'payment_credit_given_to_customer',
                'payment_expected_date',
                'payment_agent_number',
                'payment_agent_name',
                'payment_pending_amount',
                'payment_pending_reason',
                'payment_finance_provider',
                'payment_delivery_amount',
                'payment_pre_delivery_amount',
                'payment_receipt_amount_booking',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('deliveries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
