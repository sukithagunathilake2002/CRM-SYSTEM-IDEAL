<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'permitted_districts')) {
            return;
        }

        $payload = [
            'permitted_districts' => null,
        ];

        if (Schema::hasColumn('users', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        DB::table('users')->update($payload);
    }

    public function down(): void
    {
        //
    }
};
