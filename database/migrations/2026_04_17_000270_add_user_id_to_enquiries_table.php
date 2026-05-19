<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('enquiries') || Schema::hasColumn('enquiries', 'user_id')) {
            return;
        }

        Schema::table('enquiries', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->index('user_id', 'enquiries_user_id_index');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('enquiries') || !Schema::hasColumn('enquiries', 'user_id')) {
            return;
        }

        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropIndex('enquiries_user_id_index');
            $table->dropColumn('user_id');
        });
    }
};
