<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable()->after('email');
            }

            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role', 40)->default(User::ROLE_SALES_CONSULTANT)->after('phone');
            }

            if (!Schema::hasColumn('users', 'manager_id')) {
                $table->unsignedBigInteger('manager_id')->nullable()->after('role')->index();
            }
        });

        $superAdminEmail = env('SUPER_ADMIN_EMAIL', 'superadmin@idealmotors.com');
        $superAdminPassword = env('SUPER_ADMIN_PASSWORD', 'Admin@123');
        $superAdminName = env('SUPER_ADMIN_NAME', 'Super Admin');

        $hasSuperAdmin = DB::table('users')
            ->where('role', User::ROLE_SUPER_ADMIN)
            ->orWhere('email', $superAdminEmail)
            ->exists();

        if (!$hasSuperAdmin) {
            DB::table('users')->insert([
                'name' => $superAdminName,
                'email' => $superAdminEmail,
                'phone' => null,
                'role' => User::ROLE_SUPER_ADMIN,
                'manager_id' => null,
                'password' => Hash::make($superAdminPassword),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $drops = [];

            if (Schema::hasColumn('users', 'manager_id')) {
                $drops[] = 'manager_id';
            }
            if (Schema::hasColumn('users', 'role')) {
                $drops[] = 'role';
            }
            if (Schema::hasColumn('users', 'phone')) {
                $drops[] = 'phone';
            }

            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
};

