<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\FollowUpController;
use App\Http\Middleware\CheckRole;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminAccountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('employee_number')->nullable()->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('role');
            $table->integer('manager_id')->nullable();
            $table->text('permitted_districts')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('model');
        });
        Schema::create('head_of_sales_vehicle', function (Blueprint $table) {
            $table->integer('head_of_sales_id');
            $table->integer('vehicle_id');
        });
    }

    private function user(string $role, ?User $manager = null): User
    {
        return User::create([
            'name' => 'User '.(User::count() + 1),
            'email' => 'user'.(User::count() + 1).'@example.test',
            'password' => 'password123', 'role' => $role, 'manager_id' => $manager?->id,
        ]);
    }

    private function payload(User $head): array
    {
        return [
            'name' => 'Sales Admin', 'email' => 'admin@example.test', 'employee_number' => 'M12345',
            'manager_id' => $head->id, 'password' => 'password123', 'password_confirmation' => 'password123',
        ];
    }

    public function test_only_super_admin_can_create_admin_and_remains_signed_in(): void
    {
        $super = $this->user(User::ROLE_SUPER_ADMIN);
        $head = $this->user(User::ROLE_HEAD_OF_SALES);
        $this->actingAs($super)->get('/register/admin')->assertOk()
            ->assertSee('Create Admin Account')->assertSee('Assign Head Of Sales')->assertSee($head->email);
        $this->post('/register/admin', $this->payload($head))->assertRedirect(route('auth.register.form', 'admin'));
        $this->assertAuthenticatedAs($super);
        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertSame($head->id, $admin->manager_id);
        $this->assertTrue(Hash::check('password123', $admin->password));
    }

    public function test_guests_and_other_roles_cannot_create_admin_or_escalate_to_super_admin(): void
    {
        $this->get('/register/admin')->assertForbidden();
        $this->post('/register/admin', [])->assertForbidden();
        $head = $this->user(User::ROLE_HEAD_OF_SALES);
        foreach ([User::ROLE_HEAD_OF_SALES, User::ROLE_AREA_MANAGER, User::ROLE_SALES_CONSULTANT, User::ROLE_ADMIN] as $role) {
            $user = $this->user($role, $head);
            $this->actingAs($user)->get('/register/admin')->assertForbidden();
            $this->post('/register/admin', $this->payload($head))->assertForbidden();
            $this->post('/register/super-admin', $this->payload($head))->assertForbidden();
        }
        $this->assertDatabaseMissing('users', ['email' => 'admin@example.test']);
    }

    public function test_creation_requires_an_existing_head_of_sales(): void
    {
        $super = $this->user(User::ROLE_SUPER_ADMIN);
        $this->actingAs($super);
        foreach ([null, 999, $super->id] as $managerId) {
            $this->post('/register/admin', [...$this->payload($super), 'manager_id' => $managerId])
                ->assertSessionHasErrors('manager_id');
        }
        $this->assertDatabaseMissing('users', ['role' => User::ROLE_ADMIN]);
    }

    public function test_creating_other_team_accounts_preserves_current_login_and_form_token(): void
    {
        $super = $this->user(User::ROLE_SUPER_ADMIN);
        $head = $this->user(User::ROLE_HEAD_OF_SALES);
        $this->actingAs($super)->withSession(['_token' => 'existing-form-token'])
            ->post('/register/area-manager', $this->payload($head))
            ->assertRedirect(route('auth.register.form', 'area-manager'))
            ->assertSessionHas('_token', 'existing-form-token');
        $this->assertAuthenticatedAs($super);
        $this->assertDatabaseHas('users', ['email' => 'admin@example.test', 'role' => User::ROLE_AREA_MANAGER]);
    }

    public function test_head_of_sales_registers_area_managers_only_under_their_own_account(): void
    {
        $head = $this->user(User::ROLE_HEAD_OF_SALES);
        $otherHead = $this->user(User::ROLE_HEAD_OF_SALES);
        $this->actingAs($head)->get('/register/area-manager')->assertOk()
            ->assertDontSee('Assign Head Of Sales')
            ->assertDontSee('name="manager_id"', false)
            ->assertDontSee($otherHead->email);

        foreach ([false, true] as $tampered) {
            $payload = $this->payload($otherHead);
            $payload['email'] = $tampered ? 'second-area@example.test' : 'first-area@example.test';
            $payload['employee_number'] = $tampered ? 'M00002' : 'M00001';
            if (!$tampered) {
                unset($payload['manager_id']);
            }

            $this->post('/register/area-manager', $payload)
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('auth.register.form', 'area-manager'));
            $this->assertDatabaseHas('users', [
                'email' => $payload['email'],
                'role' => User::ROLE_AREA_MANAGER,
                'manager_id' => $head->id,
            ]);
            $this->assertAuthenticatedAs($head);
        }
    }

    public function test_area_manager_registers_consultants_only_under_their_own_account(): void
    {
        $head = $this->user(User::ROLE_HEAD_OF_SALES);
        $area = $this->user(User::ROLE_AREA_MANAGER, $head);
        $otherArea = $this->user(User::ROLE_AREA_MANAGER, $head);
        $this->actingAs($area)->get('/register/sales-consultant')->assertOk()
            ->assertDontSee('Assign Area Manager')
            ->assertDontSee('name="manager_id"', false)
            ->assertDontSee($otherArea->email);

        foreach ([false, true] as $tampered) {
            $payload = $this->payload($otherArea);
            $payload['email'] = $tampered ? 'second-consultant@example.test' : 'first-consultant@example.test';
            $payload['employee_number'] = $tampered ? 'M00002' : 'M00001';
            if (!$tampered) {
                unset($payload['manager_id']);
            }
            $this->post('/register/sales-consultant', $payload)
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('auth.register.form', 'sales-consultant'));
            $this->assertDatabaseHas('users', [
                'email' => $payload['email'],
                'role' => User::ROLE_SALES_CONSULTANT,
                'manager_id' => $area->id,
            ]);
            $this->assertAuthenticatedAs($area);
            $this->get('/register/sales-consultant')->assertOk()
                ->assertSee(User::ROLE_LABELS[User::ROLE_SALES_CONSULTANT].' Created Successfully')
                ->assertSee($payload['email'])->assertSee($payload['password']);
        }
    }

    public function test_registration_shows_credentials_once_for_each_role_without_switching_accounts(): void
    {
        $super = $this->user(User::ROLE_SUPER_ADMIN);
        $head = $this->user(User::ROLE_HEAD_OF_SALES);
        $area = $this->user(User::ROLE_AREA_MANAGER, $head);
        $this->actingAs($super);
        foreach (User::ROLE_SLUGS as $role => $slug) {
            $payload = $this->payload($head);
            $payload['email'] = $slug.'@example.test';
            $payload['employee_number'] = 'M'.str_pad((string) User::count(), 5, '0', STR_PAD_LEFT);
            $payload['manager_id'] = match (User::parentRoleFor($role)) {
                User::ROLE_SUPER_ADMIN => $super->id,
                User::ROLE_HEAD_OF_SALES => $head->id,
                User::ROLE_AREA_MANAGER => $area->id,
                default => null,
            };
            $this->post('/register/'.$slug, $payload)
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('auth.register.form', $slug));
            $this->assertAuthenticatedAs($super);
            $this->assertStringNotContainsString($payload['password'], session('created_account'));
            $this->get('/register/'.$slug)->assertOk()
                ->assertSee(User::ROLE_LABELS[$role].' Created Successfully')
                ->assertSee($payload['email'])->assertSee($payload['password'])
                ->assertSessionMissing('created_account');
            $this->get('/register/'.$slug)->assertOk()->assertDontSee('id="registrationSuccessDialog"', false);
        }
    }

    public function test_guest_registration_does_not_log_in_as_the_created_user(): void
    {
        $head = $this->user(User::ROLE_HEAD_OF_SALES);
        $this->post('/register/area-manager', $this->payload($head))
            ->assertRedirect(route('auth.register.form', 'area-manager'));
        $this->assertGuest();
        $this->get('/register/area-manager')->assertOk()->assertSee('Area Manager Created Successfully');
    }

    public function test_admin_inherits_only_assigned_team_and_vehicles(): void
    {
        $head = $this->user(User::ROLE_HEAD_OF_SALES);
        $area = $this->user(User::ROLE_AREA_MANAGER, $head);
        $consultant = $this->user(User::ROLE_SALES_CONSULTANT, $area);
        $admin = $this->user(User::ROLE_ADMIN, $head);
        $otherHead = $this->user(User::ROLE_HEAD_OF_SALES);
        $otherArea = $this->user(User::ROLE_AREA_MANAGER, $otherHead);
        $this->user(User::ROLE_SALES_CONSULTANT, $otherArea);
        $this->assertEqualsCanonicalizing([$head->id, $area->id, $consultant->id, $admin->id], $admin->accessibleUserIds());
        foreach ([DashboardController::class, EnquiryController::class, FollowUpController::class] as $controller) {
            $scope = new \ReflectionMethod($controller, 'resolveAccessibleUserIds');
            $this->assertSame($head->accessibleUserIds(), $scope->invoke(new $controller, $admin));
        }
        DB::table('vehicles')->insert([['id' => 1, 'model' => 'Assigned'], ['id' => 2, 'model' => 'Other']]);
        DB::table('head_of_sales_vehicle')->insert([
            ['head_of_sales_id' => $head->id, 'vehicle_id' => 1],
            ['head_of_sales_id' => $otherHead->id, 'vehicle_id' => 2],
        ]);
        $this->assertSame([1], Vehicle::visibleTo($admin)->pluck('id')->all());
        $head->update(['role' => User::ROLE_AREA_MANAGER]);
        $admin->refresh();
        $this->assertSame([], $admin->accessibleUserIds());
        $this->assertSame([], Vehicle::visibleTo($admin)->pluck('id')->all());
        $this->actingAs($admin)->get(route('dashboard.head_of_sales'))->assertForbidden();
    }

    public function test_admin_login_dashboard_and_role_permissions(): void
    {
        $head = $this->user(User::ROLE_HEAD_OF_SALES);
        $admin = $this->user(User::ROLE_ADMIN, $head);
        foreach (['auth.login.common.submit', 'auth.login.submit'] as $route) {
            $this->post('/logout');
            $this->withSession(['login_captcha_answer' => 'ABCDE'])
                ->post(route($route, $route === 'auth.login.submit' ? ['role' => 'admin'] : []), [
                    'email' => $admin->email, 'password' => 'password123', 'captcha_answer' => 'ABCDE',
                ])->assertRedirect(route('dashboard.home'));
            $this->assertAuthenticatedAs($admin);
        }
        $this->get(route('dashboard.home'))->assertRedirect(route('dashboard.head_of_sales'));
        $request = Request::create('/dashboard/head-of-sales');
        $request->setUserResolver(fn () => $admin);
        $response = (new CheckRole)->handle($request, fn () => response('Allowed'), User::ROLE_HEAD_OF_SALES);
        $this->assertSame(200, $response->getStatusCode());
        foreach (['dashboard.super_admin', 'vehicles.index', 'dashboard.area_manager', 'dashboard.sales_consultant'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
        $this->put(route('dashboard.super_admin.users.update', $admin), ['role' => User::ROLE_SUPER_ADMIN])->assertForbidden();
    }

    public function test_expired_forms_redirect_to_a_get_page_without_retrying_the_action(): void
    {
        \Illuminate\Support\Facades\Route::middleware('web')->post('/test-expired-form', function () {
            throw new \Illuminate\Session\TokenMismatchException;
        });
        $this->post('/test-expired-form')->assertRedirect(route('login'))->assertSessionHasErrors('session');
        $head = $this->user(User::ROLE_HEAD_OF_SALES);
        $admin = $this->user(User::ROLE_ADMIN, $head);
        $this->actingAs($admin)->post('/test-expired-form')
            ->assertRedirect(route('dashboard.home'))->assertSessionHasErrors('session');
        $this->postJson('/test-expired-form')->assertStatus(419);
    }
}
