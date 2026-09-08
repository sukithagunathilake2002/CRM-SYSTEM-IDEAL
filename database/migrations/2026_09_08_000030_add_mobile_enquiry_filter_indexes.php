<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PROSPECT_STATUS_INDEX = 'prospect_sheets_lead_status_index';

    public function up(): void
    {
        if (Schema::hasTable('prospect_sheets') && !$this->hasProspectStatusIndex()) {
            Schema::table('prospect_sheets', function (Blueprint $table): void {
                // Used by the Hot, Warm, and Cold lead filters.
                $table->index('lead_status', self::PROSPECT_STATUS_INDEX);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('prospect_sheets') && $this->hasProspectStatusIndex()) {
            Schema::table('prospect_sheets', function (Blueprint $table): void {
                $table->dropIndex(self::PROSPECT_STATUS_INDEX);
            });
        }
    }

    private function hasProspectStatusIndex(): bool
    {
        return collect(Schema::getIndexes('prospect_sheets'))
            ->contains(fn (array $index): bool => ($index['name'] ?? null) === self::PROSPECT_STATUS_INDEX);
    }
};
