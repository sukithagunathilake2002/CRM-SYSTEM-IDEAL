@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/delivery.css') }}">

@php
    $summaryName = trim(($customer?->title ? $customer->title . ' ' : '') . ($customer?->name ?? 'N/A'));
    $summaryMobile = collect($customer?->mobile_numbers ?? [])->filter()->values()->implode(', ') ?: 'N/A';
    $summaryAddress = collect([$customer?->address1, $customer?->address2, $customer?->location, $customer?->district, $customer?->state])->filter()->implode(', ');
    $summaryVehicle = collect([
        $delivery?->interested_model ?: $booking?->interested_model ?: $vehicle?->model,
        $delivery?->interested_engine ?: $booking?->interested_engine ?: $vehicle?->engine_type,
        $delivery?->interested_variant ?: $booking?->interested_variant ?: $vehicle?->variant,
    ])->filter()->implode(' / ');

    $selectedTitle = old('title', $defaultValues['title']);
    $selectedName = old('name', $defaultValues['name']);
    $selectedContactType = old('contact_type', $defaultValues['contact_type']);
    $selectedEmail = old('email', $defaultValues['email']);
    $selectedDob = old('date_of_birth', $delivery?->date_of_birth ?? $booking?->date_of_birth ?? $prospect?->date_of_birth ?? '');
    $selectedDob = $selectedDob ? \Carbon\Carbon::parse($selectedDob)->format('Y-m-d') : '';
    $selectedMobile = old('mobile_numbers', $defaultValues['mobile_numbers']);
    $selectedDistrict = old('district', $defaultValues['district']);
    $selectedLocation = old('location', $defaultValues['location']);
    $selectedState = old('state', $defaultValues['state']);
    $selectedAddress1 = old('address1', $defaultValues['address1']);
    $selectedAddress2 = old('address2', $defaultValues['address2']);
    $selectedCustomerType = old('customer_type', $defaultValues['customer_type']);
    $selectedCorporateName = old('corporate_name', $defaultValues['corporate_name']);
    $selectedProfession = old('profession', $defaultValues['profession']);
    $selectedInterestedModel = old('interested_model', $defaultValues['interested_model']);
    $selectedInterestedColor = old('interested_vehicle_color', $defaultValues['interested_vehicle_color']);
    $selectedQuote = old('quote_taken', $defaultValues['quote_taken']);
    $selectedQuoteDate = old('quote_date', $defaultValues['quote_date']);
    $selectedTestDrive = old('test_drive_given', $defaultValues['test_drive_given']);
    $selectedTestDriveDate = old('test_drive_date', $defaultValues['test_drive_date']);
    $selectedTestDriveModel = old('test_drive_vehicle_model', $defaultValues['test_drive_vehicle_model']);
    $testDriveVehicleOptions = collect([$selectedInterestedModel])
        ->merge($vehicleModels)
        ->map(fn($model) => trim((string) $model))
        ->filter()
        ->unique()
        ->values();
    $isSelectedTestDriveVehicleOther = $selectedTestDriveModel === 'Other'
        || (trim((string) $selectedTestDriveModel) !== '' && !$testDriveVehicleOptions->contains($selectedTestDriveModel));
    $selectedTestDriveVehicleSelect = $isSelectedTestDriveVehicleOther
        ? 'Other'
        : ($selectedTestDriveModel ?: $testDriveVehicleOptions->first());
    $selectedTestDriveVehicleOther = old(
        'test_drive_vehicle_model_other',
        $isSelectedTestDriveVehicleOther && $selectedTestDriveModel !== 'Other' ? $selectedTestDriveModel : ''
    );
    $selectedTestDriveReasonRaw = old('test_drive_not_given_reason', $defaultValues['test_drive_not_given_reason']);
    $selectedPurchaseMode = old('purchase_mode', $defaultValues['purchase_mode']);
    $selectedFinanceForm = old('finance_form', $defaultValues['finance_form']);
    $selectedCompetition = old('interested_in_competition', $defaultValues['interested_in_competition']);
    $selectedCompetitionBrand = old('competition_brand', $defaultValues['competition_brand']);
    $selectedCompetitionModel = old('competition_model', $defaultValues['competition_model']);
    $selectedFirstTimeBuyer = old('first_time_buyer', $defaultValues['first_time_buyer']);
    $selectedExistingBrand = old('existing_vehicle_brand', $defaultValues['existing_vehicle_brand']);
    $selectedExistingModel = old('existing_vehicle_model', $defaultValues['existing_vehicle_model']);
    $selectedExistingYear = old('existing_vehicle_year', $defaultValues['existing_vehicle_year']);
    $selectedInterestedExchange = old('interested_in_exchange', $defaultValues['interested_in_exchange']);
    $selectedExchangeType = old('exchange_type', $defaultValues['exchange_type']);
    $selectedExchangePurchaseValue = old('exchange_purchase_value', $defaultValues['exchange_purchase_value']);
    $selectedExchangeBrand = old('exchange_vehicle_brand', $defaultValues['exchange_vehicle_brand']);
    $selectedExchangeModel = old('exchange_vehicle_model', $defaultValues['exchange_vehicle_model']);
    $selectedExchangeYear = old('exchange_manufacture_year', $defaultValues['exchange_manufacture_year']);
    $selectedExchangeOwnership = old('exchange_ownership', $defaultValues['exchange_ownership']);
    $selectedExchangeInsuranceRaw = old('exchange_insurance_validity', $defaultValues['exchange_insurance_validity']);
    $selectedExchangeInsuranceValidity = $selectedExchangeInsuranceRaw ? \Carbon\Carbon::parse($selectedExchangeInsuranceRaw)->format('Y-m-d') : '';
    $selectedExchangeTyreReplacements = old('exchange_tyre_replacements', $defaultValues['exchange_tyre_replacements']);
    if (is_string($selectedExchangeTyreReplacements)) {
        $selectedExchangeTyreReplacements = json_decode($selectedExchangeTyreReplacements, true) ?: [];
    }
    $selectedExchangeTyreReplacements = is_array($selectedExchangeTyreReplacements) ? $selectedExchangeTyreReplacements : [];
    $selectedExchangeColor = old('exchange_color', $defaultValues['exchange_color']);
    $selectedExchangeMileage = old('exchange_mileage_km', $defaultValues['exchange_mileage_km']);
    $selectedExchangeRegistration = old('exchange_registration_no', $defaultValues['exchange_registration_no']);
    $selectedExchangeExpectedPrice = old('exchange_expected_price', $defaultValues['exchange_expected_price']);
    $selectedExchangeQuotedPrice = old('exchange_quoted_price', $defaultValues['exchange_quoted_price']);
    $selectedExchangeDifference = old('exchange_price_difference', $defaultValues['exchange_price_difference']);
    $selectedOfferUnitPrice = old('offer_unit_price', $defaultValues['offer_unit_price']);
    $selectedOfferUnitPriceDiscount = old('offer_unit_price_discount', $defaultValues['offer_unit_price_discount']);
    $selectedOfferUnitPriceFree = old('offer_unit_price_free', (int) ($defaultValues['offer_unit_price_free'] ?? 0)) == 1;
    $selectedOfferVatAmount = old('offer_vat_amount', $defaultValues['offer_vat_amount']);
    $selectedOfferVatDiscount = old('offer_vat_discount', $defaultValues['offer_vat_discount']);
    $selectedOfferVatFree = old('offer_vat_free', (int) ($defaultValues['offer_vat_free'] ?? 0)) == 1;
    $selectedOfferTotalCost = old('offer_total_cost', $defaultValues['offer_total_cost']);
    $selectedOfferTotalDiscount = old('offer_total_discount', $defaultValues['offer_total_discount']);
    $selectedOfferFinalPrice = old('offer_final_price', $defaultValues['offer_final_price']);
    $isOfferEdit = old('edit_offer_details') === '1';
    $isBuyingEdit = old('edit_buying_details') === '1';
    $isExchangeEdit = old('edit_exchange_details') === '1';

    $offerUnitValue = (float) ($selectedOfferUnitPrice ?? 0);
    $offerVatValue = (float) ($selectedOfferVatAmount ?? 0);
    $offerUnitDiscountValue = (float) ($selectedOfferUnitPriceDiscount ?? 0);
    $offerVatDiscountValue = (float) ($selectedOfferVatDiscount ?? 0);
    $selectedOfferTotalCost = $selectedOfferTotalCost ?? ($offerUnitValue + $offerVatValue);
    $selectedOfferTotalDiscount = $selectedOfferTotalDiscount ?? ($offerUnitDiscountValue + $offerVatDiscountValue);
    $selectedOfferFinalPrice = $selectedOfferFinalPrice ?? max(0, (float) $selectedOfferTotalCost - (float) $selectedOfferTotalDiscount);
    $isPersonalEdit = old('edit_personal_details') === '1';
    $selectedPaymentReceiptBooking = $defaultValues['payment_receipt_amount_booking'];
    $selectedPaymentPreDelivery = old('payment_pre_delivery_amount', $defaultValues['payment_pre_delivery_amount']);
    $selectedPaymentDelivery = old('payment_delivery_amount', $defaultValues['payment_delivery_amount']);
    $deliveryReceiptPaymentModes = $deliveryReceiptPaymentModes ?? ['Cash', 'Cheque', 'Bank Transfer', 'Credit/Debit Card'];
    $bankOptions = $bankOptions ?? [];
    $selectedDeliveryReceipts = old('delivery_receipts', $defaultValues['delivery_receipts'] ?? []);
    $selectedDeliveryReceipts = is_array($selectedDeliveryReceipts) ? array_values($selectedDeliveryReceipts) : [];
    if (empty($selectedDeliveryReceipts)) {
        $selectedDeliveryReceipts = [[
            'receipt_name_no' => '',
            'receipt_date' => '',
            'receipt_amount' => (float) ($selectedPaymentDelivery ?? 0) > 0 ? $selectedPaymentDelivery : '',
            'payment_mode' => '',
            'receipt_type' => 'Delivery',
        ]];
    }
    $selectedPaymentFinanceProvider = old('payment_finance_provider', $defaultValues['payment_finance_provider']);
    $financeProviderKey = strtolower(str_replace(['_', ' '], '-', trim((string) $selectedPaymentFinanceProvider)));
    $selectedPaymentFinanceProvider = in_array($financeProviderKey, ['in-house', 'inhouse'], true)
        ? 'In-House'
        : ($financeProviderKey === 'other' ? 'Other' : 'Self');
    $selectedPaymentFinanceBank = old('payment_finance_bank', $defaultValues['payment_finance_bank'] ?? '');
    $selectedPaymentFinanceDisbursalAmount = old('payment_finance_disbursal_amount', $defaultValues['payment_finance_disbursal_amount'] ?? '');
    $selectedPaymentFinanceOtherReason = old('payment_finance_other_reason', $defaultValues['payment_finance_other_reason'] ?? '');
    $selectedPaymentPendingReason = old('payment_pending_reason', $defaultValues['payment_pending_reason']);
    $selectedPaymentPendingAmount = old('payment_pending_amount', $defaultValues['payment_pending_amount']);
    $selectedPaymentAgentName = old('payment_agent_name', $defaultValues['payment_agent_name']);
    $selectedPaymentAgentNumber = old('payment_agent_number', $defaultValues['payment_agent_number']);
    $selectedPaymentExpectedDate = old('payment_expected_date', $defaultValues['payment_expected_date']);
    $selectedPaymentCreditGiven = old('payment_credit_given_to_customer', $defaultValues['payment_credit_given_to_customer']);
    $selectedPaymentCreditAmount = old('payment_credit_amount_pending', $defaultValues['payment_credit_amount_pending']);
    $selectedPaymentCreditPermittedBy = old('payment_credit_permitted_by', $defaultValues['payment_credit_permitted_by']);
    $selectedPaymentCreditExpectedDate = old('payment_credit_expected_date', $defaultValues['payment_credit_expected_date']);
    $selectedReferenceTaken = old('reference_taken', (int) ($defaultValues['reference_taken'] ?? 0)) == 1;
    $selectedBrandReasons = old('selecting_brand_reasons', $defaultValues['selecting_brand_reasons'] ?? []);
    $selectedBrandReasons = is_array($selectedBrandReasons) ? $selectedBrandReasons : [];
    $selectedDateOfDelivery = old('date_of_delivery', $defaultValues['date_of_delivery']);
    $selectedChassisNumber = old('chassis_number', $defaultValues['chassis_number']);
    $selectedPendingCommitments = old('pending_commitments', $defaultValues['pending_commitments']);
    $deliveryBackStep = $currentStep === 4 && $selectedFirstTimeBuyer === 'yes'
        ? 2
        : $currentStep - 1;
    $paymentReceivedTotal = (float) ($selectedPaymentReceiptBooking ?? 0)
        + (float) ($selectedPaymentPreDelivery ?? 0)
        + (float) ($selectedPaymentDelivery ?? 0);
    $selectedPaymentPendingAmount = $selectedPaymentPendingAmount ?? max(0, (float) ($selectedOfferFinalPrice ?? 0) - $paymentReceivedTotal);
    $selectedPaymentFinalBalance = max(0, (float) ($selectedOfferFinalPrice ?? 0) - $paymentReceivedTotal);
    $selectedBookingReceipts = old('booking_receipts', is_array($booking?->booking_receipts) ? $booking->booking_receipts : []);
    $selectedBookingReceipts = is_array($selectedBookingReceipts) ? array_values($selectedBookingReceipts) : [];
    if (empty($selectedBookingReceipts) && (float) ($selectedPaymentReceiptBooking ?? 0) > 0) {
        $selectedBookingReceipts = [[
            'receipt_name_no' => '',
            'receipt_date' => '',
            'receipt_amount' => $selectedPaymentReceiptBooking,
            'payment_mode' => '',
            'receipt_type' => 'Booking',
        ]];
    }

    $vehicleColorOptions = $vehicleColorOptions ?? [];
    $testDriveNoReasons = [
        'Not interested',
        'Vehicle not available',
        'Vehicle damaged/under repair',
        'Not met in person',
        'Already driven',
        'I Did Not Offer',
        'Others',
    ];
    $isSelectedTestDriveReasonOther = $selectedTestDriveReasonRaw === 'Others'
        || (trim((string) $selectedTestDriveReasonRaw) !== '' && !in_array($selectedTestDriveReasonRaw, $testDriveNoReasons, true));
    $selectedTestDriveReason = $isSelectedTestDriveReasonOther ? 'Others' : $selectedTestDriveReasonRaw;
    $selectedTestDriveReasonOther = old(
        'test_drive_not_given_reason_other',
        $isSelectedTestDriveReasonOther && $selectedTestDriveReasonRaw !== 'Others' ? $selectedTestDriveReasonRaw : ''
    );
    $competitionMap = collect($competitionMap ?? []);
    $competitionBrands = $competitionMap->keys()->values()->all();
    $selectingBrandReasonOptions = [
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

    $documentFields = [
        'purchase_order_image' => 'Purchase Order',
        'insurance_copy_1_image' => 'Insurance Copy 1',
        'insurance_copy_2_image' => 'Insurance Copy 2',
        'pan_certificate_image' => 'PAN Certificate',
        'tin_certificate_image' => 'TIN Certificate Copy',
        'company_registration_certificate_1_image' => 'Company Registration Certificate 1',
        'company_registration_certificate_2_image' => 'Company Registration Certificate 2',
        'share_certificate_copy_1_image' => 'Share Certificate Copy 1',
        'share_certificate_copy_2_image' => 'Share Certificate Copy 2',
        'citizenship_certificate_1_image' => 'Citizenship Certificate 1',
        'citizenship_certificate_2_image' => 'Citizenship Certificate 2',
    ];
    $exchangeImageFields = [
        'blue_book_image' => 'Blue Book',
        'lot_no_image' => 'Lot No',
        'car_pic_1_image' => 'Car Picture 1',
        'car_pic_2_image' => 'Car Picture 2',
    ];

    $documentUrl = fn($field) => !empty($delivery->{$field}) ? asset('storage/' . $delivery->{$field}) : null;
    $exchangeImageUrl = fn($field) => !empty($delivery->{$field}) ? asset('storage/' . $delivery->{$field}) : null;
    $displayValue = fn($value, $fallback = 'N/A') => filled($value) ? $value : $fallback;
    $displayDate = fn($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('F d, Y') : 'N/A';
    $deliveryOwnerName = $displayValue($enquiry->user?->name, $summaryName);
    $bookingDisplayName = trim(($booking?->title ? $booking->title . ' ' : '') . ($booking?->name ?? '')) ?: $summaryName;
    $customerDisplayName = $displayValue($selectedName, $customer?->name ?: 'N/A');
    $reviewRows = [
        'delivery' => [
            'title' => 'Delivery Details',
            'rows' => [
                ['label' => 'Name', 'value' => $deliveryOwnerName],
                ['label' => 'Lead Source', 'value' => $displayValue($enquiry->lead_source)],
                ['label' => 'Source of Information', 'value' => $displayValue($enquiry->source_of_information ?: $prospect?->source_of_information)],
                ['label' => 'Model', 'value' => $displayValue($selectedInterestedModel ?: $vehicle?->model)],
                ['label' => 'Variant', 'value' => $displayValue($delivery?->interested_variant ?: $booking?->interested_variant ?: $vehicle?->variant)],
                ['label' => 'Color', 'value' => $displayValue($selectedInterestedColor)],
            ],
        ],
        'enquiry' => [
            'title' => 'Enquiry Details',
            'rows' => [
                ['label' => 'Date of Enquiry', 'value' => $displayDate($enquiry->created_at)],
                ['label' => 'Name', 'value' => $deliveryOwnerName],
            ],
        ],
        'booking' => [
            'title' => 'Booking Details',
            'rows' => [
                ['label' => 'Date of Booking', 'value' => $displayDate($booking?->created_at)],
                ['label' => 'Name', 'value' => $bookingDisplayName],
            ],
        ],
        'personal' => [
            'title' => 'Personal Details',
            'rows' => [
                ['label' => 'Customer Name', 'value' => $customerDisplayName],
                ['label' => 'Mobile No', 'value' => $summaryMobile],
                ['label' => 'Email', 'value' => $displayValue($selectedEmail)],
                ['label' => 'Address', 'value' => $displayValue($summaryAddress)],
                ['label' => 'Type of Customer', 'value' => ucfirst((string) ($selectedCustomerType ?: 'N/A'))],
                ['label' => 'Profession', 'value' => ucwords(str_replace('_', ' ', (string) ($selectedProfession ?: 'N/A')))],
            ],
        ],
        'buying' => [
            'title' => 'Buying Details',
            'rows' => [
                ['label' => 'First Time Buyer', 'value' => $selectedFirstTimeBuyer ? ucfirst($selectedFirstTimeBuyer) : 'N/A'],
                ['label' => 'Mode of Purchase', 'value' => $selectedPurchaseMode ? ucfirst($selectedPurchaseMode) : 'N/A'],
                ['label' => 'Did the customer take aquote?', 'value' => $selectedQuote ? ucfirst($selectedQuote) : 'N/A'],
            ],
        ],
        'exchange' => [
            'title' => 'Exchange Details',
            'rows' => [
                ['label' => 'Interested in Exchange', 'value' => $selectedInterestedExchange ? ucfirst($selectedInterestedExchange) : 'N/A'],
            ],
        ],
    ];
@endphp

<div class="delivery-page">
    <header class="delivery-topbar booking-topbar">
        <a href="{{ route('dashboard.main') }}" class="brand-logo-link" aria-label="Go to dashboard">
            <img src="{{ asset('icons/logo.png') }}" alt="Ideal Motors" class="brand-logo">
        </a>
    </header>

    <h1 class="workflow-form-heading">Delivery</h1>

    <div class="delivery-stepper" aria-label="Delivery workflow">
        @foreach([
            1 => 'Personal Detail',
            2 => 'Buying Details',
            3 => 'Exchange Details',
            4 => 'Offer Details',
            5 => 'Payment Details',
            6 => 'Delivery Form',
        ] as $index => $label)
            <div class="delivery-step {{ $index === $currentStep ? 'active' : ($index < $currentStep ? 'complete' : '') }}">
                <span>{{ str_pad((string) $index, 2, '0', STR_PAD_LEFT) }}</span>
                <small>{{ $label }}</small>
            </div>
        @endforeach
    </div>

    <main class="delivery-shell">
        @if(session('success'))
            <div class="delivery-flash success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="delivery-flash error">
                <strong>Please check the form:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($currentStep === 4)
            <section class="delivery-offer-page-summary">
                <h3>SUMMARY</h3>
                <p>Customer Name: <strong>{{ $summaryName }}</strong></p>
                <p>Interested in: <strong>{{ strtoupper($summaryVehicle ?: 'N/A') }}</strong></p>
            </section>
        @elseif($currentStep === 5)
            <section class="delivery-offer-page-summary">
                <h3>SUMMARY</h3>
                <p>Customer Name: <strong>{{ $summaryName }}</strong></p>
                <p>Interested in: <strong>{{ strtoupper($summaryVehicle ?: 'N/A') }}</strong></p>
            </section>
            <section class="delivery-payment-total-summary">
                <div><span>Total Final Closure Amount</span><strong>{{ number_format((float) ($selectedOfferFinalPrice ?? 0), 0) }}</strong></div>
                <div><span>Booking Amount Paid</span><strong id="paymentSummaryBookingAmount">{{ number_format((float) ($selectedPaymentReceiptBooking ?? 0), 0) }}</strong></div>
                <div><span>Total payable Amount</span><strong>{{ number_format((float) ($selectedOfferFinalPrice ?? 0), 0) }}</strong></div>
                <div><span>Pending Amount</span><strong id="paymentSummaryPendingAmount">{{ number_format((float) ($selectedPaymentPendingAmount ?? 0), 0) }}</strong></div>
            </section>
        @elseif($currentStep === 6)
        @elseif($currentStep === 2)
            <section class="delivery-summary">
                <div class="summary-icon-vehicle"><span>Interested In</span><strong>{{ $summaryVehicle ?: 'N/A' }}</strong></div>
                <div class="summary-icon-color"><span>Colour</span><strong>{{ $selectedInterestedColor ?: 'N/A' }}</strong></div>
                <div class="summary-icon-test-drive"><span>Test Driven Given</span><strong>{{ $selectedTestDrive ? ucfirst($selectedTestDrive) : 'N/A' }}</strong></div>
                <div class="summary-icon-test-drive"><span>With Test Driven</span><strong>{{ $selectedTestDriveModel ?: 'Vehicle not available' }}</strong></div>
                <div class="summary-icon-competition"><span>Interested In Competition</span><strong>{{ $selectedCompetition ? ucwords(str_replace('_', ' ', $selectedCompetition)) : 'N/A' }}</strong></div>
                <div class="summary-icon-first-buyer"><span>First Time Buyer</span><strong>{{ $selectedFirstTimeBuyer ? ucfirst($selectedFirstTimeBuyer) : 'N/A' }}</strong></div>
                <div class="summary-icon-payment"><span>Mode of Purchase</span><strong>{{ $selectedPurchaseMode ? ucfirst($selectedPurchaseMode) : 'N/A' }}</strong></div>
                <div class="summary-icon-finance"><span>Finance Form</span><strong>{{ $selectedFinanceForm ? ucwords(str_replace('_', ' ', $selectedFinanceForm)) : 'N/A' }}</strong></div>
            </section>
        @elseif($currentStep === 3)
            <section class="delivery-summary">
                <div class="summary-icon-exchange"><span>Interested in Exchange?</span><strong>{{ $selectedInterestedExchange ? ucfirst($selectedInterestedExchange) : 'N/A' }}</strong></div>
                <div class="summary-icon-exchange"><span>Exchange Type</span><strong>{{ $selectedExchangeType ? ucwords(str_replace('_', ' ', $selectedExchangeType)) : 'N/A' }}</strong></div>
                <div class="summary-icon-vehicle"><span>Vehicle</span><strong>{{ trim(($selectedExchangeBrand ?: '') . ' ' . ($selectedExchangeModel ?: '')) ?: 'N/A' }}</strong></div>
                <div class="summary-icon-calendar"><span>Manufacture Year</span><strong>{{ $selectedExchangeYear ?: 'N/A' }}</strong></div>
                <div class="summary-icon-registration"><span>Registration No</span><strong>{{ $selectedExchangeRegistration ?: 'N/A' }}</strong></div>
                <div class="summary-icon-money"><span>Expected Price</span><strong>{{ $selectedExchangeExpectedPrice !== null && $selectedExchangeExpectedPrice !== '' ? number_format((float) $selectedExchangeExpectedPrice, 2) : 'N/A' }}</strong></div>
                <div class="summary-icon-money"><span>Quoted Price</span><strong>{{ $selectedExchangeQuotedPrice !== null && $selectedExchangeQuotedPrice !== '' ? number_format((float) $selectedExchangeQuotedPrice, 2) : 'N/A' }}</strong></div>
                <div class="summary-icon-balance"><span>Difference</span><strong>{{ $selectedExchangeDifference !== null && $selectedExchangeDifference !== '' ? number_format((float) $selectedExchangeDifference, 2) : 'N/A' }}</strong></div>
            </section>
        @else
            <section class="delivery-summary">
                <div class="summary-icon-customer"><span>Name</span><strong>{{ $summaryName }}</strong></div>
                <div class="summary-icon-vehicle"><span>Interested In</span><strong>{{ $summaryVehicle ?: 'N/A' }}</strong></div>
                <div class="summary-icon-phone"><span>Mobile No.</span><strong>{{ $summaryMobile }}</strong></div>
                <div class="summary-icon-email"><span>Email</span><strong>{{ $selectedEmail ?: 'N/A' }}</strong></div>
                <div class="summary-icon-address"><span>Address</span><strong>{{ $summaryAddress ?: 'N/A' }}</strong></div>
                <div class="summary-icon-customer-type"><span>Type of Customer</span><strong>{{ ucfirst((string) ($selectedCustomerType ?: 'N/A')) }}</strong></div>
                <div class="summary-icon-profession"><span>Profession</span><strong>{{ ucwords(str_replace('_', ' ', (string) ($selectedProfession ?: 'N/A'))) }}</strong></div>
            </section>
        @endif

        <form id="deliveryForm" method="POST" action="{{ route('delivery.store', $enquiry->id) }}" enctype="multipart/form-data" class="delivery-form {{ $currentStep === 6 ? 'delivery-form-review' : '' }}">
            @csrf
            <input type="hidden" name="delivery_step" value="{{ $currentStep }}">

            @if($currentStep === 1)
            <div class="delivery-section-head">
                <h2>Personal Details</h2>
                <label class="delivery-switch">
                    <input type="checkbox" id="deliveryEditToggle" name="edit_personal_details" value="1" @checked($isPersonalEdit) aria-controls="deliveryPersonalEditPanel" aria-expanded="{{ $isPersonalEdit ? 'true' : 'false' }}">
                    <span>Edit Personal Detail</span>
                </label>
            </div>

            <section id="deliveryPersonalEditPanel" class="delivery-personal-edit-panel {{ $isPersonalEdit ? '' : 'hidden' }}" @if(!$isPersonalEdit) hidden @endif>
            <div class="delivery-grid">
                <label class="delivery-pill delivery-title">
                    <span>Title</span>
                    <select name="title" data-lockable>
                        @foreach(['Mr', 'Mrs', 'Ms', 'Dr'] as $title)
                            <option value="{{ $title }}" @selected($selectedTitle === $title)>{{ $title }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="delivery-pill delivery-name-field">
                    <span>Customer Name</span>
                    <input type="text" name="name" value="{{ $selectedName }}" data-lockable>
                </label>

                <label class="delivery-pill delivery-email-field">
                    <span>Email</span>
                    <input type="email" name="email" value="{{ $selectedEmail }}" data-lockable>
                </label>

                <label class="delivery-pill delivery-dob-field">
                    <span>DOB</span>
                    <input type="date" name="date_of_birth" value="{{ $selectedDob }}" data-lockable>
                </label>

                <label class="delivery-pill delivery-contact-no-field">
                    <span>Contact No</span>
                    <input type="hidden" name="contact_type" value="{{ $selectedContactType }}">
                    <input type="hidden" name="mobile_numbers" id="deliveryMobileNumbersValue" value="{{ $selectedMobile }}">
                    <div class="delivery-contact-list" id="deliveryContactList"></div>
                    <button type="button" class="delivery-contact-add-btn" aria-label="Add contact number">+</button>
                </label>

                <label class="delivery-pill delivery-district-field">
                    <span>District</span>
                    <input type="text" name="district" value="{{ $selectedDistrict }}" data-lockable>
                </label>

                <label class="delivery-pill delivery-location-field">
                    <span>Location</span>
                    <input type="text" name="location" value="{{ $selectedLocation }}" data-lockable>
                </label>

                <label class="delivery-pill delivery-state-field">
                    <span>State</span>
                    <input type="text" name="state" value="{{ $selectedState }}" data-lockable>
                </label>

                <label class="delivery-pill delivery-address1-field">
                    <span>Address Line 1</span>
                    <input type="text" name="address1" value="{{ $selectedAddress1 }}" data-lockable>
                </label>

                <label class="delivery-pill delivery-wide">
                    <span>Address Line 2</span>
                    <input type="text" name="address2" value="{{ $selectedAddress2 }}" data-lockable>
                </label>

            </div>

            <div class="delivery-question">
                <p>Type Of Customer</p>
                <div class="delivery-segment">
                    <label><input type="radio" name="customer_type" value="individual" @checked($selectedCustomerType === 'individual')><span>Individual</span></label>
                    <label><input type="radio" name="customer_type" value="corporate" @checked($selectedCustomerType === 'corporate')><span>Corporate</span></label>
                </div>
                <label class="delivery-pill delivery-corporate {{ $selectedCustomerType === 'corporate' ? '' : 'hidden' }}" id="deliveryCorporateNameWrap">
                    <span>Corporate Name</span>
                    <input type="text" name="corporate_name" id="deliveryCorporateNameInput" value="{{ $selectedCorporateName }}" @required($selectedCustomerType === 'corporate')>
                </label>
            </div>

            <div class="delivery-question">
                <p>Profession</p>
                <div class="delivery-segment delivery-segment-four">
                    <label><input type="radio" name="profession" value="salaried" @checked($selectedProfession === 'salaried')><span>Salaried</span></label>
                    <label><input type="radio" name="profession" value="self_employed" @checked($selectedProfession === 'self_employed')><span>Self Employed</span></label>
                    <label><input type="radio" name="profession" value="other" @checked($selectedProfession === 'other')><span>Other</span></label>
                    <label><input type="radio" name="profession" value="not_asked" @checked($selectedProfession === 'not_asked')><span>I did not ask</span></label>
                </div>
            </div>

            <div class="delivery-document-grid">
                @foreach($documentFields as $field => $label)
                    @php
                        $existingUrl = $documentUrl($field);
                        $hasExistingImage = !empty($existingUrl);
                    @endphp
                    <div
                        class="delivery-doc-tile {{ $hasExistingImage ? 'has-image' : '' }}"
                        data-image-tile
                        data-default-label="{{ $label }}"
                        data-has-existing="{{ $hasExistingImage ? '1' : '0' }}"
                    >
                        <input type="hidden" name="remove_{{ $field }}" value="0" data-remove-input>
                        <label class="delivery-image-picker">
                            <input type="file" name="{{ $field }}" accept="image/*" data-image-input>
                            <img
                                src="{{ $existingUrl ?: '' }}"
                                alt="{{ $label }} preview"
                                class="delivery-image-preview {{ $hasExistingImage ? '' : 'hidden' }}"
                                data-image-preview
                            >
                            <span data-image-label>{{ $hasExistingImage ? 'Change ' . $label : $label }}</span>
                        </label>
                        <a
                            href="{{ $existingUrl ?: '#' }}"
                            target="_blank"
                            rel="noopener"
                            class="{{ $hasExistingImage ? '' : 'hidden' }}"
                            data-view-link
                        >View</a>
                        <button
                            type="button"
                            class="delivery-image-remove {{ $hasExistingImage ? '' : 'hidden' }}"
                            data-image-remove
                        >Remove</button>
                    </div>
                @endforeach
            </div>

            <div class="delivery-more">
                <p>Add more images</p>
                @php
                    $existingExtraImages = is_array($delivery->extra_images) ? $delivery->extra_images : [];
                @endphp
                @if(!empty($existingExtraImages))
                    <div class="delivery-extra-grid delivery-existing-extra-grid">
                        @foreach($existingExtraImages as $extraImagePath)
                            @php
                                $extraImageUrl = asset('storage/' . $extraImagePath);
                            @endphp
                            <div
                                class="delivery-extra-tile has-image"
                                data-image-tile
                                data-default-label="Image {{ $loop->iteration }}"
                                data-has-existing="1"
                            >
                                <input type="hidden" name="remove_extra_images[]" value="{{ $extraImagePath }}" data-remove-input disabled>
                                <img src="{{ $extraImageUrl }}" alt="Extra image {{ $loop->iteration }} preview" class="delivery-image-preview" data-image-preview>
                                <span data-image-label>Image {{ $loop->iteration }}</span>
                                <a href="{{ $extraImageUrl }}" target="_blank" rel="noopener" data-view-link>View</a>
                                <button type="button" class="delivery-image-remove" data-image-remove>Remove</button>
                            </div>
                        @endforeach
                    </div>
                @endif
                <div class="delivery-extra-grid">
                    @for($i = 1; $i <= 3; $i++)
                        <div class="delivery-extra-tile" data-image-tile data-default-label="Image {{ $i }}" data-has-existing="0">
                            <label class="delivery-image-picker">
                                <input type="file" name="extra_images[]" accept="image/*" data-image-input>
                                <img src="" alt="Image {{ $i }} preview" class="delivery-image-preview hidden" data-image-preview>
                                <span data-image-label>Image {{ $i }}</span>
                            </label>
                            <a href="#" target="_blank" rel="noopener" class="hidden" data-view-link>View</a>
                            <button type="button" class="delivery-image-remove hidden" data-image-remove>Remove</button>
                        </div>
                    @endfor
                </div>
            </div>
            </section>
            @elseif($currentStep === 2)
            <div class="delivery-section-head">
                <h2>Buying Details</h2>
                <label class="delivery-switch">
                    <input type="checkbox" id="deliveryBuyingEditToggle" name="edit_buying_details" value="1" @checked($isBuyingEdit) aria-controls="deliveryBuyingEditPanel" aria-expanded="{{ $isBuyingEdit ? 'true' : 'false' }}">
                    <span>Edit Buying Details</span>
                </label>
            </div>

            <section id="deliveryBuyingEditPanel" class="delivery-buying-edit-panel {{ $isBuyingEdit ? '' : 'hidden' }}" @if(!$isBuyingEdit) hidden @endif>
            <div class="delivery-buying-grid">
                <label class="delivery-pill">
                    <span>Color</span>
                    <select name="interested_vehicle_color" data-buying-lockable>
                        <option value="">Select Color</option>
                        @foreach($vehicleColorOptions as $colorOption)
                            <option value="{{ $colorOption }}" @selected($selectedInterestedColor === $colorOption)>{{ $colorOption }}</option>
                        @endforeach
                        @if(!empty($selectedInterestedColor) && !in_array($selectedInterestedColor, $vehicleColorOptions, true))
                            <option value="{{ $selectedInterestedColor }}" selected>{{ $selectedInterestedColor }}</option>
                        @endif
                    </select>
                </label>
            </div>

            <div class="delivery-question">
                <p>Did the customer take a quote?</p>
                <div class="delivery-segment">
                    <label><input type="radio" name="quote_taken" value="yes" @checked($selectedQuote === 'yes')><span>Yes</span></label>
                    <label><input type="radio" name="quote_taken" value="no" @checked($selectedQuote === 'no')><span>No</span></label>
                </div>
                <label class="delivery-pill delivery-conditional" id="deliveryQuoteDateWrap">
                    <span>When?</span>
                    <input type="date" name="quote_date" value="{{ $selectedQuoteDate }}">
                </label>
            </div>

            <div class="delivery-question">
                <p>Test driven given?</p>
                <div class="delivery-segment">
                    <label><input type="radio" name="test_drive_given" value="yes" @checked($selectedTestDrive === 'yes')><span>Yes</span></label>
                    <label><input type="radio" name="test_drive_given" value="no" @checked($selectedTestDrive === 'no')><span>No</span></label>
                </div>
                <div class="delivery-buying-grid delivery-conditional" id="deliveryTestDriveYesWrap">
                    <label class="delivery-pill">
                        <span>When?</span>
                        <input type="date" name="test_drive_date" value="{{ $selectedTestDriveDate }}">
                    </label>
                    <label class="delivery-pill">
                        <span>Vehicle Used?</span>
                        <select name="test_drive_vehicle_model" id="deliveryTestDriveVehicleSelect">
                            <option value="">Select Model</option>
                            @foreach($testDriveVehicleOptions as $modelOption)
                                <option value="{{ $modelOption }}" @selected($selectedTestDriveVehicleSelect === $modelOption)>{{ $modelOption }}</option>
                            @endforeach
                            <option value="Other" @selected($selectedTestDriveVehicleSelect === 'Other')>Other</option>
                        </select>
                    </label>
                    <label class="delivery-pill delivery-wide {{ $selectedTestDriveVehicleSelect === 'Other' ? '' : 'hidden' }}" id="deliveryTestDriveVehicleOtherWrap">
                        <span>Other Details</span>
                        <input type="text" name="test_drive_vehicle_model_other" value="{{ $selectedTestDriveVehicleOther }}" placeholder="Enter vehicle details">
                    </label>
                </div>
                <label class="delivery-pill delivery-conditional" id="deliveryTestDriveNoWrap">
                    <span>Why?</span>
                    <select name="test_drive_not_given_reason" id="deliveryTestDriveNoReasonSelect">
                        <option value="">Select Reason</option>
                        @foreach($testDriveNoReasons as $reasonOption)
                            <option value="{{ $reasonOption }}" @selected($selectedTestDriveReason === $reasonOption)>{{ $reasonOption }}</option>
                        @endforeach
                    </select>
                    <span id="deliveryTestDriveNoReasonOtherWrap" class="{{ $selectedTestDriveReason === 'Others' ? '' : 'hidden' }}">
                        <span>Other Details</span>
                        <input type="text" name="test_drive_not_given_reason_other" value="{{ $selectedTestDriveReasonOther }}" placeholder="Enter reason">
                    </span>
                </label>
            </div>

            <div class="delivery-question">
                <p>Interested in Competition</p>
                <div class="delivery-segment delivery-segment-three">
                    <label><input type="radio" name="interested_in_competition" value="yes" @checked($selectedCompetition === 'yes')><span>Yes</span></label>
                    <label><input type="radio" name="interested_in_competition" value="no" @checked($selectedCompetition === 'no')><span>No</span></label>
                    <label><input type="radio" name="interested_in_competition" value="not_asked" @checked($selectedCompetition === 'not_asked')><span>I did not ask</span></label>
                </div>
                <div class="delivery-buying-grid delivery-conditional" id="deliveryCompetitionWrap">
                    <label class="delivery-pill">
                        <span>Brand</span>
                        <select name="competition_brand" id="deliveryCompetitionBrand">
                            <option value="">Select Brand</option>
                            @foreach($competitionBrands as $brandOption)
                                <option value="{{ $brandOption }}" @selected($selectedCompetitionBrand === $brandOption)>{{ $brandOption }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="delivery-pill">
                        <span>Model</span>
                        <select name="competition_model" id="deliveryCompetitionModel" data-selected-model="{{ $selectedCompetitionModel }}">
                            <option value="">Select Model</option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="delivery-question">
                <p>First time buyer?</p>
                <div class="delivery-segment">
                    <label><input type="radio" name="first_time_buyer" value="yes" @checked($selectedFirstTimeBuyer === 'yes')><span>Yes</span></label>
                    <label><input type="radio" name="first_time_buyer" value="no" @checked($selectedFirstTimeBuyer === 'no')><span>No</span></label>
                </div>
                <div class="delivery-buying-grid delivery-conditional" id="deliveryExistingVehicleWrap">
                    <label class="delivery-pill">
                        <span>Brand</span>
                        <select name="existing_vehicle_brand" id="deliveryExistingBrand">
                            <option value="">Select Brand</option>
                            @foreach($competitionBrands as $brandOption)
                                <option value="{{ $brandOption }}" @selected($selectedExistingBrand === $brandOption)>{{ $brandOption }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="delivery-pill">
                        <span>Model</span>
                        <select name="existing_vehicle_model" id="deliveryExistingModel" data-selected-model="{{ $selectedExistingModel }}">
                            <option value="">Select Model</option>
                        </select>
                    </label>
                    <label class="delivery-pill">
                        <span>Year</span>
                        <input type="number" name="existing_vehicle_year" min="1950" max="2100" value="{{ $selectedExistingYear }}">
                    </label>
                </div>
            </div>

            <div class="delivery-question">
                <p>Mode of Purchase</p>
                <div class="delivery-segment">
                    <label><input type="radio" name="purchase_mode" value="cash" @checked($selectedPurchaseMode === 'cash')><span>Cash</span></label>
                    <label><input type="radio" name="purchase_mode" value="finance" @checked($selectedPurchaseMode === 'finance')><span>Finance</span></label>
                </div>
                <label class="delivery-pill delivery-conditional" id="deliveryFinanceFormWrap">
                    <span>Finance Form</span>
                    <select name="finance_form">
                        <option value="">Select finance form</option>
                        <option value="in_house" @selected($selectedFinanceForm === 'in_house')>In House</option>
                        <option value="self" @selected($selectedFinanceForm === 'self')>Self</option>
                        <option value="other" @selected($selectedFinanceForm === 'other')>Other</option>
                    </select>
                </label>
            </div>
            </section>
            @elseif($currentStep === 3)
            <div class="delivery-section-head">
                <h2>Exchange Details</h2>
                <label class="delivery-switch">
                    <input type="checkbox" id="deliveryExchangeEditToggle" name="edit_exchange_details" value="1" @checked($isExchangeEdit) aria-controls="deliveryExchangeEditPanel" aria-expanded="{{ $isExchangeEdit ? 'true' : 'false' }}">
                    <span>Edit Exchange Details</span>
                </label>
            </div>

            <section id="deliveryExchangeEditPanel" class="delivery-exchange-edit-panel {{ $isExchangeEdit ? '' : 'hidden' }}" @if(!$isExchangeEdit) hidden @endif>
            <label class="delivery-exchange-top-label">Exchange Type</label>
            <div class="delivery-segment delivery-exchange-type-segment">
                <label><input type="radio" name="exchange_type" value="in_house" @checked($selectedExchangeType !== 'outhouse')><span>In - House</span></label>
                <label><input type="radio" name="exchange_type" value="outhouse" @checked($selectedExchangeType === 'outhouse')><span>Out- House</span></label>
            </div>

            <label class="delivery-pill delivery-exchange-purchase-row" id="deliveryExchangePurchaseRow">
                <span>Purchase Value</span>
                <input type="number" step="0.01" min="0" name="exchange_purchase_value" value="{{ $selectedExchangePurchaseValue }}" data-exchange-lockable>
            </label>

            <label class="delivery-exchange-question-label">Interested in Exchange?</label>
            <div class="delivery-segment delivery-exchange-interest-segment">
                <label><input type="radio" name="interested_in_exchange" value="yes" @checked($selectedInterestedExchange === 'yes')><span>Yes</span></label>
                <label><input type="radio" name="interested_in_exchange" value="no" @checked($selectedInterestedExchange === 'no')><span>No</span></label>
            </div>

            <div id="deliveryExchangeDetailsWrap" class="delivery-exchange-wrap">
                <div class="delivery-exchange-detail-fields">
                    <div class="delivery-exchange-vehicle-pill">
                        <span>{{ strtoupper(collect([$selectedExchangeBrand, $selectedExchangeModel])->filter()->implode(' ') ?: 'Not selected') }}</span>
                    </div>

                    <div class="delivery-exchange-brand-model-row hidden" id="deliveryExchangeBrandModelRow">
                        <label class="delivery-pill">
                            <span>Select Brand</span>
                            <select name="exchange_vehicle_brand" id="deliveryExchangeBrand" data-exchange-lockable>
                                <option value="">Select Brand</option>
                                @foreach($competitionBrands as $brandOption)
                                    <option value="{{ $brandOption }}" @selected($selectedExchangeBrand === $brandOption)>{{ $brandOption }}</option>
                                @endforeach
                                @if(!empty($selectedExchangeBrand) && !in_array($selectedExchangeBrand, $competitionBrands, true))
                                    <option value="{{ $selectedExchangeBrand }}" selected>{{ $selectedExchangeBrand }}</option>
                                @endif
                            </select>
                        </label>
                        <label class="delivery-pill">
                            <span>Select Model</span>
                            <select name="exchange_vehicle_model" id="deliveryExchangeModel" data-selected-model="{{ $selectedExchangeModel }}" data-exchange-lockable>
                                <option value="">Select Model</option>
                            </select>
                        </label>
                    </div>

                    <div class="delivery-exchange-two-col">
                        <label class="delivery-pill">
                            <span>Model Year</span>
                            <input type="number" name="exchange_manufacture_year" min="1950" max="2100" value="{{ $selectedExchangeYear }}" placeholder="Year" data-exchange-lockable>
                        </label>
                        <label class="delivery-pill">
                            <span>Select Ownership</span>
                            <select name="exchange_ownership" data-exchange-lockable>
                                <option value="">Select</option>
                                <option value="1st Owner" @selected($selectedExchangeOwnership === '1st Owner')>1st Owner</option>
                                <option value="2nd Owner" @selected($selectedExchangeOwnership === '2nd Owner')>2nd Owner</option>
                                <option value="3rd Owner" @selected($selectedExchangeOwnership === '3rd Owner')>3rd Owner</option>
                            </select>
                        </label>
                    </div>

                    <label class="delivery-pill delivery-exchange-wide">
                        <span>Insurance Validity</span>
                        <input type="date" name="exchange_insurance_validity" value="{{ $selectedExchangeInsuranceValidity }}" data-exchange-lockable>
                    </label>

                    <div class="delivery-exchange-tyre-row">
                        <span>Tyre Replacement</span>
                    </div>

                    <div class="delivery-segment delivery-exchange-tyre-segment">
                        <input type="hidden" name="exchange_tyre_replacements_present" value="1">
                        <label><input type="checkbox" name="exchange_tyre_replacements[]" value="front_lhs" @checked(in_array('front_lhs', $selectedExchangeTyreReplacements, true))><span>Front LHS</span></label>
                        <label><input type="checkbox" name="exchange_tyre_replacements[]" value="front_rhs" @checked(in_array('front_rhs', $selectedExchangeTyreReplacements, true))><span>Front RHS</span></label>
                        <label><input type="checkbox" name="exchange_tyre_replacements[]" value="rear_lhs" @checked(in_array('rear_lhs', $selectedExchangeTyreReplacements, true))><span>Rear LHS</span></label>
                        <label><input type="checkbox" name="exchange_tyre_replacements[]" value="rear_rhs" @checked(in_array('rear_rhs', $selectedExchangeTyreReplacements, true))><span>Rear RHS</span></label>
                    </div>

                    <div class="delivery-exchange-two-col">
                        <label class="delivery-pill">
                            <span>Color</span>
                            <input type="text" name="exchange_color" value="{{ $selectedExchangeColor }}" data-exchange-lockable>
                        </label>
                        <label class="delivery-pill">
                            <span>Total KM</span>
                            <input type="number" name="exchange_mileage_km" min="0" value="{{ $selectedExchangeMileage }}" placeholder="Mileage KM" data-exchange-lockable>
                        </label>
                    </div>

                    <label class="delivery-pill delivery-exchange-wide">
                        <span>Registration No</span>
                        <input type="text" name="exchange_registration_no" value="{{ $selectedExchangeRegistration }}" placeholder="Registration No" data-exchange-lockable>
                    </label>

                    <div class="delivery-exchange-price-row">
                        <input type="number" step="0.01" min="0" name="exchange_expected_price" id="deliveryExchangeExpectedPrice" value="{{ $selectedExchangeExpectedPrice }}" placeholder="Expected Price" data-exchange-lockable>
                        <input type="number" step="0.01" min="0" name="exchange_quoted_price" id="deliveryExchangeQuotedPrice" value="{{ $selectedExchangeQuotedPrice }}" placeholder="Quoted Price" data-exchange-lockable>
                        <input type="number" step="0.01" name="exchange_price_difference" id="deliveryExchangeDifference" value="{{ $selectedExchangeDifference }}" placeholder="Difference" readonly>
                    </div>
                </div>

                <div class="delivery-more">
                    <p>Add Images</p>
                    <div class="delivery-document-grid">
                        @foreach($exchangeImageFields as $field => $label)
                            @php
                                $existingUrl = $exchangeImageUrl($field);
                                $hasExistingImage = !empty($existingUrl);
                            @endphp
                            <div
                                class="delivery-doc-tile {{ $hasExistingImage ? 'has-image' : '' }}"
                                data-image-tile
                                data-default-label="{{ $label }}"
                                data-has-existing="{{ $hasExistingImage ? '1' : '0' }}"
                            >
                                <input type="hidden" name="remove_{{ $field }}" value="0" data-remove-input>
                                <label class="delivery-image-picker">
                                    <input type="file" name="{{ $field }}" accept="image/*" data-image-input>
                                    <img
                                        src="{{ $existingUrl ?: '' }}"
                                        alt="{{ $label }} preview"
                                        class="delivery-image-preview {{ $hasExistingImage ? '' : 'hidden' }}"
                                        data-image-preview
                                    >
                                    <span data-image-label>{{ $hasExistingImage ? 'Change ' . $label : $label }}</span>
                                </label>
                                <a href="{{ $existingUrl ?: '#' }}" target="_blank" rel="noopener" class="{{ $hasExistingImage ? '' : 'hidden' }}" data-view-link>View</a>
                                <button type="button" class="delivery-image-remove {{ $hasExistingImage ? '' : 'hidden' }}" data-image-remove>Remove</button>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="delivery-more">
                    <p>Additional Images</p>
                    @php
                        $existingExchangeExtraImages = is_array($delivery->exchange_extra_images) ? $delivery->exchange_extra_images : [];
                    @endphp
                    @if(!empty($existingExchangeExtraImages))
                        <div class="delivery-extra-grid delivery-existing-extra-grid">
                            @foreach($existingExchangeExtraImages as $extraImagePath)
                                @php
                                    $extraImageUrl = asset('storage/' . $extraImagePath);
                                @endphp
                                <div class="delivery-extra-tile has-image" data-image-tile data-default-label="Image {{ $loop->iteration }}" data-has-existing="1">
                                    <input type="hidden" name="remove_exchange_extra_images[]" value="{{ $extraImagePath }}" data-remove-input disabled>
                                    <img src="{{ $extraImageUrl }}" alt="Exchange extra image {{ $loop->iteration }} preview" class="delivery-image-preview" data-image-preview>
                                    <span data-image-label>Image {{ $loop->iteration }}</span>
                                    <a href="{{ $extraImageUrl }}" target="_blank" rel="noopener" data-view-link>View</a>
                                    <button type="button" class="delivery-image-remove" data-image-remove>Remove</button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="delivery-extra-grid">
                        @for($i = 1; $i <= 3; $i++)
                            <div class="delivery-extra-tile" data-image-tile data-default-label="Image {{ $i }}" data-has-existing="0">
                                <label class="delivery-image-picker">
                                    <input type="file" name="exchange_extra_images[]" accept="image/*" data-image-input>
                                    <img src="" alt="Exchange image {{ $i }} preview" class="delivery-image-preview hidden" data-image-preview>
                                    <span data-image-label>Image {{ $i }}</span>
                                </label>
                                <a href="#" target="_blank" rel="noopener" class="hidden" data-view-link>View</a>
                                <button type="button" class="delivery-image-remove hidden" data-image-remove>Remove</button>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
            </section>
            @elseif($currentStep === 4)
            <div class="delivery-section-head delivery-offer-head">
                <h2>Offer Details</h2>
                <label class="delivery-offer-toggle">
                    <input type="hidden" name="edit_offer_details" value="0">
                    <input type="checkbox" id="deliveryOfferEditToggle" name="edit_offer_details" value="1" @checked($isOfferEdit)>
                    <span>Edit Buying Details</span>
                </label>
            </div>

            <div class="delivery-offer-summary-panel" id="deliveryOfferSummaryPanel">
                <div class="delivery-offer-summary-table">
                    <div class="delivery-offer-summary-head">
                        <span></span>
                        <span>Cost</span>
                        <span>Offer</span>
                        <span>Payable</span>
                    </div>
                    <div class="delivery-offer-summary-row">
                        <strong>VAT</strong>
                        <span id="deliveryOfferSummaryVatCost">{{ number_format((float) ($selectedOfferVatAmount ?? 0), 0) }}</span>
                        <span id="deliveryOfferSummaryVatOffer">{{ number_format((float) ($selectedOfferVatDiscount ?? 0), 0) }}</span>
                        <span id="deliveryOfferSummaryVatPayable">{{ number_format(max(0, (float) ($selectedOfferVatAmount ?? 0) - (float) ($selectedOfferVatDiscount ?? 0)), 0) }}</span>
                    </div>
                    <div class="delivery-offer-summary-row">
                        <strong>Unit price (without vat)</strong>
                        <span id="deliveryOfferSummaryUnitCost">{{ number_format((float) ($selectedOfferUnitPrice ?? 0), 0) }}</span>
                        <span id="deliveryOfferSummaryUnitOffer">{{ number_format((float) ($selectedOfferUnitPriceDiscount ?? 0), 0) }}</span>
                        <span id="deliveryOfferSummaryUnitPayable">{{ number_format(max(0, (float) ($selectedOfferUnitPrice ?? 0) - (float) ($selectedOfferUnitPriceDiscount ?? 0)), 0) }}</span>
                    </div>
                </div>

                <div class="delivery-offer-summary-total">
                    <strong>Total</strong>
                    <span id="deliveryOfferSummaryTotalCost">{{ number_format((float) ($selectedOfferTotalCost ?? 0), 0) }}</span>
                    <span id="deliveryOfferSummaryTotalOffer">{{ number_format((float) ($selectedOfferTotalDiscount ?? 0), 0) }}</span>
                    <span id="deliveryOfferSummaryTotalPayable">{{ number_format((float) ($selectedOfferFinalPrice ?? 0), 0) }}</span>
                </div>

                <div class="delivery-offer-remarks">
                    <label class="delivery-offer-remarks-toggle">
                        <span>Add Remarks</span>
                        <input type="checkbox" id="deliveryOfferRemarksToggle" checked>
                        <i></i>
                    </label>
                    <textarea id="deliveryOfferRemarksText" rows="4" placeholder="Type comment here......"></textarea>
                </div>
            </div>

            <div class="delivery-offer-edit-group" id="deliveryOfferEditGroup">
                <div class="delivery-offer-card">
                    <div class="delivery-offer-card-title">Unit price (without vat)</div>
                    <div class="delivery-offer-card-amount-row">
                        <input type="number" step="0.01" min="0" name="offer_unit_price" id="delivery_offer_unit_price" value="{{ $selectedOfferUnitPrice }}">
                    </div>
                    <div class="delivery-offer-card-bottom-row">
                        <label class="delivery-offer-free-check">
                            <input type="hidden" name="offer_unit_price_free" value="0">
                            <input type="checkbox" name="offer_unit_price_free" id="delivery_offer_unit_price_free" value="1" @checked($selectedOfferUnitPriceFree)>
                            <span>Free</span>
                        </label>
                        <input type="number" step="0.01" min="0" name="offer_unit_price_discount" id="delivery_offer_unit_price_discount" value="{{ $selectedOfferUnitPriceDiscount }}" placeholder="Discount amount">
                    </div>
                </div>

                <div class="delivery-offer-card">
                    <div class="delivery-offer-card-title">VAT</div>
                    <div class="delivery-offer-card-amount-row">
                        <input type="number" step="0.01" min="0" name="offer_vat_amount" id="delivery_offer_vat_amount" value="{{ $selectedOfferVatAmount }}">
                    </div>
                    <div class="delivery-offer-card-bottom-row">
                        <label class="delivery-offer-free-check">
                            <input type="hidden" name="offer_vat_free" value="0">
                            <input type="checkbox" name="offer_vat_free" id="delivery_offer_vat_free" value="1" @checked($selectedOfferVatFree)>
                            <span>Free</span>
                        </label>
                        <input type="number" step="0.01" min="0" name="offer_vat_discount" id="delivery_offer_vat_discount" value="{{ $selectedOfferVatDiscount }}" placeholder="Discount amount">
                    </div>
                </div>

                <div class="delivery-offer-total-panel">
                    <div class="delivery-offer-total-head">
                        <span>Total</span>
                        <span>Cost</span>
                        <span>Offer</span>
                        <span>Final Offer Price</span>
                    </div>
                    <div class="delivery-offer-total-values">
                        <span></span>
                        <strong id="deliveryOfferTotalCostDisplay">{{ number_format((float) ($selectedOfferTotalCost ?? 0), 0) }}</strong>
                        <strong id="deliveryOfferTotalDiscountDisplay">{{ number_format((float) ($selectedOfferTotalDiscount ?? 0), 0) }}</strong>
                        <strong id="deliveryOfferFinalPriceDisplay">{{ number_format((float) ($selectedOfferFinalPrice ?? 0), 0) }}</strong>
                    </div>
                </div>

                <input type="hidden" name="offer_total_cost" id="delivery_offer_total_cost" value="{{ $selectedOfferTotalCost }}">
                <input type="hidden" name="offer_total_discount" id="delivery_offer_total_discount" value="{{ $selectedOfferTotalDiscount }}">
                <input type="hidden" name="offer_final_price" id="delivery_offer_final_price" value="{{ $selectedOfferFinalPrice }}">
            </div>
            @elseif($currentStep === 5)
            <div class="delivery-section-head delivery-payment-head">
                <h2>Payment Received</h2>
            </div>

            <div class="delivery-payment-section">
                <section class="delivery-payment-card delivery-payment-received">
                    <h3>Payment Received</h3>
                    <div class="delivery-payment-grid">
                        <label>
                            <span>Booking Amount Paid</span>
                            <input type="number" step="0.01" min="0" name="payment_receipt_amount_booking" id="paymentReceiptBooking" value="{{ $selectedPaymentReceiptBooking }}" readonly>
                        </label>
                        <label>
                            <span>Pre Delivery</span>
                            <input type="number" step="0.01" min="0" name="payment_pre_delivery_amount" id="paymentPreDelivery" value="{{ $selectedPaymentPreDelivery }}">
                        </label>
                        <label>
                            <span>Delivery</span>
                            <span class="delivery-payment-receipt-wrap">
                                <input type="number" step="0.01" min="0" name="payment_delivery_amount" id="paymentDelivery" value="{{ $selectedPaymentDelivery }}" readonly>
                                <button type="button" id="deliveryReceiptOpen">Add Receipts</button>
                            </span>
                        </label>
                        <div class="delivery-payment-row delivery-payment-finance-row">
                            <span>Finance</span>
                            <span class="delivery-payment-finance-wrap">
                                <input type="hidden" name="payment_finance_provider" id="paymentFinanceProvider" value="{{ $selectedPaymentFinanceProvider }}">
                                <input type="text" id="paymentFinanceProviderDisplay" value="{{ $selectedPaymentFinanceProvider }}" readonly>
                                <button type="button" id="paymentFinanceEdit" aria-label="Edit finance"></button>
                                <span class="delivery-payment-finance-editor hidden" id="paymentFinanceEditor">
                                    <span class="delivery-payment-finance-segment">
                                        @foreach(['In-House', 'Self', 'Other'] as $financeOption)
                                            <label>
                                                <input type="radio" name="payment_finance_provider_choice" value="{{ $financeOption }}" @checked($selectedPaymentFinanceProvider === $financeOption)>
                                                <span>{{ $financeOption }}</span>
                                            </label>
                                        @endforeach
                                    </span>
                                    <span class="delivery-payment-finance-self" id="paymentFinanceSelfFields">
                                        <label>
                                            <span>Select Bank</span>
                                            <select name="payment_finance_bank" id="paymentFinanceBank">
                                                <option value="">Select Bank</option>
                                                @foreach($bankOptions as $bankOption)
                                                    <option value="{{ $bankOption }}" @selected($selectedPaymentFinanceBank === $bankOption)>{{ $bankOption }}</option>
                                                @endforeach
                                                @if(!empty($selectedPaymentFinanceBank) && !in_array($selectedPaymentFinanceBank, $bankOptions, true))
                                                    <option value="{{ $selectedPaymentFinanceBank }}" selected>{{ $selectedPaymentFinanceBank }}</option>
                                                @endif
                                            </select>
                                        </label>
                                        <label>
                                            <span>Disbursal Amount</span>
                                            <input type="number" step="0.01" min="0" name="payment_finance_disbursal_amount" id="paymentFinanceDisbursalAmount" value="{{ $selectedPaymentFinanceDisbursalAmount }}" placeholder="Disbursal Amount">
                                        </label>
                                    </span>
                                    <span class="delivery-payment-finance-other hidden" id="paymentFinanceOtherFields">
                                        <label>
                                            <span>Reason</span>
                                            <input type="text" name="payment_finance_other_reason" id="paymentFinanceOtherReason" value="{{ $selectedPaymentFinanceOtherReason }}" placeholder="Reason">
                                        </label>
                                    </span>
                                </span>
                            </span>
                        </div>
                    </div>

                    <div class="delivery-pending-badge">
                        <span>Pending Amount</span>
                        <strong id="paymentPendingDisplay">{{ number_format((float) ($selectedPaymentPendingAmount ?? 0), 0) }}</strong>
                    </div>
                    <input type="hidden" name="payment_pending_amount" id="paymentPendingAmount" value="{{ $selectedPaymentPendingAmount }}">

                    <div id="deliveryReceiptModal" class="delivery-receipt-modal hidden" aria-hidden="true">
                        <div class="delivery-receipt-card" role="dialog" aria-modal="true" aria-labelledby="deliveryReceiptTitle">
                            <div class="delivery-receipt-head">
                                <h4 id="deliveryReceiptTitle">Delivery Receipts</h4>
                                <button type="button" id="deliveryReceiptClose" class="delivery-receipt-close" aria-label="Close receipt details">&times;</button>
                            </div>

                            <div id="deliveryReceiptRows" class="delivery-receipt-rows">
                                @foreach($selectedDeliveryReceipts as $receiptIndex => $receipt)
                                    <div class="delivery-receipt-row">
                                        <div>
                                            <label>Receipt Name/No</label>
                                            <input type="text" name="delivery_receipts[{{ $receiptIndex }}][receipt_name_no]" value="{{ $receipt['receipt_name_no'] ?? '' }}">
                                        </div>
                                        <div>
                                            <label>Receipt Date</label>
                                            <input type="date" name="delivery_receipts[{{ $receiptIndex }}][receipt_date]" value="{{ $receipt['receipt_date'] ?? '' }}">
                                        </div>
                                        <div>
                                            <label>Receipt Amount</label>
                                            <input type="number" min="0" step="0.01" name="delivery_receipts[{{ $receiptIndex }}][receipt_amount]" value="{{ $receipt['receipt_amount'] ?? '' }}" data-delivery-receipt-amount>
                                        </div>
                                        <div>
                                            <label>Mode of Payment</label>
                                            <select name="delivery_receipts[{{ $receiptIndex }}][payment_mode]">
                                                <option value="">Select mode</option>
                                                @foreach($deliveryReceiptPaymentModes as $paymentMode)
                                                    <option value="{{ $paymentMode }}" @selected(($receipt['payment_mode'] ?? '') === $paymentMode)>{{ $paymentMode }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label>Receipt Type</label>
                                            <input type="text" name="delivery_receipts[{{ $receiptIndex }}][receipt_type]" value="Delivery" readonly>
                                        </div>
                                        <button type="button" class="delivery-receipt-remove" aria-label="Remove receipt">Remove</button>
                                    </div>
                                @endforeach
                            </div>

                            <div class="delivery-receipt-total">
                                <span>Total Receipt Amount</span>
                                <strong id="deliveryReceiptTotalDisplay">{{ number_format((float) ($selectedPaymentDelivery ?? 0), 2) }}</strong>
                            </div>

                            <div class="delivery-receipt-actions">
                                <button type="button" id="deliveryReceiptCancel">Cancel</button>
                                <button type="button" id="deliveryReceiptSave">Save</button>
                                <button type="button" id="deliveryReceiptAddMore">Add More</button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            @elseif($currentStep === 6)
            <div class="delivery-review-stack">
                @foreach($reviewRows as $sectionKey => $section)
                    <section class="delivery-review-card delivery-review-{{ $sectionKey }}">
                        <h2>{{ $section['title'] }}</h2>
                        <div class="delivery-review-rows">
                            @foreach($section['rows'] as $row)
                                <div class="delivery-review-row">
                                    <span class="delivery-review-icon" aria-hidden="true"></span>
                                    <strong>{{ $row['label'] }}</strong>
                                    <i>:</i>
                                    <p>{{ $row['value'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <section class="delivery-review-card delivery-final-offer-card">
                    <h2>Offer Details</h2>
                    <div class="delivery-final-offer-table">
                        <div class="delivery-final-offer-head">
                            <span></span>
                            <strong>Cost</strong>
                            <strong>Offer</strong>
                            <strong>Payable</strong>
                        </div>
                        <div class="delivery-final-offer-row">
                            <strong>Vat</strong>
                            <span>{{ number_format((float) ($selectedOfferVatAmount ?? 0), 0) }}</span>
                            <span>{{ number_format((float) ($selectedOfferVatDiscount ?? 0), 0) }}</span>
                            <span>{{ number_format(max(0, (float) ($selectedOfferVatAmount ?? 0) - (float) ($selectedOfferVatDiscount ?? 0)), 0) }}</span>
                        </div>
                        <div class="delivery-final-offer-row">
                            <strong>Unit price (without vat)</strong>
                            <span>{{ number_format((float) ($selectedOfferUnitPrice ?? 0), 0) }}</span>
                            <span>{{ number_format((float) ($selectedOfferUnitPriceDiscount ?? 0), 0) }}</span>
                            <span>{{ number_format(max(0, (float) ($selectedOfferUnitPrice ?? 0) - (float) ($selectedOfferUnitPriceDiscount ?? 0)), 0) }}</span>
                        </div>
                        <div class="delivery-final-offer-total">
                            <strong>Total</strong>
                            <span>{{ number_format((float) ($selectedOfferTotalCost ?? 0), 0) }}</span>
                            <span>{{ number_format((float) ($selectedOfferTotalDiscount ?? 0), 0) }}</span>
                            <span>{{ number_format((float) ($selectedOfferFinalPrice ?? 0), 0) }}</span>
                        </div>
                    </div>
                </section>

                <section class="delivery-review-card delivery-final-payment-card">
                    <h2>Payment Details</h2>
                    <div class="delivery-final-payment-rows">
                        <div><span>Total Deal Closure Amount</span><strong>{{ number_format((float) ($selectedOfferFinalPrice ?? 0), 0) }}</strong></div>
                        <div><span>Total Payable Amount</span><strong>{{ number_format((float) ($selectedOfferFinalPrice ?? 0), 0) }}</strong></div>
                        <button type="button" class="delivery-final-receipt-trigger" data-final-receipt-type="booking">
                            <span>Receipt Amount (Booking)</span>
                            <strong>{{ number_format((float) ($selectedPaymentReceiptBooking ?? 0), 0) }}</strong>
                            <i aria-hidden="true"></i>
                        </button>
                        <button type="button" class="delivery-final-receipt-trigger" data-final-receipt-type="delivery">
                            <span>Receipt Amount (Delivery)</span>
                            <strong>{{ number_format((float) ($selectedPaymentDelivery ?? 0), 0) }}</strong>
                            <i aria-hidden="true"></i>
                        </button>
                        <div><span>Final Balance</span><strong>{{ number_format((float) ($selectedPaymentFinalBalance ?? 0), 0) }}</strong></div>
                    </div>
                </section>

                <div class="delivery-final-receipt-popup hidden" id="deliveryFinalReceiptPopup" role="dialog" aria-modal="true" aria-labelledby="deliveryFinalReceiptTitle" aria-hidden="true">
                    <div class="delivery-final-receipt-card">
                        <div class="delivery-final-receipt-head">
                            <h4 id="deliveryFinalReceiptTitle">Receipts</h4>
                            <button type="button" id="deliveryFinalReceiptClose" aria-label="Close receipt popup">&times;</button>
                        </div>
                        <div class="delivery-final-receipt-list" id="deliveryFinalReceiptBody"></div>
                        <button type="button" class="delivery-final-receipt-done" id="deliveryFinalReceiptDone">Done</button>
                    </div>
                </div>

                <div class="delivery-final-reference">
                    <span>Reference Taken</span>
                    <label class="delivery-final-switch">
                        <input type="hidden" name="reference_taken" value="0">
                        <input type="checkbox" name="reference_taken" value="1" @checked($selectedReferenceTaken)>
                        <i></i>
                    </label>
                </div>

                <section class="delivery-final-form-card">
                    <h2>Top Reason For Selecting Brand</h2>
                    <div class="delivery-final-reasons">
                        @foreach($selectingBrandReasonOptions as $reasonOption)
                            <label>
                                <input type="checkbox" name="selecting_brand_reasons[]" value="{{ $reasonOption }}" @checked(in_array($reasonOption, $selectedBrandReasons, true))>
                                <span>{{ $reasonOption }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="delivery-final-input-grid">
                        <label>
                            <span>Date of Delivery</span>
                            <input type="date" name="date_of_delivery" value="{{ $selectedDateOfDelivery }}">
                        </label>
                        <label>
                            <span>Chassis Number</span>
                            <input type="text" name="chassis_number" value="{{ $selectedChassisNumber }}" placeholder="Chassis Number" required>
                        </label>
                        <label>
                            <span>Pending Commitments</span>
                            <input type="text" name="pending_commitments" value="{{ $selectedPendingCommitments }}" placeholder="Pending Commitments">
                        </label>
                    </div>
                </section>
            </div>
            @endif

        </form>

        <div class="delivery-actions {{ $currentStep === 1 ? 'no-back' : '' }} {{ $currentStep === 6 ? 'delivery-final-actions' : '' }}">
            @if($currentStep > 1)
                <a href="{{ route('delivery.show', ['enquiry' => $enquiry->id, 'step' => $deliveryBackStep]) }}" class="delivery-action back">Back</a>
            @endif
            @if($currentStep !== 6)
                <button type="submit" form="deliveryForm" name="action_type" value="save_exit" class="delivery-action save-exit">Save &amp; Exit</button>
            @endif
            @if($currentStep === 6)
                <button type="submit" form="deliveryForm" name="action_type" value="submit" class="delivery-action save-next delivery-submit-action">Deliver Now</button>
            @else
                <button type="submit" form="deliveryForm" name="action_type" value="save_next" class="delivery-action save-next" id="deliverySaveNextButton" @if($currentStep === 5) data-requires-pending-zero="1" title="Pending Amount must be 0 before Save & Next" aria-disabled="{{ (float) ($selectedPaymentPendingAmount ?? 0) > 0 ? 'true' : 'false' }}" @endif>Save &amp; Next</button>
            @endif
        </div>
    </main>
</div>

@if($currentStep === 5 || session('delivery_pending_block_popup'))
    <div class="delivery-submit-popup {{ session('delivery_pending_block_popup') ? '' : 'hidden' }}" id="deliveryPendingBlockPopup" role="dialog" aria-modal="true" aria-labelledby="deliveryPendingBlockTitle" aria-hidden="{{ session('delivery_pending_block_popup') ? 'false' : 'true' }}" @if(!session('delivery_pending_block_popup')) hidden @endif>
        <div class="delivery-submit-popup-card">
            <div class="delivery-submit-icon delivery-submit-icon-warning" aria-hidden="true">!</div>
            <h4 id="deliveryPendingBlockTitle">Pending Amount Required</h4>
            <p>Until Pending Amount is 0, you cannot go ahead.</p>
            <button type="button" class="delivery-submit-popup-btn" id="deliveryPendingBlockOk">OK</button>
        </div>
    </div>
@endif

@if(session('delivery_submitted_popup'))
    <div class="delivery-submit-popup" id="deliverySubmitPopup" role="dialog" aria-modal="true" aria-labelledby="deliverySubmitTitle">
        <div class="delivery-submit-popup-card" data-dashboard-url="{{ route('dashboard.main') }}">
            <div class="delivery-submit-icon" aria-hidden="true">&#10003;</div>
            <h4 id="deliverySubmitTitle">Delivery Submitted Successfully</h4>
            <p>{{ session('delivery_submitted_message', 'Delivery Submitted Successfully.') }}</p>
            <button type="button" class="delivery-submit-popup-btn" id="deliverySubmitPopupOk">OK</button>
        </div>
    </div>
@endif

@if(session('delivery_offer_summary_popup'))
    <div
        class="delivery-offer-popup"
        id="deliveryOfferPopup"
        role="dialog"
        aria-modal="true"
        aria-labelledby="deliveryOfferPopupTitle"
        data-next-url="{{ session('delivery_offer_summary_next_url', route('delivery.show', ['enquiry' => $enquiry->id, 'step' => 5])) }}"
    >
        <div class="delivery-offer-popup-card">
            <h3 id="deliveryOfferPopupTitle">SUMMARY</h3>
            <div class="delivery-offer-popup-customer">
                <p>Customer Name: <strong>{{ $summaryName }}</strong></p>
                <p>Interested in: <strong>{{ strtoupper($summaryVehicle ?: 'N/A') }}</strong></p>
            </div>

            <div class="delivery-offer-popup-table">
                <div class="delivery-offer-popup-head">
                    <span></span>
                    <span>Cost</span>
                    <span>Offer</span>
                    <span>Payable</span>
                </div>
                <div class="delivery-offer-popup-row">
                    <strong>VAT</strong>
                    <span>{{ number_format((float) ($selectedOfferVatAmount ?? 0), 0) }}</span>
                    <span>{{ number_format((float) ($selectedOfferVatDiscount ?? 0), 0) }}</span>
                    <span>{{ number_format(max(0, (float) ($selectedOfferVatAmount ?? 0) - (float) ($selectedOfferVatDiscount ?? 0)), 0) }}</span>
                </div>
                <div class="delivery-offer-popup-row">
                    <strong>Unit price (without vat)</strong>
                    <span>{{ number_format((float) ($selectedOfferUnitPrice ?? 0), 0) }}</span>
                    <span>{{ number_format((float) ($selectedOfferUnitPriceDiscount ?? 0), 0) }}</span>
                    <span>{{ number_format(max(0, (float) ($selectedOfferUnitPrice ?? 0) - (float) ($selectedOfferUnitPriceDiscount ?? 0)), 0) }}</span>
                </div>
            </div>

            <div class="delivery-offer-popup-total">
                <strong>Total</strong>
                <span>{{ number_format((float) ($selectedOfferTotalCost ?? 0), 0) }}</span>
                <span>{{ number_format((float) ($selectedOfferTotalDiscount ?? 0), 0) }}</span>
                <span>{{ number_format((float) ($selectedOfferFinalPrice ?? 0), 0) }}</span>
            </div>

            <button type="button" class="delivery-offer-popup-ok" id="deliveryOfferPopupOk">OK</button>
        </div>
    </div>
@endif

<script>
(() => {
    const editToggle = document.getElementById('deliveryEditToggle');
    const personalEditPanel = document.getElementById('deliveryPersonalEditPanel');
    const deliveryContactAddButton = document.querySelector('.delivery-contact-add-btn');
    const deliveryContactList = document.getElementById('deliveryContactList');
    const deliveryMobileNumbersValue = document.getElementById('deliveryMobileNumbersValue');

    const deliveryContactValues = () => Array.from(deliveryContactList?.querySelectorAll('[data-delivery-contact-input]') || [])
        .map((field) => field.value.trim())
        .filter(Boolean);

    const syncDeliveryContactValue = () => {
        if (deliveryMobileNumbersValue) {
            deliveryMobileNumbersValue.value = deliveryContactValues().join(', ');
        }
    };

    const syncDeliveryContactControls = () => {
        const editable = Boolean(editToggle?.checked);
        const rows = Array.from(deliveryContactList?.querySelectorAll('.delivery-contact-row') || []);

        rows.forEach((row) => {
            const input = row.querySelector('[data-delivery-contact-input]');
            const remove = row.querySelector('.delivery-contact-remove');

            if (input) {
                input.readOnly = !editable;
                input.classList.toggle('delivery-locked', !editable);
            }
            if (remove) {
                remove.disabled = !editable || rows.length <= 1;
            }
        });

        if (deliveryContactAddButton) {
            deliveryContactAddButton.disabled = !editable;
        }
    };

    const addDeliveryContactRow = (value = '') => {
        if (!deliveryContactList) return;

        const row = document.createElement('div');
        row.className = 'delivery-contact-row';

        const input = document.createElement('input');
        input.type = 'text';
        input.value = value;
        input.placeholder = 'Contact No';
        input.setAttribute('data-delivery-contact-input', '');
        input.addEventListener('input', syncDeliveryContactValue);

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'delivery-contact-remove';
        remove.setAttribute('aria-label', 'Remove contact number');
        remove.textContent = 'x';
        remove.addEventListener('click', () => {
            const rows = deliveryContactList.querySelectorAll('.delivery-contact-row');
            if (rows.length <= 1) {
                input.value = '';
            } else {
                row.remove();
            }
            syncDeliveryContactValue();
            syncDeliveryContactControls();
        });

        row.append(input, remove);
        deliveryContactList.appendChild(row);
        syncDeliveryContactControls();
    };

    if (deliveryContactList && deliveryMobileNumbersValue) {
        const initialContacts = deliveryMobileNumbersValue.value
            .split(',')
            .map((mobile) => mobile.trim())
            .filter(Boolean);

        (initialContacts.length ? initialContacts : ['']).forEach((mobile) => addDeliveryContactRow(mobile));
        syncDeliveryContactValue();
    }

    const syncLockable = () => {
        const editable = Boolean(editToggle?.checked);
        if (personalEditPanel) {
            personalEditPanel.hidden = !editable;
            personalEditPanel.classList.toggle('hidden', !editable);
        }
        editToggle?.setAttribute('aria-expanded', editable ? 'true' : 'false');

        document.querySelectorAll('[data-lockable]').forEach((field) => {
            field.classList.toggle('delivery-locked', !editable);
            if (field.tagName.toLowerCase() === 'input') {
                field.readOnly = !editable;
            }
        });
        syncDeliveryContactControls();
    };

    if (editToggle) {
        editToggle.addEventListener('change', syncLockable);
        syncLockable();
    }

    deliveryContactAddButton?.addEventListener('click', () => {
        addDeliveryContactRow();
        syncDeliveryContactValue();
        deliveryContactList?.querySelector('.delivery-contact-row:last-child input')?.focus();
    });

    const buyingEditToggle = document.getElementById('deliveryBuyingEditToggle');
    const buyingEditPanel = document.getElementById('deliveryBuyingEditPanel');
    const buyingLockableFields = Array.from(document.querySelectorAll('[data-buying-lockable]'));

    const syncBuyingLockable = () => {
        const editable = Boolean(buyingEditToggle?.checked);
        if (buyingEditPanel) {
            buyingEditPanel.hidden = !editable;
            buyingEditPanel.classList.toggle('hidden', !editable);
        }
        buyingEditToggle?.setAttribute('aria-expanded', editable ? 'true' : 'false');

        buyingLockableFields.forEach((field) => {
            field.classList.toggle('delivery-locked', !editable);
        });
    };

    buyingEditToggle?.addEventListener('change', syncBuyingLockable);
    syncBuyingLockable();

    const deliveryOfferEditToggle = document.getElementById('deliveryOfferEditToggle');
    const deliveryOfferSummaryPanel = document.getElementById('deliveryOfferSummaryPanel');
    const deliveryOfferEditGroup = document.getElementById('deliveryOfferEditGroup');
    const deliveryOfferUnitPriceInput = document.getElementById('delivery_offer_unit_price');
    const deliveryOfferUnitPriceDiscountInput = document.getElementById('delivery_offer_unit_price_discount');
    const deliveryOfferUnitPriceFreeInput = document.getElementById('delivery_offer_unit_price_free');
    const deliveryOfferVatAmountInput = document.getElementById('delivery_offer_vat_amount');
    const deliveryOfferVatDiscountInput = document.getElementById('delivery_offer_vat_discount');
    const deliveryOfferVatFreeInput = document.getElementById('delivery_offer_vat_free');
    const deliveryOfferTotalCostInput = document.getElementById('delivery_offer_total_cost');
    const deliveryOfferTotalDiscountInput = document.getElementById('delivery_offer_total_discount');
    const deliveryOfferFinalPriceInput = document.getElementById('delivery_offer_final_price');
    const deliveryOfferTotalCostDisplay = document.getElementById('deliveryOfferTotalCostDisplay');
    const deliveryOfferTotalDiscountDisplay = document.getElementById('deliveryOfferTotalDiscountDisplay');
    const deliveryOfferFinalPriceDisplay = document.getElementById('deliveryOfferFinalPriceDisplay');
    const deliveryOfferSummaryVatCost = document.getElementById('deliveryOfferSummaryVatCost');
    const deliveryOfferSummaryVatOffer = document.getElementById('deliveryOfferSummaryVatOffer');
    const deliveryOfferSummaryVatPayable = document.getElementById('deliveryOfferSummaryVatPayable');
    const deliveryOfferSummaryUnitCost = document.getElementById('deliveryOfferSummaryUnitCost');
    const deliveryOfferSummaryUnitOffer = document.getElementById('deliveryOfferSummaryUnitOffer');
    const deliveryOfferSummaryUnitPayable = document.getElementById('deliveryOfferSummaryUnitPayable');
    const deliveryOfferSummaryTotalCost = document.getElementById('deliveryOfferSummaryTotalCost');
    const deliveryOfferSummaryTotalOffer = document.getElementById('deliveryOfferSummaryTotalOffer');
    const deliveryOfferSummaryTotalPayable = document.getElementById('deliveryOfferSummaryTotalPayable');
    const deliveryOfferRemarksToggle = document.getElementById('deliveryOfferRemarksToggle');
    const deliveryOfferRemarksText = document.getElementById('deliveryOfferRemarksText');

    const deliveryOfferMoney = (value) => {
        const parsed = parseFloat(value || '0');
        return Number.isNaN(parsed) ? 0 : Math.max(0, parsed);
    };

    const formatDeliveryOfferMoney = (value) => Math.round(deliveryOfferMoney(value)).toLocaleString('en-US');

    const syncDeliveryOfferReadonlyState = () => {
        if (!deliveryOfferEditToggle || !deliveryOfferEditGroup) {
            return;
        }

        const editable = deliveryOfferEditToggle.checked;
        deliveryOfferEditGroup.classList.toggle('hidden', !editable);
        deliveryOfferSummaryPanel?.classList.toggle('hidden', editable);

        deliveryOfferEditGroup
            .querySelectorAll('input[type="number"], input[type="checkbox"]')
            .forEach((field) => {
                if (
                    field === deliveryOfferTotalCostInput ||
                    field === deliveryOfferTotalDiscountInput ||
                    field === deliveryOfferFinalPriceInput
                ) {
                    field.readOnly = true;
                    return;
                }

                if (field.type === 'checkbox') {
                    field.disabled = !editable;
                } else {
                    field.readOnly = !editable;
                }
            });
    };

    const syncDeliveryOfferRemarksState = () => {
        if (!deliveryOfferRemarksToggle || !deliveryOfferRemarksText) {
            return;
        }

        deliveryOfferRemarksText.classList.toggle('hidden', !deliveryOfferRemarksToggle.checked);
    };

    const syncDeliveryOfferTotals = () => {
        if (!deliveryOfferUnitPriceInput || !deliveryOfferVatAmountInput) {
            return;
        }

        const unit = deliveryOfferMoney(deliveryOfferUnitPriceInput.value);
        const vat = deliveryOfferMoney(deliveryOfferVatAmountInput.value);
        let unitDiscount = deliveryOfferMoney(deliveryOfferUnitPriceDiscountInput?.value);
        let vatDiscount = deliveryOfferMoney(deliveryOfferVatDiscountInput?.value);
        const unitFree = Boolean(deliveryOfferUnitPriceFreeInput?.checked);
        const vatFree = Boolean(deliveryOfferVatFreeInput?.checked);

        if (unitFree) {
            unitDiscount = unit;
            if (deliveryOfferUnitPriceDiscountInput) {
                deliveryOfferUnitPriceDiscountInput.value = unit.toFixed(2);
            }
        } else {
            if (unitDiscount > unit) {
                unitDiscount = unit;
                if (deliveryOfferUnitPriceDiscountInput) {
                    deliveryOfferUnitPriceDiscountInput.value = unitDiscount.toFixed(2);
                }
            }
        }

        if (vatFree) {
            vatDiscount = vat;
            if (deliveryOfferVatDiscountInput) {
                deliveryOfferVatDiscountInput.value = vat.toFixed(2);
            }
        } else {
            if (vatDiscount > vat) {
                vatDiscount = vat;
                if (deliveryOfferVatDiscountInput) {
                    deliveryOfferVatDiscountInput.value = vatDiscount.toFixed(2);
                }
            }
        }

        const totalCost = unit + vat;
        const totalDiscount = unitDiscount + vatDiscount;
        const finalPrice = Math.max(0, totalCost - totalDiscount);

        if (deliveryOfferTotalCostInput) deliveryOfferTotalCostInput.value = totalCost.toFixed(2);
        if (deliveryOfferTotalDiscountInput) deliveryOfferTotalDiscountInput.value = totalDiscount.toFixed(2);
        if (deliveryOfferFinalPriceInput) deliveryOfferFinalPriceInput.value = finalPrice.toFixed(2);

        if (deliveryOfferTotalCostDisplay) deliveryOfferTotalCostDisplay.textContent = formatDeliveryOfferMoney(totalCost);
        if (deliveryOfferTotalDiscountDisplay) deliveryOfferTotalDiscountDisplay.textContent = formatDeliveryOfferMoney(totalDiscount);
        if (deliveryOfferFinalPriceDisplay) deliveryOfferFinalPriceDisplay.textContent = formatDeliveryOfferMoney(finalPrice);

        if (deliveryOfferSummaryVatCost) deliveryOfferSummaryVatCost.textContent = formatDeliveryOfferMoney(vat);
        if (deliveryOfferSummaryVatOffer) deliveryOfferSummaryVatOffer.textContent = formatDeliveryOfferMoney(vatDiscount);
        if (deliveryOfferSummaryVatPayable) deliveryOfferSummaryVatPayable.textContent = formatDeliveryOfferMoney(Math.max(0, vat - vatDiscount));
        if (deliveryOfferSummaryUnitCost) deliveryOfferSummaryUnitCost.textContent = formatDeliveryOfferMoney(unit);
        if (deliveryOfferSummaryUnitOffer) deliveryOfferSummaryUnitOffer.textContent = formatDeliveryOfferMoney(unitDiscount);
        if (deliveryOfferSummaryUnitPayable) deliveryOfferSummaryUnitPayable.textContent = formatDeliveryOfferMoney(Math.max(0, unit - unitDiscount));
        if (deliveryOfferSummaryTotalCost) deliveryOfferSummaryTotalCost.textContent = formatDeliveryOfferMoney(totalCost);
        if (deliveryOfferSummaryTotalOffer) deliveryOfferSummaryTotalOffer.textContent = formatDeliveryOfferMoney(totalDiscount);
        if (deliveryOfferSummaryTotalPayable) deliveryOfferSummaryTotalPayable.textContent = formatDeliveryOfferMoney(finalPrice);
    };

    deliveryOfferEditToggle?.addEventListener('change', syncDeliveryOfferReadonlyState);
    deliveryOfferRemarksToggle?.addEventListener('change', syncDeliveryOfferRemarksState);
    [
        deliveryOfferUnitPriceInput,
        deliveryOfferUnitPriceDiscountInput,
        deliveryOfferVatAmountInput,
        deliveryOfferVatDiscountInput,
    ].forEach((field) => field?.addEventListener('input', syncDeliveryOfferTotals));
    [deliveryOfferUnitPriceFreeInput, deliveryOfferVatFreeInput].forEach((field) => {
        field?.addEventListener('change', syncDeliveryOfferTotals);
    });
    syncDeliveryOfferReadonlyState();
    syncDeliveryOfferRemarksState();
    syncDeliveryOfferTotals();

    const paymentTotalPayable = {{ (float) ($selectedOfferFinalPrice ?? 0) }};
    const paymentReceiptBooking = document.getElementById('paymentReceiptBooking');
    const paymentPreDelivery = document.getElementById('paymentPreDelivery');
    const paymentDelivery = document.getElementById('paymentDelivery');
    const paymentPendingDisplay = document.getElementById('paymentPendingDisplay');
    const paymentPendingAmount = document.getElementById('paymentPendingAmount');
    const deliverySaveNextButton = document.getElementById('deliverySaveNextButton');
    const deliveryPendingBlockPopup = document.getElementById('deliveryPendingBlockPopup');
    const deliveryPendingBlockOk = document.getElementById('deliveryPendingBlockOk');
    const paymentSummaryBookingAmount = document.getElementById('paymentSummaryBookingAmount');
    const paymentSummaryPendingAmount = document.getElementById('paymentSummaryPendingAmount');
    const paymentFinanceProvider = document.getElementById('paymentFinanceProvider');
    const paymentFinanceProviderDisplay = document.getElementById('paymentFinanceProviderDisplay');
    const paymentFinanceEdit = document.getElementById('paymentFinanceEdit');
    const paymentFinanceEditor = document.getElementById('paymentFinanceEditor');
    const paymentFinanceSelfFields = document.getElementById('paymentFinanceSelfFields');
    const paymentFinanceOtherFields = document.getElementById('paymentFinanceOtherFields');
    const paymentFinanceChoices = Array.from(document.querySelectorAll('input[name="payment_finance_provider_choice"]'));
    const paymentFinanceBank = document.getElementById('paymentFinanceBank');
    const paymentFinanceDisbursalAmount = document.getElementById('paymentFinanceDisbursalAmount');
    const paymentFinanceOtherReason = document.getElementById('paymentFinanceOtherReason');
    const finalReceiptPopup = document.getElementById('deliveryFinalReceiptPopup');
    const finalReceiptTitle = document.getElementById('deliveryFinalReceiptTitle');
    const finalReceiptBody = document.getElementById('deliveryFinalReceiptBody');
    const finalReceiptClose = document.getElementById('deliveryFinalReceiptClose');
    const finalReceiptDone = document.getElementById('deliveryFinalReceiptDone');
    const finalReceiptTriggers = Array.from(document.querySelectorAll('[data-final-receipt-type]'));
    const finalReceiptData = {
        booking: @json($selectedBookingReceipts),
        delivery: @json($selectedDeliveryReceipts),
    };
    const deliveryReceiptOpen = document.getElementById('deliveryReceiptOpen');
    const deliveryReceiptModal = document.getElementById('deliveryReceiptModal');
    const deliveryReceiptRows = document.getElementById('deliveryReceiptRows');
    const deliveryReceiptClose = document.getElementById('deliveryReceiptClose');
    const deliveryReceiptCancel = document.getElementById('deliveryReceiptCancel');
    const deliveryReceiptSave = document.getElementById('deliveryReceiptSave');
    const deliveryReceiptAddMore = document.getElementById('deliveryReceiptAddMore');
    const deliveryReceiptTotalDisplay = document.getElementById('deliveryReceiptTotalDisplay');
    const deliveryReceiptPaymentModes = @json($deliveryReceiptPaymentModes);
    let deliveryReceiptSnapshot = null;

    const escapeDeliveryReceiptValue = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    const deliveryReceiptRowTemplate = (index, values = {}) => {
        const paymentOptions = ['<option value="">Select mode</option>']
            .concat(deliveryReceiptPaymentModes.map((mode) => {
                const selected = mode === (values.payment_mode || '') ? ' selected' : '';
                return `<option value="${mode}"${selected}>${mode}</option>`;
            }))
            .join('');

        return `
            <div class="delivery-receipt-row">
                <div>
                    <label>Receipt Name/No</label>
                    <input type="text" name="delivery_receipts[${index}][receipt_name_no]" value="${escapeDeliveryReceiptValue(values.receipt_name_no)}">
                </div>
                <div>
                    <label>Receipt Date</label>
                    <input type="date" name="delivery_receipts[${index}][receipt_date]" value="${escapeDeliveryReceiptValue(values.receipt_date)}">
                </div>
                <div>
                    <label>Receipt Amount</label>
                    <input type="number" min="0" step="0.01" name="delivery_receipts[${index}][receipt_amount]" value="${escapeDeliveryReceiptValue(values.receipt_amount)}" data-delivery-receipt-amount>
                </div>
                <div>
                    <label>Mode of Payment</label>
                    <select name="delivery_receipts[${index}][payment_mode]">${paymentOptions}</select>
                </div>
                <div>
                    <label>Receipt Type</label>
                    <input type="text" name="delivery_receipts[${index}][receipt_type]" value="Delivery" readonly>
                </div>
                <button type="button" class="delivery-receipt-remove" aria-label="Remove receipt">Remove</button>
            </div>
        `;
    };

    const serializeDeliveryReceiptRows = () => {
        if (!deliveryReceiptRows) return [];

        return Array.from(deliveryReceiptRows.querySelectorAll('.delivery-receipt-row')).map((row) => ({
            receipt_name_no: row.querySelector('[name$="[receipt_name_no]"]')?.value || '',
            receipt_date: row.querySelector('[name$="[receipt_date]"]')?.value || '',
            receipt_amount: row.querySelector('[name$="[receipt_amount]"]')?.value || '',
            payment_mode: row.querySelector('[name$="[payment_mode]"]')?.value || '',
            receipt_type: 'Delivery',
        }));
    };

    const syncDeliveryReceiptTotal = () => {
        if (!deliveryReceiptRows) return;

        const total = Array.from(deliveryReceiptRows.querySelectorAll('[data-delivery-receipt-amount]'))
            .reduce((sum, input) => sum + (parseFloat(input.value || '0') || 0), 0);

        if (paymentDelivery) {
            paymentDelivery.value = total.toFixed(2);
        }
        if (deliveryReceiptTotalDisplay) {
            deliveryReceiptTotalDisplay.textContent = total.toFixed(2);
        }
        syncPaymentPendingAmount();
    };

    const renderDeliveryReceiptRows = (rows) => {
        if (!deliveryReceiptRows) return;

        const safeRows = rows.length ? rows : [{}];
        deliveryReceiptRows.innerHTML = safeRows
            .map((row, index) => deliveryReceiptRowTemplate(index, row))
            .join('');
        syncDeliveryReceiptTotal();
    };

    const openDeliveryReceiptModal = () => {
        if (!deliveryReceiptModal) return;

        deliveryReceiptSnapshot = serializeDeliveryReceiptRows();
        deliveryReceiptModal.hidden = false;
        deliveryReceiptModal.classList.remove('hidden');
        deliveryReceiptModal.setAttribute('aria-hidden', 'false');
        deliveryReceiptModal.querySelector('input, select, button')?.focus();
    };

    const closeDeliveryReceiptModal = () => {
        if (!deliveryReceiptModal) return;

        deliveryReceiptModal.classList.add('hidden');
        deliveryReceiptModal.hidden = true;
        deliveryReceiptModal.setAttribute('aria-hidden', 'true');
    };

    const syncPaymentPendingAmount = () => {
        if (!paymentPendingDisplay || !paymentPendingAmount) {
            return;
        }

        const received = [paymentReceiptBooking, paymentPreDelivery, paymentDelivery].reduce((sum, field) => {
            const value = parseFloat(field?.value || '0');
            return sum + (Number.isNaN(value) ? 0 : Math.max(0, value));
        }, 0);
        const pending = Math.max(0, paymentTotalPayable - received);
        const bookingPaid = parseFloat(paymentReceiptBooking?.value || '0');

        if (paymentSummaryBookingAmount) {
            paymentSummaryBookingAmount.textContent = Math.round(Number.isNaN(bookingPaid) ? 0 : Math.max(0, bookingPaid)).toLocaleString('en-US');
        }
        if (paymentSummaryPendingAmount) {
            paymentSummaryPendingAmount.textContent = Math.round(pending).toLocaleString('en-US');
        }
        paymentPendingDisplay.textContent = Math.round(pending).toLocaleString('en-US');
        paymentPendingAmount.value = pending.toFixed(2);

        if (deliverySaveNextButton?.dataset.requiresPendingZero === '1') {
            const canSaveNext = pending <= 0.009;
            deliverySaveNextButton.dataset.pendingBlocked = canSaveNext ? '0' : '1';
            deliverySaveNextButton.setAttribute('aria-disabled', canSaveNext ? 'false' : 'true');
            deliverySaveNextButton.classList.toggle('is-blocked', !canSaveNext);
            deliverySaveNextButton.title = canSaveNext ? '' : 'Pending Amount must be 0 before Save & Next';
        }
    };

    const openPendingBlockPopup = () => {
        if (!deliveryPendingBlockPopup) return;

        deliveryPendingBlockPopup.hidden = false;
        deliveryPendingBlockPopup.classList.remove('hidden');
        deliveryPendingBlockPopup.setAttribute('aria-hidden', 'false');
        document.body.classList.add('delivery-modal-open');
        deliveryPendingBlockOk?.focus();
    };

    const closePendingBlockPopup = () => {
        if (!deliveryPendingBlockPopup) return;

        deliveryPendingBlockPopup.hidden = true;
        deliveryPendingBlockPopup.classList.add('hidden');
        deliveryPendingBlockPopup.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('delivery-modal-open');
        deliverySaveNextButton?.focus();
    };

    deliverySaveNextButton?.addEventListener('click', (event) => {
        if (deliverySaveNextButton.dataset.requiresPendingZero === '1' && deliverySaveNextButton.dataset.pendingBlocked === '1') {
            event.preventDefault();
            event.stopPropagation();
            openPendingBlockPopup();
        }
    });

    deliveryPendingBlockOk?.addEventListener('click', closePendingBlockPopup);
    deliveryPendingBlockPopup?.addEventListener('click', (event) => {
        if (event.target === deliveryPendingBlockPopup) {
            closePendingBlockPopup();
        }
    });
    if (deliveryPendingBlockPopup && !deliveryPendingBlockPopup.hidden) {
        document.body.classList.add('delivery-modal-open');
        deliveryPendingBlockOk?.focus();
    }

    [paymentReceiptBooking, paymentPreDelivery, paymentDelivery].forEach((field) => {
        field?.addEventListener('input', syncPaymentPendingAmount);
    });
    syncPaymentPendingAmount();
    deliveryReceiptOpen?.addEventListener('click', openDeliveryReceiptModal);
    deliveryReceiptRows?.addEventListener('input', (event) => {
        if (event.target instanceof Element && event.target.matches('[data-delivery-receipt-amount]')) {
            syncDeliveryReceiptTotal();
        }
    });
    deliveryReceiptRows?.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) return;

        const removeButton = target.closest('.delivery-receipt-remove');
        if (!removeButton) return;

        const row = removeButton.closest('.delivery-receipt-row');
        const rows = deliveryReceiptRows.querySelectorAll('.delivery-receipt-row');
        if (row && rows.length > 1) {
            row.remove();
        } else if (row) {
            row.querySelectorAll('input:not([readonly]), select').forEach((field) => {
                field.value = '';
            });
        }

        renderDeliveryReceiptRows(serializeDeliveryReceiptRows());
    });
    deliveryReceiptAddMore?.addEventListener('click', () => {
        const rows = serializeDeliveryReceiptRows();
        rows.push({});
        renderDeliveryReceiptRows(rows);
    });
    deliveryReceiptSave?.addEventListener('click', () => {
        syncDeliveryReceiptTotal();
        closeDeliveryReceiptModal();
    });

    const cancelDeliveryReceiptChanges = () => {
        renderDeliveryReceiptRows(Array.isArray(deliveryReceiptSnapshot) ? deliveryReceiptSnapshot : []);
        closeDeliveryReceiptModal();
    };

    deliveryReceiptCancel?.addEventListener('click', cancelDeliveryReceiptChanges);
    deliveryReceiptClose?.addEventListener('click', cancelDeliveryReceiptChanges);
    deliveryReceiptModal?.addEventListener('click', (event) => {
        if (event.target === deliveryReceiptModal) {
            cancelDeliveryReceiptChanges();
        }
    });
    const syncPaymentFinanceFields = () => {
        const selectedFinance = paymentFinanceChoices.find((choice) => choice.checked)?.value || paymentFinanceProvider?.value || 'Self';
        if (paymentFinanceProvider) {
            paymentFinanceProvider.value = selectedFinance;
        }
        if (paymentFinanceProviderDisplay) {
            paymentFinanceProviderDisplay.value = selectedFinance;
        }
        const showSelf = ['In-House', 'Self'].includes(selectedFinance);
        const showOther = selectedFinance === 'Other';
        paymentFinanceSelfFields?.classList.toggle('hidden', !showSelf);
        paymentFinanceOtherFields?.classList.toggle('hidden', !showOther);
        [paymentFinanceBank, paymentFinanceDisbursalAmount].forEach((field) => {
            if (field) {
                field.disabled = !showSelf;
            }
        });
        if (paymentFinanceOtherReason) {
            paymentFinanceOtherReason.disabled = !showOther;
        }
    };

    const enablePaymentFinanceEdit = (event) => {
        event?.preventDefault();
        event?.stopPropagation();
        if (!paymentFinanceProvider) {
            return;
        }

        paymentFinanceEditor?.classList.remove('hidden');
        paymentFinanceProvider.closest('.delivery-payment-finance-wrap')?.classList.add('is-editing');
        syncPaymentFinanceFields();
        paymentFinanceChoices.find((choice) => choice.checked)?.focus();
    };

    paymentFinanceEdit?.addEventListener('click', enablePaymentFinanceEdit);
    paymentFinanceProviderDisplay?.addEventListener('dblclick', enablePaymentFinanceEdit);
    paymentFinanceChoices.forEach((choice) => {
        choice.addEventListener('change', syncPaymentFinanceFields);
    });
    syncPaymentFinanceFields();

    const formatFinalReceiptAmount = (value) => {
        const numeric = Number.parseFloat(value || '0');
        return Number.isFinite(numeric)
            ? numeric.toLocaleString('en-US', { maximumFractionDigits: 0 })
            : '';
    };

    const formatFinalReceiptDate = (value) => {
        if (!value) {
            return '';
        }

        const date = new Date(`${value}T00:00:00`);
        return Number.isNaN(date.getTime())
            ? value
            : date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    };

    const openFinalReceiptPopup = (type) => {
        if (!finalReceiptPopup || !finalReceiptBody || !finalReceiptTitle) {
            return;
        }

        const rows = Array.isArray(finalReceiptData[type]) ? finalReceiptData[type] : [];
        const receiptTypeLabel = type === 'booking' ? 'Booking' : 'Delivery';
        finalReceiptTitle.textContent = rows.length > 1 ? `${receiptTypeLabel} Receipts` : `${receiptTypeLabel} Receipt`;
        finalReceiptBody.innerHTML = '';

        if (!rows.length) {
            const empty = document.createElement('p');
            empty.className = 'delivery-final-receipt-empty';
            empty.textContent = 'No receipts available.';
            finalReceiptBody.appendChild(empty);
        } else {
            rows.forEach((receipt) => {
                const card = document.createElement('div');
                card.className = 'delivery-final-receipt-detail-card';
                [
                    ['name', 'Receipt Name/No', receipt?.receipt_name_no || '-'],
                    ['date', 'Receipt Date', formatFinalReceiptDate(receipt?.receipt_date) || '-'],
                    ['amount', 'Receipt Amount', formatFinalReceiptAmount(receipt?.receipt_amount) || '-'],
                    ['payment', 'Mode of Payment', receipt?.payment_mode || '-'],
                    ['type', 'Receipt Type', receipt?.receipt_type || receiptTypeLabel],
                ].forEach(([icon, label, value]) => {
                    const item = document.createElement('div');
                    item.className = 'delivery-final-receipt-detail-row';

                    const iconEl = document.createElement('span');
                    iconEl.className = `delivery-final-receipt-icon receipt-icon-${icon}`;
                    iconEl.setAttribute('aria-hidden', 'true');

                    const labelEl = document.createElement('strong');
                    labelEl.textContent = label;

                    const colonEl = document.createElement('i');
                    colonEl.textContent = ':';

                    const valueEl = document.createElement('span');
                    valueEl.textContent = value || '-';

                    item.append(iconEl, labelEl, colonEl, valueEl);
                    card.appendChild(item);
                });
                finalReceiptBody.appendChild(card);
            });
        }

        finalReceiptPopup.classList.remove('hidden');
        finalReceiptPopup.setAttribute('aria-hidden', 'false');
        finalReceiptClose?.focus();
    };

    const closeFinalReceiptPopup = () => {
        finalReceiptPopup?.classList.add('hidden');
        finalReceiptPopup?.setAttribute('aria-hidden', 'true');
    };

    finalReceiptTriggers.forEach((trigger) => {
        trigger.addEventListener('click', () => openFinalReceiptPopup(trigger.dataset.finalReceiptType || 'delivery'));
    });
    finalReceiptClose?.addEventListener('click', closeFinalReceiptPopup);
    finalReceiptDone?.addEventListener('click', closeFinalReceiptPopup);
    finalReceiptPopup?.addEventListener('click', (event) => {
        if (event.target === finalReceiptPopup) {
            closeFinalReceiptPopup();
        }
    });
    syncDeliveryReceiptTotal();
    syncPaymentPendingAmount();

    document.querySelectorAll('[data-image-tile]').forEach((tile) => {
        const fileInput = tile.querySelector('[data-image-input]');
        const removeInput = tile.querySelector('[data-remove-input]');
        const preview = tile.querySelector('[data-image-preview]');
        const label = tile.querySelector('[data-image-label]');
        const viewLink = tile.querySelector('[data-view-link]');
        const removeButton = tile.querySelector('[data-image-remove]');
        const defaultLabel = tile.dataset.defaultLabel || 'Image';

        if (fileInput) {
            fileInput.addEventListener('change', () => {
                const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                if (!file) {
                    return;
                }

                const previewUrl = URL.createObjectURL(file);
                tile.classList.add('has-image');

                if (preview) {
                    preview.src = previewUrl;
                    preview.classList.remove('hidden');
                }

                if (label) {
                    label.textContent = file.name;
                }

                if (viewLink) {
                    viewLink.href = previewUrl;
                    viewLink.classList.remove('hidden');
                }

                if (removeButton) {
                    removeButton.classList.remove('hidden');
                }

                if (removeInput) {
                    removeInput.value = '0';
                    removeInput.disabled = false;
                }
            });
        }

        if (removeButton) {
            removeButton.addEventListener('click', () => {
                if (fileInput) {
                    fileInput.value = '';
                }

                if (removeInput) {
                    removeInput.value = '1';
                    removeInput.disabled = false;
                }

                if (preview) {
                    preview.removeAttribute('src');
                    preview.classList.add('hidden');
                }

                if (label) {
                    label.textContent = defaultLabel;
                }

                if (viewLink) {
                    viewLink.href = '#';
                    viewLink.classList.add('hidden');
                }

                removeButton.classList.add('hidden');
                tile.classList.remove('has-image');

                if (!fileInput) {
                    tile.hidden = true;
                }
            });
        }
    });

    const corporateNameWrap = document.getElementById('deliveryCorporateNameWrap');
    const corporateNameInput = document.getElementById('deliveryCorporateNameInput');
    const customerTypeInputs = Array.from(document.querySelectorAll('input[name="customer_type"]'));

    const syncCorporateName = () => {
        const selectedType = customerTypeInputs.find((input) => input.checked)?.value || '';
        const isCorporate = selectedType === 'corporate';

        if (corporateNameWrap) {
            corporateNameWrap.classList.toggle('hidden', !isCorporate);
        }

        if (corporateNameInput) {
            corporateNameInput.required = isCorporate;
            if (!isCorporate) {
                corporateNameInput.value = '';
            }
        }
    };

    customerTypeInputs.forEach((input) => {
        input.addEventListener('change', syncCorporateName);
    });
    syncCorporateName();

    const picked = (name) => document.querySelector('input[name="' + name + '"]:checked')?.value || '';
    const deliveryTestDriveVehicleSelect = document.getElementById('deliveryTestDriveVehicleSelect');
    const deliveryTestDriveVehicleOtherWrap = document.getElementById('deliveryTestDriveVehicleOtherWrap');
    const deliveryTestDriveNoReasonSelect = document.getElementById('deliveryTestDriveNoReasonSelect');
    const deliveryTestDriveNoReasonOtherWrap = document.getElementById('deliveryTestDriveNoReasonOtherWrap');
    const toggleByValue = (elementId, name, value) => {
        const element = document.getElementById(elementId);
        if (element) {
            element.classList.toggle('hidden', picked(name) !== value);
        }
    };

    const syncBuyingDetails = () => {
        toggleByValue('deliveryQuoteDateWrap', 'quote_taken', 'yes');
        toggleByValue('deliveryTestDriveYesWrap', 'test_drive_given', 'yes');
        toggleByValue('deliveryTestDriveNoWrap', 'test_drive_given', 'no');
        toggleByValue('deliveryCompetitionWrap', 'interested_in_competition', 'yes');
        toggleByValue('deliveryExistingVehicleWrap', 'first_time_buyer', 'no');
        toggleByValue('deliveryFinanceFormWrap', 'purchase_mode', 'finance');

        const showOtherVehicle = deliveryTestDriveVehicleSelect && deliveryTestDriveVehicleSelect.value === 'Other';
        if (deliveryTestDriveVehicleOtherWrap) {
            deliveryTestDriveVehicleOtherWrap.classList.toggle('hidden', !showOtherVehicle);
            if (!showOtherVehicle) {
                const otherInput = deliveryTestDriveVehicleOtherWrap.querySelector('input');
                if (otherInput) {
                    otherInput.value = '';
                }
            }
        }

        const showOtherReason = deliveryTestDriveNoReasonSelect && deliveryTestDriveNoReasonSelect.value === 'Others';
        if (deliveryTestDriveNoReasonOtherWrap) {
            deliveryTestDriveNoReasonOtherWrap.classList.toggle('hidden', !showOtherReason);
            if (!showOtherReason) {
                const otherInput = deliveryTestDriveNoReasonOtherWrap.querySelector('input');
                if (otherInput) {
                    otherInput.value = '';
                }
            }
        }
    };

    document
        .querySelectorAll('input[name="quote_taken"], input[name="test_drive_given"], input[name="interested_in_competition"], input[name="first_time_buyer"], input[name="purchase_mode"]')
        .forEach((input) => input.addEventListener('change', syncBuyingDetails));
    deliveryTestDriveVehicleSelect?.addEventListener('change', syncBuyingDetails);
    deliveryTestDriveNoReasonSelect?.addEventListener('change', syncBuyingDetails);
    syncBuyingDetails();

    const exchangeWrap = document.getElementById('deliveryExchangeDetailsWrap');
    const exchangeTypeInputs = Array.from(document.querySelectorAll('input[name="interested_in_exchange"]'));
    const exchangeHouseTypeInputs = Array.from(document.querySelectorAll('input[name="exchange_type"]'));
    const exchangeEditToggle = document.getElementById('deliveryExchangeEditToggle');
    const exchangeEditPanel = document.getElementById('deliveryExchangeEditPanel');
    const exchangePurchaseRow = document.getElementById('deliveryExchangePurchaseRow');
    const exchangeBrandModelRow = document.getElementById('deliveryExchangeBrandModelRow');
    const exchangeLockableFields = Array.from(document.querySelectorAll('[data-exchange-lockable]'));

    const syncExchangeLockable = () => {
        const isEditable = Boolean(exchangeEditToggle?.checked);
        if (exchangeBrandModelRow) {
            exchangeBrandModelRow.classList.toggle('hidden', !isEditable);
        }
        exchangeLockableFields.forEach((field) => {
            field.classList.toggle('delivery-locked', !isEditable);
            if (field.tagName.toLowerCase() === 'input') {
                field.readOnly = !isEditable || field.id === 'deliveryExchangeDifference';
            } else if (field.tagName.toLowerCase() === 'select') {
                field.toggleAttribute('data-locked', !isEditable);
            }
        });
    };

    const syncExchangeDetails = () => {
        const isExchangeEdit = Boolean(exchangeEditToggle?.checked);
        const isExchange = picked('interested_in_exchange') === 'yes';
        const isInHouse = picked('exchange_type') !== 'outhouse';
        if (exchangeEditPanel) {
            exchangeEditPanel.hidden = !isExchangeEdit;
            exchangeEditPanel.classList.toggle('hidden', !isExchangeEdit);
        }
        exchangeEditToggle?.setAttribute('aria-expanded', isExchangeEdit ? 'true' : 'false');
        if (exchangeWrap) {
            exchangeWrap.classList.toggle('hidden', !isExchange);
        }
        if (exchangePurchaseRow) {
            exchangePurchaseRow.classList.toggle('hidden', !isInHouse);
        }
        syncExchangeLockable();
    };

    exchangeTypeInputs.forEach((input) => input.addEventListener('change', syncExchangeDetails));
    exchangeHouseTypeInputs.forEach((input) => input.addEventListener('change', syncExchangeDetails));
    exchangeEditToggle?.addEventListener('change', syncExchangeDetails);

    const competitionMap = @json($competitionMap);
    const fillModelSelect = (brandSelectId, modelSelectId) => {
        const brandSelect = document.getElementById(brandSelectId);
        const modelSelect = document.getElementById(modelSelectId);
        if (!brandSelect || !modelSelect) {
            return;
        }

        const selectedModel = modelSelect.dataset.selectedModel || '';
        const models = competitionMap[brandSelect.value] || [];
        modelSelect.innerHTML = '<option value="">Select Model</option>';
        models.forEach((model) => {
            const option = document.createElement('option');
            option.value = model;
            option.textContent = model;
            option.selected = model === selectedModel;
            modelSelect.appendChild(option);
        });

        if (selectedModel && !models.includes(selectedModel)) {
            const option = document.createElement('option');
            option.value = selectedModel;
            option.textContent = selectedModel;
            option.selected = true;
            modelSelect.appendChild(option);
        }
    };

    fillModelSelect('deliveryCompetitionBrand', 'deliveryCompetitionModel');
    fillModelSelect('deliveryExistingBrand', 'deliveryExistingModel');
    fillModelSelect('deliveryExchangeBrand', 'deliveryExchangeModel');
    document.getElementById('deliveryCompetitionBrand')?.addEventListener('change', () => {
        const modelSelect = document.getElementById('deliveryCompetitionModel');
        if (modelSelect) {
            modelSelect.dataset.selectedModel = '';
            modelSelect.value = '';
        }
        fillModelSelect('deliveryCompetitionBrand', 'deliveryCompetitionModel');
    });
    document.getElementById('deliveryExistingBrand')?.addEventListener('change', () => {
        const modelSelect = document.getElementById('deliveryExistingModel');
        if (modelSelect) {
            modelSelect.dataset.selectedModel = '';
            modelSelect.value = '';
        }
        fillModelSelect('deliveryExistingBrand', 'deliveryExistingModel');
    });
    document.getElementById('deliveryExchangeBrand')?.addEventListener('change', () => {
        const modelSelect = document.getElementById('deliveryExchangeModel');
        if (modelSelect) {
            modelSelect.dataset.selectedModel = '';
            modelSelect.value = '';
        }
        fillModelSelect('deliveryExchangeBrand', 'deliveryExchangeModel');
    });

    const expectedPrice = document.getElementById('deliveryExchangeExpectedPrice');
    const quotedPrice = document.getElementById('deliveryExchangeQuotedPrice');
    const differencePrice = document.getElementById('deliveryExchangeDifference');
    const syncExchangeDifference = () => {
        if (!expectedPrice || !quotedPrice || !differencePrice) {
            return;
        }

        const expected = parseFloat(expectedPrice.value || '0');
        const quoted = parseFloat(quotedPrice.value || '0');
        differencePrice.value = Number.isFinite(expected - quoted) ? (expected - quoted).toFixed(2) : '';
    };

    expectedPrice?.addEventListener('input', syncExchangeDifference);
    quotedPrice?.addEventListener('input', syncExchangeDifference);
    syncExchangeDetails();
    syncExchangeDifference();

    const deliveryOfferPopup = document.getElementById('deliveryOfferPopup');
    const deliveryOfferPopupOk = document.getElementById('deliveryOfferPopupOk');
    if (deliveryOfferPopup && deliveryOfferPopupOk) {
        document.body.classList.add('delivery-modal-open');
        deliveryOfferPopupOk.addEventListener('click', () => {
            window.location.href = deliveryOfferPopup.dataset.nextUrl || '{{ route('delivery.show', ['enquiry' => $enquiry->id, 'step' => 5]) }}';
        });
    }

    const deliverySubmitPopup = document.getElementById('deliverySubmitPopup');
    const deliverySubmitPopupOk = document.getElementById('deliverySubmitPopupOk');
    if (deliverySubmitPopup && deliverySubmitPopupOk) {
        document.body.classList.add('delivery-modal-open');
        deliverySubmitPopupOk.addEventListener('click', () => {
            const card = deliverySubmitPopup.querySelector('.delivery-submit-popup-card');
            window.location.href = card?.dataset.dashboardUrl || '{{ route('dashboard.main') }}';
        });
    }

})();
</script>
@endsection
