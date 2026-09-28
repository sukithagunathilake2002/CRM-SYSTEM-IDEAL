<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GeographyFiltersTest extends TestCase
{
    public function test_filters_update_both_geographic_summaries(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach ([
            'users' => ['name', 'role', 'manager_id'],
            'customers' => ['district'],
            'vehicles' => ['model'],
            'prospect_sheets' => ['enquiry_id'],
            'bookings' => ['enquiry_id'],
            'enquiries' => ['user_id', 'customer_id', 'vehicle_id', 'status', 'followup_result', 'followup_lead_temperature', 'follow_type', 'followup_status', 'created_at'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns): void {
                $table->id();
                foreach ($columns as $column) $table->string($column)->nullable();
            });
        }
        DB::table('users')->insert(['id' => 1, 'name' => 'Consultant', 'role' => User::ROLE_SALES_CONSULTANT]);
        DB::table('customers')->insert([['id' => 1, 'district' => 'Colombo'], ['id' => 2, 'district' => 'Kandy']]);
        foreach ([1, 2] as $id) {
            DB::table('enquiries')->insert(['user_id' => 1, 'customer_id' => $id, 'status' => 'OPEN', 'created_at' => "2026-09-0$id 10:00:00", 'followup_lead_temperature' => 'hot', 'follow_type' => 'Call', 'followup_status' => 'pending']);
        }
        $viewer = (new User())->forceFill(['id' => 99, 'role' => User::ROLE_SUPER_ADMIN]);
        $method = new \ReflectionMethod(DashboardController::class, 'buildAnalytics');
        $build = fn (array $filters) => $method->invoke(new DashboardController(), $viewer, Request::create('/', 'GET', $filters), true);
        $all = $build([]);
        $this->assertSame(2, array_sum(array_column($all['by_district'], 'leads')));
        $filtered = $build(['district' => 'Colombo', 'from_date' => '2026-09-01', 'to_date' => '2026-09-01', 'owner_role' => User::ROLE_SALES_CONSULTANT, 'user_id' => '1', 'user_scope' => 'self', 'lead_result' => 'active', 'lead_temperature' => 'hot', 'follow_type' => 'Call', 'followup_status' => 'pending']);
        $this->assertSame([['district' => 'Colombo', 'leads' => 1]], $filtered['by_district']);
        $this->assertSame(1, array_sum(array_column($filtered['by_province'], 'leads')));
        $this->assertTrue($filtered['has_active_filters']);
        $this->assertSame([], $build(['lead_result' => 'lost'])['by_district']);
        DB::table('users')->insert(['id' => 2, 'name' => 'Manager', 'role' => User::ROLE_AREA_MANAGER]);
        DB::table('users')->where('id', 1)->update(['manager_id' => 2]);
        $hierarchy = $build(['owner_role' => User::ROLE_AREA_MANAGER, 'user_id' => '2', 'user_scope' => 'hierarchy']);
        $this->assertSame(2, array_sum(array_column($hierarchy['by_district'], 'leads')), 'Selecting a manager and hierarchy must include consultant leads.');
        $self = $build(['owner_role' => User::ROLE_AREA_MANAGER, 'user_id' => '2', 'user_scope' => 'self']);
        $this->assertSame([], $self['by_district']);
        Schema::create('head_of_sales_vehicle', function (Blueprint $table): void {
            $table->integer('head_of_sales_id');
            $table->integer('vehicle_id');
        });
        DB::table('vehicles')->insert(['id' => 1, 'model' => 'Pickup']);
        DB::table('head_of_sales_vehicle')->insert(['head_of_sales_id' => 3, 'vehicle_id' => 1]);
        DB::table('enquiries')->update(['vehicle_id' => 1]);
        DB::table('users')->insert([
            ['id' => 3, 'name' => 'Head', 'role' => User::ROLE_HEAD_OF_SALES, 'manager_id' => null],
            ['id' => 4, 'name' => 'Admin', 'role' => User::ROLE_ADMIN, 'manager_id' => 3],
            ['id' => 5, 'name' => 'Outside consultant', 'role' => User::ROLE_SALES_CONSULTANT, 'manager_id' => null],
        ]);
        DB::table('users')->where('id', 2)->update(['manager_id' => 3]);
        DB::table('enquiries')->insert(['user_id' => 5, 'customer_id' => 1, 'vehicle_id' => 1, 'status' => 'OPEN']);
        foreach ([3, 4] as $viewerId) {
            $scoped = $method->invoke(new DashboardController(), User::findOrFail($viewerId), Request::create('/', 'GET', [
                'owner_role' => User::ROLE_AREA_MANAGER, 'user_id' => '2', 'user_scope' => 'hierarchy',
            ]), true);
            $this->assertSame(2, array_sum(array_column($scoped['by_district'], 'leads')));
            $this->assertSame(2, array_sum(array_column($scoped['by_province'], 'leads')));
        }
        $html = view('dashboards.partials.geography-filters', ['analytics' => $filtered])->render();
        $this->assertStringContainsString('Apply Filters', $html);
        $this->assertStringContainsString('value="Colombo" selected', $html);
    }
}
