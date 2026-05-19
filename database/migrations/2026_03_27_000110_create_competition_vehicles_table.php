<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('model');
            $table->timestamps();

            $table->unique(['brand', 'model']);
        });

        DB::table('competition_vehicles')->insert([
            ['brand' => 'Renault', 'model' => 'Kwid', 'created_at' => now(), 'updated_at' => now()],
            ['brand' => 'Renault', 'model' => 'Kiger', 'created_at' => now(), 'updated_at' => now()],
            ['brand' => 'Renault', 'model' => 'Triber', 'created_at' => now(), 'updated_at' => now()],
            ['brand' => 'Tata', 'model' => 'Punch', 'created_at' => now(), 'updated_at' => now()],
            ['brand' => 'Tata', 'model' => 'Nexon', 'created_at' => now(), 'updated_at' => now()],
            ['brand' => 'Tata', 'model' => 'Tiago', 'created_at' => now(), 'updated_at' => now()],
            ['brand' => 'Toyota', 'model' => 'Raize', 'created_at' => now(), 'updated_at' => now()],
            ['brand' => 'Toyota', 'model' => 'Yaris', 'created_at' => now(), 'updated_at' => now()],
            ['brand' => 'Suzuki', 'model' => 'Swift', 'created_at' => now(), 'updated_at' => now()],
            ['brand' => 'Suzuki', 'model' => 'Baleno', 'created_at' => now(), 'updated_at' => now()],
            ['brand' => 'Suzuki', 'model' => 'Fronx', 'created_at' => now(), 'updated_at' => now()],
            ['brand' => 'Hyundai', 'model' => 'Grand i10', 'created_at' => now(), 'updated_at' => now()],
            ['brand' => 'Hyundai', 'model' => 'Venue', 'created_at' => now(), 'updated_at' => now()],
            ['brand' => 'Hyundai', 'model' => 'Creta', 'created_at' => now(), 'updated_at' => now()],
            ['brand' => 'Kia', 'model' => 'Sonet', 'created_at' => now(), 'updated_at' => now()],
            ['brand' => 'Kia', 'model' => 'Seltos', 'created_at' => now(), 'updated_at' => now()],
            ['brand' => 'Honda', 'model' => 'City', 'created_at' => now(), 'updated_at' => now()],
            ['brand' => 'Honda', 'model' => 'Amaze', 'created_at' => now(), 'updated_at' => now()],
            ['brand' => 'Nissan', 'model' => 'Magnite', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_vehicles');
    }
};

