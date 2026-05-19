<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospect_sheets', function (Blueprint $table) {
            $table->string('customer_type', 20)->nullable()->after('enquiry_id');
            $table->string('corporate_name')->nullable()->after('customer_type');
            $table->string('profession', 30)->nullable()->after('corporate_name');
            $table->date('date_of_birth')->nullable()->after('profession');
        });
    }

    public function down(): void
    {
        Schema::table('prospect_sheets', function (Blueprint $table) {
            $table->dropColumn(['customer_type', 'corporate_name', 'profession', 'date_of_birth']);
        });
    }
};
