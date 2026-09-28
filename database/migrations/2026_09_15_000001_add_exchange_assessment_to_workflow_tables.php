<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['prospect_sheets', 'bookings', 'deliveries'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->json('exchange_assessment')->nullable());
        }
    }

    public function down(): void
    {
        foreach (['prospect_sheets', 'bookings', 'deliveries'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('exchange_assessment'));
        }
    }
};
