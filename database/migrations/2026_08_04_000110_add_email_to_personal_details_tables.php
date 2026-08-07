<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customers') && !Schema::hasColumn('customers', 'email')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('email')->nullable()->after('name');
            });
        }

        if (Schema::hasTable('bookings') && !Schema::hasColumn('bookings', 'email')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->string('email')->nullable()->after('mobile_numbers');
            });
        }

        if (Schema::hasTable('deliveries') && !Schema::hasColumn('deliveries', 'email')) {
            Schema::table('deliveries', function (Blueprint $table) {
                $table->string('email')->nullable()->after('mobile_numbers');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deliveries') && Schema::hasColumn('deliveries', 'email')) {
            Schema::table('deliveries', function (Blueprint $table) {
                $table->dropColumn('email');
            });
        }

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'email')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('email');
            });
        }

        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'email')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('email');
            });
        }
    }
};
