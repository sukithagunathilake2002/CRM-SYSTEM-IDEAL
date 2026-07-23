<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CompetitionVehicle;
use App\Models\Enquiry;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function show(Enquiry $enquiry)
    {
        $enquiry->load(['customer', 'vehicle', 'prospectSheet', 'booking', 'user']);

        $booking = $enquiry->booking ?: new Booking([
            'enquiry_id' => $enquiry->id,
        ]);

        $customer = $enquiry->customer;
        $prospect = $enquiry->prospectSheet;

        $mobileNumbers = collect($customer?->mobile_numbers ?? [])
            ->map(fn($mobile) => trim((string) $mobile))
            ->filter()
            ->values()
            ->all();

        $defaultMobileString = implode(', ', $mobileNumbers);
        $currentStep = (int) old('booking_step', request()->query('step', 1));
        $currentStep = max(1, min(5, $currentStep));
        $vehicleModels = Vehicle::query()
            ->select('model')
            ->distinct()
            ->orderBy('model')
            ->pluck('model');
        $competitionMap = CompetitionVehicle::query()
            ->orderBy('brand')
            ->orderBy('model')
            ->get()
            ->groupBy('brand')
            ->map(function ($items) {
                return $items->pluck('model')->unique()->values();
            });

        $viewData = [
            'enquiry' => $enquiry,
            'booking' => $booking,
            'customer' => $customer,
            'prospect' => $prospect,
            'vehicleModels' => $vehicleModels,
            'competitionMap' => $competitionMap,
            'currentStep' => $currentStep,
            'defaultValues' => [
                'title' => $booking->title ?: $customer?->title,
                'name' => $booking->name ?: $customer?->name,
                'contact_type' => $booking->contact_type ?: 'Mobile',
                'mobile_numbers' => $booking->mobile_numbers ?: $defaultMobileString,
                'district' => $booking->district ?: $customer?->district,
                'location' => $booking->location ?: $customer?->location,
                'state' => $booking->state ?: $customer?->state,
                'address1' => $booking->address1 ?: $customer?->address1,
                'address2' => $booking->address2 ?: $customer?->address2,
                'customer_type' => $booking->customer_type ?: $prospect?->customer_type,
                'profession' => $booking->profession ?: $prospect?->profession,
                'date_of_birth' => $booking->date_of_birth ?: $prospect?->date_of_birth,
                'interested_model' => $booking->interested_model ?: $enquiry->vehicle?->model,
                'interested_engine' => $booking->interested_engine ?: $enquiry->vehicle?->engine_type,
                'interested_variant' => $booking->interested_variant ?: $enquiry->vehicle?->variant,
                'interested_vehicle_color' => $booking->interested_vehicle_color ?: $prospect?->interested_vehicle_color,
                'quote_taken' => $booking->quote_taken ?: $prospect?->quote_taken,
                'quote_date' => $booking->quote_date ?: $prospect?->quote_date,
                'test_drive_given' => $booking->test_drive_given ?: $prospect?->test_drive_given,
                'test_drive_date' => $booking->test_drive_date ?: $prospect?->test_drive_date,
                'test_drive_vehicle_model' => $booking->test_drive_vehicle_model ?: $prospect?->test_drive_vehicle_model,
                'test_drive_to_whom' => $booking->test_drive_to_whom ?: $prospect?->test_drive_to_whom,
                'test_drive_not_given_reason' => $booking->test_drive_not_given_reason ?: $prospect?->test_drive_not_given_reason,
                'purchase_mode' => $booking->purchase_mode ?: $prospect?->purchase_mode,
                'finance_form' => $booking->finance_form,
                'interested_in_competition' => $booking->interested_in_competition ?: $prospect?->interested_in_competition,
                'competition_brand' => $booking->competition_brand ?: $prospect?->competition_brand,
                'competition_model' => $booking->competition_model ?: $prospect?->competition_model,
                'first_time_buyer' => $booking->first_time_buyer ?: $prospect?->first_time_buyer,
                'existing_vehicle_brand' => $booking->existing_vehicle_brand ?: $prospect?->existing_vehicle_brand,
                'existing_vehicle_model' => $booking->existing_vehicle_model ?: $prospect?->existing_vehicle_model,
                'existing_vehicle_year' => $booking->existing_vehicle_year ?: $prospect?->existing_vehicle_year,
                'interested_in_exchange' => $booking->interested_in_exchange ?: $prospect?->interested_in_exchange,
                'exchange_type' => $booking->exchange_type ?: 'in_house',
                'exchange_vehicle_brand' => $booking->exchange_vehicle_brand ?: $prospect?->exchange_vehicle_brand,
                'exchange_vehicle_model' => $booking->exchange_vehicle_model ?: $prospect?->exchange_vehicle_model,
                'exchange_manufacture_year' => $booking->exchange_manufacture_year ?: $prospect?->exchange_manufacture_year,
                'exchange_color' => $booking->exchange_color ?: $prospect?->exchange_color,
                'exchange_mileage_km' => $booking->exchange_mileage_km ?: $prospect?->exchange_mileage_km,
                'exchange_registration_no' => $booking->exchange_registration_no ?: $prospect?->exchange_registration_no,
                'exchange_expected_price' => $booking->exchange_expected_price ?? $prospect?->exchange_expected_price,
                'exchange_quoted_price' => $booking->exchange_quoted_price ?? $prospect?->exchange_quoted_price,
                'exchange_price_difference' => $booking->exchange_price_difference ?? $prospect?->exchange_price_difference,
                'offer_unit_price' => $booking->offer_unit_price ?? $prospect?->offer_unit_price,
                'offer_unit_price_discount' => $booking->offer_unit_price_discount ?? $prospect?->offer_unit_price_discount,
                'offer_unit_price_free' => (bool) (($booking->offer_unit_price_free ?? null) ?? $prospect?->offer_unit_price_free),
                'offer_vat_amount' => $booking->offer_vat_amount ?? $prospect?->offer_vat_amount,
                'offer_vat_discount' => $booking->offer_vat_discount ?? $prospect?->offer_vat_discount,
                'offer_vat_free' => (bool) (($booking->offer_vat_free ?? null) ?? $prospect?->offer_vat_free),
                'offer_total_cost' => $booking->offer_total_cost ?? $prospect?->offer_total_cost,
                'offer_total_discount' => $booking->offer_total_discount ?? $prospect?->offer_total_discount,
                'offer_final_price' => $booking->offer_final_price ?? $prospect?->offer_final_price,
            ],
            'sameAsCustomer' => old(
                'booking_same_as_customer',
                $booking->exists ? (string) ((int) $booking->booking_same_as_customer) : '1'
            ) === '1',
        ];

        return view('booking.show', $viewData);
    }

    public function store(Request $request, Enquiry $enquiry)
    {
        $enquiry->load(['customer', 'vehicle', 'prospectSheet', 'booking', 'user']);

        $booking = $enquiry->booking ?: new Booking([
            'enquiry_id' => $enquiry->id,
        ]);

        $validated = $request->validate([
            'booking_same_as_customer' => ['nullable', 'in:0,1'],
            'title' => ['nullable', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255', 'required_if:booking_same_as_customer,0'],
            'contact_type' => ['nullable', Rule::in(['Mobile', 'Home', 'Office'])],
            'mobile_numbers' => ['nullable', 'string', 'max:255', 'required_if:booking_same_as_customer,0'],
            'district' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'address1' => ['nullable', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'customer_type' => ['nullable', Rule::in(['individual', 'corporate'])],
            'profession' => ['nullable', Rule::in(['salaried', 'self_employed', 'other', 'not_asked'])],
            'date_of_birth' => ['nullable', 'date'],
            'interested_model' => ['nullable', 'string', 'max:255'],
            'interested_engine' => ['nullable', 'string', 'max:255'],
            'interested_variant' => ['nullable', 'string', 'max:255'],
            'interested_vehicle_color' => ['nullable', 'string', 'max:50'],
            'quote_taken' => ['nullable', Rule::in(['yes', 'no'])],
            'quote_date' => ['nullable', 'date'],
            'test_drive_given' => ['nullable', Rule::in(['yes', 'no'])],
            'test_drive_date' => ['nullable', 'date'],
            'test_drive_vehicle_model' => ['nullable', 'string', 'max:255'],
            'test_drive_to_whom' => ['nullable', 'string', 'max:255'],
            'test_drive_not_given_reason' => ['nullable', 'string', 'max:255'],
            'purchase_mode' => ['nullable', Rule::in(['cash', 'finance'])],
            'finance_form' => ['nullable', Rule::in(['in_house', 'self', 'other'])],
            'interested_in_competition' => ['nullable', Rule::in(['yes', 'no', 'not_asked'])],
            'competition_brand' => ['nullable', 'string', 'max:255'],
            'competition_model' => ['nullable', 'string', 'max:255'],
            'first_time_buyer' => ['nullable', Rule::in(['yes', 'no'])],
            'existing_vehicle_brand' => ['nullable', 'string', 'max:255'],
            'existing_vehicle_model' => ['nullable', 'string', 'max:255'],
            'existing_vehicle_year' => ['nullable', 'integer', 'between:1950,2100'],
            'interested_in_exchange' => ['nullable', Rule::in(['yes', 'no'])],
            'exchange_type' => ['nullable', Rule::in(['in_house', 'outhouse'])],
            'exchange_vehicle_brand' => ['nullable', 'string', 'max:255'],
            'exchange_vehicle_model' => ['nullable', 'string', 'max:255'],
            'exchange_manufacture_year' => ['nullable', 'integer', 'between:1950,2100'],
            'exchange_color' => ['nullable', 'string', 'max:255'],
            'exchange_mileage_km' => ['nullable', 'integer', 'min:0'],
            'exchange_registration_no' => ['nullable', 'string', 'max:50'],
            'exchange_expected_price' => ['nullable', 'numeric', 'min:0'],
            'exchange_quoted_price' => ['nullable', 'numeric', 'min:0'],
            'exchange_price_difference' => ['nullable', 'numeric'],
            'blue_book_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'lot_no_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'car_pic_1_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'car_pic_2_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'extra_exchange_images' => ['nullable', 'array'],
            'extra_exchange_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'offer_unit_price' => ['nullable', 'numeric', 'min:0'],
            'offer_unit_price_discount' => ['nullable', 'numeric', 'min:0'],
            'offer_unit_price_free' => ['nullable', 'in:0,1'],
            'offer_vat_amount' => ['nullable', 'numeric', 'min:0'],
            'offer_vat_discount' => ['nullable', 'numeric', 'min:0'],
            'offer_vat_free' => ['nullable', 'in:0,1'],
            'offer_total_cost' => ['nullable', 'numeric', 'min:0'],
            'offer_total_discount' => ['nullable', 'numeric', 'min:0'],
            'offer_final_price' => ['nullable', 'numeric', 'min:0'],
            'edit_offer_details' => ['nullable', 'in:0,1'],
            'purchase_order_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'booking_step' => ['nullable', 'integer', 'between:1,5'],
            'action_type' => ['nullable', Rule::in(['next', 'save_exit', 'save', 'submit'])],
        ]);

        $currentStep = (int) ($validated['booking_step'] ?? 1);
        $currentStep = max(1, min(5, $currentStep));
        $sameAsCustomer = ($validated['booking_same_as_customer'] ?? '1') === '1';
        $customer = $enquiry->customer;
        $prospect = $enquiry->prospectSheet;
        $actionType = $validated['action_type'] ?? 'next';

        $requiresPurchaseOrder = $currentStep === 1 && in_array($actionType, ['next', 'save_exit'], true);
        if ($requiresPurchaseOrder && !$request->hasFile('purchase_order_image') && empty($booking->purchase_order_image)) {
            return back()
                ->withErrors(['purchase_order_image' => 'Purchase Order image is required.'])
                ->withInput();
        }

        $mobileNumbers = collect($customer?->mobile_numbers ?? [])
            ->map(fn($mobile) => trim((string) $mobile))
            ->filter()
            ->values()
            ->all();

        $payload = [
            'booking_same_as_customer' => $sameAsCustomer,
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
            'profession' => $validated['profession'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'interested_model' => $validated['interested_model'] ?? null,
            'interested_engine' => $validated['interested_engine'] ?? null,
            'interested_variant' => $validated['interested_variant'] ?? null,
            'interested_vehicle_color' => $validated['interested_vehicle_color'] ?? null,
            'quote_taken' => $validated['quote_taken'] ?? null,
            'quote_date' => ($validated['quote_taken'] ?? null) === 'yes' ? ($validated['quote_date'] ?? null) : null,
            'test_drive_given' => $validated['test_drive_given'] ?? null,
            'test_drive_date' => null,
            'test_drive_vehicle_model' => null,
            'test_drive_to_whom' => null,
            'test_drive_not_given_reason' => null,
            'purchase_mode' => $validated['purchase_mode'] ?? null,
            'finance_form' => ($validated['purchase_mode'] ?? null) === 'finance' ? ($validated['finance_form'] ?? null) : null,
            'interested_in_competition' => $validated['interested_in_competition'] ?? null,
            'competition_brand' => null,
            'competition_model' => null,
            'first_time_buyer' => $validated['first_time_buyer'] ?? null,
            'existing_vehicle_brand' => null,
            'existing_vehicle_model' => null,
            'existing_vehicle_year' => null,
            'interested_in_exchange' => $validated['interested_in_exchange'] ?? null,
            'exchange_type' => null,
            'exchange_vehicle_brand' => null,
            'exchange_vehicle_model' => null,
            'exchange_manufacture_year' => null,
            'exchange_color' => null,
            'exchange_mileage_km' => null,
            'exchange_registration_no' => null,
            'exchange_expected_price' => null,
            'exchange_quoted_price' => null,
            'exchange_price_difference' => null,
            'blue_book_image' => $booking->blue_book_image ?: $prospect?->blue_book_image,
            'lot_no_image' => $booking->lot_no_image ?: $prospect?->lot_no_image,
            'car_pic_1_image' => $booking->car_pic_1_image ?: $prospect?->car_pic_1_image,
            'car_pic_2_image' => $booking->car_pic_2_image ?: $prospect?->car_pic_2_image,
            'exchange_extra_images' => is_array($booking->exchange_extra_images)
                ? $booking->exchange_extra_images
                : (is_array($prospect?->exchange_extra_images) ? $prospect->exchange_extra_images : []),
            'offer_unit_price' => null,
            'offer_unit_price_discount' => null,
            'offer_unit_price_free' => false,
            'offer_vat_amount' => null,
            'offer_vat_discount' => null,
            'offer_vat_free' => false,
            'offer_total_cost' => null,
            'offer_total_discount' => null,
            'offer_final_price' => null,
            'purchase_order_image' => $booking->purchase_order_image,
        ];

        if (($validated['test_drive_given'] ?? null) === 'yes') {
            $payload['test_drive_date'] = $validated['test_drive_date'] ?? null;
            $payload['test_drive_vehicle_model'] = $validated['test_drive_vehicle_model'] ?? null;
            $payload['test_drive_to_whom'] = $validated['test_drive_to_whom'] ?? null;
        }

        if (($validated['test_drive_given'] ?? null) === 'no') {
            $payload['test_drive_not_given_reason'] = $validated['test_drive_not_given_reason'] ?? null;
        }

        if (($validated['interested_in_competition'] ?? null) === 'yes') {
            $payload['competition_brand'] = $validated['competition_brand'] ?? null;
            $payload['competition_model'] = $validated['competition_model'] ?? null;
        }

        if (($validated['first_time_buyer'] ?? null) === 'no') {
            $payload['existing_vehicle_brand'] = $validated['existing_vehicle_brand'] ?? null;
            $payload['existing_vehicle_model'] = $validated['existing_vehicle_model'] ?? null;
            $payload['existing_vehicle_year'] = $validated['existing_vehicle_year'] ?? null;
        }

        if (($validated['interested_in_exchange'] ?? null) === 'yes') {
            $payload['exchange_type'] = $validated['exchange_type'] ?? 'in_house';
            $payload['exchange_vehicle_brand'] = $validated['exchange_vehicle_brand'] ?? null;
            $payload['exchange_vehicle_model'] = $validated['exchange_vehicle_model'] ?? null;
            $payload['exchange_manufacture_year'] = $validated['exchange_manufacture_year'] ?? null;
            $payload['exchange_color'] = $validated['exchange_color'] ?? null;
            $payload['exchange_mileage_km'] = $validated['exchange_mileage_km'] ?? null;
            $payload['exchange_registration_no'] = $validated['exchange_registration_no'] ?? null;
            $payload['exchange_expected_price'] = $validated['exchange_expected_price'] ?? null;
            $payload['exchange_quoted_price'] = $validated['exchange_quoted_price'] ?? null;

            if (
                array_key_exists('exchange_expected_price', $validated) &&
                array_key_exists('exchange_quoted_price', $validated) &&
                $validated['exchange_expected_price'] !== null &&
                $validated['exchange_quoted_price'] !== null
            ) {
                $payload['exchange_price_difference'] =
                    (float) $validated['exchange_expected_price'] - (float) $validated['exchange_quoted_price'];
            } else {
                $payload['exchange_price_difference'] = $validated['exchange_price_difference'] ?? null;
            }
        }

        $isEditingOffer = ($validated['edit_offer_details'] ?? '0') === '1';
        $offerSource = fn(string $field, $fallback = 0) => $booking->{$field} ?? $prospect?->{$field} ?? $fallback;

        $offerUnitPrice = (float) (
            $isEditingOffer
                ? ($validated['offer_unit_price'] ?? $offerSource('offer_unit_price'))
                : $offerSource('offer_unit_price')
        );
        $offerVatAmount = (float) (
            $isEditingOffer
                ? ($validated['offer_vat_amount'] ?? $offerSource('offer_vat_amount'))
                : $offerSource('offer_vat_amount')
        );
        $offerUnitPriceDiscount = (float) (
            $isEditingOffer
                ? ($validated['offer_unit_price_discount'] ?? $offerSource('offer_unit_price_discount'))
                : $offerSource('offer_unit_price_discount')
        );
        $offerVatDiscount = (float) (
            $isEditingOffer
                ? ($validated['offer_vat_discount'] ?? $offerSource('offer_vat_discount'))
                : $offerSource('offer_vat_discount')
        );
        $offerUnitPriceFree = $isEditingOffer
            ? (($validated['offer_unit_price_free'] ?? '0') === '1')
            : (bool) $offerSource('offer_unit_price_free', false);
        $offerVatFree = $isEditingOffer
            ? (($validated['offer_vat_free'] ?? '0') === '1')
            : (bool) $offerSource('offer_vat_free', false);

        $offerUnitPrice = max(0, $offerUnitPrice);
        $offerVatAmount = max(0, $offerVatAmount);
        $offerUnitPriceDiscount = max(0, $offerUnitPriceDiscount);
        $offerVatDiscount = max(0, $offerVatDiscount);

        if ($offerUnitPriceFree) {
            $offerUnitPriceDiscount = $offerUnitPrice;
        } else {
            $offerUnitPriceDiscount = min($offerUnitPriceDiscount, $offerUnitPrice);
        }

        if ($offerVatFree) {
            $offerVatDiscount = $offerVatAmount;
        } else {
            $offerVatDiscount = min($offerVatDiscount, $offerVatAmount);
        }

        $offerTotalCost = $offerUnitPrice + $offerVatAmount;
        $offerTotalDiscount = $offerUnitPriceDiscount + $offerVatDiscount;
        $offerFinalPrice = max(0, $offerTotalCost - $offerTotalDiscount);

        $payload['offer_unit_price'] = $offerUnitPrice;
        $payload['offer_unit_price_discount'] = $offerUnitPriceDiscount;
        $payload['offer_unit_price_free'] = $offerUnitPriceFree;
        $payload['offer_vat_amount'] = $offerVatAmount;
        $payload['offer_vat_discount'] = $offerVatDiscount;
        $payload['offer_vat_free'] = $offerVatFree;
        $payload['offer_total_cost'] = $offerTotalCost;
        $payload['offer_total_discount'] = $offerTotalDiscount;
        $payload['offer_final_price'] = $offerFinalPrice;

        if ($sameAsCustomer) {
            $payload['title'] = $customer?->title;
            $payload['name'] = $customer?->name;
            $payload['contact_type'] = 'Mobile';
            $payload['mobile_numbers'] = implode(', ', $mobileNumbers);
            $payload['district'] = $customer?->district;
            $payload['location'] = $customer?->location;
            $payload['state'] = $customer?->state;
            $payload['address1'] = $customer?->address1;
            $payload['address2'] = $customer?->address2;
            $payload['customer_type'] = $prospect?->customer_type;
            $payload['profession'] = $prospect?->profession;
            $payload['date_of_birth'] = $prospect?->date_of_birth;
        }

        if ($request->hasFile('purchase_order_image')) {
            $payload['purchase_order_image'] = $request->file('purchase_order_image')->store('booking/purchase-order', 'public');
        }

        if ($request->hasFile('blue_book_image')) {
            $payload['blue_book_image'] = $request->file('blue_book_image')->store('booking/exchange', 'public');
        }
        if ($request->hasFile('lot_no_image')) {
            $payload['lot_no_image'] = $request->file('lot_no_image')->store('booking/exchange', 'public');
        }
        if ($request->hasFile('car_pic_1_image')) {
            $payload['car_pic_1_image'] = $request->file('car_pic_1_image')->store('booking/exchange', 'public');
        }
        if ($request->hasFile('car_pic_2_image')) {
            $payload['car_pic_2_image'] = $request->file('car_pic_2_image')->store('booking/exchange', 'public');
        }
        if ($request->hasFile('extra_exchange_images')) {
            $extraImages = is_array($payload['exchange_extra_images']) ? $payload['exchange_extra_images'] : [];
            foreach ($request->file('extra_exchange_images') as $extraImageFile) {
                if ($extraImageFile) {
                    $extraImages[] = $extraImageFile->store('booking/exchange', 'public');
                }
            }
            $payload['exchange_extra_images'] = $extraImages;
        }

        Booking::updateOrCreate(
            ['enquiry_id' => $enquiry->id],
            $payload
        );

        if ($actionType === 'save_exit') {
            return redirect('/epr')->with('success', 'Booking details saved.');
        }

        if ($actionType === 'save') {
            return redirect()
                ->route('booking.show', ['enquiry' => $enquiry->id, 'step' => $currentStep])
                ->with('success', 'Booking details saved.');
        }

        if ($actionType === 'submit') {
            return redirect()
                ->route('booking.show', ['enquiry' => $enquiry->id, 'step' => 5])
                ->with('booking_submitted_popup', true)
                ->with('booking_submitted_message', 'Submitted successfully.');
        }

        $nextStep = min(5, $currentStep + 1);

        return redirect()
            ->route('booking.show', ['enquiry' => $enquiry->id, 'step' => $nextStep])
            ->with('success', $currentStep >= 5 ? 'Booking details saved successfully.' : 'Step saved successfully.');
    }
}
