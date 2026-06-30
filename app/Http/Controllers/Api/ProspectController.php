<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\ProspectSheet;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProspectController extends Controller
{
    public function show(Enquiry $enquiry)
    {
        $enquiry->load(['customer', 'vehicle']);
        $prospect = ProspectSheet::firstOrNew(['enquiry_id' => $enquiry->id]);

        return response()->json([
            'enquiry' => $enquiry,
            'prospect' => $prospect,
        ]);
    }

    public function store(Request $request, Enquiry $enquiry)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'mobile_numbers' => ['required', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'address1' => ['nullable', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'customer_type' => ['required', Rule::in(['individual', 'corporate'])],
            'corporate_name' => ['nullable', 'string', 'max:255'],
            'profession' => ['required', Rule::in(['salaried', 'self_employed', 'other', 'not_asked'])],
            'date_of_birth' => ['nullable', 'date'],
            'interested_model' => ['nullable', 'string', 'max:255'],
            'interested_engine' => ['nullable', 'string', 'max:255'],
            'interested_variant' => ['nullable', 'string', 'max:255'],
            'interested_vehicle_color' => ['nullable', 'string', 'max:50'],
            'lead_source' => ['nullable', Rule::in(['Walk-In', 'Tele-In', 'Activity', 'Digital', 'Referral', 'Press'])],
            'source_of_information' => ['nullable', 'string', 'max:255'],
            'quote_taken' => ['nullable', Rule::in(['yes', 'no'])],
            'quote_date' => ['nullable', 'date'],
            'test_drive_given' => ['nullable', Rule::in(['yes', 'no'])],
            'test_drive_date' => ['nullable', 'date'],
            'test_drive_vehicle_model' => ['nullable', 'string', 'max:255'],
            'test_drive_to_whom' => ['nullable', 'string', 'max:255'],
            'test_drive_not_given_reason' => ['nullable', 'string', 'max:255'],
            'purchase_mode' => ['nullable', Rule::in(['cash', 'finance'])],
            'interested_in_exchange' => ['nullable', Rule::in(['yes', 'no'])],
            'exchange_vehicle_brand' => ['nullable', 'string', 'max:255'],
            'exchange_vehicle_model' => ['nullable', 'string', 'max:255'],
            'exchange_manufacture_year' => ['nullable', 'integer', 'between:1950,2100'],
            'exchange_color' => ['nullable', 'string', 'max:255'],
            'exchange_mileage_km' => ['nullable', 'integer', 'min:0'],
            'exchange_registration_no' => ['nullable', 'string', 'max:50'],
            'exchange_expected_price' => ['nullable', 'numeric', 'min:0'],
            'exchange_quoted_price' => ['nullable', 'numeric', 'min:0'],
            'exchange_price_difference' => ['nullable', 'numeric'],
            'interested_in_competition' => ['nullable', Rule::in(['yes', 'no', 'not_asked'])],
            'competition_brand' => ['nullable', 'string', 'max:255'],
            'competition_model' => ['nullable', 'string', 'max:255'],
            'first_time_buyer' => ['nullable', Rule::in(['yes', 'no'])],
            'existing_vehicle_brand' => ['nullable', 'string', 'max:255'],
            'existing_vehicle_model' => ['nullable', 'string', 'max:255'],
            'existing_vehicle_year' => ['nullable', 'integer', 'between:1950,2100'],
            'reschedule_followup' => ['nullable', 'boolean'],
            'follow_type' => ['nullable', Rule::in(['Home Visit', 'Showroom Visit', 'Call'])],
            'follow_date' => ['nullable', 'date'],
            'follow_time' => ['nullable', 'date_format:H:i'],
            'lead_status' => ['nullable', Rule::in(['hot', 'warm', 'cold'])],
            'customer_remark' => ['nullable', 'string', 'max:1000'],
        ]);

        $customer = $enquiry->customer;

        $mobileNumbers = collect(explode(',', (string) $validated['mobile_numbers']))
            ->map(fn($mobile) => trim($mobile))
            ->filter()
            ->values()
            ->all();

        if (empty($mobileNumbers) && !empty($customer->mobile_numbers)) {
            $mobileNumbers = $customer->mobile_numbers;
        }

        $customer->update([
            'title' => $validated['title'],
            'name' => $validated['name'],
            'mobile_numbers' => $mobileNumbers,
            'district' => $validated['district'] ?? null,
            'location' => $validated['location'] ?? null,
            'state' => $validated['state'] ?? null,
            'address1' => $validated['address1'] ?? null,
            'address2' => $validated['address2'] ?? null,
        ]);

        if (isset($validated['lead_source'])) {
            $enquiry->lead_source = $validated['lead_source'];
            $enquiry->save();
        }

        if (isset($validated['purchase_mode'])) {
            $enquiry->finance = $validated['purchase_mode'] === 'finance' ? 1 : 0;
            $enquiry->save();
        }

        $rescheduleFollowup = ($validated['reschedule_followup'] ?? false) === true;
        if ($rescheduleFollowup && isset($validated['follow_type'])) {
            $enquiry->follow_type = $validated['follow_type'];
            $enquiry->follow_date = $validated['follow_date'];
            $enquiry->follow_time = $validated['follow_time'];
            $enquiry->followup_status = 'pending';
            $enquiry->followup_marked_at = null;
            $enquiry->save();
        }

        $prospect = ProspectSheet::updateOrCreate(
            ['enquiry_id' => $enquiry->id],
            [
                'customer_type' => $validated['customer_type'],
                'corporate_name' => $validated['customer_type'] === 'corporate' ? ($validated['corporate_name'] ?? null) : null,
                'profession' => $validated['profession'],
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'interested_vehicle_color' => $validated['interested_vehicle_color'] ?? null,
                'source_of_information' => $validated['source_of_information'] ?? null,
                'quote_taken' => $validated['quote_taken'] ?? null,
                'quote_date' => $validated['quote_date'] ?? null,
                'test_drive_given' => $validated['test_drive_given'] ?? null,
                'test_drive_date' => $validated['test_drive_date'] ?? null,
                'test_drive_vehicle_model' => $validated['test_drive_vehicle_model'] ?? null,
                'test_drive_to_whom' => $validated['test_drive_to_whom'] ?? null,
                'test_drive_not_given_reason' => $validated['test_drive_not_given_reason'] ?? null,
                'purchase_mode' => $validated['purchase_mode'] ?? null,
                'interested_in_exchange' => $validated['interested_in_exchange'] ?? null,
                'exchange_vehicle_brand' => $validated['exchange_vehicle_brand'] ?? null,
                'exchange_vehicle_model' => $validated['exchange_vehicle_model'] ?? null,
                'exchange_manufacture_year' => $validated['exchange_manufacture_year'] ?? null,
                'exchange_color' => $validated['exchange_color'] ?? null,
                'exchange_mileage_km' => $validated['exchange_mileage_km'] ?? null,
                'exchange_registration_no' => $validated['exchange_registration_no'] ?? null,
                'exchange_expected_price' => $validated['exchange_expected_price'] ?? null,
                'exchange_quoted_price' => $validated['exchange_quoted_price'] ?? null,
                'exchange_price_difference' => $validated['exchange_price_difference'] ?? null,
                'interested_in_competition' => $validated['interested_in_competition'] ?? null,
                'competition_brand' => $validated['competition_brand'] ?? null,
                'competition_model' => $validated['competition_model'] ?? null,
                'first_time_buyer' => $validated['first_time_buyer'] ?? null,
                'existing_vehicle_brand' => $validated['existing_vehicle_brand'] ?? null,
                'existing_vehicle_model' => $validated['existing_vehicle_model'] ?? null,
                'existing_vehicle_year' => $validated['existing_vehicle_year'] ?? null,
                'reschedule_followup' => $rescheduleFollowup,
                'lead_status' => $validated['lead_status'] ?? null,
                'customer_remark' => $validated['customer_remark'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Prospect sheet saved successfully',
            'prospect' => $prospect,
        ]);
    }
}