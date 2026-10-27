<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\Enquiry;
use App\Models\ProspectSheet;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ActiveAnalyticsTest extends TestCase
{
    public function test_source_information_marks_other_without_saved_details(): void
    {
        $enquiry = new Enquiry();
        $enquiry->forceFill(['prospect_source_of_information' => 'Other']);
        $method = new \ReflectionMethod(DashboardController::class, 'sourceInformationAnalyticsLabel');
        $this->assertSame('Other (Details Not Provided)', $method->invoke(new DashboardController(), $enquiry));
    }

    public function test_source_information_includes_other_details_beyond_top_twelve_and_can_filter_them(): void
    {
        $rows = collect();
        for ($index = 1; $index <= 14; $index++) {
            $enquiry = new Enquiry();
            $enquiry->forceFill([
                'followup_result' => 'active',
                'created_at' => '2026-10-01',
                'prospect_source_of_information' => 'Other',
                'enquiry_source_of_information' => 'Custom reason ' . $index,
            ]);
            $rows->push($enquiry);
        }
        $method = new \ReflectionMethod(DashboardController::class, 'buildActiveAnalytics');
        $analytics = $method->invoke(new DashboardController(), $rows, collect());
        $tab = collect($analytics['tabs'])->firstWhere('key', 'source_information');
        $this->assertCount(14, $tab['rows']);
        $this->assertContains('Custom Reason 14', array_column($tab['rows'], 'label'));
        $this->assertNotContains('Other', array_column($tab['rows'], 'label'));
        $filtered = $method->invoke(new DashboardController(), $rows, collect(), null, ['source_information' => 'Custom Reason 14']);
        $this->assertSame(1, $filtered['total']);
    }

    public function test_month_chart_drills_into_weeks_and_dates_in_order(): void
    {
        $rows = collect(['2026-10-08', '2026-10-01', '2026-10-01', '2026-10-31', '2026-11-01'])
            ->map(function (string $date): Enquiry {
                $enquiry = new Enquiry();
                $enquiry->forceFill(['created_at' => $date]);
                return $enquiry;
            });
        $method = new \ReflectionMethod(DashboardController::class, 'formatActiveMonthRows');
        $months = $method->invoke(new DashboardController(), $rows, 5);
        $this->assertSame(['Oct 2026', 'Nov 2026'], array_column($months, 'label'));
        $this->assertSame(80.0, $months[0]['contribution']);
        $weeks = $months[0]['drilldown'];
        $this->assertSame(4, $weeks['total']);
        $this->assertSame(['Week 1', 'Week 2', 'Week 5'], array_column($weeks['rows'], 'label'));
        $this->assertSame(50.0, $weeks['rows'][0]['contribution']);
        $dates = $weeks['rows'][0]['drilldown'];
        $this->assertSame(2, $dates['total']);
        $this->assertSame([['label' => '2026-10-01', 'count' => 2, 'contribution' => 100.0]], $dates['rows']);
    }

    public function test_chart_selections_combine_with_registration_and_update_totals(): void
    {
        $owner = new User(['name' => 'Selected Consultant', 'role' => User::ROLE_SALES_CONSULTANT]);
        $owner->id = 10;
        $rows = collect();
        foreach ([['hot', 10, 'Model A'], ['warm', 11, 'Model A'], ['cold', 10, 'Model B'], [null, 10, 'Model A']] as [$status, $userId, $model]) {
            $enquiry = new Enquiry();
            $enquiry->forceFill(['prospect_lead_status' => $status, 'user_id' => $userId, 'vehicle_model' => $model, 'followup_result' => 'active', 'created_at' => '2026-10-01 10:00:00']);
            $rows->push($enquiry);
        }
        $method = new \ReflectionMethod(DashboardController::class, 'buildActiveAnalytics');
        $selections = ['sales_consultant' => 'Selected Consultant', 'model' => 'Model A'];
        $analytics = $method->invoke(new DashboardController(), $rows, collect([10 => $owner]), 'REGISTERED', $selections);
        $this->assertSame(1, $analytics['total']);
        $this->assertSame($selections, $analytics['selections']);
        $this->assertSame(1, $analytics['export_tabs'][0]['total_row']['count']);

        $analytics = $method->invoke(new DashboardController(), $rows, collect([10 => $owner]), 'REGISTERED', ['model' => 'Model A']);
        $this->assertSame(2, $analytics['total']);
        $analytics = $method->invoke(new DashboardController(), $rows, collect([10 => $owner]), 'REGISTERED', ['model' => 'Missing model']);
        $this->assertSame(0, $analytics['total']);
    }

    public function test_registration_selection_filters_all_active_breakdowns(): void
    {
        $method = new \ReflectionMethod(DashboardController::class, 'buildActiveAnalytics');
        $rows = collect();
        foreach ([['hot', 'Registered model', 'active'], [null, 'EPR model', 'active'], ['warm', 'Lost model', 'lost']] as [$status, $model, $result]) {
            $enquiry = new Enquiry();
            $enquiry->forceFill([
                'prospect_lead_status' => $status,
                'vehicle_model' => $model,
                'followup_result' => $result,
                'created_at' => '2026-10-01 10:00:00',
            ]);
            $rows->push($enquiry);
        }

        foreach (['REGISTERED' => 'Registered Model', 'EPR' => 'EPR Model'] as $selection => $model) {
            $analytics = $method->invoke(new DashboardController(), $rows, collect(), $selection);
            $this->assertSame(1, $analytics['total']);
            $this->assertSame($selection, $analytics['registration_filter']);
            $modelTab = collect($analytics['tabs'])->firstWhere('key', 'model');
            $this->assertSame($model, $modelTab['rows'][0]['label']);
            $this->assertSame(1, $modelTab['rows'][0]['count']);
            $this->assertEquals(100, $modelTab['rows'][0]['contribution']);
            $this->assertSame(1, $analytics['export_tabs'][0]['total_row']['count']);
        }
    }

    public function test_dashboard_registration_matches_prospect_registration_regardless_of_saved_step(): void
    {
        $method = new \ReflectionMethod(DashboardController::class, 'isRegisteredAnalyticsLead');
        $controller = new DashboardController();

        foreach (['hot', 'warm', 'cold', ' HOT ', null, '', 'pending'] as $status) {
            foreach ([null, 1, 4, 5] as $step) {
                $enquiry = new Enquiry();
                $enquiry->forceFill([
                    'prospect_lead_status' => $status,
                    'prospect_current_step' => $step,
                ]);
                $enquiry->setRelation('prospectSheet', new ProspectSheet(['lead_status' => $status]));

                $expected = in_array(strtolower(trim((string) $status)), ['hot', 'warm', 'cold'], true);
                $this->assertSame($expected, $enquiry->hasCompletedProspectSheet());
                $this->assertSame($expected, $method->invoke($controller, $enquiry));
            }
        }
    }

    public function test_open_leads_are_counted_and_filtered_without_a_followup_result(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('enquiries', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->nullable();
            $table->string('followup_result')->nullable();
        });

        $cases = [
            ['OPEN', null, 'active'],
            ['open', '', 'active'],
            ['OPEN', ' Active ', 'active'],
            [null, null, 'active'],
            ['OPEN', ' Lost ', 'lost'],
            ['OPEN', 'closed', 'closed'],
            ['CLOSED', 'active', 'closed'],
            ['LOST', null, 'lost'],
            ['CANCELLED', 'active', null],
            ['canceled', null, null],
        ];
        foreach ($cases as [$status, $result]) {
            DB::table('enquiries')->insert(['status' => $status, 'followup_result' => $result]);
        }

        $method = new \ReflectionMethod(DashboardController::class, 'analyticsLeadResultSql');
        $sql = $method->invoke(new DashboardController());
        $results = DB::table('enquiries')->selectRaw("$sql as result")->orderBy('id')->pluck('result')->all();
        $this->assertSame(array_column($cases, 2), $results);
        $this->assertSame([1, 2, 3, 4], DB::table('enquiries')->whereRaw("($sql) = ?", ['active'])->pluck('id')->all());
    }
}
