<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('enquiries') && !Schema::hasColumn('enquiries', 'followup_attempted_type')) {
            Schema::table('enquiries', function (Blueprint $table): void {
                $table->string('followup_attempted_type', 30)->nullable()->after('followup_marked_at');
            });
        }

        if (Schema::hasTable('followup_attempts') && DB::table('followup_attempts')->count() === 0) {
            Schema::drop('followup_attempts');
        }

        if (!Schema::hasTable('followup_attempts')) {
            Schema::create('followup_attempts', function (Blueprint $table): void {
                $table->id();
                $table->integer('enquiry_id');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('follow_type', 30)->nullable();
                $table->string('followup_status', 20);
                $table->timestamp('attempted_at')->nullable()->index();
                $table->timestamps();

                $table->foreign('enquiry_id')->references('id')->on('enquiries')->cascadeOnDelete();
                $table->index(['enquiry_id', 'attempted_at']);
                $table->index(['follow_type', 'attempted_at']);
            });
        }

        if (Schema::hasTable('enquiries')
            && Schema::hasTable('followup_attempts')
            && DB::table('followup_attempts')->count() === 0) {
            DB::table('enquiries')
                ->whereNotNull('followup_marked_at')
                ->whereRaw("LOWER(COALESCE(followup_status, '')) IN (?, ?)", ['done', 'not_done'])
                ->orderBy('id')
                ->select(['id', 'user_id', 'follow_type', 'followup_status', 'followup_marked_at'])
                ->chunkById(500, function ($enquiries): void {
                    $now = now();
                    $rows = [];

                    foreach ($enquiries as $enquiry) {
                        $rows[] = [
                            'enquiry_id' => (int) $enquiry->id,
                            'user_id' => $enquiry->user_id ? (int) $enquiry->user_id : null,
                            'follow_type' => $enquiry->follow_type,
                            'followup_status' => $enquiry->followup_status,
                            'attempted_at' => $enquiry->followup_marked_at,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if (!empty($rows)) {
                        DB::table('followup_attempts')->insert($rows);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('followup_attempts');

        if (Schema::hasTable('enquiries') && Schema::hasColumn('enquiries', 'followup_attempted_type')) {
            Schema::table('enquiries', function (Blueprint $table): void {
                $table->dropColumn('followup_attempted_type');
            });
        }
    }
};
