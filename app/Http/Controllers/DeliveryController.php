<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CompetitionVehicle;
use App\Models\Delivery;
use App\Models\Enquiry;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
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

        if ($enquiry->isTerminalLead()) {
            return $this->redirectTerminalLead($enquiry);
        }

        if (!$enquiry->hasCompletedProspectSheet()) {
            return redirect()
                ->route('prospect.show', $enquiry->id)
                ->with('error', 'Please complete the Prospect Sheet before opening Delivery.');
        }

        if (!$enquiry->canOpenDelivery()) {
            return redirect()
                ->route('booking.show', $enquiry->id)
                ->with('error', 'Please complete Booking before opening Delivery.');
        }

        $delivery = $enquiry->delivery ?: new Delivery([
            'enquiry_id' => $enquiry->id,
        ]);

        $customer = $enquiry->customer;
        $prospect = $enquiry->prospectSheet;
        $booking = $enquiry->booking;
        $currentStep = (int) old('delivery_step', request()->query('step', 1));
        $currentStep = max(1, min(6, $currentStep));
        $firstTimeBuyerForNavigation = old('first_time_buyer', $delivery->first_time_buyer ?: $booking?->first_time_buyer ?: $prospect?->first_time_buyer);
        if ($currentStep === 3 && $firstTimeBuyerForNavigation === 'yes') {
            return redirect()->route('delivery.show', ['enquiry' => $enquiry->id, 'step' => 4]);
        }
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
        $bookingPaymentAmount = $this->bookingPaymentAmount($booking);

        $defaultValues = [
            'title' => $delivery->title ?: $booking?->title ?: $customer?->title,
            'name' => $delivery->name ?: $booking?->name ?: $customer?->name,
            'contact_type' => $delivery->contact_type ?: $booking?->contact_type ?: 'Mobile',
            'email' => $delivery->email ?: $booking?->email ?: $customer?->email,
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
            'offer_unit_price' => $booking?->offer_unit_price ?? $prospect?->offer_unit_price ?? $enquiry->vehicle?->unit_price,
            'offer_unit_price_discount' => $booking?->offer_unit_price_discount ?? $prospect?->offer_unit_price_discount ?? 0,
            'offer_unit_price_free' => (bool) (($booking?->offer_unit_price_free ?? null) ?? $prospect?->offer_unit_price_free),
            'offer_vat_amount' => $booking?->offer_vat_amount ?? $prospect?->offer_vat_amount ?? $enquiry->vehicle?->vat_amount,
            'offer_vat_discount' => $booking?->offer_vat_discount ?? $prospect?->offer_vat_discount ?? 0,
            'offer_vat_free' => (bool) (($booking?->offer_vat_free ?? null) ?? $prospect?->offer_vat_free),
            'offer_total_cost' => $booking?->offer_total_cost ?? $prospect?->offer_total_cost,
            'offer_total_discount' => $booking?->offer_total_discount ?? $prospect?->offer_total_discount,
            'offer_final_price' => $booking?->offer_final_price ?? $prospect?->offer_final_price,
            'payment_receipt_amount_booking' => $bookingPaymentAmount,
            'payment_pre_delivery_amount' => $delivery->payment_pre_delivery_amount,
            'payment_delivery_amount' => $delivery->payment_delivery_amount,
            'delivery_receipts' => is_array($delivery->delivery_receipts) ? $delivery->delivery_receipts : [],
            'reference_taken' => (bool) ($delivery->reference_taken ?? false),
            'selecting_brand_reasons' => is_array($delivery->selecting_brand_reasons) ? $delivery->selecting_brand_reasons : [],
            'date_of_delivery' => $delivery->date_of_delivery ? substr((string) $delivery->date_of_delivery, 0, 10) : now()->toDateString(),
            'chassis_number' => $delivery->chassis_number,
            'pending_commitments' => $delivery->pending_commitments,
            'payment_finance_provider' => $delivery->payment_finance_provider ?: 'Self',
            'payment_finance_bank' => $delivery->payment_finance_bank ?: $booking?->finance_bank,
            'payment_finance_disbursal_amount' => $delivery->payment_finance_disbursal_amount,
            'payment_finance_other_reason' => $delivery->payment_finance_other_reason ?: $booking?->finance_other_details,
            'payment_pending_reason' => $delivery->payment_pending_reason,
            'payment_pending_amount' => $delivery->payment_pending_amount,
            'payment_agent_name' => $delivery->payment_agent_name,
            'payment_agent_number' => $delivery->payment_agent_number,
            'payment_expected_date' => $delivery->payment_expected_date ? substr((string) $delivery->payment_expected_date, 0, 10) : null,
            'payment_credit_given_to_customer' => $delivery->payment_credit_given_to_customer,
            'payment_credit_amount_pending' => $delivery->payment_credit_amount_pending,
            'payment_credit_permitted_by' => $delivery->payment_credit_permitted_by,
            'payment_credit_expected_date' => $delivery->payment_credit_expected_date ? substr((string) $delivery->payment_credit_expected_date, 0, 10) : null,
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
            'deliveryReceiptPaymentModes' => $this->deliveryReceiptPaymentModes(),
            'bankOptions' => $this->bankOptions(),
        ]);
    }

    public function store(Request $request, Enquiry $enquiry)
    {
        $enquiry->load(['delivery', 'booking', 'prospectSheet', 'vehicle']);

        if ($enquiry->isTerminalLead()) {
            return $this->redirectTerminalLead($enquiry);
        }

        if (!$enquiry->hasCompletedProspectSheet()) {
            return redirect()
                ->route('prospect.show', $enquiry->id)
                ->with('error', 'Please complete the Prospect Sheet before saving Delivery.');
        }

        if (!$enquiry->canOpenDelivery()) {
            return redirect()
                ->route('booking.show', $enquiry->id)
                ->with('error', 'Please complete Booking before saving Delivery.');
        }

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
            'action_type' => ['nullable', Rule::in(['save_exit', 'save_next', 'submit'])],
            'delivery_step' => ['nullable', 'integer', 'between:1,6'],
            'title' => ['nullable', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'contact_type' => ['nullable', Rule::in(['Mobile', 'Home', 'Office'])],
            'email' => ['nullable', 'email', 'max:255'],
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
            'test_drive_vehicle_model_other' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn() => $request->input('test_drive_given') === 'yes' && $request->input('test_drive_vehicle_model') === 'Other')],
            'test_drive_to_whom' => ['nullable', 'string', 'max:255'],
            'test_drive_not_given_reason' => ['nullable', Rule::in($this->testDriveNotGivenReasons())],
            'test_drive_not_given_reason_other' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn() => $request->input('test_drive_given') === 'no' && $request->input('test_drive_not_given_reason') === 'Others'),
            ],
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
            'exchange_tyre_replacements_present' => ['nullable', 'in:1'],
            'exchange_tyre_replacements' => ['nullable', 'array'],
            'exchange_tyre_replacements.*' => ['nullable', Rule::in(['front_lhs', 'front_rhs', 'rear_lhs', 'rear_rhs'])],
            'exchange_expected_price' => ['nullable', 'numeric', 'min:0'],
            'exchange_quoted_price' => ['nullable', 'numeric', 'min:0'],
            'exchange_price_difference' => ['nullable', 'numeric'],
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
            'payment_pre_delivery_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_delivery_amount' => ['nullable', 'numeric', 'min:0'],
            'delivery_receipts' => ['nullable', 'array'],
            'delivery_receipts.*.receipt_name_no' => ['nullable', 'string', 'max:255'],
            'delivery_receipts.*.receipt_date' => ['nullable', 'date'],
            'delivery_receipts.*.receipt_amount' => ['nullable', 'numeric', 'min:0'],
            'delivery_receipts.*.payment_mode' => ['nullable', Rule::in($this->deliveryReceiptPaymentModes())],
            'delivery_receipts.*.receipt_type' => ['nullable', Rule::in(['Delivery'])],
            'reference_taken' => ['nullable', 'in:0,1'],
            'selecting_brand_reasons' => ['nullable', 'array'],
            'selecting_brand_reasons.*' => ['nullable', Rule::in($this->selectingBrandReasonOptions())],
            'date_of_delivery' => ['nullable', 'date'],
            'chassis_number' => [
                'nullable',
                Rule::requiredIf(fn() => (int) $request->input('delivery_step') === 6),
                'string',
                'max:255',
            ],
            'pending_commitments' => ['nullable', 'string', 'max:1000'],
            'payment_finance_provider' => ['nullable', Rule::in(['In-House', 'Self', 'Other'])],
            'payment_finance_bank' => ['nullable', 'string', 'max:255', Rule::in($this->bankOptions())],
            'payment_finance_disbursal_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_finance_other_reason' => ['nullable', 'string', 'max:255'],
            'payment_pending_reason' => ['nullable', 'string', 'max:255'],
            'payment_pending_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_agent_name' => ['nullable', 'string', 'max:255'],
            'payment_agent_number' => ['nullable', 'string', 'max:50'],
            'payment_expected_date' => ['nullable', 'date'],
            'payment_credit_given_to_customer' => ['nullable', 'string', 'max:255'],
            'payment_credit_amount_pending' => ['nullable', 'numeric', 'min:0'],
            'payment_credit_permitted_by' => ['nullable', 'string', 'max:255'],
            'payment_credit_expected_date' => ['nullable', 'date'],
            'extra_images' => ['nullable', 'array'],
            'extra_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_extra_images' => ['nullable', 'array'],
            'remove_extra_images.*' => ['nullable', 'string'],
            'exchange_extra_images' => ['nullable', 'array'],
            'exchange_extra_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_exchange_extra_images' => ['nullable', 'array'],
            'remove_exchange_extra_images.*' => ['nullable', 'string'],
        ] + $documentValidation);

        $currentStep = (int) ($validated['delivery_step'] ?? 1);
        $currentStep = max(1, min(6, $currentStep));
        $existingDelivery = $enquiry->delivery;
        $bookingPaymentAmount = $this->bookingPaymentAmount($enquiry->booking);
        $deliveryReceipts = $this->normalizeDeliveryReceipts($validated['delivery_receipts'] ?? []);
        $deliveryReceiptTotal = collect($deliveryReceipts)->sum(fn(array $receipt): float => (float) ($receipt['receipt_amount'] ?? 0));
        $paymentDeliveryAmount = !empty($deliveryReceipts)
            ? $deliveryReceiptTotal
            : (float) ($validated['payment_delivery_amount'] ?? $existingDelivery?->payment_delivery_amount ?? 0);
        $paymentReceivedTotal = $bookingPaymentAmount
            + (float) ($validated['payment_pre_delivery_amount'] ?? $existingDelivery?->payment_pre_delivery_amount ?? 0)
            + $paymentDeliveryAmount;
        $paymentPendingAmount = max(0, $this->currentOfferFinalPrice($enquiry) - $paymentReceivedTotal);
        $removeDocumentFields = collect(array_merge(self::DOCUMENT_FIELDS, self::EXCHANGE_IMAGE_FIELDS))
            ->map(fn($field) => 'remove_' . $field)
            ->all();
        $payload = collect($validated)
            ->except(array_merge([
                'action_type',
                'delivery_step',
                'extra_images',
                'remove_extra_images',
                'exchange_extra_images',
                'remove_exchange_extra_images',
                'exchange_tyre_replacements_present',
                'exchange_tyre_replacements',
                'test_drive_vehicle_model_other',
                'test_drive_not_given_reason_other',
                'offer_unit_price',
                'offer_unit_price_discount',
                'offer_unit_price_free',
                'offer_vat_amount',
                'offer_vat_discount',
                'offer_vat_free',
                'offer_total_cost',
                'offer_total_discount',
                'offer_final_price',
                'edit_offer_details',
            ], $removeDocumentFields))
            ->all();
        $payload['payment_receipt_amount_booking'] = $bookingPaymentAmount;
        if ($currentStep === 5) {
            $payload['payment_pending_amount'] = $paymentPendingAmount;
        }
        $payload['delivery_receipts'] = $deliveryReceipts;
        if ($currentStep === 6) {
            $payload['reference_taken'] = ($validated['reference_taken'] ?? '0') === '1';
            $payload['selecting_brand_reasons'] = $validated['selecting_brand_reasons'] ?? [];
            $payload['date_of_delivery'] = $validated['date_of_delivery'] ?? null;
            $payload['chassis_number'] = $validated['chassis_number'] ?? null;
            $payload['pending_commitments'] = $validated['pending_commitments'] ?? null;
        }
        if (!empty($deliveryReceipts)) {
            $payload['payment_delivery_amount'] = $deliveryReceiptTotal;
        }

        if (array_key_exists('payment_finance_provider', $payload)) {
            $financeProvider = $payload['payment_finance_provider'] ?? null;
            if (!in_array($financeProvider, ['In-House', 'Self'], true)) {
                $payload['payment_finance_bank'] = null;
                $payload['payment_finance_disbursal_amount'] = null;
            }

            if ($financeProvider !== 'Other') {
                $payload['payment_finance_other_reason'] = null;
            }
        }

        if (array_key_exists('customer_type', $payload) && ($payload['customer_type'] ?? null) !== 'corporate') {
            $payload['corporate_name'] = null;
        }

        if (array_key_exists('quote_taken', $payload) && ($payload['quote_taken'] ?? null) !== 'yes') {
            $payload['quote_date'] = null;
        }

        if (array_key_exists('test_drive_given', $payload)) {
            if (($payload['test_drive_given'] ?? null) === 'yes') {
                $payload['test_drive_vehicle_model'] = $this->resolveTestDriveVehicleUsed(
                    $validated['test_drive_vehicle_model'] ?? null,
                    $validated['test_drive_vehicle_model_other'] ?? null
                );
                $payload['test_drive_to_whom'] = null;
                $payload['test_drive_not_given_reason'] = null;
            } elseif (($payload['test_drive_given'] ?? null) === 'no') {
                $payload['test_drive_date'] = null;
                $payload['test_drive_vehicle_model'] = null;
                $payload['test_drive_to_whom'] = null;
                $payload['test_drive_not_given_reason'] = $this->resolveTestDriveNotGivenReason(
                    $validated['test_drive_not_given_reason'] ?? null,
                    $validated['test_drive_not_given_reason_other'] ?? null
                );
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

        $bookingVehicleSync = [];
        if (array_key_exists('interested_model', $payload)) {
            $bookingVehicleSync['interested_model'] = $payload['interested_model'];
        }
        if (array_key_exists('interested_vehicle_color', $payload)) {
            $bookingVehicleSync['interested_vehicle_color'] = $payload['interested_vehicle_color'];
        }

        if (!empty($bookingVehicleSync) && $enquiry->booking) {
            $enquiry->booking->fill($bookingVehicleSync)->save();
        }

        if (array_key_exists('exchange_tyre_replacements_present', $validated) && $enquiry->booking) {
            $enquiry->booking->fill([
                'exchange_tyre_replacements' => $validated['exchange_tyre_replacements'] ?? [],
            ])->save();
        }

        if (array_key_exists('interested_vehicle_color', $payload) && $enquiry->prospectSheet) {
            $enquiry->prospectSheet->fill([
                'interested_vehicle_color' => $payload['interested_vehicle_color'],
            ])->save();
        }

        if ($currentStep === 4) {
            $isEditingOffer = ($validated['edit_offer_details'] ?? '0') === '1';
            $offerSource = function (string $field, $fallback = 0) use ($enquiry) {
                $vehicleField = match ($field) {
                    'offer_unit_price' => 'unit_price',
                    'offer_vat_amount' => 'vat_amount',
                    default => null,
                };

                return $enquiry->booking?->{$field}
                    ?? $enquiry->prospectSheet?->{$field}
                    ?? ($vehicleField ? $enquiry->vehicle?->{$vehicleField} : null)
                    ?? $fallback;
            };

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

            $offerPayload = [
                'offer_unit_price' => $offerUnitPrice,
                'offer_unit_price_discount' => $offerUnitPriceDiscount,
                'offer_unit_price_free' => $offerUnitPriceFree,
                'offer_vat_amount' => $offerVatAmount,
                'offer_vat_discount' => $offerVatDiscount,
                'offer_vat_free' => $offerVatFree,
                'offer_total_cost' => $offerUnitPrice + $offerVatAmount,
                'offer_total_discount' => $offerUnitPriceDiscount + $offerVatDiscount,
                'offer_final_price' => max(0, ($offerUnitPrice + $offerVatAmount) - ($offerUnitPriceDiscount + $offerVatDiscount)),
            ];

            if ($enquiry->booking) {
                $enquiry->booking->fill($offerPayload)->save();
            } elseif ($isEditingOffer) {
                Booking::create(array_merge(['enquiry_id' => $enquiry->id], $offerPayload));
            }

            if ($enquiry->prospectSheet) {
                $enquiry->prospectSheet->fill($offerPayload)->save();
            }
        }

        $actionType = $validated['action_type'] ?? null;

        if ($actionType === 'save_exit') {
            return redirect('/epr')->with('success', 'Delivery details saved.');
        }

        if ($actionType === 'submit' && $currentStep === 6) {
            $viewer = $request->user();
            $delivery = Delivery::query()->where('enquiry_id', $enquiry->id)->first();
            if ($delivery) {
                $approvalPayload = [
                    'submitted_by' => $viewer?->id,
                    'submitted_at' => now(),
                ];

                if ($viewer?->role === User::ROLE_SALES_CONSULTANT) {
                    $approvalPayload['approval_status'] = Delivery::APPROVAL_PENDING;
                    $approvalPayload['approved_by'] = null;
                    $approvalPayload['approved_at'] = null;
                    $approvalPayload['approval_note'] = null;
                } else {
                    $approvalPayload['approval_status'] = Delivery::APPROVAL_APPROVED;
                    $approvalPayload['approved_by'] = $viewer?->id;
                    $approvalPayload['approved_at'] = now();
                    $approvalPayload['approval_note'] = null;
                }

                $delivery->fill($approvalPayload)->save();
            }

            if ($viewer?->role === User::ROLE_SALES_CONSULTANT) {
                return redirect()
                    ->route('delivery.show', ['enquiry' => $enquiry->id, 'step' => 6])
                    ->with('delivery_submitted_popup', true)
                    ->with('delivery_submitted_message', 'Delivery submitted successfully. Waiting for Area Manager approval.');
            }

            return redirect()
                ->route('delivery.show', ['enquiry' => $enquiry->id, 'step' => 6])
                ->with('delivery_submitted_popup', true)
                ->with('delivery_submitted_message', 'Delivery Submitted Successfully.');
        }

        if ($actionType === 'save_next' && $currentStep === 4) {
            return redirect()
                ->route('delivery.show', ['enquiry' => $enquiry->id, 'step' => 4])
                ->with('delivery_offer_summary_popup', true)
                ->with('delivery_offer_summary_next_url', route('delivery.show', ['enquiry' => $enquiry->id, 'step' => 5]))
                ->with('success', 'Offer details saved.');
        }

        if ($actionType === 'save_next' && $currentStep === 5 && $paymentPendingAmount > 0.009) {
            return redirect()
                ->route('delivery.show', ['enquiry' => $enquiry->id, 'step' => 5])
                ->withErrors(['payment_pending_amount' => 'Pending Amount must be 0 before Save & Next.'])
                ->with('delivery_pending_block_popup', true)
                ->withInput();
        }

        if ($actionType === 'save_next' && $currentStep < 6) {
            $nextStep = $currentStep === 2 && ($payload['first_time_buyer'] ?? null) === 'yes'
                ? 4
                : $currentStep + 1;

            return redirect()
                ->route('delivery.show', ['enquiry' => $enquiry->id, 'step' => $nextStep])
                ->with('success', 'Delivery details saved.');
        }

        return redirect()
            ->route('delivery.show', ['enquiry' => $enquiry->id, 'step' => $currentStep])
            ->with('success', 'Delivery details saved.');
    }

    public function approvals(Request $request)
    {
        $areaManager = $request->user();
        abort_unless($areaManager?->role === User::ROLE_AREA_MANAGER, 403);

        $deliveries = Delivery::query()
            ->with([
                'enquiry.customer:id,title,name,mobile_numbers',
                'enquiry.vehicle:id,model,variant',
                'enquiry.user:id,name,email,manager_id',
                'submittedBy:id,name,email',
                'approvedBy:id,name,email',
            ])
            ->whereHas('enquiry.user', function ($query) use ($areaManager): void {
                $query->where('manager_id', $areaManager->id);
            })
            ->whereIn('approval_status', [Delivery::APPROVAL_PENDING, Delivery::APPROVAL_APPROVED, Delivery::APPROVAL_REJECTED])
            ->latest('submitted_at')
            ->get();

        return view('delivery.approvals', [
            'deliveries' => $deliveries,
        ]);
    }

    public function approve(Request $request, Delivery $delivery): RedirectResponse
    {
        $areaManager = $request->user();
        abort_unless($areaManager?->role === User::ROLE_AREA_MANAGER, 403);

        $delivery->load('enquiry.user:id,manager_id');
        abort_unless((int) ($delivery->enquiry?->user?->manager_id ?? 0) === (int) $areaManager->id, 403);

        if ($delivery->approval_status !== Delivery::APPROVAL_PENDING) {
            return back()->withErrors(['delivery' => 'This delivery is not pending approval.']);
        }

        $delivery->fill([
            'approval_status' => Delivery::APPROVAL_APPROVED,
            'approved_by' => $areaManager->id,
            'approved_at' => now(),
            'approval_note' => null,
        ])->save();

        return redirect()
            ->route('delivery.approvals')
            ->with('success', 'Delivery approved successfully.');
    }

    public function reject(Request $request, Delivery $delivery): RedirectResponse
    {
        $areaManager = $request->user();
        abort_unless($areaManager?->role === User::ROLE_AREA_MANAGER, 403);

        $validated = $request->validate([
            'approval_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $delivery->load('enquiry.user:id,manager_id');
        abort_unless((int) ($delivery->enquiry?->user?->manager_id ?? 0) === (int) $areaManager->id, 403);

        if ($delivery->approval_status !== Delivery::APPROVAL_PENDING) {
            return back()->withErrors(['delivery' => 'This delivery is not pending approval.']);
        }

        $delivery->fill([
            'approval_status' => Delivery::APPROVAL_REJECTED,
            'approved_by' => $areaManager->id,
            'approved_at' => now(),
            'approval_note' => $validated['approval_note'] ?? null,
        ])->save();

        return redirect()
            ->route('delivery.approvals')
            ->with('success', 'Delivery rejected.');
    }

    private function redirectTerminalLead(Enquiry $enquiry)
    {
        return redirect()
            ->route('enquiries.list', $enquiry->terminalLeadRouteParameters())
            ->with('success', $enquiry->terminalLeadLabel() . ' lead is finalized. Delivery is not available.');
    }

    private function deliveryReceiptPaymentModes(): array
    {
        return [
            'Cash',
            'Cheque',
            'Bank Transfer',
            'Credit/Debit Card',
        ];
    }

    private function bankOptions(): array
    {
        return [
            'Amana Bank PLC',
            'Bank of Ceylon',
            'Bank of China Ltd',
            'Cargills Bank PLC',
            'Citibank, N.A.',
            'Commercial Bank of Ceylon PLC',
            'Deutsche Bank AG',
            'DFCC Bank PLC',
            'Habib Bank Ltd',
            'Hatton National Bank PLC',
            'Indian Bank',
            'Indian Overseas Bank',
            'MCB Bank Ltd',
            'National Development Bank PLC',
            'Nations Trust Bank PLC',
            'Pan Asia Banking Corporation PLC',
            "People's Bank",
            'Public Bank Berhad',
            'Sampath Bank PLC',
            'Seylan Bank PLC',
            'Standard Chartered Bank',
            'State Bank of India',
            'The Hongkong & Shanghai Banking Corporation Ltd (HSBC)',
            'Union Bank of Colombo PLC',
        ];
    }

    private function selectingBrandReasonOptions(): array
    {
        return [
            'Design',
            'Performance',
            'Mileage',
            'Ride Comfort',
            'Resale Value',
            'Price',
            'After Sale Support',
            'New Model',
            'Brand Appeal',
            'Got Better Exchange Value At the Outlet',
            'Got Credit Facility At the Outlet',
            'Happy with Price/Discount',
            'Happy with Finance Terms/Facility',
            'Friends/Family Recommend',
            'Other',
            'I Did Not Ask',
        ];
    }

    private function normalizeDeliveryReceipts(array $receipts): array
    {
        return collect($receipts)
            ->map(function ($receipt): array {
                $receipt = is_array($receipt) ? $receipt : [];

                return [
                    'receipt_name_no' => trim((string) ($receipt['receipt_name_no'] ?? '')),
                    'receipt_date' => trim((string) ($receipt['receipt_date'] ?? '')),
                    'receipt_amount' => (float) ($receipt['receipt_amount'] ?? 0),
                    'payment_mode' => trim((string) ($receipt['payment_mode'] ?? '')),
                    'receipt_type' => 'Delivery',
                ];
            })
            ->filter(function (array $receipt): bool {
                return $receipt['receipt_name_no'] !== ''
                    || $receipt['receipt_date'] !== ''
                    || $receipt['receipt_amount'] > 0
                    || $receipt['payment_mode'] !== '';
            })
            ->values()
            ->all();
    }

    private function bookingPaymentAmount(?Booking $booking): float
    {
        $receiptTotal = collect(is_array($booking?->booking_receipts) ? $booking->booking_receipts : [])
            ->sum(fn($receipt): float => (float) ($receipt['receipt_amount'] ?? 0));
        $amountCollected = (float) ($booking?->amount_collected ?? 0);

        return $amountCollected > 0 ? $amountCollected : $receiptTotal;
    }

    private function currentOfferFinalPrice(Enquiry $enquiry): float
    {
        $booking = $enquiry->booking;
        $prospect = $enquiry->prospectSheet;

        $finalPrice = $booking?->offer_final_price ?? $prospect?->offer_final_price;
        if ($finalPrice !== null) {
            return max(0, (float) $finalPrice);
        }

        $unitPrice = (float) ($booking?->offer_unit_price ?? $prospect?->offer_unit_price ?? $enquiry->vehicle?->unit_price ?? 0);
        $vatAmount = (float) ($booking?->offer_vat_amount ?? $prospect?->offer_vat_amount ?? $enquiry->vehicle?->vat_amount ?? 0);
        $unitDiscount = (float) ($booking?->offer_unit_price_discount ?? $prospect?->offer_unit_price_discount ?? 0);
        $vatDiscount = (float) ($booking?->offer_vat_discount ?? $prospect?->offer_vat_discount ?? 0);

        return max(0, ($unitPrice + $vatAmount) - ($unitDiscount + $vatDiscount));
    }

    private function resolveTestDriveVehicleUsed(?string $selectedVehicle, ?string $otherVehicle): ?string
    {
        $selectedVehicle = trim((string) $selectedVehicle);
        $otherVehicle = trim((string) $otherVehicle);

        if ($selectedVehicle === 'Other') {
            return $otherVehicle !== '' ? $otherVehicle : null;
        }

        return $selectedVehicle !== '' ? $selectedVehicle : null;
    }

    private function testDriveNotGivenReasons(): array
    {
        return [
            'Not interested',
            'Vehicle not available',
            'Vehicle damaged/under repair',
            'Not met in person',
            'Already driven',
            'I Did Not Offer',
            'Others',
        ];
    }

    private function resolveTestDriveNotGivenReason(?string $selectedReason, ?string $otherReason): ?string
    {
        $selectedReason = trim((string) $selectedReason);
        $otherReason = trim((string) $otherReason);

        if ($selectedReason === 'Others') {
            return $otherReason !== '' ? $otherReason : null;
        }

        return $selectedReason !== '' ? $selectedReason : null;
    }
}
