<?php

namespace Tests\Feature;

use App\Models\{Booking, Customer, Delivery, Enquiry, ProspectSheet, User, Vehicle};
use App\Support\ExchangeAssessment;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema, Validator};
use Tests\TestCase;

class ExchangeAssessmentTest extends TestCase
{
    public function test_exchange_details_save_and_carry_through_all_three_stages(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach ([new User(), new Customer(), new Vehicle(), new \App\Models\CompetitionVehicle(), new Enquiry(), new ProspectSheet(), new Booking(), new Delivery()] as $model) {
            Schema::create($model->getTable(), function (Blueprint $table) use ($model): void {
                $table->id();
                foreach ($model->getFillable() as $column) {
                    if (!in_array($column, ['id', 'created_at', 'updated_at'])) $table->text($column)->nullable();
                }
                $table->timestamps();
            });
        }
        $user = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'test', 'role' => User::ROLE_SUPER_ADMIN]);
        $customer = Customer::create(['name' => 'Customer', 'title' => 'Mr', 'mobile_numbers' => ['0771234567']]);
        $vehicle = Vehicle::create(['model' => 'KUV 100 NXT']);
        $enquiry = Enquiry::create(['user_id' => $user->id, 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'status' => 'OPEN']);
        ProspectSheet::create(['enquiry_id' => $enquiry->id, 'lead_status' => 'hot']);
        $assessment = [
            'inspection_date' => '2026-09-15', 'has_finance' => 'yes', 'finance_company' => 'Test Finance',
            'leased_value' => '200000', 'valuation' => '1500000', 'valued_by' => 'Valuer',
            'purchased_price' => '1400000', 'inspected_by' => 'Inspector',
            'items' => [['description' => 'Repair', 'amount' => '100.10'], ['description' => 'Cleaning', 'amount' => '200.20']],
            'total_price' => '999999',
        ];
        $base = ['interested_in_exchange' => 'yes', 'exchange_vehicle_model' => 'KUV 100 NXT K6 AMT', 'exchange_assessment' => $assessment];
        $this->actingAs($user)->post(route('prospect.store', $enquiry), $base + [
            'title' => 'Mr', 'name' => 'Customer', 'mobile_numbers' => '0771234567', 'customer_type' => 'individual',
            'profession' => 'other', 'active_step' => 3, 'exit_after_save' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame('300.30', $enquiry->fresh()->prospectSheet->exchange_assessment['total_price']);
        $this->actingAs($user)->post(route('booking.store', $enquiry), $base + ['booking_step' => 3, 'action_type' => 'save_exit'])->assertSessionHasNoErrors();
        $booking = $enquiry->fresh()->booking;
        $this->assertSame('300.30', $booking->exchange_assessment['total_price']);
        $this->assertSame('KUV 100 NXT K6 AMT', $booking->exchange_vehicle_model);
        $booking->update(['booking_completed_at' => now()]);
        $assessment['has_finance'] = 'no';
        $assessment['items'][1]['amount'] = '250.20';
        $base['exchange_assessment'] = $assessment;
        $this->actingAs($user)->post(route('delivery.store', $enquiry), $base + ['delivery_step' => 3, 'action_type' => 'save_exit'])->assertSessionHasNoErrors();
        foreach (['prospectSheet', 'booking', 'delivery'] as $relation) {
            $saved = $enquiry->fresh()->$relation->exchange_assessment;
            $this->assertSame('350.30', $saved['total_price']);
            $this->assertNull($saved['finance_company']);
            $this->assertNull($saved['leased_value']);
        }
        foreach (['prospect.show', 'booking.show', 'delivery.show'] as $route) {
            $this->actingAs($user)->get(route($route, ['enquiry' => $enquiry->id, 'step' => 3]))
                ->assertOk()->assertSee('Inspection Date')->assertSee('Inspected By');
        }
        $html = view('partials.exchange-assessment', ['exchangeRecord' => $enquiry->fresh()->delivery, 'exchangeFallback' => null, 'errors' => new \Illuminate\Support\ViewErrorBag()])->render();
        $this->assertStringContainsString('Inspection Date', $html);
        $this->assertStringContainsString('350.30', $html);
        $this->assertStringNotContainsString('Select Brand', $html);
        $this->assertStringNotContainsString('Insurance Validity', $html);
        $this->actingAs($user)->postJson('/api/booking/'.$enquiry->id, [
            'exchange_vehicle_model' => 'Unsupported vehicle',
        ])->assertUnprocessable()->assertJsonValidationErrors('exchange_vehicle_model');
        foreach (['prospect', 'booking', 'delivery'] as $stage) {
            // API clients use the same validated representation and computed total.
            $this->actingAs($user)->postJson('/api/'.$stage.'/'.$enquiry->id, $base + [
                'title' => 'Mr', 'name' => 'Customer', 'mobile_numbers' => '0771234567',
                'customer_type' => 'individual', 'profession' => 'other', 'lead_status' => 'hot',
                'action_type' => 'save_exit',
            ])->assertSuccessful();
            $this->assertSame('350.30', $enquiry->fresh()->prospectSheet->exchange_assessment['total_price']);
        }

    }

    public function test_finance_requires_company_and_leased_value_and_rows_require_both_fields(): void
    {
        $validator = Validator::make(['exchange_assessment' => ['has_finance' => 'yes', 'items' => [['description' => 'Repair']]]], ExchangeAssessment::rules());
        $this->assertTrue($validator->fails());
        foreach (['finance_company', 'leased_value', 'items.0.amount'] as $field) {
            $this->assertTrue($validator->errors()->has('exchange_assessment.'.$field));
        }
        $this->assertNull(ExchangeAssessment::resolve([], ['valuation' => '100'], 'no'));
        $this->assertSame(['valuation' => '100'], ExchangeAssessment::resolve([], ['valuation' => '100'], 'yes'));
    }
}
