<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ActiveAnalyticsTest extends TestCase
{
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
