<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'enquiries_dashboard_followup_index';

    public function up(): void
    {
        if (!Schema::hasTable('enquiries') || $this->hasIndex(self::INDEX_NAME)) {
            return;
        }

        Schema::table('enquiries', function (Blueprint $table): void {
            $table->index(['user_id', 'follow_type', 'follow_date'], self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('enquiries') && $this->hasIndex(self::INDEX_NAME)) {
            Schema::table('enquiries', function (Blueprint $table): void {
                $table->dropIndex(self::INDEX_NAME);
            });
        }
    }

    private function hasIndex(string $indexName): bool
    {
        return collect(Schema::getIndexes('enquiries'))
            ->contains(fn (array $index): bool => ($index['name'] ?? null) === $indexName);
    }
};
