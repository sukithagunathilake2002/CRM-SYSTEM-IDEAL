<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_INDEX = 'enquiries_dashboard_followup_index';
    private const NEW_INDEX = 'enquiries_dashboard_followup_covering_index';

    public function up(): void
    {
        if (!Schema::hasTable('enquiries') || $this->hasIndex(self::NEW_INDEX)) {
            return;
        }

        Schema::table('enquiries', function (Blueprint $table): void {
            // Keep every predicate used by the dashboard count in the index.
            // This avoids reading 120k+ wide enquiry rows just to calculate
            // the three dashboard card totals.
            $table->index([
                'user_id',
                'follow_type',
                'follow_date',
                'followup_result',
                'status',
                'followup_status',
            ], self::NEW_INDEX);
        });

        if ($this->hasIndex(self::OLD_INDEX)) {
            Schema::table('enquiries', function (Blueprint $table): void {
                $table->dropIndex(self::OLD_INDEX);
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('enquiries')) {
            return;
        }

        if ($this->hasIndex(self::NEW_INDEX)) {
            Schema::table('enquiries', function (Blueprint $table): void {
                $table->dropIndex(self::NEW_INDEX);
            });
        }

        if (!$this->hasIndex(self::OLD_INDEX)) {
            Schema::table('enquiries', function (Blueprint $table): void {
                $table->index(['user_id', 'follow_type', 'follow_date'], self::OLD_INDEX);
            });
        }
    }

    private function hasIndex(string $indexName): bool
    {
        return collect(Schema::getIndexes('enquiries'))
            ->contains(fn (array $index): bool => ($index['name'] ?? null) === $indexName);
    }
};
