<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (!Schema::hasColumn('bookings', 'finance_bank')) {
                $table->string('finance_bank')->nullable()->after('finance_form');
            }

            if (!Schema::hasColumn('bookings', 'finance_other_details')) {
                $table->string('finance_other_details')->nullable()->after('finance_bank');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (Schema::hasColumn('bookings', 'finance_other_details')) {
                $table->dropColumn('finance_other_details');
            }

            if (Schema::hasColumn('bookings', 'finance_bank')) {
                $table->dropColumn('finance_bank');
            }
        });
    }
};
