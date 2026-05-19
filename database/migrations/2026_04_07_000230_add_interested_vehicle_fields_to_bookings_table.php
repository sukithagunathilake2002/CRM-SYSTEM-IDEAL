<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('interested_model')->nullable()->after('date_of_birth');
            $table->string('interested_engine')->nullable()->after('interested_model');
            $table->string('interested_variant')->nullable()->after('interested_engine');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'interested_model',
                'interested_engine',
                'interested_variant',
            ]);
        });
    }
};
