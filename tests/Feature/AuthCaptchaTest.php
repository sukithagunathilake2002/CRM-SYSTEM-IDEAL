<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthCaptchaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('employee_number', 50)->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone', 30)->nullable();
            $table->string('role', 40)->default(User::ROLE_SALES_CONSULTANT);
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->json('permitted_districts')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function test_common_login_page_displays_a_captcha_challenge(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('data:image/svg+xml;base64,', false);
        $response->assertSee('captcha_answer', false);
        $response->assertSessionHas('login_captcha_answer');
    }

    public function test_common_login_rejects_an_invalid_captcha_answer(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $response = $this
            ->withSession(['login_captcha_answer' => '9M4BP'])
            ->post(route('auth.login.common.submit'), [
                'email' => $user->email,
                'password' => 'password',
                'captcha_answer' => '9M4BQ',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('captcha_answer');
        $this->assertGuest();
    }

    public function test_common_login_accepts_a_valid_captcha_answer(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $response = $this
            ->withSession(['login_captcha_answer' => '9M4BP'])
            ->post(route('auth.login.common.submit'), [
                'email' => $user->email,
                'password' => 'password',
                'captcha_answer' => '9m4bp',
            ]);

        $response->assertRedirect(route('dashboard.home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_role_login_rejects_an_invalid_captcha_answer(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SALES_CONSULTANT,
        ]);

        $response = $this
            ->withSession(['login_captcha_answer' => 'H7K2D'])
            ->post(route('auth.login.submit', 'sales-consultant'), [
                'email' => $user->email,
                'password' => 'password',
                'captcha_answer' => 'H7K2E',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('captcha_answer');
        $this->assertGuest();
    }

    public function test_role_login_accepts_a_valid_captcha_answer(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SALES_CONSULTANT,
        ]);

        $response = $this
            ->withSession(['login_captcha_answer' => 'H7K2D'])
            ->post(route('auth.login.submit', 'sales-consultant'), [
                'email' => $user->email,
                'password' => 'password',
                'captcha_answer' => 'h7k2d',
            ]);

        $response->assertRedirect(route('dashboard.home'));
        $this->assertAuthenticatedAs($user);
    }
}
