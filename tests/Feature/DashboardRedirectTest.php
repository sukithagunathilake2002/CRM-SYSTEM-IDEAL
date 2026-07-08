<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class DashboardRedirectTest extends TestCase
{
    public function test_dashboard_home_redirects_super_admin_to_super_admin_dashboard(): void
    {
        $user = new User([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
        $user->id = 1;

        $response = $this->actingAs($user)->get(route('dashboard.home'));

        $response->assertRedirect(route('dashboard.super_admin'));
    }

    public function test_super_admin_crm_dashboard_redirects_to_super_admin_dashboard(): void
    {
        $user = new User([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
        $user->id = 1;

        $response = $this->actingAs($user)->get(route('dashboard.main'));

        $response->assertRedirect(route('dashboard.super_admin'));
    }
}
