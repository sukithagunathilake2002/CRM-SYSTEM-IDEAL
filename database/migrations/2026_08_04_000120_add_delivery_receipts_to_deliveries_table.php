<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('deliveries') || Schema::hasColumn('deliveries', 'delivery_receipts')) {
            return;
        }

        Schema::table('deliveries', function (Blueprint $table) {
            $table->json('delivery_receipts')->nullable()->after('payment_delivery_amount');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('deliveries') || !Schema::hasColumn('deliveries', 'delivery_receipts')) {
            return;
        }

        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn('delivery_receipts');
        });
    }
};
