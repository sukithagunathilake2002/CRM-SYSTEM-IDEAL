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
            if (!Schema::hasColumn('deliveries', 'exchange_purchase_value')) {
                $table->decimal('exchange_purchase_value', 15, 2)->nullable()->after('exchange_type');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('deliveries') || !Schema::hasColumn('deliveries', 'exchange_purchase_value')) {
            return;
        }

        Schema::table('deliveries', function (Blueprint $table): void {
            $table->dropColumn('exchange_purchase_value');
        });
    }
};
