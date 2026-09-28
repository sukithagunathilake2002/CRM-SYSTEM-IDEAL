<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LeadEditAccessTest extends TestCase
{
    public function test_management_roles_can_edit_only_their_own_leads(): void
    {
        foreach ([User::ROLE_ADMIN, User::ROLE_AREA_MANAGER, User::ROLE_HEAD_OF_SALES] as $role) {
            $user = (new User())->forceFill(['id' => 10, 'role' => $role]);
            $this->assertTrue((new Enquiry(['user_id' => 10]))->isEditableBy($user));
            $this->assertFalse((new Enquiry(['user_id' => 20]))->isEditableBy($user));
            $this->assertFalse((new Enquiry())->isEditableBy($user));
        }
        $this->assertFalse((new Enquiry())->isEditableBy(null));
    }

    public function test_web_and_api_reject_changes_to_other_users_leads_before_validation(): void
    {
        $controllers = [
            [\App\Http\Controllers\ProspectSheetController::class, 'store'],
            [\App\Http\Controllers\BookingController::class, 'store'],
            [\App\Http\Controllers\DeliveryController::class, 'store'],
            [\App\Http\Controllers\FollowUpController::class, 'updateStatus'],
            [\App\Http\Controllers\Api\ProspectController::class, 'store'],
            [\App\Http\Controllers\Api\BookingController::class, 'store'],
            [\App\Http\Controllers\Api\DeliveryController::class, 'store'],
            [\App\Http\Controllers\Api\FollowUpController::class, 'updateStatus'],
        ];
        foreach ([User::ROLE_ADMIN, User::ROLE_AREA_MANAGER, User::ROLE_HEAD_OF_SALES] as $role) {
            $user = (new User())->forceFill(['id' => 10, 'role' => $role]);
            $request = Request::create('/', 'POST', []);
            $request->setUserResolver(fn () => $user);
            foreach ($controllers as [$controller, $method]) {
                try {
                    app($controller)->$method($request, new Enquiry(['user_id' => 20]));
                    $this->fail("$role must not edit another user's lead using $controller.");
                } catch (HttpException $exception) {
                    $this->assertSame(403, $exception->getStatusCode());
                }
            }
        }
    }
}
