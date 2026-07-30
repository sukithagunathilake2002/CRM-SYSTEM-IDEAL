<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (!Schema::hasColumn('bookings', 'expected_delivery_date')) {
                $table->date('expected_delivery_date')->nullable()->after('offer_remark');
            }

            if (!Schema::hasColumn('bookings', 'booking_date')) {
                $table->date('booking_date')->nullable()->after('expected_delivery_date');
            }

            if (!Schema::hasColumn('bookings', 'amount_collected')) {
                $table->decimal('amount_collected', 15, 2)->nullable()->after('booking_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            foreach (['amount_collected', 'booking_date', 'expected_delivery_date'] as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
