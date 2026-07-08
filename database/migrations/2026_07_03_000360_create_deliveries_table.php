<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('enquiry_id')->unique();
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
            $table->string('corporate_name')->nullable();
            $table->string('profession', 30)->nullable();
            $table->string('purchase_order_image')->nullable();
            $table->string('insurance_copy_1_image')->nullable();
            $table->string('insurance_copy_2_image')->nullable();
            $table->string('pan_certificate_image')->nullable();
            $table->string('tin_certificate_image')->nullable();
            $table->string('company_registration_certificate_1_image')->nullable();
            $table->string('company_registration_certificate_2_image')->nullable();
            $table->string('share_certificate_copy_1_image')->nullable();
            $table->string('share_certificate_copy_2_image')->nullable();
            $table->string('citizenship_certificate_1_image')->nullable();
            $table->string('citizenship_certificate_2_image')->nullable();
            $table->json('extra_images')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
