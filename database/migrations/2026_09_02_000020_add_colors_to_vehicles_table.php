<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vehicles') || Schema::hasColumn('vehicles', 'colors')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->json('colors')->nullable()->after('variant');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('vehicles') || !Schema::hasColumn('vehicles', 'colors')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropColumn('colors');
        });
    }
};
