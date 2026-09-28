<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use App\Models\User;
use App\Support\WorkflowSubmission;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkflowSubmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
        });
        foreach (['bookings' => 'booking_completed_at', 'deliveries' => 'submitted_at'] as $name => $stamp) {
            Schema::create($name, function (Blueprint $table) use ($stamp) {
                $table->id();
                $table->unsignedBigInteger('enquiry_id')->unique();
                $table->timestamp($stamp)->nullable();
            });
        }
        DB::table('enquiries')->insert(['id' => 1, 'user_id' => 10]);
    }

    private function requestFor(array $data, int $userId = 10): Request
    {
        $request = Request::create('/api/booking/1', 'POST', $data);
        $user = (new User())->forceFill(['id' => $userId, 'role' => User::ROLE_SUPER_ADMIN]);
        $request->setUserResolver(fn () => $user);
        return $request;
    }

    public function test_completed_records_block_repeat_submission_and_require_explicit_edit(): void
    {
        foreach (['booking' => ['bookings', 'booking_completed_at'], 'delivery' => ['deliveries', 'submitted_at']] as $type => [$table, $stamp]) {
            DB::table($table)->insert(['enquiry_id' => 1, $stamp => '2026-09-01 10:00:00']);
            foreach ([['edit' => 1], ['action_type' => 'submit'], ['action_type' => 'save_exit'], ['action_type' => 'submit', 'edit' => 1]] as $data) {
                $response = WorkflowSubmission::save($this->requestFor($data), Enquiry::find(1), $type,
                    function () { $this->fail('Duplicate submission must not save.'); });
                $this->assertSame(409, $response->getStatusCode());
                $this->assertTrue($response->getData(true)['already_submitted']);
            }
            $response = WorkflowSubmission::save($this->requestFor(['action_type' => 'save_exit', 'edit' => 1]),
                Enquiry::find(1), $type, fn ($enquiry) => response()->json(['saved' => $enquiry->id]));
            $this->assertSame(1, $response->getData(true)['saved']);
            $this->assertSame('2026-09-01 10:00:00', DB::table($table)->value($stamp));
        }
    }

    public function test_even_super_admin_cannot_modify_another_creators_record(): void
    {
        foreach (['booking', 'delivery'] as $type) {
            try {
                WorkflowSubmission::save($this->requestFor(['edit' => 1, 'action_type' => 'save_exit'], 20),
                    Enquiry::find(1), $type, function () { $this->fail('Non-owner must not save.'); });
                $this->fail('Expected access denial.');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
    }

    public function test_first_submission_is_saved_and_second_is_blocked(): void
    {
        foreach (['booking' => ['bookings', 'booking_completed_at'], 'delivery' => ['deliveries', 'submitted_at']] as $type => [$table, $stamp]) {
            $save = function ($enquiry) use ($table, $stamp) {
                DB::table($table)->insert(['enquiry_id' => $enquiry->id, $stamp => now()]);
                return response()->json(['saved' => true]);
            };
            $request = $this->requestFor(['action_type' => 'submit']);
            $this->assertSame(200, WorkflowSubmission::save($request, Enquiry::find(1), $type, $save)->getStatusCode());
            $this->assertSame(409, WorkflowSubmission::save($request, Enquiry::find(1), $type, $save)->getStatusCode());
            $this->assertSame(1, DB::table($table)->count());
        }
    }
}
