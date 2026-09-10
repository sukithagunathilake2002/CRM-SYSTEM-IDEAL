<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use App\Models\User;
use App\Support\AllLeadsReport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AllLeadsReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 9, 8)->setTime(12, 0));
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role');
            $table->integer('manager_id')->nullable();
        });
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('model');
            $table->string('engine_type')->nullable();
            $table->string('variant')->nullable();
        });
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('district')->nullable();
            $table->text('mobile_numbers')->nullable();
        });
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id')->nullable();
            $table->integer('vehicle_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('exchange')->nullable();
            foreach (['selected_vehicle_models', 'lead_source', 'followup_test_drive_given', 'followup_first_time_buyer',
                'followup_result', 'followup_status', 'followup_lost_to', 'followup_lost_competition_brand', 'followup_lost_competition_model',
                'follow_type', 'follow_date', 'follow_time', 'status'] as $field) {
                $table->text($field)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('prospect_sheets', function (Blueprint $table) {
            $table->id();
            $table->integer('enquiry_id');
            foreach (['lead_status', 'test_drive_given', 'first_time_buyer', 'interested_in_exchange', 'interested_in_competition'] as $field) {
                $table->text($field)->nullable();
            }
        });
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'North Manager', 'role' => User::ROLE_AREA_MANAGER, 'manager_id' => null],
            ['id' => 2, 'name' => 'South Manager', 'role' => User::ROLE_AREA_MANAGER, 'manager_id' => null],
            ['id' => 3, 'name' => 'North Consultant', 'role' => User::ROLE_SALES_CONSULTANT, 'manager_id' => 1],
            ['id' => 4, 'name' => 'South Consultant', 'role' => User::ROLE_SALES_CONSULTANT, 'manager_id' => 2],
        ]);
        DB::table('vehicles')->insert(['id' => 1, 'model' => 'Pickup']);
        DB::table('customers')->insert(['id' => 1, 'name' => 'Test Customer', 'mobile_numbers' => '["0771234567"]']);
        for ($id = 1; $id <= 15; $id++) {
            DB::table('enquiries')->insert([
                'id' => $id, 'customer_id' => 1, 'vehicle_id' => 1, 'user_id' => $id === 1 ? 3 : 4,
                'exchange' => 0, 'created_at' => $id === 15 ? '2026-08-31 23:59:59' : '2026-09-01 00:00:00',
                'lead_source' => $id === 1 ? 'Referral' : 'Digital', 'status' => 'open',
            ]);
        }
        DB::table('enquiries')->where('id', 1)->update([
            'followup_result' => 'lost', 'followup_lost_to' => 'competitor',
            'followup_lost_competition_brand' => 'Brand', 'followup_lost_competition_model' => 'Other SUV',
            'selected_vehicle_models' => json_encode([['model' => 'SUV', 'label' => 'SUV Diesel']]),
        ]);
        DB::table('prospect_sheets')->insert([
            ['enquiry_id' => 1, 'test_drive_given' => 'yes', 'first_time_buyer' => 'yes', 'interested_in_exchange' => 'yes', 'interested_in_competition' => 'no'],
            ['enquiry_id' => 2, 'test_drive_given' => 'no', 'first_time_buyer' => 'no', 'interested_in_exchange' => 'no', 'interested_in_competition' => 'yes'],
        ]);
        $user = new User(['name' => 'Admin', 'role' => User::ROLE_SUPER_ADMIN]);
        $user->id = 99;
        $this->actingAs($user);
    }

    private function ids(array $input = []): array
    {
        $report = new AllLeadsReport;

        return $report->query($report->filters(Request::create('/report', 'GET', $input)))->orderBy('id')->pluck('id')->all();
    }

    public function test_each_filter_works_independently_and_all_can_be_combined(): void
    {
        $filters = [
            'area_managers' => [1], 'consultants' => [3], 'vehicle_models' => ['suv'], 'lead_sources' => ['referral'],
            'test_drive' => ['yes'], 'first_buyer' => ['yes'], 'interested_exchange' => ['yes'], 'competition' => ['yes'],
        ];
        foreach ($filters as $key => $values) {
            $this->assertSame([1], $this->ids([$key => $values]), $key);
        }
        $this->assertSame([1], $this->ids($filters));
        $this->assertSame([], $this->ids(['area_managers' => [1], 'consultants' => [4]]));
        $this->assertSame(range(1, 14), $this->ids(['area_managers' => [1, 2]]));
    }

    public function test_competition_means_lost_to_competitor_not_prospect_interest(): void
    {
        $this->assertSame([1], $this->ids(['competition' => ['yes']]));
        $this->assertSame(range(2, 14), $this->ids(['competition' => ['no']]));
        $this->assertSame(range(1, 14), $this->ids(['competition' => ['yes', 'no']]));
        $report = new AllLeadsReport;
        $rows = $report->query($report->filters(Request::create('/report')))->orderBy('id')->get();
        $this->assertSame('Yes', $report->row($rows[0])['competition']);
        $this->assertSame('No', $report->row($rows[1])['competition']);
    }

    public function test_missing_answers_are_not_no_and_fallback_answers_match_rows(): void
    {
        DB::table('enquiries')->where('id', 3)->update(['followup_test_drive_given' => 'yes', 'followup_first_time_buyer' => 'yes']);
        $this->assertSame([1, 3], $this->ids(['test_drive' => ['yes']]));
        $this->assertSame([2], $this->ids(['test_drive' => ['no']]));
        $report = new AllLeadsReport;
        $record = Enquiry::with(['user.manager', 'customer', 'vehicle', 'prospectSheet'])->findOrFail(3);
        $this->assertSame('Yes', $report->row($record)['test_drive']);
        $unknown = Enquiry::with(['user.manager', 'customer', 'vehicle', 'prospectSheet'])->findOrFail(4);
        $this->assertSame('Not recorded', $report->row($unknown)['test_drive']);
    }

    public function test_date_defaults_boundaries_and_long_range_fallback(): void
    {
        $this->assertSame(range(1, 14), $this->ids());
        $this->assertSame([15], $this->ids(['from_date' => '2026-08-31', 'to_date' => '2026-08-31']));
        $report = new AllLeadsReport;
        $filters = $report->filters(Request::create('/report?from_date=2025-01-01&to_date=2026-09-08'));
        $this->assertSame('2026-09-01', $filters['from_date']);
        $this->assertNotEmpty($filters['date_notice']);
        $this->getJson(route('dashboard.super_admin.leads', ['from_date' => '2026-09-08', 'to_date' => '2026-09-01']))->assertUnprocessable();
        $this->getJson(route('dashboard.super_admin.leads', ['from_date' => '2026-09-01']))->assertUnprocessable();
    }

    public function test_export_includes_date_or_reason_from_the_answer_source(): void
    {
        $report = new AllLeadsReport;
        $record = Enquiry::with(['user.manager', 'customer', 'vehicle', 'prospectSheet'])->findOrFail(1);
        $record->prospectSheet->test_drive_date = '2026-09-05';
        $record->prospectSheet->test_drive_not_given_reason = 'Stale reason';
        $row = $report->row($record);
        $this->assertSame('2026-09-05', $row['test_drive_date']);
        $this->assertSame('', $row['test_drive_not_given_reason']);
        $this->assertCount(count($report->headings()), $row);

        $record->prospectSheet->test_drive_given = 'no';
        $record->prospectSheet->test_drive_not_given_reason = 'Customer unavailable';
        $row = $report->row($record);
        $this->assertSame('', $row['test_drive_date']);
        $this->assertSame('Customer unavailable', $row['test_drive_not_given_reason']);

        $record->prospectSheet->test_drive_given = '';
        $record->followup_test_drive_given = 'yes';
        $record->followup_test_drive_when = '2026-09-06';
        $this->assertSame('2026-09-06', $report->row($record)['test_drive_date']);
        $record->followup_test_drive_given = 'no';
        $record->followup_test_drive_not_given_reason = 'Requested another day';
        $this->assertSame('Requested another day', $report->row($record)['test_drive_not_given_reason']);

        $record->followup_test_drive_given = null;
        $row = $report->row($record);
        $this->assertSame('', $row['test_drive_date']);
        $this->assertSame('', $row['test_drive_not_given_reason']);
        $csv = $this->get(route('dashboard.super_admin.leads.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Test Drive Given Date', $csv);
        $this->assertStringContainsString('Reason for Not Giving Test Drive', $csv);
    }

    public function test_initial_page_and_ajax_pagination_render_ten_records(): void
    {
        $response = $this->get(route('dashboard.super_admin.leads'))->assertOk();
        $this->assertSame(10, substr_count($response->getContent(), 'data-lead-id='));
        $response->assertSee('14</strong> matching leads', false)->assertSee('Download CSV')->assertSee('Select Filters');
        $response = $this->getJson(route('dashboard.super_admin.leads', ['page' => 2]), ['X-All-Leads-Partial' => '1'])->assertOk();
        $this->assertSame(4, substr_count($response->json('html'), 'data-lead-id='));
        $this->assertStringContainsString('from_date=2026-09-01', $response->json('url'));
    }

    public function test_csv_exports_all_matching_pages_and_neutralizes_formula_cells(): void
    {
        DB::table('customers')->where('id', 1)->update(['name' => '=SUM(1,1)']);
        $response = $this->get(route('dashboard.super_admin.leads.export'))->assertOk();
        $csv = $response->streamedContent();
        $this->assertSame(14, substr_count($csv, "'=SUM(1,1)"));
        $this->assertStringContainsString('Total Matching Leads",14', $csv);
        $this->assertStringContainsString('Lost to Competitor', $csv);
        $filtered = $this->get(route('dashboard.super_admin.leads.export', ['area_managers' => [1], 'competition' => ['yes']]))->assertOk()->streamedContent();
        $this->assertSame(1, substr_count($filtered, "'=SUM(1,1)"));
        $this->assertStringContainsString('North Manager', $filtered);
    }

    public function test_filter_options_include_hierarchy_and_snapshot_models(): void
    {
        $response = $this->getJson(route('dashboard.super_admin.leads.options'))->assertOk();
        $response->assertJsonFragment(['value' => '3', 'label' => 'North Consultant', 'area_manager' => '1']);
        $response->assertJsonFragment(['value' => 'suv', 'label' => 'SUV']);
        $response->assertJsonFragment(['value' => 'pickup', 'label' => 'Pickup']);
    }

    public function test_all_report_routes_require_super_admin_and_reject_invalid_roles(): void
    {
        $this->getJson(route('dashboard.super_admin.leads', ['area_managers' => [3]]))->assertUnprocessable();
        $viewer = new User(['role' => User::ROLE_SALES_CONSULTANT]);
        $viewer->id = 3;
        $this->actingAs($viewer);
        foreach (['dashboard.super_admin.leads', 'dashboard.super_admin.leads.options', 'dashboard.super_admin.leads.export'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
    }

    public function test_admin_report_options_and_export_are_limited_to_assigned_team(): void
    {
        DB::table('users')->insert([
            ['id' => 9, 'name' => 'Head', 'role' => User::ROLE_HEAD_OF_SALES, 'manager_id' => null],
            ['id' => 10, 'name' => 'Admin', 'role' => User::ROLE_ADMIN, 'manager_id' => 9],
        ]);
        DB::table('users')->where('id', 1)->update(['manager_id' => 9]);
        Schema::create('head_of_sales_vehicle', function (Blueprint $table) {
            $table->integer('head_of_sales_id');
            $table->integer('vehicle_id');
        });
        DB::table('head_of_sales_vehicle')->insert(['head_of_sales_id' => 9, 'vehicle_id' => 1]);
        $this->actingAs(User::findOrFail(10));
        $this->get(route('dashboard.admin.leads'))->assertOk()
            ->assertSee('1</strong> matching leads', false)
            ->assertSee(route('dashboard.admin.leads.export'), false)
            ->assertDontSee('South Consultant');
        $this->getJson(route('dashboard.admin.leads.options'))->assertOk()
            ->assertJsonFragment(['value' => '3', 'label' => 'North Consultant', 'area_manager' => '1'])
            ->assertJsonMissing(['label' => 'South Consultant'])
            ->assertJsonMissing(['value' => 'digital', 'label' => 'Digital']);
        $csv = $this->get(route('dashboard.admin.leads.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Total Matching Leads",1', $csv);
        $this->assertStringNotContainsString('South Consultant', $csv);
        $this->getJson(route('dashboard.admin.leads', ['consultants' => [4]]))->assertUnprocessable();
        $this->getJson(route('dashboard.admin.leads.export', ['area_managers' => [2]]))->assertUnprocessable();
        $this->get(route('dashboard.super_admin.leads'))->assertForbidden();
        DB::table('head_of_sales_vehicle')->delete();
        $this->get(route('dashboard.admin.leads'))->assertOk()->assertSee('No leads match these filters');
    }

    public function test_dashboard_overview_aggregates_only_the_assigned_team_and_permitted_vehicles(): void
    {
        DB::table('users')->insert([
            ['id' => 9, 'name' => 'Head', 'role' => User::ROLE_HEAD_OF_SALES, 'manager_id' => null],
            ['id' => 10, 'name' => 'Admin', 'role' => User::ROLE_ADMIN, 'manager_id' => 9],
        ]);
        DB::table('users')->where('id', 1)->update(['manager_id' => 9]);
        DB::table('customers')->where('id', 1)->update(['district' => ' COLOMBO ']);
        DB::table('enquiries')->where('id', 1)->update(['followup_result' => ' LOST ', 'followup_status' => ' DONE ']);
        Schema::create('head_of_sales_vehicle', function (Blueprint $table) {
            $table->integer('head_of_sales_id');
            $table->integer('vehicle_id');
        });
        DB::table('head_of_sales_vehicle')->insert(['head_of_sales_id' => 9, 'vehicle_id' => 1]);
        $admin = User::findOrFail(10);
        DB::enableQueryLog();
        $overview = (new \App\Support\HeadOfSalesOverview)->build($admin);
        $queries = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], '"enquiries"'));
        DB::disableQueryLog();
        $this->assertCount(1, $queries);
        $this->assertSame([
            'total_leads' => 1, 'active_leads' => 0, 'lost_leads' => 1, 'closed_leads' => 0,
            'pending_followups' => 0, 'done_followups' => 1,
        ], $overview['kpis']);
        $this->assertSame([['district' => 'Colombo', 'leads' => 1]], $overview['by_district']);
        $this->assertSame([['province' => 'Western', 'leads' => 1]], $overview['by_province']);
        DB::table('head_of_sales_vehicle')->delete();
        $empty = (new \App\Support\HeadOfSalesOverview)->build($admin);
        $this->assertSame(0, $empty['kpis']['total_leads']);
        $this->assertSame([], $empty['by_district']);
    }
}
