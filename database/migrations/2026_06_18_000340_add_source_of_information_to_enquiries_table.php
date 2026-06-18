<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('enquiries') || Schema::hasColumn('enquiries', 'source_of_information')) {
            return;
        }

        Schema::table('enquiries', function (Blueprint $table): void {
            $table->string('source_of_information')->nullable()->after('lead_source');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('enquiries') || !Schema::hasColumn('enquiries', 'source_of_information')) {
            return;
        }

        Schema::table('enquiries', function (Blueprint $table): void {
            $table->dropColumn('source_of_information');
        });
    }
};
