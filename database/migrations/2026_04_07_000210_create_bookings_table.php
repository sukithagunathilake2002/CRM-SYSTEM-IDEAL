<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->integer('enquiry_id')->unique();
            $table->foreign('enquiry_id')->references('id')->on('enquiries')->cascadeOnDelete();

            $table->boolean('booking_same_as_customer')->default(true);
            $table->string('title', 20)->nullable();
            $table->string('name')->nullable();
            $table->string('contact_type', 20)->nullable();
            $table->string('mobile_numbers')->nullable();
            $table->string('district')->nullable();
            $table->string('location')->nullable();
            $table->string('state')->nullable();
            $table->string('address1')->nullable();
            $table->string('address2')->nullable();
            $table->string('customer_type', 20)->nullable();
            $table->string('profession', 30)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('purchase_order_image')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
