<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Enquiry;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
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

        $enquiry->load(['customer', 'vehicle', 'prospectSheet', 'booking', 'user']);

        if ($enquiry->isTerminalLead()) {
            return response()->json([
                'message' => $enquiry->terminalLeadLabel() . ' lead is finalized. Booking is not available.',
                'terminal' => true,
                'terminal_result' => $enquiry->terminalLeadResult()
            ], 422);
        }

        if (!$enquiry->canOpenBooking()) {
            return response()->json([
                'message' => 'Please complete the Prospect Sheet before opening Booking.',
                'can_open' => false
            ], 422);
        }

        $booking = $enquiry->booking ?: new Booking([
            'enquiry_id' => $enquiry->id,
        ]);

        $customer = $enquiry->customer;
        $prospect = $enquiry->prospectSheet;
        $vehicle = $enquiry->vehicle;

        // Build default values
        $mobileNumbers = collect($customer?->mobile_numbers ?? [])
            ->map(fn($mobile) => trim((string) $mobile))
            ->filter()
            ->values()
            ->all();

        $defaultMobileString = implode(', ', $mobileNumbers);

        // Determine current step from request or booking completion
        $currentStep = (int) request()->query('step', 1);
        $currentStep = max(1, min(5, $currentStep));

        // Check if booking has custom values - use booking data if exists, otherwise customer data
        $hasCustomValues = $booking->exists && (
            $booking->name !== null ||
            $booking->title !== null ||
            $booking->mobile_numbers !== null ||
            $booking->email !== null ||
            $booking->district !== null ||
            $booking->location !== null ||
            $booking->state !== null ||
            $booking->address1 !== null ||
            $booking->address2 !== null ||
            $booking->customer_type !== null ||
            $booking->profession !== null ||
            $booking->date_of_birth !== null
        );

        // Default to false (read-only mode) if no custom values exist
        $isEditMode = $hasCustomValues;

        // Build default values - prioritize booking data over customer data
        $defaultValues = [
            // Personal Details - Use booking data if exists, otherwise customer data
            'title' => $booking->title ?? $customer?->title,
            'name' => $booking->name ?? $customer?->name,
            'contact_type' => $booking->contact_type ?? 'Mobile',
            'email' => $booking->email ?? $customer?->email,
            'mobile_numbers' => $booking->mobile_numbers ?? $defaultMobileString,
            'district' => $booking->district ?? $customer?->district,
            'location' => $booking->location ?? $customer?->location,
            'state' => $booking->state ?? $customer?->state,
            'address1' => $booking->address1 ?? $customer?->address1,
            'address2' => $booking->address2 ?? $customer?->address2,
            'customer_type' => $booking->customer_type ?? $prospect?->customer_type,
            'corporate_name' => $booking->corporate_name ?? $prospect?->corporate_name,
            'profession' => $booking->profession ?? $prospect?->profession,
            'date_of_birth' => $booking->date_of_birth ?? $prospect?->date_of_birth,
            
            // Buying Details
            'interested_model' => $booking->interested_model ?? $enquiry->vehicle?->model,
            'interested_engine' => $booking->interested_engine ?? $enquiry->vehicle?->engine_type,
            'interested_variant' => $booking->interested_variant ?? $enquiry->vehicle?->variant,
            'interested_vehicle_color' => $booking->interested_vehicle_color ?? $prospect?->interested_vehicle_color,
            'quote_taken' => $booking->quote_taken ?? $prospect?->quote_taken,
            'quote_date' => $booking->quote_date ?? $prospect?->quote_date,
            'test_drive_given' => $booking->test_drive_given ?? $prospect?->test_drive_given,
            'test_drive_date' => $booking->test_drive_date ?? $prospect?->test_drive_date,
            'test_drive_vehicle_model' => $booking->test_drive_vehicle_model ?? $prospect?->test_drive_vehicle_model,
            'test_drive_to_whom' => $booking->test_drive_to_whom ?? $prospect?->test_drive_to_whom,
            'test_drive_not_given_reason' => $booking->test_drive_not_given_reason ?? $prospect?->test_drive_not_given_reason,
            'purchase_mode' => $booking->purchase_mode ?? $prospect?->purchase_mode,
            'finance_form' => $booking->finance_form,
            'finance_bank' => $booking->finance_bank,
            'finance_other_details' => $booking->finance_other_details,
            'interested_in_competition' => $booking->interested_in_competition ?? $prospect?->interested_in_competition,
            'competition_brand' => $booking->competition_brand ?? $prospect?->competition_brand,
            'competition_model' => $booking->competition_model ?? $prospect?->competition_model,
            'competition_model_year' => $booking->competition_model_year,
            'first_time_buyer' => $booking->first_time_buyer ?? $prospect?->first_time_buyer,
            'existing_vehicle_brand' => $booking->existing_vehicle_brand ?? $prospect?->existing_vehicle_brand,
            'existing_vehicle_model' => $booking->existing_vehicle_model ?? $prospect?->existing_vehicle_model,
            'existing_vehicle_year' => $booking->existing_vehicle_year ?? $prospect?->existing_vehicle_year,
            
            // Exchange Details
            'interested_in_exchange' => $booking->interested_in_exchange ?? $prospect?->interested_in_exchange,
            'exchange_type' => $booking->exchange_type ?? 'in_house',
            'exchange_purchase_value' => $booking->exchange_purchase_value,
            'exchange_vehicle_brand' => $booking->exchange_vehicle_brand ?? $prospect?->exchange_vehicle_brand,
            'exchange_vehicle_model' => $booking->exchange_vehicle_model ?? $prospect?->exchange_vehicle_model,
            'exchange_manufacture_year' => $booking->exchange_manufacture_year ?? $prospect?->exchange_manufacture_year,
            'exchange_ownership' => $booking->exchange_ownership ?? $prospect?->exchange_ownership,
            'exchange_insurance_validity' => $booking->exchange_insurance_validity ?? $prospect?->exchange_insurance_validity,
            'exchange_color' => $booking->exchange_color ?? $prospect?->exchange_color,
            'exchange_mileage_km' => $booking->exchange_mileage_km ?? $prospect?->exchange_mileage_km,
            'exchange_registration_no' => $booking->exchange_registration_no ?? $prospect?->exchange_registration_no,
            'exchange_tyre_replacements' => $booking->exchange_tyre_replacements ?? [],
            'exchange_expected_price' => $booking->exchange_expected_price ?? $prospect?->exchange_expected_price,
            'exchange_quoted_price' => $booking->exchange_quoted_price ?? $prospect?->exchange_quoted_price,
            'exchange_price_difference' => $booking->exchange_price_difference ?? $prospect?->exchange_price_difference,
            
            // Offer Details
            'offer_unit_price' => $booking->offer_unit_price ?? $prospect?->offer_unit_price ?? $vehicle?->unit_price,
            'offer_unit_price_discount' => $booking->offer_unit_price_discount ?? $prospect?->offer_unit_price_discount ?? 0,
            'offer_unit_price_free' => (bool) (($booking->offer_unit_price_free ?? null) ?? $prospect?->offer_unit_price_free),
            'offer_vat_amount' => $booking->offer_vat_amount ?? $prospect?->offer_vat_amount ?? $vehicle?->vat_amount,
            'offer_vat_discount' => $booking->offer_vat_discount ?? $prospect?->offer_vat_discount ?? 0,
            'offer_vat_free' => (bool) (($booking->offer_vat_free ?? null) ?? $prospect?->offer_vat_free),
            'offer_total_cost' => $booking->offer_total_cost ?? $prospect?->offer_total_cost,
            'offer_total_discount' => $booking->offer_total_discount ?? $prospect?->offer_total_discount,
            'offer_final_price' => $booking->offer_final_price ?? $prospect?->offer_final_price,
            'offer_remark' => $booking->offer_remark ?? $prospect?->offer_remark,
            
            // Booking Form
            'expected_delivery_date' => $booking->expected_delivery_date,
            'booking_date' => $booking->booking_date,
            'amount_collected' => $booking->amount_collected ?? 0,
            'booking_receipts' => is_array($booking->booking_receipts) ? $booking->booking_receipts : [],
            'booking_completed_at' => $booking->booking_completed_at,
        ];

        return response()->json([
            'enquiry' => $enquiry,
            'booking' => $booking,
            'customer' => $customer,
            'prospect' => $prospect,
            'vehicle' => $vehicle,
            'current_step' => $currentStep,
            'is_edit_mode' => $isEditMode,
            'default_values' => $defaultValues,
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

        $enquiry->load(['customer', 'vehicle', 'prospectSheet', 'booking', 'user']);

        if ($enquiry->isTerminalLead()) {
            return response()->json([
                'message' => $enquiry->terminalLeadLabel() . ' lead is finalized. Booking is not available.',
                'terminal' => true,
                'terminal_result' => $enquiry->terminalLeadResult()
            ], 422);
        }

        if (!$enquiry->canOpenBooking()) {
            return response()->json([
                'message' => 'Please complete the Prospect Sheet before saving Booking.',
                'can_open' => false
            ], 422);
        }

        $booking = $enquiry->booking ?: new Booking([
            'enquiry_id' => $enquiry->id,
        ]);

        // Validation
        $validated = $request->validate([
            'is_edit_mode' => ['nullable', 'in:0,1'],
            'title' => ['nullable', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'contact_type' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile_numbers' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'address1' => ['nullable', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'customer_type' => ['nullable', 'string', 'max:50'],
            'corporate_name' => ['nullable', 'string', 'max:255'],
            'profession' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
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
            'finance_bank' => ['nullable', 'string', 'max:255'],
            'finance_other_details' => ['nullable', 'string', 'max:255'],
            'interested_in_competition' => ['nullable', 'string', 'max:20'],
            'competition_brand' => ['nullable', 'string', 'max:255'],
            'competition_model' => ['nullable', 'string', 'max:255'],
            'competition_model_year' => ['nullable', 'integer'],
            'first_time_buyer' => ['nullable', 'string', 'max:10'],
            'existing_vehicle_brand' => ['nullable', 'string', 'max:255'],
            'existing_vehicle_model' => ['nullable', 'string', 'max:255'],
            'existing_vehicle_year' => ['nullable', 'integer'],
            'interested_in_exchange' => ['nullable', 'string', 'max:10'],
            'exchange_type' => ['nullable', 'string', 'max:20'],
            'exchange_purchase_value' => ['nullable', 'numeric'],
            'exchange_vehicle_brand' => ['nullable', 'string', 'max:255'],
            'exchange_vehicle_model' => ['nullable', 'string', 'max:255'],
            'exchange_manufacture_year' => ['nullable', 'integer'],
            'exchange_ownership' => ['nullable', 'string', 'max:50'],
            'exchange_insurance_validity' => ['nullable', 'date'],
            'exchange_color' => ['nullable', 'string', 'max:255'],
            'exchange_mileage_km' => ['nullable', 'integer'],
            'exchange_registration_no' => ['nullable', 'string', 'max:50'],
            'exchange_tyre_replacements' => ['nullable', 'array'],
            'exchange_expected_price' => ['nullable', 'numeric'],
            'exchange_quoted_price' => ['nullable', 'numeric'],
            'exchange_price_difference' => ['nullable', 'numeric'],
            'offer_unit_price' => ['nullable', 'numeric'],
            'offer_unit_price_discount' => ['nullable', 'numeric'],
            'offer_unit_price_free' => ['nullable', 'in:0,1'],
            'offer_vat_amount' => ['nullable', 'numeric'],
            'offer_vat_discount' => ['nullable', 'numeric'],
            'offer_vat_free' => ['nullable', 'in:0,1'],
            'offer_total_cost' => ['nullable', 'numeric'],
            'offer_total_discount' => ['nullable', 'numeric'],
            'offer_final_price' => ['nullable', 'numeric'],
            'offer_remark' => ['nullable', 'string', 'max:1000'],
            'expected_delivery_date' => ['nullable', 'date'],
            'booking_date' => ['nullable', 'date'],
            'amount_collected' => ['nullable', 'numeric'],
            'booking_receipts' => ['nullable', 'array'],
            'purchase_order_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'blue_book_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'lot_no_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'car_pic_1_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'car_pic_2_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'exchange_extra_images' => ['nullable', 'array'],
            'exchange_extra_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'booking_step' => ['nullable', 'integer', 'between:1,5'],
            'action_type' => ['nullable', 'string', 'in:next,save_exit,save,submit,exit'],
        ]);

        $currentStep = (int) ($validated['booking_step'] ?? 1);
        $currentStep = max(1, min(5, $currentStep));
        $isEditMode = ($validated['is_edit_mode'] ?? '0') === '1';
        $customer = $enquiry->customer;
        $prospect = $enquiry->prospectSheet;
        $actionType = $validated['action_type'] ?? 'next';

        // Build the payload with ALL fields from the request
        $payload = [
            // Personal Details - Always use the request values (they will be null if not edited)
            'title' => $validated['title'] ?? null,
            'name' => $validated['name'] ?? null,
            'contact_type' => $validated['contact_type'] ?? 'Mobile',
            'email' => $validated['email'] ?? null,
            'mobile_numbers' => $validated['mobile_numbers'] ?? null,
            'district' => $validated['district'] ?? null,
            'location' => $validated['location'] ?? null,
            'state' => $validated['state'] ?? null,
            'address1' => $validated['address1'] ?? null,
            'address2' => $validated['address2'] ?? null,
            'customer_type' => $validated['customer_type'] ?? null,
            'corporate_name' => ($validated['customer_type'] ?? null) === 'corporate' ? ($validated['corporate_name'] ?? null) : null,
            'profession' => $validated['profession'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            
            // Buying Details
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
            'finance_bank' => null,
            'finance_other_details' => null,
            'interested_in_competition' => $validated['interested_in_competition'] ?? null,
            'competition_brand' => null,
            'competition_model' => null,
            'competition_model_year' => null,
            'first_time_buyer' => $validated['first_time_buyer'] ?? null,
            'existing_vehicle_brand' => null,
            'existing_vehicle_model' => null,
            'existing_vehicle_year' => null,
            
            // Exchange Details
            'interested_in_exchange' => $validated['interested_in_exchange'] ?? null,
            'exchange_type' => null,
            'exchange_purchase_value' => null,
            'exchange_vehicle_brand' => null,
            'exchange_vehicle_model' => null,
            'exchange_manufacture_year' => null,
            'exchange_ownership' => null,
            'exchange_insurance_validity' => null,
            'exchange_color' => null,
            'exchange_mileage_km' => null,
            'exchange_registration_no' => null,
            'exchange_tyre_replacements' => [],
            'exchange_expected_price' => null,
            'exchange_quoted_price' => null,
            'exchange_price_difference' => null,
            
            // Offer Details
            'offer_unit_price' => $validated['offer_unit_price'] ?? $booking->offer_unit_price,
            'offer_unit_price_discount' => $validated['offer_unit_price_discount'] ?? $booking->offer_unit_price_discount ?? 0,
            'offer_unit_price_free' => ($validated['offer_unit_price_free'] ?? '0') === '1',
            'offer_vat_amount' => $validated['offer_vat_amount'] ?? $booking->offer_vat_amount,
            'offer_vat_discount' => $validated['offer_vat_discount'] ?? $booking->offer_vat_discount ?? 0,
            'offer_vat_free' => ($validated['offer_vat_free'] ?? '0') === '1',
            'offer_total_cost' => $validated['offer_total_cost'] ?? $booking->offer_total_cost,
            'offer_total_discount' => $validated['offer_total_discount'] ?? $booking->offer_total_discount ?? 0,
            'offer_final_price' => $validated['offer_final_price'] ?? $booking->offer_final_price,
            'offer_remark' => $validated['offer_remark'] ?? $booking->offer_remark,
            
            // Booking Form
            'expected_delivery_date' => $validated['expected_delivery_date'] ?? $booking->expected_delivery_date,
            'booking_date' => $validated['booking_date'] ?? $booking->booking_date ?? now()->toDateString(),
            'booking_receipts' => $validated['booking_receipts'] ?? $booking->booking_receipts ?? [],
            'amount_collected' => $validated['amount_collected'] ?? $booking->amount_collected ?? 0,
            'purchase_order_image' => $booking->purchase_order_image,
            'blue_book_image' => $booking->blue_book_image,
            'lot_no_image' => $booking->lot_no_image,
            'car_pic_1_image' => $booking->car_pic_1_image,
            'car_pic_2_image' => $booking->car_pic_2_image,
            'exchange_extra_images' => is_array($booking->exchange_extra_images)
                ? $booking->exchange_extra_images
                : [],
        ];

        // Handle finance details
        if (($payload['purchase_mode'] ?? null) === 'finance') {
            if (in_array($payload['finance_form'] ?? null, ['in_house', 'self'], true)) {
                $payload['finance_bank'] = $validated['finance_bank'] ?? null;
            } elseif (($payload['finance_form'] ?? null) === 'other') {
                $payload['finance_other_details'] = $validated['finance_other_details'] ?? null;
            }
        }

        // Handle competition details
        if (($validated['interested_in_competition'] ?? null) === 'yes') {
            $payload['competition_brand'] = $validated['competition_brand'] ?? null;
            $payload['competition_model'] = $validated['competition_model'] ?? null;
            $payload['competition_model_year'] = $validated['competition_model_year'] ?? null;
        }

        // Handle first time buyer
        if (($validated['first_time_buyer'] ?? null) === 'no') {
            $payload['existing_vehicle_brand'] = $validated['existing_vehicle_brand'] ?? null;
            $payload['existing_vehicle_model'] = $validated['existing_vehicle_model'] ?? null;
            $payload['existing_vehicle_year'] = $validated['existing_vehicle_year'] ?? null;
        }

        // Handle exchange
        if (($validated['interested_in_exchange'] ?? null) === 'yes') {
            $payload['exchange_type'] = $validated['exchange_type'] ?? 'in_house';
            $payload['exchange_purchase_value'] = ($payload['exchange_type'] ?? null) === 'in_house' 
                ? ($validated['exchange_purchase_value'] ?? null) 
                : null;
            $payload['exchange_vehicle_brand'] = $validated['exchange_vehicle_brand'] ?? null;
            $payload['exchange_vehicle_model'] = $validated['exchange_vehicle_model'] ?? null;
            $payload['exchange_manufacture_year'] = $validated['exchange_manufacture_year'] ?? null;
            $payload['exchange_ownership'] = $validated['exchange_ownership'] ?? null;
            $payload['exchange_insurance_validity'] = $validated['exchange_insurance_validity'] ?? null;
            $payload['exchange_color'] = $validated['exchange_color'] ?? null;
            $payload['exchange_mileage_km'] = $validated['exchange_mileage_km'] ?? null;
            $payload['exchange_registration_no'] = $validated['exchange_registration_no'] ?? null;
            $payload['exchange_tyre_replacements'] = $validated['exchange_tyre_replacements'] ?? [];
            $payload['exchange_expected_price'] = $validated['exchange_expected_price'] ?? null;
            $payload['exchange_quoted_price'] = $validated['exchange_quoted_price'] ?? null;
            $payload['exchange_price_difference'] = $validated['exchange_price_difference'] ?? null;
        }

        if ($request->hasFile('purchase_order_image')) {
            $payload['purchase_order_image'] = $request->file('purchase_order_image')
                ->store('booking/purchase-order', 'public');
        }

        foreach (['blue_book_image', 'lot_no_image', 'car_pic_1_image', 'car_pic_2_image'] as $imageField) {
            if ($request->hasFile($imageField)) {
                $payload[$imageField] = $request->file($imageField)
                    ->store('booking/exchange', 'public');
            }
        }

        if ($request->hasFile('exchange_extra_images')) {
            $extraImages = $payload['exchange_extra_images'];
            foreach ($request->file('exchange_extra_images') as $extraImage) {
                if ($extraImage) {
                    $extraImages[] = $extraImage->store('booking/exchange', 'public');
                }
            }
            $payload['exchange_extra_images'] = $extraImages;
        }

        // Keep API submissions consistent with the web booking flow. Delivery
        // is unlocked only when this timestamp is set by a complete submit.
        $bookingComplete = $this->bookingPayloadIsComplete($payload);
        if ($actionType === 'submit') {
            $payload['booking_completed_at'] = $bookingComplete
                ? ($booking->booking_completed_at ?? now())
                : null;
        } elseif ($actionType === 'save_exit' && !$bookingComplete && $booking->booking_completed_at === null) {
            $payload['booking_completed_at'] = null;
        }

        // Save booking - this will store ALL fields, even null values
        $booking->fill($payload);
        $booking->save();

        // Reload the booking with fresh data from database
        $booking->refresh();

        // Get fresh customer data
        $customer = $enquiry->fresh()->customer;
        $prospect = $enquiry->fresh()->prospectSheet;
        $vehicle = $enquiry->fresh()->vehicle;

        // Build default values for response - prioritize booking data
        $mobileNumbers = collect($customer?->mobile_numbers ?? [])
            ->map(fn($mobile) => trim((string) $mobile))
            ->filter()
            ->values()
            ->all();
        $defaultMobileString = implode(', ', $mobileNumbers);

        $defaultValues = [
            // Personal Details - Use booking data if exists, otherwise customer data
            'title' => $booking->title ?? $customer?->title,
            'name' => $booking->name ?? $customer?->name,
            'contact_type' => $booking->contact_type ?? 'Mobile',
            'email' => $booking->email ?? $customer?->email,
            'mobile_numbers' => $booking->mobile_numbers ?? $defaultMobileString,
            'district' => $booking->district ?? $customer?->district,
            'location' => $booking->location ?? $customer?->location,
            'state' => $booking->state ?? $customer?->state,
            'address1' => $booking->address1 ?? $customer?->address1,
            'address2' => $booking->address2 ?? $customer?->address2,
            'customer_type' => $booking->customer_type ?? $prospect?->customer_type,
            'corporate_name' => $booking->corporate_name ?? $prospect?->corporate_name,
            'profession' => $booking->profession ?? $prospect?->profession,
            'date_of_birth' => $booking->date_of_birth ?? $prospect?->date_of_birth,
            
            // Buying Details
            'interested_model' => $booking->interested_model ?? $enquiry->vehicle?->model,
            'interested_engine' => $booking->interested_engine ?? $enquiry->vehicle?->engine_type,
            'interested_variant' => $booking->interested_variant ?? $enquiry->vehicle?->variant,
            'interested_vehicle_color' => $booking->interested_vehicle_color ?? $prospect?->interested_vehicle_color,
            'quote_taken' => $booking->quote_taken ?? $prospect?->quote_taken,
            'quote_date' => $booking->quote_date ?? $prospect?->quote_date,
            'test_drive_given' => $booking->test_drive_given ?? $prospect?->test_drive_given,
            'test_drive_date' => $booking->test_drive_date ?? $prospect?->test_drive_date,
            'test_drive_vehicle_model' => $booking->test_drive_vehicle_model ?? $prospect?->test_drive_vehicle_model,
            'test_drive_to_whom' => $booking->test_drive_to_whom ?? $prospect?->test_drive_to_whom,
            'test_drive_not_given_reason' => $booking->test_drive_not_given_reason ?? $prospect?->test_drive_not_given_reason,
            'purchase_mode' => $booking->purchase_mode ?? $prospect?->purchase_mode,
            'finance_form' => $booking->finance_form,
            'finance_bank' => $booking->finance_bank,
            'finance_other_details' => $booking->finance_other_details,
            'interested_in_competition' => $booking->interested_in_competition ?? $prospect?->interested_in_competition,
            'competition_brand' => $booking->competition_brand ?? $prospect?->competition_brand,
            'competition_model' => $booking->competition_model ?? $prospect?->competition_model,
            'competition_model_year' => $booking->competition_model_year,
            'first_time_buyer' => $booking->first_time_buyer ?? $prospect?->first_time_buyer,
            'existing_vehicle_brand' => $booking->existing_vehicle_brand ?? $prospect?->existing_vehicle_brand,
            'existing_vehicle_model' => $booking->existing_vehicle_model ?? $prospect?->existing_vehicle_model,
            'existing_vehicle_year' => $booking->existing_vehicle_year ?? $prospect?->existing_vehicle_year,
            
            // Exchange Details
            'interested_in_exchange' => $booking->interested_in_exchange ?? $prospect?->interested_in_exchange,
            'exchange_type' => $booking->exchange_type ?? 'in_house',
            'exchange_purchase_value' => $booking->exchange_purchase_value,
            'exchange_vehicle_brand' => $booking->exchange_vehicle_brand ?? $prospect?->exchange_vehicle_brand,
            'exchange_vehicle_model' => $booking->exchange_vehicle_model ?? $prospect?->exchange_vehicle_model,
            'exchange_manufacture_year' => $booking->exchange_manufacture_year ?? $prospect?->exchange_manufacture_year,
            'exchange_ownership' => $booking->exchange_ownership ?? $prospect?->exchange_ownership,
            'exchange_insurance_validity' => $booking->exchange_insurance_validity ?? $prospect?->exchange_insurance_validity,
            'exchange_color' => $booking->exchange_color ?? $prospect?->exchange_color,
            'exchange_mileage_km' => $booking->exchange_mileage_km ?? $prospect?->exchange_mileage_km,
            'exchange_registration_no' => $booking->exchange_registration_no ?? $prospect?->exchange_registration_no,
            'exchange_tyre_replacements' => $booking->exchange_tyre_replacements ?? [],
            'exchange_expected_price' => $booking->exchange_expected_price ?? $prospect?->exchange_expected_price,
            'exchange_quoted_price' => $booking->exchange_quoted_price ?? $prospect?->exchange_quoted_price,
            'exchange_price_difference' => $booking->exchange_price_difference ?? $prospect?->exchange_price_difference,
            
            // Offer Details
            'offer_unit_price' => $booking->offer_unit_price ?? $prospect?->offer_unit_price ?? $enquiry->vehicle?->unit_price,
            'offer_unit_price_discount' => $booking->offer_unit_price_discount ?? $prospect?->offer_unit_price_discount ?? 0,
            'offer_unit_price_free' => (bool) (($booking->offer_unit_price_free ?? null) ?? $prospect?->offer_unit_price_free),
            'offer_vat_amount' => $booking->offer_vat_amount ?? $prospect?->offer_vat_amount ?? $enquiry->vehicle?->vat_amount,
            'offer_vat_discount' => $booking->offer_vat_discount ?? $prospect?->offer_vat_discount ?? 0,
            'offer_vat_free' => (bool) (($booking->offer_vat_free ?? null) ?? $prospect?->offer_vat_free),
            'offer_total_cost' => $booking->offer_total_cost ?? $prospect?->offer_total_cost,
            'offer_total_discount' => $booking->offer_total_discount ?? $prospect?->offer_total_discount,
            'offer_final_price' => $booking->offer_final_price ?? $prospect?->offer_final_price,
            'offer_remark' => $booking->offer_remark ?? $prospect?->offer_remark,
            
            // Booking Form
            'expected_delivery_date' => $booking->expected_delivery_date,
            'booking_date' => $booking->booking_date,
            'amount_collected' => $booking->amount_collected ?? 0,
            'booking_receipts' => is_array($booking->booking_receipts) ? $booking->booking_receipts : [],
            'booking_completed_at' => $booking->booking_completed_at,
        ];

        if ($actionType === 'submit' && !$bookingComplete) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete all required booking fields before opening Delivery.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking saved successfully',
            'booking' => $booking,
            'current_step' => $currentStep,
            'is_edit_mode' => $isEditMode,
            'default_values' => $defaultValues,
        ]);
    }

    private function bookingPayloadIsComplete(array $payload): bool
    {
        $filled = fn(string $field): bool => trim((string) ($payload[$field] ?? '')) !== '';

        foreach ([
            'name',
            'mobile_numbers',
            'district',
            'interested_model',
            'interested_variant',
            'interested_vehicle_color',
            'expected_delivery_date',
            'booking_date',
        ] as $field) {
            if (!$filled($field)) {
                return false;
            }
        }

        if (!$filled('address1') && !$filled('location')) {
            return false;
        }

        if (($payload['customer_type'] ?? null) === 'corporate' && !$filled('corporate_name')) {
            return false;
        }

        if (($payload['purchase_mode'] ?? null) === 'finance') {
            if (!$filled('finance_form')) {
                return false;
            }

            if (in_array($payload['finance_form'] ?? null, ['in_house', 'self'], true) && !$filled('finance_bank')) {
                return false;
            }

            if (($payload['finance_form'] ?? null) === 'other' && !$filled('finance_other_details')) {
                return false;
            }
        }

        if (($payload['interested_in_exchange'] ?? null) === 'yes') {
            foreach ([
                'exchange_type',
                'exchange_vehicle_brand',
                'exchange_vehicle_model',
                'exchange_manufacture_year',
                'exchange_ownership',
                'exchange_insurance_validity',
                'exchange_color',
                'exchange_mileage_km',
                'exchange_registration_no',
                'exchange_expected_price',
                'exchange_quoted_price',
            ] as $field) {
                if (!$filled($field)) {
                    return false;
                }
            }

            if (($payload['exchange_type'] ?? null) === 'in_house' && !$filled('exchange_purchase_value')) {
                return false;
            }
        }

        return true;
    }
}
