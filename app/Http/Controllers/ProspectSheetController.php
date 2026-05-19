<?php

namespace App\Http\Controllers;

use App\Models\CompetitionVehicle;
use App\Models\Enquiry;
use App\Models\ProspectSheet;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProspectSheetController extends Controller
{
    public function show(Enquiry $enquiry)
    {
        $enquiry->load(['customer', 'vehicle']);

        $prospect = ProspectSheet::firstOrNew([
            'enquiry_id' => $enquiry->id,
        ]);

        $competitionMap = CompetitionVehicle::query()
            ->orderBy('brand')
            ->orderBy('model')
            ->get()
            ->groupBy('brand')
            ->map(function ($items) {
                return $items->pluck('model')->unique()->values();
            });

        $vehicleModels = Vehicle::query()
            ->select('model')
            ->distinct()
            ->orderBy('model')
            ->pluck('model');

        $sourceInfoMap = [
            'Walk-In' => ['Showroom Visit', 'Road Show', 'Display', 'Existing Customer', 'Other'],
            'Tele-In' => ['Call Center', 'Hotline', 'Inbound Call', 'Missed Call', 'Other'],
            'Activity' => ['Event', 'Mall Display', 'Corporate Visit', 'Canvasing', 'Other'],
            'Digital' => ['Facebook', 'Instagram', 'Google', 'Website', 'YouTube', 'TikTok', 'Other'],
            'Referral' => ['Customer Referral', 'Employee Referral', 'Dealer Referral', 'Friends/Family', 'Other'],
            'Press' => ['Newspaper', 'Magazine', 'Radio', 'TV', 'Other'],
        ];

        $initialStep = (int) request()->query('step', 1);
        $initialStep = max(1, min(5, $initialStep));

        return view('prospect.show', compact(
            'enquiry',
            'prospect',
            'competitionMap',
            'vehicleModels',
            'sourceInfoMap',
            'initialStep'
        ));
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
            'corporate_name' => ['nullable', 'string', 'max:255', 'required_if:customer_type,corporate'],
            'profession' => ['required', Rule::in(['salaried', 'self_employed', 'other', 'not_asked'])],
            'date_of_birth' => ['nullable', 'date'],
            'edit_interested_vehicle' => ['nullable', 'in:1'],
            'interested_model' => ['nullable', 'string', 'max:255', 'required_if:edit_interested_vehicle,1'],
            'interested_engine' => ['nullable', 'string', 'max:255', 'required_if:edit_interested_vehicle,1'],
            'interested_variant' => ['nullable', 'string', 'max:255', 'required_if:edit_interested_vehicle,1'],
            'interested_vehicle_color' => ['nullable', 'string', 'max:50'],
            'lead_source' => ['nullable', Rule::in(['Walk-In', 'Tele-In', 'Activity', 'Digital', 'Referral', 'Press'])],
            'source_of_information' => ['nullable', 'string', 'max:255'],

            'quote_taken' => ['nullable', Rule::in(['yes', 'no'])],
            'quote_date' => ['nullable', 'date', 'required_if:quote_taken,yes'],

            'test_drive_given' => ['nullable', Rule::in(['yes', 'no'])],
            'test_drive_date' => ['nullable', 'date', 'required_if:test_drive_given,yes'],
            'test_drive_vehicle_model' => ['nullable', 'string', 'max:255', 'required_if:test_drive_given,yes'],
            'test_drive_to_whom' => ['nullable', 'string', 'max:255', 'required_if:test_drive_given,yes'],
            'test_drive_not_given_reason' => ['nullable', 'string', 'max:255', 'required_if:test_drive_given,no'],
            'purchase_mode' => ['nullable', Rule::in(['cash', 'finance'])],

            'interested_in_exchange' => ['nullable', Rule::in(['yes', 'no'])],
            'exchange_vehicle_brand' => ['nullable', 'string', 'max:255'],
            'exchange_vehicle_model' => ['nullable', 'string', 'max:255'],
            'exchange_manufacture_year' => ['nullable', 'integer', 'between:1950,2100', 'required_if:interested_in_exchange,yes'],
            'exchange_color' => ['nullable', 'string', 'max:255', 'required_if:interested_in_exchange,yes'],
            'exchange_mileage_km' => ['nullable', 'integer', 'min:0', 'required_if:interested_in_exchange,yes'],
            'exchange_registration_no' => ['nullable', 'string', 'max:50', 'required_if:interested_in_exchange,yes'],
            'exchange_expected_price' => ['nullable', 'numeric', 'min:0', 'required_if:interested_in_exchange,yes'],
            'exchange_quoted_price' => ['nullable', 'numeric', 'min:0', 'required_if:interested_in_exchange,yes'],
            'exchange_price_difference' => ['nullable', 'numeric'],

            'add_exchange_images' => ['nullable', 'in:1'],
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

            'interested_in_competition' => ['nullable', Rule::in(['yes', 'no', 'not_asked'])],
            'competition_brand' => ['nullable', 'string', 'max:255', 'required_if:interested_in_competition,yes'],
            'competition_model' => ['nullable', 'string', 'max:255', 'required_if:interested_in_competition,yes'],

            'first_time_buyer' => ['nullable', Rule::in(['yes', 'no'])],
            'existing_vehicle_brand' => ['nullable', 'string', 'max:255', 'required_if:first_time_buyer,no'],
            'existing_vehicle_model' => ['nullable', 'string', 'max:255', 'required_if:first_time_buyer,no'],
            'existing_vehicle_year' => ['nullable', 'integer', 'between:1950,2100', 'required_if:first_time_buyer,no'],

            'reschedule_followup' => ['nullable', 'in:0,1'],
            'follow_type' => ['nullable', Rule::in(['Home Visit', 'Showroom Visit', 'Call']), 'required_if:reschedule_followup,1'],
            'follow_date' => ['nullable', 'date', 'required_if:reschedule_followup,1'],
            'follow_time' => ['nullable', 'date_format:H:i', 'required_if:reschedule_followup,1'],
            'lead_status' => ['nullable', Rule::in(['hot', 'warm', 'cold'])],
            'customer_remark' => ['nullable', 'string', 'max:1000'],

            'active_step' => ['nullable', 'integer', 'between:1,5'],
            'exit_after_save' => ['nullable', 'in:0,1'],
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

        if (($validated['edit_interested_vehicle'] ?? '0') === '1') {
            $selectedVehicle = Vehicle::query()
                ->where('model', $validated['interested_model'])
                ->where('engine_type', $validated['interested_engine'])
                ->where('variant', $validated['interested_variant'])
                ->first();

            if (!$selectedVehicle) {
                return back()
                    ->withErrors(['interested_variant' => 'Please select a valid model, engine type, and variant.'])
                    ->withInput();
            }

            if ((int) $enquiry->vehicle_id !== (int) $selectedVehicle->id) {
                $enquiry->vehicle_id = $selectedVehicle->id;
                $enquiry->save();
            }
        }

        if (array_key_exists('lead_source', $validated) && !empty($validated['lead_source'])) {
            $enquiry->lead_source = $validated['lead_source'];
            $enquiry->save();
        }

        if (array_key_exists('purchase_mode', $validated) && !empty($validated['purchase_mode'])) {
            $enquiry->finance = $validated['purchase_mode'] === 'finance' ? 1 : 0;
            $enquiry->save();
        }

        $rescheduleFollowup = ($validated['reschedule_followup'] ?? '0') === '1';
        if ($rescheduleFollowup) {
            $enquiry->follow_type = $validated['follow_type'];
            $enquiry->follow_date = $validated['follow_date'];
            $enquiry->follow_time = $validated['follow_time'];
            $enquiry->followup_status = 'pending';
            $enquiry->followup_marked_at = null;
            $enquiry->save();
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

        $existingProspect = ProspectSheet::firstOrNew(['enquiry_id' => $enquiry->id]);
        $pick = fn(string $key, $default = null) => array_key_exists($key, $validated) ? $validated[$key] : $default;
        $vehicleUnitPrice = (float) (optional($enquiry->vehicle)->unit_price ?? 0);
        $vehicleVatAmount = (float) (optional($enquiry->vehicle)->vat_amount ?? 0);
        $interestedVehicleColor = $pick('interested_vehicle_color', $existingProspect->interested_vehicle_color);
        $sourceOfInformation = $pick('source_of_information', $existingProspect->source_of_information);

        $hasQuoteTaken = array_key_exists('quote_taken', $validated);
        $quoteTaken = $pick('quote_taken', $existingProspect->quote_taken);
        $quoteDate = $hasQuoteTaken
            ? ($quoteTaken === 'yes' ? $pick('quote_date') : null)
            : $existingProspect->quote_date;

        $hasTestDriveGiven = array_key_exists('test_drive_given', $validated);
        $testDriveGiven = $pick('test_drive_given', $existingProspect->test_drive_given);
        $purchaseMode = $pick('purchase_mode', $existingProspect->purchase_mode);
        $testDriveDate = $existingProspect->test_drive_date;
        $testDriveVehicleModel = $existingProspect->test_drive_vehicle_model;
        $testDriveToWhom = $existingProspect->test_drive_to_whom;
        $testDriveNotGivenReason = $existingProspect->test_drive_not_given_reason;

        if ($hasTestDriveGiven) {
            if ($testDriveGiven === 'yes') {
                $testDriveDate = $pick('test_drive_date');
                $testDriveVehicleModel = $pick('test_drive_vehicle_model');
                $testDriveToWhom = $pick('test_drive_to_whom');
                $testDriveNotGivenReason = null;
            } elseif ($testDriveGiven === 'no') {
                $testDriveDate = null;
                $testDriveVehicleModel = null;
                $testDriveToWhom = null;
                $testDriveNotGivenReason = $pick('test_drive_not_given_reason');
            }
        }

        $hasInterestedExchange = array_key_exists('interested_in_exchange', $validated);
        $interestedInExchange = $pick('interested_in_exchange', $existingProspect->interested_in_exchange);
        $exchangeVehicleBrand = $existingProspect->exchange_vehicle_brand;
        $exchangeVehicleModel = $existingProspect->exchange_vehicle_model;
        $exchangeManufactureYear = $existingProspect->exchange_manufacture_year;
        $exchangeColor = $existingProspect->exchange_color;
        $exchangeMileageKm = $existingProspect->exchange_mileage_km;
        $exchangeRegistrationNo = $existingProspect->exchange_registration_no;
        $exchangeExpectedPrice = $existingProspect->exchange_expected_price;
        $exchangeQuotedPrice = $existingProspect->exchange_quoted_price;
        $exchangePriceDifference = $existingProspect->exchange_price_difference;

        $blueBookImage = $existingProspect->blue_book_image;
        $lotNoImage = $existingProspect->lot_no_image;
        $carPic1Image = $existingProspect->car_pic_1_image;
        $carPic2Image = $existingProspect->car_pic_2_image;
        $exchangeExtraImages = is_array($existingProspect->exchange_extra_images) ? $existingProspect->exchange_extra_images : [];

        if ($hasInterestedExchange) {
            if ($interestedInExchange === 'yes') {
                $exchangeVehicleBrand = $pick('exchange_vehicle_brand');
                $exchangeVehicleModel = $pick('exchange_vehicle_model');
                $exchangeManufactureYear = $pick('exchange_manufacture_year');
                $exchangeColor = $pick('exchange_color');
                $exchangeMileageKm = $pick('exchange_mileage_km');
                $exchangeRegistrationNo = $pick('exchange_registration_no');
                $exchangeExpectedPrice = $pick('exchange_expected_price');
                $exchangeQuotedPrice = $pick('exchange_quoted_price');

                if ($exchangeExpectedPrice !== null && $exchangeQuotedPrice !== null) {
                    $exchangePriceDifference = (float) $exchangeExpectedPrice - (float) $exchangeQuotedPrice;
                } else {
                    $exchangePriceDifference = $pick('exchange_price_difference');
                }
            } else {
                $exchangeVehicleBrand = null;
                $exchangeVehicleModel = null;
                $exchangeManufactureYear = null;
                $exchangeColor = null;
                $exchangeMileageKm = null;
                $exchangeRegistrationNo = null;
                $exchangeExpectedPrice = null;
                $exchangeQuotedPrice = null;
                $exchangePriceDifference = null;
                $blueBookImage = null;
                $lotNoImage = null;
                $carPic1Image = null;
                $carPic2Image = null;
                $exchangeExtraImages = [];
            }
        }

        $addExchangeImages = $request->input('add_exchange_images') === '1';
        if ($interestedInExchange !== 'yes') {
            $addExchangeImages = false;
        }

        if ($addExchangeImages) {
            $requiredImageFields = [
                'blue_book_image' => 'Blue Book image is required.',
                'lot_no_image' => 'Lot No image is required.',
                'car_pic_1_image' => 'Car Pic 1 image is required.',
                'car_pic_2_image' => 'Car Pic 2 image is required.',
            ];

            $missingErrors = [];
            foreach ($requiredImageFields as $field => $message) {
                $existingValue = match ($field) {
                    'blue_book_image' => $blueBookImage,
                    'lot_no_image' => $lotNoImage,
                    'car_pic_1_image' => $carPic1Image,
                    'car_pic_2_image' => $carPic2Image,
                    default => null,
                };

                if (!$request->hasFile($field) && empty($existingValue)) {
                    $missingErrors[$field] = $message;
                }
            }

            if (!empty($missingErrors)) {
                return back()->withErrors($missingErrors)->withInput();
            }

            if ($request->hasFile('blue_book_image')) {
                $blueBookImage = $request->file('blue_book_image')->store('prospect/exchange', 'public');
            }
            if ($request->hasFile('lot_no_image')) {
                $lotNoImage = $request->file('lot_no_image')->store('prospect/exchange', 'public');
            }
            if ($request->hasFile('car_pic_1_image')) {
                $carPic1Image = $request->file('car_pic_1_image')->store('prospect/exchange', 'public');
            }
            if ($request->hasFile('car_pic_2_image')) {
                $carPic2Image = $request->file('car_pic_2_image')->store('prospect/exchange', 'public');
            }

            if ($request->hasFile('extra_exchange_images')) {
                foreach ($request->file('extra_exchange_images') as $extraImageFile) {
                    if ($extraImageFile) {
                        $exchangeExtraImages[] = $extraImageFile->store('prospect/exchange', 'public');
                    }
                }
            }
        }

        $hasCompetitionInterest = array_key_exists('interested_in_competition', $validated);
        $competitionInterest = $pick('interested_in_competition', $existingProspect->interested_in_competition);
        $competitionBrand = $existingProspect->competition_brand;
        $competitionModel = $existingProspect->competition_model;

        if ($hasCompetitionInterest) {
            if ($competitionInterest === 'yes') {
                $competitionBrand = $pick('competition_brand');
                $competitionModel = $pick('competition_model');
            } else {
                $competitionBrand = null;
                $competitionModel = null;
            }
        }

        $hasFirstTimeBuyer = array_key_exists('first_time_buyer', $validated);
        $firstTimeBuyer = $pick('first_time_buyer', $existingProspect->first_time_buyer);
        $existingVehicleBrand = $existingProspect->existing_vehicle_brand;
        $existingVehicleModel = $existingProspect->existing_vehicle_model;
        $existingVehicleYear = $existingProspect->existing_vehicle_year;

        if ($hasFirstTimeBuyer) {
            if ($firstTimeBuyer === 'no') {
                $existingVehicleBrand = $pick('existing_vehicle_brand');
                $existingVehicleModel = $pick('existing_vehicle_model');
                $existingVehicleYear = $pick('existing_vehicle_year');
            } else {
                $existingVehicleBrand = null;
                $existingVehicleModel = null;
                $existingVehicleYear = null;
            }
        }

        $offerUnitPrice = $existingProspect->offer_unit_price;
        $offerUnitPriceDiscount = $existingProspect->offer_unit_price_discount;
        $offerUnitPriceFree = (bool) $existingProspect->offer_unit_price_free;
        $offerVatAmount = $existingProspect->offer_vat_amount;
        $offerVatDiscount = $existingProspect->offer_vat_discount;
        $offerVatFree = (bool) $existingProspect->offer_vat_free;
        $offerTotalCost = $existingProspect->offer_total_cost;
        $offerTotalDiscount = $existingProspect->offer_total_discount;
        $offerFinalPrice = $existingProspect->offer_final_price;

        $hasOfferDetails = array_key_exists('offer_unit_price', $validated)
            || array_key_exists('offer_vat_amount', $validated);

        if ($hasOfferDetails) {
            $offerUnitPrice = (float) $pick('offer_unit_price', $existingProspect->offer_unit_price ?? $vehicleUnitPrice);
            $offerVatAmount = (float) $pick('offer_vat_amount', $existingProspect->offer_vat_amount ?? $vehicleVatAmount);
            $offerUnitPriceDiscount = (float) $pick('offer_unit_price_discount', 0);
            $offerVatDiscount = (float) $pick('offer_vat_discount', 0);
            $offerUnitPriceFree = $pick('offer_unit_price_free', '0') === '1';
            $offerVatFree = $pick('offer_vat_free', '0') === '1';

            $offerUnitPrice = max($offerUnitPrice, 0);
            $offerVatAmount = max($offerVatAmount, 0);
            $offerUnitPriceDiscount = max($offerUnitPriceDiscount, 0);
            $offerVatDiscount = max($offerVatDiscount, 0);

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
        }

        ProspectSheet::updateOrCreate(
            ['enquiry_id' => $enquiry->id],
            [
                'customer_type' => $validated['customer_type'],
                'corporate_name' => $validated['customer_type'] === 'corporate' ? ($validated['corporate_name'] ?? null) : null,
                'profession' => $validated['profession'],
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'interested_vehicle_color' => $interestedVehicleColor,
                'source_of_information' => $sourceOfInformation,
                'quote_taken' => $quoteTaken,
                'quote_date' => $quoteDate,
                'test_drive_given' => $testDriveGiven,
                'test_drive_date' => $testDriveDate,
                'test_drive_vehicle_model' => $testDriveVehicleModel,
                'test_drive_to_whom' => $testDriveToWhom,
                'test_drive_not_given_reason' => $testDriveNotGivenReason,
                'purchase_mode' => $purchaseMode,
                'interested_in_exchange' => $interestedInExchange,
                'exchange_vehicle_brand' => $exchangeVehicleBrand,
                'exchange_vehicle_model' => $exchangeVehicleModel,
                'exchange_manufacture_year' => $exchangeManufactureYear,
                'exchange_color' => $exchangeColor,
                'exchange_mileage_km' => $exchangeMileageKm,
                'exchange_registration_no' => $exchangeRegistrationNo,
                'exchange_expected_price' => $exchangeExpectedPrice,
                'exchange_quoted_price' => $exchangeQuotedPrice,
                'exchange_price_difference' => $exchangePriceDifference,
                'blue_book_image' => $blueBookImage,
                'lot_no_image' => $lotNoImage,
                'car_pic_1_image' => $carPic1Image,
                'car_pic_2_image' => $carPic2Image,
                'exchange_extra_images' => $exchangeExtraImages,
                'offer_unit_price' => $offerUnitPrice,
                'offer_unit_price_discount' => $offerUnitPriceDiscount,
                'offer_unit_price_free' => $offerUnitPriceFree,
                'offer_vat_amount' => $offerVatAmount,
                'offer_vat_discount' => $offerVatDiscount,
                'offer_vat_free' => $offerVatFree,
                'offer_total_cost' => $offerTotalCost,
                'offer_total_discount' => $offerTotalDiscount,
                'offer_final_price' => $offerFinalPrice,
                'interested_in_competition' => $competitionInterest,
                'competition_brand' => $competitionBrand,
                'competition_model' => $competitionModel,
                'first_time_buyer' => $firstTimeBuyer,
                'existing_vehicle_brand' => $existingVehicleBrand,
                'existing_vehicle_model' => $existingVehicleModel,
                'existing_vehicle_year' => $existingVehicleYear,
                'reschedule_followup' => $rescheduleFollowup,
                'lead_status' => $pick('lead_status', $existingProspect->lead_status),
                'customer_remark' => $pick('customer_remark', $existingProspect->customer_remark),
                'current_step' => $validated['active_step'] ?? 1,
            ]
        );

        if (($validated['exit_after_save'] ?? '0') === '1') {
            return redirect('/epr')->with('success', 'Prospect sheet saved.');
        }

        $nextStep = ($validated['active_step'] ?? 1) + 1;
        $nextStep = max(1, min(5, $nextStep));

        if (($validated['active_step'] ?? 1) >= 5) {
            return redirect()
                ->route('prospect.show', ['enquiry' => $enquiry->id, 'step' => 5])
                ->with('success', 'Prospect sheet saved.');
        }

        return redirect()
            ->route('prospect.show', ['enquiry' => $enquiry->id, 'step' => $nextStep])
            ->with('success', 'Step saved successfully.');
    }
}









