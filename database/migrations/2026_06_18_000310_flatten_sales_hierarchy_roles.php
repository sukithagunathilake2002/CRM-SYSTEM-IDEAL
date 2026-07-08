<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'role')) {
            return;
        }

        $removedRole = implode('_', ['regional', 'manager']);
        $legacyRegionalManagers = DB::table('users')
            ->where('role', $removedRole)
            ->get(['id', 'manager_id']);

        if (Schema::hasColumn('users', 'manager_id')) {
            foreach ($legacyRegionalManagers as $legacyRegionalManager) {
                $payload = [
                    'manager_id' => $legacyRegionalManager->manager_id,
                ];

                if (Schema::hasColumn('users', 'updated_at')) {
                    $payload['updated_at'] = now();
                }

                DB::table('users')
                    ->where('role', User::ROLE_AREA_MANAGER)
                    ->where('manager_id', $legacyRegionalManager->id)
                    ->update($payload);
            }
        }

        DB::table('users')
            ->where('role', $removedRole)
            ->delete();
    }

    public function down(): void
    {
        //
    }
};
