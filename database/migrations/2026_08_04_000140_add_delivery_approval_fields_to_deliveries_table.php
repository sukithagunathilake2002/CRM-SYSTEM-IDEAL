<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('deliveries')) {
            return;
        }

        Schema::table('deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('deliveries', 'approval_status')) {
                $table->string('approval_status', 20)->default('draft')->after('pending_commitments');
            }

            if (!Schema::hasColumn('deliveries', 'submitted_by')) {
                $table->unsignedBigInteger('submitted_by')->nullable()->after('approval_status');
            }

            if (!Schema::hasColumn('deliveries', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            }

            if (!Schema::hasColumn('deliveries', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('submitted_at');
            }

            if (!Schema::hasColumn('deliveries', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (!Schema::hasColumn('deliveries', 'approval_note')) {
                $table->text('approval_note')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('deliveries')) {
            return;
        }

        Schema::table('deliveries', function (Blueprint $table) {
            $dropColumns = [];

            foreach ([
                'approval_note',
                'approved_at',
                'approved_by',
                'submitted_at',
                'submitted_by',
                'approval_status',
            ] as $column) {
                if (Schema::hasColumn('deliveries', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
