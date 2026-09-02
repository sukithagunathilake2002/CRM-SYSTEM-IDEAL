<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasTable('vehicles') || Schema::hasTable('head_of_sales_vehicle')) {
            return;
        }

        Schema::create('head_of_sales_vehicle', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('head_of_sales_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->timestamps();

            $table->unique(['head_of_sales_id', 'vehicle_id'], 'hos_vehicle_unique');
            $table->index('head_of_sales_id');
            $table->index('vehicle_id');
        });

        Schema::table('head_of_sales_vehicle', function (Blueprint $table): void {
            $table->foreign('head_of_sales_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('vehicle_id')
                ->references('id')
                ->on('vehicles')
                ->cascadeOnDelete();
        });

        $headIds = DB::table('users')
            ->where('role', User::ROLE_HEAD_OF_SALES)
            ->pluck('id');
        $vehicleIds = DB::table('vehicles')->pluck('id');
        $now = now();
        $rows = [];

        foreach ($headIds as $headId) {
            foreach ($vehicleIds as $vehicleId) {
                $rows[] = [
                    'head_of_sales_id' => $headId,
                    'vehicle_id' => $vehicleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($rows)) {
            DB::table('head_of_sales_vehicle')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('head_of_sales_vehicle');
    }
};
