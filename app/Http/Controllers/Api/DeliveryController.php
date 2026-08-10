<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Delivery;
use App\Models\Enquiry;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliveryController extends Controller
{
    private function resolveAccessibleUserIds(User $viewer): array
    {
        if ($viewer->role === User::ROLE_SUPER_ADMIN) {
            return User::query()->pluck('id')->map(fn($id) => (int) $id)->values()->all();
        }

        $resolvedIds = [(int) $viewer->id];
        $frontier = [(int) $viewer->id];

        while (!empty($frontier)) {
            $childIds = User::query()
                ->whereIn('manager_id', $frontier)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->values()
                ->all();

            $next = array_values(array_diff($childIds, $resolvedIds));
            if (empty($next)) {
                break;
            }

            $resolvedIds = array_values(array_unique(array_merge($resolvedIds, $next)));
            $frontier = $next;
        }

        return $resolvedIds;
    }

    public function show(Enquiry $enquiry)
    {
        $viewer = request()->user();
        
        // Check access
        if ($viewer->role !== User::ROLE_SUPER_ADMIN) {
            $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);
            if (!in_array((int) $enquiry->user_id, $accessibleUserIds, true)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $enquiry->load(['customer', 'vehicle', 'prospectSheet', 'booking', 'delivery', 'user']);

        if ($enquiry->isTerminalLead()) {
            return response()->json([
                'message' => $enquiry->terminalLeadLabel() . ' lead is finalized. Delivery is not available.',
                'terminal' => true,
                'terminal_result' => $enquiry->terminalLeadResult()
            ], 422);
        }

        if (!$enquiry->hasCompletedProspectSheet()) {
            return response()->json([
                'message' => 'Please complete the Prospect Sheet before opening Delivery.',
                'can_open' => false
            ], 422);
        }

        if (!$enquiry->canOpenDelivery()) {
            return response()->json([
                'message' => 'Please complete Booking before opening Delivery.',
                'can_open' => false
            ], 422);
        }

        $delivery = $enquiry->delivery ?: new Delivery([
            'enquiry_id' => $enquiry->id,
        ]);

        $customer = $enquiry->customer;
        $prospect = $enquiry->prospectSheet;
        $booking = $enquiry->booking;
        $vehicle = $enquiry->vehicle;

        $currentStep = (int) request()->query('step', 1);
        $currentStep = max(1, min(3, $currentStep));

        return response()->json([
            'enquiry' => $enquiry,
            'delivery' => $delivery,
            'customer' => $customer,
            'prospect' => $prospect,
            'booking' => $booking,
            'vehicle' => $vehicle,
            'current_step' => $currentStep,
        ]);
    }

    public function store(Request $request, Enquiry $enquiry)
    {
        $viewer = $request->user();
        
        // Check access
        if ($viewer->role !== User::ROLE_SUPER_ADMIN) {
            $accessibleUserIds = $this->resolveAccessibleUserIds($viewer);
            if (!in_array((int) $enquiry->user_id, $accessibleUserIds, true)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $enquiry->load(['customer', 'vehicle', 'prospectSheet', 'booking', 'delivery', 'user']);

        if ($enquiry->isTerminalLead()) {
            return response()->json([
                'message' => $enquiry->terminalLeadLabel() . ' lead is finalized. Delivery is not available.',
                'terminal' => true,
                'terminal_result' => $enquiry->terminalLeadResult()
            ], 422);
        }

        if (!$enquiry->hasCompletedProspectSheet()) {
            return response()->json([
                'message' => 'Please complete the Prospect Sheet before saving Delivery.',
                'can_open' => false
            ], 422);
        }

        if (!$enquiry->canOpenDelivery()) {
            return response()->json([
                'message' => 'Please complete Booking before saving Delivery.',
                'can_open' => false
            ], 422);
        }

        $delivery = $enquiry->delivery ?: new Delivery([
            'enquiry_id' => $enquiry->id,
        ]);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'contact_type' => ['nullable', 'string', 'max:50'],
            'mobile_numbers' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'address1' => ['nullable', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'customer_type' => ['nullable', 'string', 'max:50'],
            'corporate_name' => ['nullable', 'string', 'max:255'],
            'profession' => ['nullable', 'string', 'max:50'],
            'interested_model' => ['nullable', 'string', 'max:255'],
            'interested_engine' => ['nullable', 'string', 'max:255'],
            'interested_variant' => ['nullable', 'string', 'max:255'],
            'interested_vehicle_color' => ['nullable', 'string', 'max:50'],
            'quote_taken' => ['nullable', 'string', 'max:10'],
            'quote_date' => ['nullable', 'date'],
            'test_drive_given' => ['nullable', 'string', 'max:10'],
            'test_drive_date' => ['nullable', 'date'],
            'test_drive_vehicle_model' => ['nullable', 'string', 'max:255'],
            'test_drive_to_whom' => ['nullable', 'string', 'max:255'],
            'test_drive_not_given_reason' => ['nullable', 'string', 'max:255'],
            'purchase_mode' => ['nullable', 'string', 'max:20'],
            'finance_form' => ['nullable', 'string', 'max:20'],
            'interested_in_competition' => ['nullable', 'string', 'max:20'],
            'competition_brand' => ['nullable', 'string', 'max:255'],
            'competition_model' => ['nullable', 'string', 'max:255'],
            'first_time_buyer' => ['nullable', 'string', 'max:10'],
            'existing_vehicle_brand' => ['nullable', 'string', 'max:255'],
            'existing_vehicle_model' => ['nullable', 'string', 'max:255'],
            'existing_vehicle_year' => ['nullable', 'integer'],
            'interested_in_exchange' => ['nullable', 'string', 'max:10'],
            'exchange_type' => ['nullable', 'string', 'max:20'],
            'exchange_vehicle_brand' => ['nullable', 'string', 'max:255'],
            'exchange_vehicle_model' => ['nullable', 'string', 'max:255'],
            'exchange_manufacture_year' => ['nullable', 'integer'],
            'exchange_color' => ['nullable', 'string', 'max:255'],
            'exchange_mileage_km' => ['nullable', 'integer'],
            'exchange_registration_no' => ['nullable', 'string', 'max:50'],
            'exchange_expected_price' => ['nullable', 'numeric'],
            'exchange_quoted_price' => ['nullable', 'numeric'],
            'exchange_price_difference' => ['nullable', 'numeric'],
            'payment_receipt_amount_booking' => ['nullable', 'numeric'],
            'payment_pre_delivery_amount' => ['nullable', 'numeric'],
            'payment_delivery_amount' => ['nullable', 'numeric'],
            'delivery_receipts' => ['nullable', 'array'],
            'reference_taken' => ['nullable', 'in:0,1'],
            'selecting_brand_reasons' => ['nullable', 'array'],
            'date_of_delivery' => ['nullable', 'date'],
            'chassis_number' => ['nullable', 'string', 'max:255'],
            'pending_commitments' => ['nullable', 'string', 'max:1000'],
            'payment_finance_provider' => ['nullable', 'string', 'max:255'],
            'payment_pending_reason' => ['nullable', 'string', 'max:255'],
            'payment_pending_amount' => ['nullable', 'numeric'],
            'payment_agent_name' => ['nullable', 'string', 'max:255'],
            'payment_agent_number' => ['nullable', 'string', 'max:50'],
            'payment_expected_date' => ['nullable', 'date'],
            'payment_credit_given_to_customer' => ['nullable', 'string', 'max:255'],
            'payment_credit_amount_pending' => ['nullable', 'numeric'],
            'payment_credit_permitted_by' => ['nullable', 'string', 'max:255'],
            'payment_credit_expected_date' => ['nullable', 'date'],
            'delivery_step' => ['nullable', 'integer', 'between:1,3'],
            'action_type' => ['nullable', 'string', 'in:save_exit,save_next,submit,exit'],
        ]);

        $currentStep = (int) ($validated['delivery_step'] ?? 1);
        $currentStep = max(1, min(3, $currentStep));
        $actionType = $validated['action_type'] ?? 'save_next';

        // Start with request data
        $payload = [
            'title' => $validated['title'] ?? null,
            'name' => $validated['name'] ?? null,
            'contact_type' => $validated['contact_type'] ?? 'Mobile',
            'mobile_numbers' => $validated['mobile_numbers'] ?? null,
            'district' => $validated['district'] ?? null,
            'location' => $validated['location'] ?? null,
            'state' => $validated['state'] ?? null,
            'address1' => $validated['address1'] ?? null,
            'address2' => $validated['address2'] ?? null,
            'customer_type' => $validated['customer_type'] ?? null,
            'corporate_name' => ($validated['customer_type'] ?? null) === 'corporate' ? ($validated['corporate_name'] ?? null) : null,
            'profession' => $validated['profession'] ?? null,
            'interested_model' => $validated['interested_model'] ?? null,
            'interested_engine' => $validated['interested_engine'] ?? null,
            'interested_variant' => $validated['interested_variant'] ?? null,
            'interested_vehicle_color' => $validated['interested_vehicle_color'] ?? null,
            'quote_taken' => $validated['quote_taken'] ?? null,
            'quote_date' => ($validated['quote_taken'] ?? null) === 'yes' ? ($validated['quote_date'] ?? null) : null,
            'test_drive_given' => $validated['test_drive_given'] ?? null,
            'test_drive_date' => ($validated['test_drive_given'] ?? null) === 'yes' ? ($validated['test_drive_date'] ?? null) : null,
            'test_drive_vehicle_model' => ($validated['test_drive_given'] ?? null) === 'yes' ? ($validated['test_drive_vehicle_model'] ?? null) : null,
            'test_drive_to_whom' => ($validated['test_drive_given'] ?? null) === 'yes' ? ($validated['test_drive_to_whom'] ?? null) : null,
            'test_drive_not_given_reason' => ($validated['test_drive_given'] ?? null) === 'no' ? ($validated['test_drive_not_given_reason'] ?? null) : null,
            'purchase_mode' => $validated['purchase_mode'] ?? null,
            'finance_form' => ($validated['purchase_mode'] ?? null) === 'finance' ? ($validated['finance_form'] ?? null) : null,
            'interested_in_competition' => $validated['interested_in_competition'] ?? null,
            'competition_brand' => ($validated['interested_in_competition'] ?? null) === 'yes' ? ($validated['competition_brand'] ?? null) : null,
            'competition_model' => ($validated['interested_in_competition'] ?? null) === 'yes' ? ($validated['competition_model'] ?? null) : null,
            'first_time_buyer' => $validated['first_time_buyer'] ?? null,
            'existing_vehicle_brand' => ($validated['first_time_buyer'] ?? null) === 'no' ? ($validated['existing_vehicle_brand'] ?? null) : null,
            'existing_vehicle_model' => ($validated['first_time_buyer'] ?? null) === 'no' ? ($validated['existing_vehicle_model'] ?? null) : null,
            'existing_vehicle_year' => ($validated['first_time_buyer'] ?? null) === 'no' ? ($validated['existing_vehicle_year'] ?? null) : null,
            'interested_in_exchange' => $validated['interested_in_exchange'] ?? null,
            'exchange_type' => ($validated['interested_in_exchange'] ?? null) === 'yes' ? ($validated['exchange_type'] ?? 'in_house') : null,
            'exchange_vehicle_brand' => ($validated['interested_in_exchange'] ?? null) === 'yes' ? ($validated['exchange_vehicle_brand'] ?? null) : null,
            'exchange_vehicle_model' => ($validated['interested_in_exchange'] ?? null) === 'yes' ? ($validated['exchange_vehicle_model'] ?? null) : null,
            'exchange_manufacture_year' => ($validated['interested_in_exchange'] ?? null) === 'yes' ? ($validated['exchange_manufacture_year'] ?? null) : null,
            'exchange_color' => ($validated['interested_in_exchange'] ?? null) === 'yes' ? ($validated['exchange_color'] ?? null) : null,
            'exchange_mileage_km' => ($validated['interested_in_exchange'] ?? null) === 'yes' ? ($validated['exchange_mileage_km'] ?? null) : null,
            'exchange_registration_no' => ($validated['interested_in_exchange'] ?? null) === 'yes' ? ($validated['exchange_registration_no'] ?? null) : null,
            'exchange_expected_price' => ($validated['interested_in_exchange'] ?? null) === 'yes' ? ($validated['exchange_expected_price'] ?? null) : null,
            'exchange_quoted_price' => ($validated['interested_in_exchange'] ?? null) === 'yes' ? ($validated['exchange_quoted_price'] ?? null) : null,
            'exchange_price_difference' => ($validated['interested_in_exchange'] ?? null) === 'yes' ? ($validated['exchange_price_difference'] ?? null) : null,
            'payment_receipt_amount_booking' => $validated['payment_receipt_amount_booking'] ?? null,
            'payment_pre_delivery_amount' => $validated['payment_pre_delivery_amount'] ?? null,
            'payment_delivery_amount' => $validated['payment_delivery_amount'] ?? null,
            'delivery_receipts' => $validated['delivery_receipts'] ?? [],
            'reference_taken' => ($validated['reference_taken'] ?? '0') === '1',
            'selecting_brand_reasons' => $validated['selecting_brand_reasons'] ?? [],
            'date_of_delivery' => $validated['date_of_delivery'] ?? null,
            'chassis_number' => $validated['chassis_number'] ?? null,
            'pending_commitments' => $validated['pending_commitments'] ?? null,
            'payment_finance_provider' => $validated['payment_finance_provider'] ?? null,
            'payment_pending_reason' => $validated['payment_pending_reason'] ?? null,
            'payment_pending_amount' => $validated['payment_pending_amount'] ?? null,
            'payment_agent_name' => $validated['payment_agent_name'] ?? null,
            'payment_agent_number' => $validated['payment_agent_number'] ?? null,
            'payment_expected_date' => $validated['payment_expected_date'] ?? null,
            'payment_credit_given_to_customer' => $validated['payment_credit_given_to_customer'] ?? null,
            'payment_credit_amount_pending' => $validated['payment_credit_amount_pending'] ?? null,
            'payment_credit_permitted_by' => $validated['payment_credit_permitted_by'] ?? null,
            'payment_credit_expected_date' => $validated['payment_credit_expected_date'] ?? null,
        ];

        $delivery->fill($payload);
        $delivery->save();

        return response()->json([
            'message' => 'Delivery saved successfully',
            'delivery' => $delivery,
            'current_step' => $currentStep,
        ]);
    }
}