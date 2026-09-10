<?php

namespace Tests\Feature;

use App\Http\Controllers\DeliveryController;
use App\Models\Enquiry;
use App\Models\Delivery;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DeliveryReviewAccessTest extends TestCase
{
    public function test_manager_can_review_team_deliveries_without_vehicle_permissions(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('model');
        });
        Schema::create('competition_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('model');
        });

        $manager = \Mockery::mock(User::class)->makePartial();
        $manager->forceFill(['id' => 10, 'role' => User::ROLE_AREA_MANAGER]);
        $manager->shouldReceive('headOfSalesForVehiclePermissions')->andReturnNull();
        $this->actingAs($manager);
        $request = Request::create('/delivery/1?step=6');
        $request->setUserResolver(fn () => $manager);
        $this->app->instance('request', $request);

        $enquiry = \Mockery::mock(Enquiry::class)->makePartial();
        $enquiry->forceFill(['id' => 1, 'user_id' => 20, 'vehicle_id' => 1, 'status' => 'closed']);
        $enquiry->shouldReceive('load')->andReturnSelf();
        $enquiry->setRelation('user', new User(['manager_id' => 10]));
        $enquiry->setRelation('vehicle', new Vehicle(['model' => 'Review vehicle']));
        foreach (['customer', 'prospectSheet', 'booking'] as $relation) {
            $enquiry->setRelation($relation, null);
        }

        foreach ([Delivery::APPROVAL_PENDING, Delivery::APPROVAL_APPROVED, Delivery::APPROVAL_REJECTED] as $status) {
            $enquiry->setRelation('delivery', new Delivery(['approval_status' => $status]));
            $view = app(DeliveryController::class)->show($enquiry);
            $this->assertSame('delivery.show', $view->name());
            $this->assertSame(6, $view->getData()['currentStep']);
            $html = $view->with('errors', new \Illuminate\Support\ViewErrorBag())->render();
            $this->assertStringContainsString('Review only.', $html);
            $this->assertStringNotContainsString('>Deliver Now</button>', $html);
        }

        $enquiry->user->manager_id = 99;
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(0);
        try {
            app(DeliveryController::class)->show($enquiry);
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            throw $exception;
        }
    }

    public function test_area_manager_cannot_save_delivery_data(): void
    {
        $manager = new User(['role' => User::ROLE_AREA_MANAGER]);

        foreach (['save_exit', 'save_next', 'submit'] as $action) {
            $request = Request::create('/delivery/1', 'POST', [
                'action_type' => $action,
                'chassis_number' => 'UNAUTHORIZED-CHANGE',
            ]);
            $request->setUserResolver(fn () => $manager);

            try {
                app(DeliveryController::class)->store($request, new Enquiry());
                $this->fail('Area Manager delivery changes must be rejected.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
    }

    public function test_rejection_requires_a_nonblank_note(): void
    {
        $manager = new User(['role' => User::ROLE_AREA_MANAGER]);
        foreach ([null, '', '   ', str_repeat('a', 1001)] as $note) {
            $request = Request::create('/delivery-approvals/1/reject', 'POST', ['approval_note' => $note]);
            $request->setUserResolver(fn () => $manager);
            try {
                app(DeliveryController::class)->reject($request, new Delivery());
                $this->fail('Invalid rejection notes must be rejected before saving.');
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $this->assertArrayHasKey('approval_note', $exception->errors());
            }
        }
    }

    public function test_rejection_saves_the_note_for_the_managers_delivery(): void
    {
        $manager = new User(['role' => User::ROLE_AREA_MANAGER]);
        $manager->id = 10;
        $enquiry = new Enquiry();
        $enquiry->setRelation('user', new User(['manager_id' => 10]));
        $delivery = \Mockery::mock(Delivery::class)->makePartial();
        $delivery->approval_status = Delivery::APPROVAL_PENDING;
        $delivery->setRelation('enquiry', $enquiry);
        $delivery->shouldReceive('load')->once()->andReturnSelf();
        $delivery->shouldReceive('save')->once()->andReturnTrue();
        $request = Request::create('/delivery-approvals/1/reject', 'POST', ['approval_note' => '  Receipt is missing.  ']);
        $request->setUserResolver(fn () => $manager);

        $response = app(DeliveryController::class)->reject($request, $delivery);

        $this->assertSame(Delivery::APPROVAL_REJECTED, $delivery->approval_status);
        $this->assertSame('Receipt is missing.', $delivery->approval_note);
        $this->assertSame(10, $delivery->approved_by);
        $this->assertSame(route('delivery.approvals'), $response->getTargetUrl());
    }
}
