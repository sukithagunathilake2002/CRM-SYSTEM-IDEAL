<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lead_transfer_requests')) {
            return;
        }

        Schema::create('lead_transfer_requests', function (Blueprint $table): void {
            $table->id();
            $table->integer('enquiry_id')->index();
            $table->unsignedBigInteger('from_user_id')->index();
            $table->unsignedBigInteger('to_user_id')->index();
            $table->unsignedBigInteger('area_manager_id')->index();
            $table->unsignedBigInteger('requested_by')->index();
            $table->unsignedBigInteger('decided_by')->nullable()->index();
            $table->string('status', 20)->default('pending')->index();
            $table->text('reason');
            $table->text('decision_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_transfer_requests');
    }
};
