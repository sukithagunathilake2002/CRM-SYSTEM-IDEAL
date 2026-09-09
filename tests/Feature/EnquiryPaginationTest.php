<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use App\Models\User;
use App\Support\EnquiryListing;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EnquiryPaginationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->integer('vehicle_id')->nullable();
            foreach (['selected_vehicle_models', 'lead_source', 'source_of_information', 'follow_type', 'follow_date', 'follow_time', 'followup_status', 'followup_result', 'status'] as $column) $table->text($column)->nullable();
            $table->integer('exchange')->default(0);
            $table->timestamps();
        });
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->text('title')->nullable();
            $table->text('mobile_numbers')->nullable();
        });
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->text('model');
            $table->text('engine_type')->nullable();
            $table->text('variant')->nullable();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->text('role');
        });
        foreach (['prospect_sheets', 'bookings', 'deliveries'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->integer('enquiry_id');
                $table->text('lead_status')->nullable();
                $table->text('approval_status')->nullable();
            });
        }
        DB::table('customers')->insert(['id' => 1, 'name' => 'Test Customer', 'mobile_numbers' => '["077 123-4567"]']);
        DB::table('vehicles')->insert(['id' => 1, 'model' => 'Pickup']);
        DB::table('users')->insert(['id' => 1, 'name' => 'Owner', 'role' => User::ROLE_SALES_CONSULTANT]);
        for ($id = 1; $id <= 25; $id++) {
            DB::table('enquiries')->insert([
                'id' => $id, 'customer_id' => 1, 'vehicle_id' => 1, 'user_id' => 1,
                'follow_date' => '2026-01-01', 'follow_time' => '10:00:00', 'follow_type' => 'Call',
                'lead_source' => $id === 1 ? 'Referral' : 'Digital',
                'created_at' => '2026-01-01 12:00:00', 'status' => 'open',
            ]);
        }
    }

    public function test_database_pages_have_ten_records_and_stable_order(): void
    {
        $listing = new EnquiryListing();
        $first = $listing->paginate(Enquiry::query(), Request::create('/epr?view=all'));
        $this->assertCount(10, $first);
        $this->assertSame(25, $first->total());
        $this->assertSame(range(25, 16), $first->pluck('id')->all());
        $last = $listing->paginate(Enquiry::query(), Request::create('/epr?view=all&page=3'));
        $this->assertSame([5, 4, 3, 2, 1], $last->pluck('id')->all());
        $this->assertStringContainsString('view=all', $last->url(1));
        $outOfRange = $listing->paginate(Enquiry::query(), Request::create('/epr?page=999'));
        $this->assertSame(3, $outOfRange->currentPage());
        $this->assertCount(5, $outOfRange);
    }

    public function test_search_and_filters_find_records_outside_the_first_page(): void
    {
        DB::table('enquiries')->where('id', 1)->update(['selected_vehicle_models' => json_encode([['model' => 'Unique SUV', 'label' => 'Unique SUV Diesel']])]);
        $listing = new EnquiryListing();
        $filtered = $listing->paginate(Enquiry::query(), Request::create('/epr', 'GET', [
            'q' => 'Test Customer', 'leadSource' => ['referral'], 'model' => ['unique suv'],
            'dueFrom' => '2026-01-01', 'dueTo' => '2026-01-01', 'role' => [User::ROLE_SALES_CONSULTANT],
        ]));
        $this->assertSame([1], $filtered->pluck('id')->all());
        $phone = $listing->paginate(Enquiry::query(), Request::create('/epr?q=0771234567'));
        $this->assertSame(25, $phone->total());
        $empty = $listing->paginate(Enquiry::query(), Request::create('/epr?q=missingcustomer'));
        $this->assertSame(0, $empty->total());
        $oldest = $listing->paginate(Enquiry::query(), Request::create('/epr?sort=oldest'));
        $this->assertSame(range(1, 10), $oldest->pluck('id')->all());
    }

    public function test_filters_and_options_preserve_the_existing_visibility_scope(): void
    {
        DB::table('enquiries')->where('id', 1)->update(['user_id' => 2, 'lead_source' => 'Private Source']);
        $listing = new EnquiryListing();
        $filtered = $listing->paginate(Enquiry::where('user_id', 1), Request::create('/epr?q=Private'));
        $this->assertSame(0, $filtered->total());
        $options = $listing->options(Enquiry::where('user_id', 1));
        $this->assertSame(['digital'], array_column($options['leadSource'], 'value'));
    }

    public function test_list_routes_render_only_ten_cards_in_partial_responses(): void
    {
        $viewer = new User(['role' => User::ROLE_SUPER_ADMIN]);
        $viewer->id = 99;
        $this->actingAs($viewer);
        foreach (['/epr?view=all' => 'Call', '/epr/call' => 'Call', '/epr/showroom' => 'Showroom Visit', '/epr/home' => 'Home Visit'] as $url => $type) {
            DB::table('enquiries')->update(['follow_type' => $type]);
            $response = $this->getJson($url, ['X-Enquiry-Partial' => '1'])->assertOk();
            $this->assertSame(10, substr_count($response->json('html'), 'class="epr-card"'));
            $this->assertStringContainsString('of 25 enquiries', $response->json('html'));
            $this->assertStringContainsString('page=2', $response->json('html'));
        }
    }

    public function test_initial_html_load_is_paginated_without_javascript(): void
    {
        $viewer = new User(['role' => User::ROLE_SUPER_ADMIN]);
        $viewer->id = 99;
        $response = $this->actingAs($viewer)->get('/epr?view=all')->assertOk();
        $this->assertSame(10, substr_count($response->getContent(), 'class="epr-card"'));
        $response->assertSee('page=2', false);
        $response->assertSee('of 25 enquiries');
    }

    public function test_dashboard_district_counts_use_one_grouped_query_and_preserve_filters(): void
    {
        Schema::table('customers', fn (Blueprint $table) => $table->text('district')->nullable());
        DB::table('customers')->where('id', 1)->update(['district' => ' COLOMBO ']);
        DB::table('enquiries')->where('id', 1)->update(['followup_status' => 'done']);
        DB::table('enquiries')->where('id', 2)->update(['status' => 'lost']);
        DB::table('prospect_sheets')->insert(['enquiry_id' => 3, 'lead_status' => 'hot']);
        $viewer = new User(['role' => User::ROLE_SUPER_ADMIN]);
        $viewer->id = 99;
        DB::enableQueryLog();
        $method = new \ReflectionMethod(\App\Http\Controllers\DashboardController::class, 'getDistrictEpData');
        $data = $method->invoke(new \App\Http\Controllers\DashboardController, $viewer);
        $queries = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], '"enquiries"'));
        DB::disableQueryLog();
        $this->assertCount(1, $queries);
        $this->assertSame(22, $data['district_counts']['Colombo']);
        $this->assertSame(0, $data['district_counts']['Galle']);
        $this->assertSame(22, $data['total_active_eprs']);
        $this->assertCount(25, $data['map_data']);
    }

    public function test_dashboard_followup_counts_preserve_due_and_status_filters(): void
    {
        DB::table('enquiries')->where('id', 1)->update(['follow_type' => 'Showroom Visit']);
        DB::table('enquiries')->where('id', 2)->update(['follow_type' => 'Home Visit']);
        DB::table('enquiries')->where('id', 3)->update(['followup_status' => 'done']);
        DB::table('enquiries')->where('id', 4)->update(['follow_date' => '2099-01-01']);
        $viewer = new User(['role' => User::ROLE_SUPER_ADMIN]);
        $viewer->id = 99;
        $method = new \ReflectionMethod(\App\Http\Controllers\DashboardController::class, 'getDashboardEpData');
        $data = $method->invoke(new \App\Http\Controllers\DashboardController, $viewer);
        $this->assertSame(21, $data['call_count']);
        $this->assertSame(1, $data['showroom_count']);
        $this->assertSame(1, $data['home_count']);
        $this->assertSame(24, $data['total_count']);
        $this->assertCount(10, $data['call_epds']);
    }
}
