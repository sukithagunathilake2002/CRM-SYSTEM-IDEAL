<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospect_sheets', function (Blueprint $table) {
            $table->id();
            $table->integer('enquiry_id')->unique();
            $table->foreign('enquiry_id')->references('id')->on('enquiries')->cascadeOnDelete();

            $table->string('quote_taken', 20)->nullable();
            $table->date('quote_date')->nullable();

            $table->string('test_drive_given', 20)->nullable();
            $table->date('test_drive_date')->nullable();
            $table->string('test_drive_vehicle_model')->nullable();
            $table->string('test_drive_to_whom')->nullable();
            $table->string('test_drive_not_given_reason')->nullable();

            $table->string('interested_in_competition', 20)->nullable();
            $table->string('competition_brand')->nullable();
            $table->string('competition_model')->nullable();

            $table->string('first_time_buyer', 20)->nullable();
            $table->string('existing_vehicle_brand')->nullable();
            $table->string('existing_vehicle_model')->nullable();
            $table->unsignedSmallInteger('existing_vehicle_year')->nullable();

            $table->unsignedTinyInteger('current_step')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospect_sheets');
    }
};
