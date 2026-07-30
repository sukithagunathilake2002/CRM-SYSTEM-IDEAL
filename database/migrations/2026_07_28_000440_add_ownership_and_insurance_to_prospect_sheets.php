<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospect_sheets', function (Blueprint $table) {
            if (!Schema::hasColumn('prospect_sheets', 'exchange_ownership')) {
                $table->string('exchange_ownership', 50)->nullable()->after('exchange_manufacture_year');
            }

            if (!Schema::hasColumn('prospect_sheets', 'exchange_insurance_validity')) {
                $table->date('exchange_insurance_validity')->nullable()->after('exchange_ownership');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prospect_sheets', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('prospect_sheets', 'exchange_insurance_validity')) {
                $columns[] = 'exchange_insurance_validity';
            }

            if (Schema::hasColumn('prospect_sheets', 'exchange_ownership')) {
                $columns[] = 'exchange_ownership';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
