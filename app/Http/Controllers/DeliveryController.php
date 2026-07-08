<?php

namespace App\Http\Controllers;

use App\Models\CompetitionVehicle;
use App\Models\Delivery;
use App\Models\Enquiry;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DeliveryController extends Controller
{
    private const DOCUMENT_FIELDS = [
        'purchase_order_image',
        'insurance_copy_1_image',
        'insurance_copy_2_image',
        'pan_certificate_image',
        'tin_certificate_image',
        'company_registration_certificate_1_image',
        'company_registration_certificate_2_image',
        'share_certificate_copy_1_image',
        'share_certificate_copy_2_image',
        'citizenship_certificate_1_image',
        'citizenship_certificate_2_image',
    ];

    private const EXCHANGE_IMAGE_FIELDS = [
        'blue_book_image',
        'lot_no_image',
        'car_pic_1_image',
        'car_pic_2_image',
    ];

    public function show(Enquiry $enquiry)
    {
        $enquiry->load(['customer', 'vehicle', 'prospectSheet', 'booking', 'delivery', 'user']);

        $delivery = $enquiry->delivery ?: new Delivery([
            'enquiry_id' => $enquiry->id,
        ]);

        $customer = $enquiry->customer;
        $prospect = $enquiry->prospectSheet;
        $booking = $enquiry->booking;
        $currentStep = (int) old('delivery_step', request()->query('step', 1));
        $currentStep = max(1, min(3, $currentStep));
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
        $mobileNumbers = collect($customer?->mobile_numbers ?? [])
            ->map(fn($mobile) => trim((string) $mobile))
            ->filter()
            ->values()
            ->all();

        $defaultValues = [
            'title' => $delivery->title ?: $booking?->title ?: $customer?->title,
            'name' => $delivery->name ?: $booking?->name ?: $customer?->name,
            'contact_type' => $delivery->contact_type ?: $booking?->contact_type ?: 'Mobile',
            'mobile_numbers' => $delivery->mobile_numbers ?: $booking?->mobile_numbers ?: implode(', ', $mobileNumbers),
            'district' => $delivery->district ?: $booking?->district ?: $customer?->district,
            'location' => $delivery->location ?: $booking?->location ?: $customer?->location,
            'state' => $delivery->state ?: $booking?->state ?: $customer?->state,
            'address1' => $delivery->address1 ?: $booking?->address1 ?: $customer?->address1,
            'address2' => $delivery->address2 ?: $booking?->address2 ?: $customer?->address2,
            'customer_type' => $delivery->customer_type ?: $booking?->customer_type ?: $prospect?->customer_type ?: 'individual',
            'corporate_name' => $delivery->corporate_name ?: $prospect?->corporate_name,
            'profession' => $delivery->profession ?: $booking?->profession ?: $prospect?->profession ?: 'self_employed',
            'interested_model' => $delivery->interested_model ?: $booking?->interested_model ?: $enquiry->vehicle?->model,
            'interested_engine' => $delivery->interested_engine ?: $booking?->interested_engine ?: $enquiry->vehicle?->engine_type,
            'interested_variant' => $delivery->interested_variant ?: $booking?->interested_variant ?: $enquiry->vehicle?->variant,
            'interested_vehicle_color' => $delivery->interested_vehicle_color ?: $booking?->interested_vehicle_color ?: $prospect?->interested_vehicle_color,
            'quote_taken' => $delivery->quote_taken ?: $booking?->quote_taken ?: $prospect?->quote_taken,
            'quote_date' => $delivery->quote_date ?: $booking?->quote_date ?: $prospect?->quote_date,
            'test_drive_given' => $delivery->test_drive_given ?: $booking?->test_drive_given ?: $prospect?->test_drive_given,
            'test_drive_date' => $delivery->test_drive_date ?: $booking?->test_drive_date ?: $prospect?->test_drive_date,
            'test_drive_vehicle_model' => $delivery->test_drive_vehicle_model ?: $booking?->test_drive_vehicle_model ?: $prospect?->test_drive_vehicle_model,
            'test_drive_to_whom' => $delivery->test_drive_to_whom ?: $booking?->test_drive_to_whom ?: $prospect?->test_drive_to_whom,
            'test_drive_not_given_reason' => $delivery->test_drive_not_given_reason ?: $booking?->test_drive_not_given_reason ?: $prospect?->test_drive_not_given_reason,
            'purchase_mode' => $delivery->purchase_mode ?: $booking?->purchase_mode ?: $prospect?->purchase_mode,
            'finance_form' => $delivery->finance_form ?: $booking?->finance_form,
            'interested_in_competition' => $delivery->interested_in_competition ?: $booking?->interested_in_competition ?: $prospect?->interested_in_competition,
            'competition_brand' => $delivery->competition_brand ?: $booking?->competition_brand ?: $prospect?->competition_brand,
            'competition_model' => $delivery->competition_model ?: $booking?->competition_model ?: $prospect?->competition_model,
            'first_time_buyer' => $delivery->first_time_buyer ?: $booking?->first_time_buyer ?: $prospect?->first_time_buyer,
            'existing_vehicle_brand' => $delivery->existing_vehicle_brand ?: $booking?->existing_vehicle_brand ?: $prospect?->existing_vehicle_brand,
            'existing_vehicle_model' => $delivery->existing_vehicle_model ?: $booking?->existing_vehicle_model ?: $prospect?->existing_vehicle_model,
            'existing_vehicle_year' => $delivery->existing_vehicle_year ?: $booking?->existing_vehicle_year ?: $prospect?->existing_vehicle_year,
            'interested_in_exchange' => $delivery->interested_in_exchange ?: $booking?->interested_in_exchange ?: $prospect?->interested_in_exchange,
            'exchange_type' => $delivery->exchange_type ?: $booking?->exchange_type ?: 'in_house',
            'exchange_vehicle_brand' => $delivery->exchange_vehicle_brand ?: $booking?->exchange_vehicle_brand ?: $prospect?->exchange_vehicle_brand,
            'exchange_vehicle_model' => $delivery->exchange_vehicle_model ?: $booking?->exchange_vehicle_model ?: $prospect?->exchange_vehicle_model,
            'exchange_manufacture_year' => $delivery->exchange_manufacture_year ?: $booking?->exchange_manufacture_year ?: $prospect?->exchange_manufacture_year,
            'exchange_color' => $delivery->exchange_color ?: $booking?->exchange_color ?: $prospect?->exchange_color,
            'exchange_mileage_km' => $delivery->exchange_mileage_km ?: $booking?->exchange_mileage_km ?: $prospect?->exchange_mileage_km,
            'exchange_registration_no' => $delivery->exchange_registration_no ?: $booking?->exchange_registration_no ?: $prospect?->exchange_registration_no,
            'exchange_expected_price' => $delivery->exchange_expected_price ?: $booking?->exchange_expected_price ?: $prospect?->exchange_expected_price,
            'exchange_quoted_price' => $delivery->exchange_quoted_price ?: $booking?->exchange_quoted_price ?: $prospect?->exchange_quoted_price,
            'exchange_price_difference' => $delivery->exchange_price_difference ?: $booking?->exchange_price_difference ?: $prospect?->exchange_price_difference,
        ];

        return view('delivery.show', [
            'enquiry' => $enquiry,
            'delivery' => $delivery,
            'customer' => $customer,
            'vehicle' => $enquiry->vehicle,
            'prospect' => $prospect,
            'booking' => $booking,
            'defaultValues' => $defaultValues,
            'currentStep' => $currentStep,
            'vehicleModels' => $vehicleModels,
            'competitionMap' => $competitionMap,
        ]);
    }

    public function store(Request $request, Enquiry $enquiry)
    {
        $enquiry->load(['delivery']);

        $documentValidation = [];
        foreach (self::DOCUMENT_FIELDS as $field) {
            $documentValidation[$field] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
            $documentValidation['remove_' . $field] = ['nullable', 'in:0,1'];
        }
        foreach (self::EXCHANGE_IMAGE_FIELDS as $field) {
            $documentValidation[$field] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
            $documentValidation['remove_' . $field] = ['nullable', 'in:0,1'];
        }

        $validated = $request->validate([
            'action_type' => ['nullable', Rule::in(['save_exit', 'save_next'])],
            'delivery_step' => ['nullable', 'integer', 'between:1,3'],
            'title' => ['nullable', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'contact_type' => ['nullable', Rule::in(['Mobile', 'Home', 'Office'])],
            'mobile_numbers' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'address1' => ['nullable', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'customer_type' => ['nullable', Rule::in(['individual', 'corporate'])],
            'corporate_name' => ['nullable', 'required_if:customer_type,corporate', 'string', 'max:255'],
            'profession' => ['nullable', Rule::in(['salaried', 'self_employed', 'other', 'not_asked'])],
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
            'extra_images' => ['nullable', 'array'],
            'extra_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_extra_images' => ['nullable', 'array'],
            'remove_extra_images.*' => ['nullable', 'string'],
            'exchange_extra_images' => ['nullable', 'array'],
            'exchange_extra_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_exchange_extra_images' => ['nullable', 'array'],
            'remove_exchange_extra_images.*' => ['nullable', 'string'],
        ] + $documentValidation);

        $existingDelivery = $enquiry->delivery;
        $removeDocumentFields = collect(array_merge(self::DOCUMENT_FIELDS, self::EXCHANGE_IMAGE_FIELDS))
            ->map(fn($field) => 'remove_' . $field)
            ->all();
        $payload = collect($validated)
            ->except(array_merge(['action_type', 'delivery_step', 'extra_images', 'remove_extra_images', 'exchange_extra_images', 'remove_exchange_extra_images'], $removeDocumentFields))
            ->all();

        if (array_key_exists('customer_type', $payload) && ($payload['customer_type'] ?? null) !== 'corporate') {
            $payload['corporate_name'] = null;
        }

        if (array_key_exists('quote_taken', $payload) && ($payload['quote_taken'] ?? null) !== 'yes') {
            $payload['quote_date'] = null;
        }

        if (array_key_exists('test_drive_given', $payload)) {
            if (($payload['test_drive_given'] ?? null) === 'yes') {
                $payload['test_drive_not_given_reason'] = null;
            } elseif (($payload['test_drive_given'] ?? null) === 'no') {
                $payload['test_drive_date'] = null;
                $payload['test_drive_vehicle_model'] = null;
                $payload['test_drive_to_whom'] = null;
            } else {
                $payload['test_drive_date'] = null;
                $payload['test_drive_vehicle_model'] = null;
                $payload['test_drive_to_whom'] = null;
                $payload['test_drive_not_given_reason'] = null;
            }
        }

        if (array_key_exists('purchase_mode', $payload) && ($payload['purchase_mode'] ?? null) !== 'finance') {
            $payload['finance_form'] = null;
        }

        if (array_key_exists('interested_in_competition', $payload) && ($payload['interested_in_competition'] ?? null) !== 'yes') {
            $payload['competition_brand'] = null;
            $payload['competition_model'] = null;
        }

        if (array_key_exists('first_time_buyer', $payload) && ($payload['first_time_buyer'] ?? null) !== 'no') {
            $payload['existing_vehicle_brand'] = null;
            $payload['existing_vehicle_model'] = null;
            $payload['existing_vehicle_year'] = null;
        }

        if (array_key_exists('interested_in_exchange', $payload) && ($payload['interested_in_exchange'] ?? null) !== 'yes') {
            $payload['exchange_type'] = null;
            $payload['exchange_vehicle_brand'] = null;
            $payload['exchange_vehicle_model'] = null;
            $payload['exchange_manufacture_year'] = null;
            $payload['exchange_color'] = null;
            $payload['exchange_mileage_km'] = null;
            $payload['exchange_registration_no'] = null;
            $payload['exchange_expected_price'] = null;
            $payload['exchange_quoted_price'] = null;
            $payload['exchange_price_difference'] = null;
        }

        foreach (self::DOCUMENT_FIELDS as $field) {
            $currentPath = $existingDelivery?->{$field};
            $shouldRemove = ($validated['remove_' . $field] ?? '0') === '1';

            if ($shouldRemove && !empty($currentPath)) {
                Storage::disk('public')->delete($currentPath);
                $currentPath = null;
            }

            if ($request->hasFile($field)) {
                if (!empty($currentPath)) {
                    Storage::disk('public')->delete($currentPath);
                }
                $payload[$field] = $request->file($field)->store('delivery/documents', 'public');
            } else {
                $payload[$field] = $currentPath;
            }
        }

        foreach (self::EXCHANGE_IMAGE_FIELDS as $field) {
            $currentPath = $existingDelivery?->{$field};
            $shouldRemove = ($validated['remove_' . $field] ?? '0') === '1'
                || (($payload['interested_in_exchange'] ?? null) === 'no');

            if ($shouldRemove && !empty($currentPath)) {
                Storage::disk('public')->delete($currentPath);
                $currentPath = null;
            }

            if ($request->hasFile($field)) {
                if (!empty($currentPath)) {
                    Storage::disk('public')->delete($currentPath);
                }
                $payload[$field] = $request->file($field)->store('delivery/exchange', 'public');
            } else {
                $payload[$field] = $currentPath;
            }
        }

        $extraImages = is_array($existingDelivery?->extra_images) ? $existingDelivery->extra_images : [];
        $removeExtraImages = collect($validated['remove_extra_images'] ?? [])
            ->map(fn($path) => (string) $path)
            ->filter()
            ->values()
            ->all();

        if (!empty($removeExtraImages)) {
            foreach ($removeExtraImages as $removePath) {
                if (in_array($removePath, $extraImages, true)) {
                    Storage::disk('public')->delete($removePath);
                }
            }

            $extraImages = array_values(array_filter(
                $extraImages,
                fn($path) => !in_array($path, $removeExtraImages, true)
            ));
        }

        if ($request->hasFile('extra_images')) {
            foreach ($request->file('extra_images') as $extraImageFile) {
                if ($extraImageFile) {
                    $extraImages[] = $extraImageFile->store('delivery/extra-images', 'public');
                }
            }
        }
        $payload['extra_images'] = $extraImages;

        $exchangeExtraImages = is_array($existingDelivery?->exchange_extra_images) ? $existingDelivery->exchange_extra_images : [];
        $removeExchangeExtraImages = collect($validated['remove_exchange_extra_images'] ?? [])
            ->map(fn($path) => (string) $path)
            ->filter()
            ->values()
            ->all();

        if (($payload['interested_in_exchange'] ?? null) === 'no') {
            $removeExchangeExtraImages = array_values(array_unique(array_merge($removeExchangeExtraImages, $exchangeExtraImages)));
        }

        if (!empty($removeExchangeExtraImages)) {
            foreach ($removeExchangeExtraImages as $removePath) {
                if (in_array($removePath, $exchangeExtraImages, true)) {
                    Storage::disk('public')->delete($removePath);
                }
            }

            $exchangeExtraImages = array_values(array_filter(
                $exchangeExtraImages,
                fn($path) => !in_array($path, $removeExchangeExtraImages, true)
            ));
        }

        if ($request->hasFile('exchange_extra_images')) {
            foreach ($request->file('exchange_extra_images') as $extraImageFile) {
                if ($extraImageFile) {
                    $exchangeExtraImages[] = $extraImageFile->store('delivery/exchange', 'public');
                }
            }
        }
        $payload['exchange_extra_images'] = $exchangeExtraImages;

        Delivery::updateOrCreate(
            ['enquiry_id' => $enquiry->id],
            $payload
        );

        $currentStep = (int) ($validated['delivery_step'] ?? 1);
        $currentStep = max(1, min(3, $currentStep));
        $actionType = $validated['action_type'] ?? null;

        if ($actionType === 'save_exit') {
            return redirect('/epr')->with('success', 'Delivery details saved.');
        }

        if ($actionType === 'save_next' && $currentStep < 3) {
            return redirect()
                ->route('delivery.show', ['enquiry' => $enquiry->id, 'step' => $currentStep + 1])
                ->with('success', 'Delivery details saved.');
        }

        return redirect()
            ->route('delivery.show', ['enquiry' => $enquiry->id, 'step' => $currentStep])
            ->with('success', 'Delivery details saved.');
    }
}
