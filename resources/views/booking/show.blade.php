@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/booking.css') }}?v={{ filemtime(public_path('css/booking.css')) }}">
<style>
    .booking-page.booking-step-3 .exchange-section .exchange-upload-grid-primary,
    .booking-page.booking-step-3 .exchange-section .exchange-upload-grid-extra {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 10px !important;
        width: min(740px, 100%) !important;
    }

    .booking-page.booking-step-3 .exchange-section .exchange-upload-tile {
        position: relative !important;
        display: block !important;
        box-sizing: border-box !important;
        min-width: 0 !important;
        height: 176px !important;
        min-height: 176px !important;
        padding: 8px !important;
        border: 1px solid #d9dde4 !important;
        border-radius: 6px !important;
        background: #bfbfbf !important;
        overflow: hidden !important;
        cursor: pointer !important;
        isolation: isolate !important;
    }

    .booking-page.booking-step-3 .exchange-section .exchange-upload-tile::before {
        content: "" !important;
        position: absolute !important;
        top: 18px !important;
        left: 18px !important;
        width: 30px !important;
        height: 30px !important;
        margin: 0 !important;
        background: url("{{ asset('icons/imageuploadic.png') }}") center / contain no-repeat !important;
        filter: brightness(0) !important;
        z-index: 2 !important;
    }

    .booking-page.booking-step-3 .exchange-section .exchange-upload-title {
        position: absolute !important;
        top: 50px !important;
        left: 14px !important;
        right: 12px !important;
        z-index: 2 !important;
        color: #ffffff !important;
        font-size: 16px !important;
        font-weight: 900 !important;
        line-height: 1.2 !important;
        text-align: left !important;
        text-transform: uppercase !important;
    }

    .booking-page.booking-step-3 .exchange-section .exchange-file-input {
        position: absolute !important;
        inset: 0 !important;
        width: 100% !important;
        height: 100% !important;
        opacity: 0 !important;
        cursor: pointer !important;
        z-index: 10 !important;
    }

    .booking-page.booking-step-3 .exchange-section .exchange-upload-preview[hidden] {
        display: none !important;
    }

    .booking-page.booking-step-3 .exchange-section .exchange-upload-preview {
        position: absolute !important;
        inset: 0 !important;
        width: 100% !important;
        height: 100% !important;
        max-width: 100% !important;
        max-height: 100% !important;
        border-radius: 6px !important;
        object-fit: cover !important;
        display: block !important;
        z-index: 4 !important;
        pointer-events: none !important;
    }

    .booking-page.booking-step-3 .exchange-section .exchange-upload-tile.has-preview .exchange-file-input {
        pointer-events: none !important;
    }

    .booking-page.booking-step-3 .exchange-section .exchange-upload-tile.has-preview::before {
        display: none !important;
    }

    .booking-page.booking-step-3 .exchange-section .exchange-upload-tile.has-preview::after {
        content: "" !important;
        position: absolute !important;
        inset: 0 !important;
        background: rgba(0, 0, 0, 0.2) !important;
        z-index: 5 !important;
    }

    .booking-page.booking-step-3 .exchange-section .exchange-upload-tile.has-preview .exchange-upload-title {
        top: 50% !important;
        text-align: center !important;
        color: #f3d04f !important;
        text-shadow: 0 1px 5px rgba(0, 0, 0, 0.7) !important;
        transform: translateY(-50%) !important;
        z-index: 6 !important;
    }

    .booking-page.booking-step-3 .exchange-section .exchange-upload-actions {
        position: absolute !important;
        left: 8px !important;
        right: 8px !important;
        bottom: 8px !important;
        z-index: 8 !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: flex-end !important;
        gap: 8px !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }

    .booking-page.booking-step-3 .exchange-section .exchange-upload-tile.has-preview .exchange-upload-actions {
        opacity: 1 !important;
        pointer-events: auto !important;
    }

    .booking-page.booking-step-3 .exchange-section .exchange-upload-tile:not(.has-preview) .exchange-upload-actions {
        display: none !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }

    .booking-page.booking-step-3 .exchange-section .exchange-preview-view,
    .booking-page.booking-step-3 .exchange-section .exchange-preview-clear {
        position: static !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: auto !important;
        height: auto !important;
        min-height: 24px !important;
        border: 0 !important;
        color: #ffffff !important;
        font-size: 16px !important;
        font-weight: 900 !important;
        line-height: 1 !important;
        cursor: pointer !important;
    }

    .booking-page.booking-step-3 .exchange-section .exchange-preview-view {
        background: transparent !important;
        padding: 0 !important;
        text-shadow: 0 1px 4px rgba(0, 0, 0, 0.85) !important;
    }

    .booking-page.booking-step-3 .exchange-section .exchange-preview-clear {
        background: #ed1c24 !important;
        border-radius: 999px !important;
        padding: 0 12px !important;
    }

    .booking-page.booking-step-3 .exchange-section .exchange-preview-view:disabled,
    .booking-page.booking-step-3 .exchange-section .exchange-preview-clear:disabled {
        opacity: 0.55 !important;
        cursor: default !important;
    }
</style>

@php
$summaryName = trim(($customer?->title ? $customer->title . ' ' : '') . ($customer?->name ?? 'N/A'));
$summaryMobiles = collect($customer?->mobile_numbers ?? [])->filter()->values();
$summaryMobile = $summaryMobiles->isNotEmpty() ? $summaryMobiles->implode(', ') : 'N/A';
$summaryAddress = collect([$customer?->address1, $customer?->address2, $customer?->location, $customer?->district, $customer?->state])
->filter()
->implode(', ');
$summaryScName = $enquiry->user?->name ?? 'N/A';

$customerTypeLabel = match ($prospect?->customer_type) {
'individual' => 'Individual',
'corporate' => 'Corporate',
default => 'N/A',
};

$professionLabel = match ($prospect?->profession) {
'salaried' => 'Salaried',
'self_employed' => 'Self Employed',
'other' => 'Other',
'not_asked' => 'I Did Not Ask',
default => 'N/A',
};

$dobLabel = $prospect?->date_of_birth
? \Carbon\Carbon::parse($prospect->date_of_birth)->format('d-M-Y')
: 'N/A';

$selectedTitle = old('title', $defaultValues['title']);
$selectedName = old('name', $defaultValues['name']);
$selectedContactType = old('contact_type', $defaultValues['contact_type']);
$selectedEmail = old('email', $defaultValues['email']);
$selectedMobile = old('mobile_numbers', $defaultValues['mobile_numbers']);
$selectedDistrict = old('district', $defaultValues['district']);
$selectedLocation = old('location', $defaultValues['location']);
$selectedState = old('state', $defaultValues['state']);
$selectedAddress1 = old('address1', $defaultValues['address1']);
$selectedAddress2 = old('address2', $defaultValues['address2']);
$selectedCustomerType = old('customer_type', $defaultValues['customer_type']);
$selectedCorporateName = old('corporate_name', $defaultValues['corporate_name'] ?? null);
$selectedProfession = old('profession', $defaultValues['profession']);
$selectedDob = old('date_of_birth', $defaultValues['date_of_birth']);
$selectedMobileNumbers = collect(explode(',', (string) $selectedMobile))
->map(fn($mobile) => trim($mobile))
->filter()
->values();
if ($selectedMobileNumbers->isEmpty()) {
$selectedMobileNumbers = collect(['']);
}
$selectedInterestedModel = old('interested_model', $defaultValues['interested_model']);
$selectedInterestedEngine = old('interested_engine', $defaultValues['interested_engine']);
$selectedInterestedVariant = old('interested_variant', $defaultValues['interested_variant']);
$selectedVehicleColor = old('interested_vehicle_color', $defaultValues['interested_vehicle_color']);
$isBuyingVehicleEdit = old('edit_buying_vehicle') === '1';
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
$selectedPurchaseMode = old('purchase_mode', $defaultValues['purchase_mode']);
$selectedFinanceForm = old('finance_form', $defaultValues['finance_form']);
$selectedFinanceBank = old('finance_bank', $defaultValues['finance_bank'] ?? '');
$selectedFinanceOtherDetails = old('finance_other_details', $defaultValues['finance_other_details'] ?? '');
$bankOptions = $bankOptions ?? [];
$selectedCompetition = old('interested_in_competition', $defaultValues['interested_in_competition']);
$selectedCompetitionBrand = old('competition_brand', $defaultValues['competition_brand']);
$selectedCompetitionModel = old('competition_model', $defaultValues['competition_model']);
$selectedCompetitionYear = old('competition_model_year', $defaultValues['competition_model_year'] ?? '');
$selectedFirstTimeBuyer = old('first_time_buyer', $defaultValues['first_time_buyer']);
$selectedExistingBrand = old('existing_vehicle_brand', $defaultValues['existing_vehicle_brand']);
$selectedExistingModel = old('existing_vehicle_model', $defaultValues['existing_vehicle_model']);
$selectedExistingYear = old('existing_vehicle_year', $defaultValues['existing_vehicle_year']);
$selectedInterestedExchange = old('interested_in_exchange', $defaultValues['interested_in_exchange']);
$selectedExchangeType = old('exchange_type', $defaultValues['exchange_type']);
$selectedExchangePurchaseValue = old('exchange_purchase_value', $defaultValues['exchange_purchase_value'] ?? null);
$selectedExchangeBrand = old('exchange_vehicle_brand', $defaultValues['exchange_vehicle_brand']);
$selectedExchangeModel = old('exchange_vehicle_model', $defaultValues['exchange_vehicle_model']);
$selectedExchangeYear = old('exchange_manufacture_year', $defaultValues['exchange_manufacture_year']);
$selectedExchangeOwnership = old('exchange_ownership', $defaultValues['exchange_ownership'] ?? null);
$selectedExchangeInsuranceRaw = old('exchange_insurance_validity', $defaultValues['exchange_insurance_validity'] ?? null);
$selectedExchangeInsuranceValidity = $selectedExchangeInsuranceRaw
? \Carbon\Carbon::parse($selectedExchangeInsuranceRaw)->format('Y-m-d')
: '';
$selectedExchangeInsuranceLabel = $selectedExchangeInsuranceRaw
? \Carbon\Carbon::parse($selectedExchangeInsuranceRaw)->format('d-M-Y')
: '';
$selectedExchangeColor = old('exchange_color', $defaultValues['exchange_color']);
$selectedExchangeMileage = old('exchange_mileage_km', $defaultValues['exchange_mileage_km']);
$selectedExchangeRegNo = old('exchange_registration_no', $defaultValues['exchange_registration_no']);
$selectedExchangeTyreReplacements = old('exchange_tyre_replacements', $defaultValues['exchange_tyre_replacements'] ?? []);
$selectedExchangeTyreReplacements = is_array($selectedExchangeTyreReplacements) ? $selectedExchangeTyreReplacements : [];
$selectedExchangeExpectedPrice = old('exchange_expected_price', $defaultValues['exchange_expected_price']);
$selectedExchangeQuotedPrice = old('exchange_quoted_price', $defaultValues['exchange_quoted_price']);
$selectedExchangeDifference = old('exchange_price_difference', $defaultValues['exchange_price_difference']);
$exchangeImageUrl = function ($path): string {
$path = trim((string) $path);
return $path !== '' ? asset('storage/' . $path) : '';
};
$exchangeImageFields = [
['name' => 'blue_book_image', 'remove' => 'remove_blue_book_image', 'label' => 'Blue Book', 'path' => $defaultValues['blue_book_image'] ?? ''],
['name' => 'lot_no_image', 'remove' => 'remove_lot_no_image', 'label' => 'Lot No', 'path' => $defaultValues['lot_no_image'] ?? ''],
['name' => 'car_pic_1_image', 'remove' => 'remove_car_pic_1_image', 'label' => 'Car picture 1', 'path' => $defaultValues['car_pic_1_image'] ?? ''],
['name' => 'car_pic_2_image', 'remove' => 'remove_car_pic_2_image', 'label' => 'Car picture 2', 'path' => $defaultValues['car_pic_2_image'] ?? ''],
];
$existingExtraExchangeImages = collect($defaultValues['exchange_extra_images'] ?? [])
->map(fn($path) => trim((string) $path))
->filter()
->values();
$extraImageTileCount = max(3, $existingExtraExchangeImages->count());
$exchangeNeedsVehicleInput = $selectedInterestedExchange === 'yes'
&& (trim((string) $selectedExchangeBrand) === '' || trim((string) $selectedExchangeModel) === '');
$isExchangeEdit = old('edit_exchange_details') === '1' || $exchangeNeedsVehicleInput;
$exchangeTypeLabel = match ($selectedExchangeType) {
'in_house' => 'In-House',
'outhouse' => 'Out-House',
default => '',
};

$exchangeInterestLabel = match ($prospect?->interested_in_exchange) {
'yes' => 'Yes',
'no' => 'No',
default => 'N/A',
};

$money = fn($value) => $value === null ? 'N/A' : number_format((float) $value, 2);

$backUrl = $currentStep > 1
? route('booking.show', ['enquiry' => $enquiry->id, 'step' => $currentStep - 1])
: route('prospect.show', ['enquiry' => $enquiry->id, 'step' => 4]);
$showExchangeDetails = $selectedInterestedExchange === 'yes' && in_array($selectedExchangeType, ['in_house', 'outhouse'], true);
$selectedOfferUnitPrice = old('offer_unit_price', $defaultValues['offer_unit_price']);
$selectedOfferUnitPriceDiscount = old('offer_unit_price_discount', $defaultValues['offer_unit_price_discount']);
$selectedOfferUnitPriceFree = old('offer_unit_price_free', (int) ($defaultValues['offer_unit_price_free'] ?? 0)) == 1;
$selectedOfferVatAmount = old('offer_vat_amount', $defaultValues['offer_vat_amount']);
$selectedOfferVatDiscount = old('offer_vat_discount', $defaultValues['offer_vat_discount']);
$selectedOfferVatFree = old('offer_vat_free', (int) ($defaultValues['offer_vat_free'] ?? 0)) == 1;
$selectedOfferTotalCost = old('offer_total_cost', $defaultValues['offer_total_cost']);
$selectedOfferTotalDiscount = old('offer_total_discount', $defaultValues['offer_total_discount']);
$selectedOfferFinalPrice = old('offer_final_price', $defaultValues['offer_final_price']);
$selectedOfferRemark = old('offer_remark', $defaultValues['offer_remark'] ?? '');
$hasOfferRemark = trim((string) $selectedOfferRemark) !== '';
$isOfferEdit = old('edit_offer_details') === '1';
$dateInputValue = function ($value, $fallback = null): string {
$raw = $value ?: $fallback;
if (empty($raw)) {
return '';
}

try {
return \Carbon\Carbon::parse($raw)->format('Y-m-d');
} catch (\Throwable $e) {
return (string) $raw;
}
};
$selectedExpectedDeliveryDate = old('expected_delivery_date', $dateInputValue($defaultValues['expected_delivery_date'], now()->toDateString()));
$selectedBookingDate = old('booking_date', $dateInputValue($defaultValues['booking_date'], now()->toDateString()));
$selectedAmountCollected = old('amount_collected', $defaultValues['amount_collected'] ?? 0);
$bookingReceiptPaymentModes = $bookingReceiptPaymentModes ?? ['Cash', 'Cheque', 'Bank Transfer', 'Credit/Debit Card'];
$selectedBookingReceipts = old('booking_receipts', $defaultValues['booking_receipts'] ?? []);
$selectedBookingReceipts = is_array($selectedBookingReceipts) ? array_values($selectedBookingReceipts) : [];
if (empty($selectedBookingReceipts)) {
$selectedBookingReceipts = [[
'receipt_name_no' => '',
'receipt_date' => '',
'receipt_amount' => (float) $selectedAmountCollected > 0 ? $selectedAmountCollected : '',
'payment_mode' => '',
'receipt_type' => 'Booking',
]];
}

$interestedVehicleLine = collect([$selectedInterestedModel, $selectedInterestedEngine, $selectedInterestedVariant])
->filter()
->implode(' / ');
$interestedVehicleLine = $interestedVehicleLine ?: 'Not selected';
$buyingYesNoLabel = fn($value) => match ($value) {
'yes' => 'Yes',
'no' => 'No',
default => '',
};
$competitionVehicleLine = collect([$selectedCompetitionBrand, $selectedCompetitionModel, $selectedCompetitionYear])->filter()->implode(' ');
$competitionSummaryLabel = match ($selectedCompetition) {
'yes' => $competitionVehicleLine ? 'Yes - ' . $competitionVehicleLine : 'Yes',
'no' => 'No',
'not_asked' => 'I did not ask',
default => '',
};
$purchaseModeLabel = match ($selectedPurchaseMode) {
'cash' => 'Cash',
'finance' => 'Finance',
default => '',
};
$financeFormLabel = match ($selectedFinanceForm) {
'in_house' => 'In House',
'self' => 'Self',
'other' => 'Other',
default => '',
};
$financeDetailsLabel = trim(implode(' - ', array_filter([
$financeFormLabel,
in_array($selectedFinanceForm, ['in_house', 'self'], true) ? $selectedFinanceBank : $selectedFinanceOtherDetails,
])));
$selectedCustomerTypeLabel = match ($selectedCustomerType) {
'individual' => 'Individual',
'corporate' => 'Corporate',
default => '',
};
$selectedProfessionLabel = match ($selectedProfession) {
'salaried' => 'Salaried',
'self_employed' => 'Self Employed',
'other' => 'Other',
'not_asked' => 'I Did Not Ask',
default => '',
};
$bookingScName = $enquiry->user?->name ?? 'N/A';
$bookingLeadSource = $enquiry->lead_source ?: 'N/A';
$bookingSourceInfo = $enquiry->source_of_information ?: ($prospect?->source_of_information ?: 'N/A');
$enquiryDateLabel = $enquiry->created_at ? \Carbon\Carbon::parse($enquiry->created_at)->format('F d, Y') : 'N/A';
$testDriveDetailsLabel = '';
if ($selectedTestDrive === 'yes') {
$testDriveDateLabel = $selectedTestDriveDate
? \Carbon\Carbon::parse($selectedTestDriveDate)->format('M d,Y')
: '';
$testDriveDetailsLabel = collect([
$selectedTestDriveModel || $interestedVehicleLine !== 'Not selected' ? 'By ' . ($selectedTestDriveModel ?: $interestedVehicleLine) : '',
$testDriveDateLabel ? 'on ' . $testDriveDateLabel : '',
])->filter()->implode(' ');
} elseif ($selectedTestDrive === 'no') {
$testDriveDetailsLabel = $selectedTestDriveReason ?: '';
}
$buyingSummaryRowClass = fn($value): string => trim((string) $value) === '' || trim((string) $value) === 'N/A' ? ' buying-summary-empty' : '';
$exchangeVehicleLine = collect([$selectedExchangeBrand, $selectedExchangeModel])
->filter()
->implode(' ');
$exchangeVehicleLine = $exchangeVehicleLine ?: 'Not selected';

$vehicleColorOptions = $vehicleColorOptions ?? [];
$competitionMap = collect($competitionMap ?? []);
$competitionBrands = $competitionMap->keys()->values()->all();
$stepTitleMap = [
1 => 'Personal Details',
2 => 'Buying Details',
3 => 'Exchange Details',
4 => 'Offer Details',
5 => 'Booking Form',
];
$pageTitle = $stepTitleMap[$currentStep] ?? 'Booking Detail';
@endphp

<div class="booking-page booking-step-{{ $currentStep }}">
    <header class="booking-topbar">
        <a href="{{ route('dashboard.main') }}" class="brand-logo-link" aria-label="Go to dashboard">
            <img src="{{ asset('icons/logo.png') }}" alt="Ideal Motors" class="brand-logo">
        </a>
    </header>

    <div class="booking-stepper">
        @foreach([
        1 => 'Personal Details',
        2 => 'Buying Details',
        3 => 'Exchange Details',
        4 => 'Offer Details',
        5 => 'Booking Form'
        ] as $index => $label)
        <div class="stepper-item {{ $index === $currentStep ? 'active' : ($index < $currentStep ? 'complete' : '') }}">
            <span class="step-number">{{ str_pad((string) $index, 2, '0', STR_PAD_LEFT) }}</span>
            <span class="step-label">{{ $label }}</span>
        </div>
        @endforeach
    </div>

    <main class="booking-shell">
        @if(session('success'))
        <div class="booking-flash success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
        <div class="booking-flash error">
            <ul>
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if($currentStep === 1)
        <div class="booking-personal-summary">
            <div class="booking-personal-summary-row"><span>Customer Name</span><strong id="bookingSummaryCustomerName">{{ $summaryName }}</strong></div>
            <div class="booking-personal-summary-row"><span>Address</span><strong id="bookingSummaryAddress">{{ $summaryAddress ?: 'N/A' }}</strong></div>
            <div class="booking-personal-summary-row"><span>Mobile No</span><strong id="bookingSummaryMobile">{{ $summaryMobile }}</strong></div>
            <div class="booking-personal-summary-row"><span>Email</span><strong id="bookingSummaryEmail">{{ $selectedEmail ?: 'N/A' }}</strong></div>
            <div class="booking-personal-summary-row"><span>Type of Customer</span><strong id="bookingSummaryCustomerType">{{ $customerTypeLabel }}</strong></div>
            <div class="booking-personal-summary-row {{ $selectedCustomerType === 'corporate' ? '' : 'hidden' }}" id="bookingSummaryCorporateRow"><span>Corporate Name</span><strong id="bookingSummaryCorporateName">{{ $selectedCorporateName ?: 'N/A' }}</strong></div>
            <div class="booking-personal-summary-row"><span>Profession</span><strong id="bookingSummaryProfession">{{ $professionLabel }}</strong></div>
        </div>
        @endif

        @if($currentStep > 2)
        @if($currentStep !== 4)
        <h2>{{ $pageTitle }}</h2>
        @endif

        @if($currentStep === 4)
        <div class="offer-page-summary">
            <h3>SUMMARY</h3>
            <p>Customer Name: <strong>{{ $summaryName }}</strong></p>
            <p>Interested in: <strong>{{ strtoupper($interestedVehicleLine) }}</strong></p>
        </div>
        @elseif($currentStep === 3)
        <div class="booking-summary exchange-summary">
            <div class="exchange-summary-row exchange-summary-customer"><span>Customer Name</span><strong id="exchangeSummaryCustomerName">{{ $summaryName ?: '-' }}</strong></div>
            <div class="exchange-summary-row exchange-summary-vehicle"><span>Interested In</span><strong id="exchangeSummaryInterested">{{ strtoupper($interestedVehicleLine !== 'Not selected' ? $interestedVehicleLine : '-') }}</strong></div>
            <div class="exchange-summary-row exchange-summary-mobile"><span>Mobile No</span><strong id="exchangeSummaryMobile">{{ $summaryMobile ?: '-' }}</strong></div>
            <div class="exchange-summary-row exchange-summary-exchange"><span>Interested in Exchange?</span><strong id="exchangeSummaryInterestedExchange">{{ $buyingYesNoLabel($selectedInterestedExchange) ?: '-' }}</strong></div>
            <div class="exchange-summary-row exchange-summary-type"><span>Exchange Type</span><strong id="exchangeSummaryType">{{ $selectedExchangeType ? str_replace('_', ' ', ucwords($selectedExchangeType, '_')) : '-' }}</strong></div>
            <div class="exchange-summary-row exchange-summary-brand"><span>Exchange Brand</span><strong id="exchangeSummaryBrand">{{ $selectedExchangeBrand ?: '-' }}</strong></div>
            <div class="exchange-summary-row exchange-summary-model"><span>Exchange Model</span><strong id="exchangeSummaryModel">{{ $selectedExchangeModel ?: '-' }}</strong></div>
            <div class="exchange-summary-row exchange-summary-year"><span>Model Year</span><strong id="exchangeSummaryYear">{{ $selectedExchangeYear ?: '-' }}</strong></div>
            <div class="exchange-summary-row exchange-summary-ownership"><span>Ownership</span><strong id="exchangeSummaryOwnership">{{ $selectedExchangeOwnership ?: '-' }}</strong></div>
            <div class="exchange-summary-row exchange-summary-insurance"><span>Insurance Validity</span><strong id="exchangeSummaryInsurance">{{ $selectedExchangeInsuranceLabel ?: '-' }}</strong></div>
            <div class="exchange-summary-row exchange-summary-color"><span>Color</span><strong id="exchangeSummaryColor">{{ $selectedExchangeColor ?: '-' }}</strong></div>
            <div class="exchange-summary-row exchange-summary-mileage"><span>Total KM</span><strong id="exchangeSummaryMileage">{{ $selectedExchangeMileage !== null && $selectedExchangeMileage !== '' ? number_format((float) $selectedExchangeMileage, 0) : '-' }}</strong></div>
            <div class="exchange-summary-row exchange-summary-registration"><span>Registration No</span><strong id="exchangeSummaryRegistration">{{ $selectedExchangeRegNo ?: '-' }}</strong></div>
            <div class="exchange-summary-row exchange-summary-expected"><span>Expected Price</span><strong id="exchangeSummaryExpected">{{ $selectedExchangeExpectedPrice !== null && $selectedExchangeExpectedPrice !== '' ? number_format((float) $selectedExchangeExpectedPrice, 0) : '-' }}</strong></div>
            <div class="exchange-summary-row exchange-summary-quoted"><span>Quoted Price</span><strong id="exchangeSummaryQuoted">{{ $selectedExchangeQuotedPrice !== null && $selectedExchangeQuotedPrice !== '' ? number_format((float) $selectedExchangeQuotedPrice, 0) : '-' }}</strong></div>
            <div class="exchange-summary-row exchange-summary-difference"><span>Difference</span><strong id="exchangeSummaryDifference">{{ $selectedExchangeDifference !== null && $selectedExchangeDifference !== '' ? number_format((float) $selectedExchangeDifference, 0) : '-' }}</strong></div>
        </div>
        @elseif($currentStep !== 5)
        <div class="booking-summary">
            <p>{{ $summaryName }}</p>
            <p>{{ $summaryMobile }}</p>
            <p>{{ $summaryAddress ?: 'N/A' }}</p>
            <p>{{ $customerTypeLabel }}</p>
            <p>Profession - {{ $professionLabel }}</p>
            <p>DOB: {{ $dobLabel }}</p>
        </div>
        @endif
        @endif

        <form method="POST" action="{{ route('booking.store', $enquiry->id) }}" enctype="multipart/form-data" id="bookingForm" novalidate>
            @csrf
            <input type="hidden" name="booking_step" value="{{ $currentStep }}">
            <input type="hidden" name="action_type_fallback" id="bookingActionTypeFallback" value="{{ $currentStep === 5 ? 'submit' : 'next' }}">

            <section class="booking-section personal-section {{ $currentStep === 1 ? 'active' : '' }}">
                <div class="section-head-inline personal-head">
                    <h3 class="section-heading">Personal Details</h3>
                    <label class="inline-edit-check">
                        <input type="checkbox" id="sameAsToggle" @checked(!$sameAsCustomer)>
                        <span>Edit</span>
                    </label>
                    <input type="hidden" id="bookingSameAsCustomer" name="booking_same_as_customer" value="{{ $sameAsCustomer ? '1' : '0' }}">
                </div>

                <div id="editBlock" class="personal-edit-block">
                    <div class="row personal-row-top">
                        <div class="field-title">
                            <label>Title</label>
                            <select name="title" data-personal-editable>
                                @foreach(['Mr', 'Mrs', 'Ms', 'Dr'] as $titleOption)
                                <option value="{{ $titleOption }}" @selected($selectedTitle===$titleOption)>{{ $titleOption }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field-name">
                            <label>Name</label>
                            <input type="text" name="name" value="{{ $selectedName }}" data-personal-editable>
                        </div>

                        <div class="field-email">
                            <label>Email</label>
                            <input type="email" name="email" value="{{ $selectedEmail }}" data-personal-editable>
                        </div>

                        <div class="field-dob">
                            <label>DOB</label>
                            <input type="date" name="date_of_birth" value="{{ $selectedDob }}" data-personal-editable>
                        </div>

                        <div class="field-contact">
                            <label>Contact No</label>
                            <div class="contact-list" id="bookingContactList">
                                <select name="contact_type" class="contact-type-select" data-personal-editable>
                                    @foreach(['Mobile', 'Home', 'Office'] as $contactTypeOption)
                                    <option value="{{ $contactTypeOption }}" @selected($selectedContactType===$contactTypeOption)>{{ $contactTypeOption }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="mobile_numbers" id="bookingMobileNumbers" value="{{ $selectedMobile }}" data-personal-editable>
                                @foreach($selectedMobileNumbers as $mobileIndex => $mobileNumber)
                                <div class="contact-pill-wrap">
                                    <input type="text" class="booking-mobile-input" value="{{ $mobileNumber }}" data-personal-editable>
                                    @if($mobileIndex === 0)
                                    <button type="button" class="mini-add-btn" id="addBookingMobileBtn" aria-label="Add contact">+</button>
                                    @else
                                    <button type="button" class="mini-remove-btn" aria-label="Remove contact">&times;</button>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="row personal-row-two">
                        <div>
                            <label>District</label>
                            <select name="district" data-personal-editable>
                                <option value="">Select District</option>
                                @foreach($districtOptions as $districtOption)
                                <option value="{{ $districtOption }}" @selected($selectedDistrict===$districtOption)>{{ $districtOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>Location</label>
                            <input type="text" name="location" value="{{ $selectedLocation }}" data-personal-editable>
                        </div>
                    </div>

                    <div class="row personal-row-three">
                        <div>
                            <label>State</label>
                            <input type="text" name="state" value="{{ $selectedState }}" data-personal-editable>
                        </div>
                        <div>
                            <label>Address Line 1</label>
                            <input type="text" name="address1" value="{{ $selectedAddress1 }}" data-personal-editable>
                        </div>
                    </div>

                    <div class="row personal-row-full">
                        <label>Address Line 2</label>
                        <input type="text" name="address2" value="{{ $selectedAddress2 }}" data-personal-editable>
                    </div>

                    <label>Type Of Customer</label>
                    <div class="segment-row two personal-segment">
                        <label><input type="radio" name="customer_type" value="individual" data-personal-editable @checked($selectedCustomerType==='individual' )><span>Individual</span></label>
                        <label><input type="radio" name="customer_type" value="corporate" data-personal-editable @checked($selectedCustomerType==='corporate' )><span>Corporate</span></label>
                    </div>

                    <div id="corporateNameRow" class="row {{ $selectedCustomerType === 'corporate' ? '' : 'hidden' }}">
                        <label>Corporate Name</label>
                        <input type="text" name="corporate_name" value="{{ $selectedCorporateName }}" placeholder="Corporate Name" data-personal-editable>
                    </div>

                    <label>Profession</label>
                    <div class="segment-row four personal-segment">
                        <label><input type="radio" name="profession" value="salaried" data-personal-editable @checked($selectedProfession==='salaried' )><span>Salaried</span></label>
                        <label><input type="radio" name="profession" value="self_employed" data-personal-editable @checked($selectedProfession==='self_employed' )><span>Self Employed</span></label>
                        <label><input type="radio" name="profession" value="other" data-personal-editable @checked($selectedProfession==='other' )><span>Other</span></label>
                        <label><input type="radio" name="profession" value="not_asked" data-personal-editable @checked($selectedProfession==='not_asked' )><span>I Did Not Ask</span></label>
                    </div>
                </div>

                <div class="purchase-order-box personal-purchase-order">
                    <label for="purchase_order_image">Purchase Order</label>
                    <input type="hidden" id="remove_purchase_order_image" name="remove_purchase_order_image" value="0">
                    <div
                        class="purchase-order-upload-tile {{ !empty($booking->purchase_order_image) ? 'has-preview' : '' }}"
                        id="purchaseOrderTile"
                        data-existing-src="{{ !empty($booking->purchase_order_image) ? asset('storage/' . $booking->purchase_order_image) : '' }}">
                        <label class="purchase-order-upload-pill" for="purchase_order_image">
                            <span aria-hidden="true"></span>
                            <strong>Purchase Order</strong>
                            <img
                                id="purchaseOrderPreview"
                                class="purchase-order-preview"
                                alt="Purchase Order preview"
                                src="{{ !empty($booking->purchase_order_image) ? asset('storage/' . $booking->purchase_order_image) : '' }}"
                                @if(empty($booking->purchase_order_image)) hidden @endif
                            >
                        </label>
                        <button type="button" class="purchase-order-remove" id="purchaseOrderRemove" aria-label="Remove purchase order image">&times;</button>
                        <input id="purchase_order_image" type="file" name="purchase_order_image" accept=".jpg,.jpeg,.png,.webp">
                    </div>

                    <div class="purchase-order-actions">
                        <button type="button" id="purchaseOrderAdd">Add</button>
                        <button type="button" id="purchaseOrderView" @disabled(empty($booking->purchase_order_image))>View</button>
                        <button type="button" id="purchaseOrderClear" @disabled(empty($booking->purchase_order_image))>Clear</button>
                    </div>
                </div>
            </section>

            <section class="booking-section buying-section {{ $currentStep === 2 ? 'active' : '' }}">
                <div class="buying-details-summary">
                    <div class="buying-summary-row{{ $buyingSummaryRowClass($summaryName) }}"><span>Customer Name</span><strong id="buyingSummaryCustomerName">{{ $summaryName }}</strong></div>
                    <div class="buying-summary-row{{ $buyingSummaryRowClass($summaryAddress) }}"><span>Address</span><strong id="buyingSummaryAddress">{{ $summaryAddress }}</strong></div>
                    <div class="buying-summary-row{{ $buyingSummaryRowClass($summaryMobile) }}"><span>Mobile No</span><strong id="buyingSummaryMobile">{{ $summaryMobile }}</strong></div>
                    <div class="buying-summary-row{{ $buyingSummaryRowClass($interestedVehicleLine === 'Not selected' ? '' : $interestedVehicleLine) }}"><span>Interested In</span><strong id="buyingSummaryInterested">{{ $interestedVehicleLine === 'Not selected' ? '' : $interestedVehicleLine }}</strong></div>
                    <div class="buying-summary-row{{ $buyingSummaryRowClass($selectedVehicleColor) }}"><span>Color</span><strong id="buyingSummaryColor">{{ $selectedVehicleColor }}</strong></div>
                    <div class="buying-summary-row{{ $buyingSummaryRowClass($buyingYesNoLabel($selectedQuote)) }}"><span>Did the customer take quote?</span><strong id="buyingSummaryQuote">{{ $buyingYesNoLabel($selectedQuote) }}</strong></div>
                    <div class="buying-summary-row{{ $buyingSummaryRowClass($buyingYesNoLabel($selectedTestDrive)) }}"><span>Test Driven Given</span><strong id="buyingSummaryTestDrive">{{ $buyingYesNoLabel($selectedTestDrive) }}</strong></div>
                    <div class="buying-summary-row{{ $buyingSummaryRowClass($testDriveDetailsLabel) }}"><span>Test Drive Details</span><strong id="buyingSummaryTestDriveDetails">{{ $testDriveDetailsLabel }}</strong></div>
                    <div class="buying-summary-row{{ $buyingSummaryRowClass($competitionSummaryLabel) }}"><span>Interested In Competition</span><strong id="buyingSummaryCompetition">{{ $competitionSummaryLabel }}</strong></div>
                    <div class="buying-summary-row{{ $buyingSummaryRowClass($buyingYesNoLabel($selectedFirstTimeBuyer)) }}"><span>First Time Buyer</span><strong id="buyingSummaryFirstTime">{{ $buyingYesNoLabel($selectedFirstTimeBuyer) }}</strong></div>
                    <div class="buying-summary-row{{ $buyingSummaryRowClass($purchaseModeLabel) }}"><span>Mode of Purchase</span><strong id="buyingSummaryPurchaseMode">{{ $purchaseModeLabel }}</strong></div>
                </div>

                <div class="buying-edit-switch-row">
                    <label class="booking-toggle-label">
                        <span>Edit Buying Details</span>
                        <input type="hidden" name="edit_buying_vehicle" value="0">
                        <input type="checkbox" id="toggleBuyingVehicleEdit" name="edit_buying_vehicle" value="1" @checked($isBuyingVehicleEdit || $currentStep===2)>
                        <i aria-hidden="true"></i>
                    </label>
                </div>

                <div class="buying-edit-card">
                    <div class="buying-card-head">
                        <h3 class="section-heading">Interested In</h3>
                        <span class="buying-card-edit-mark">Edit</span>
                    </div>

                    <div id="vehicleEditFields" class="buying-vehicle-fields">
                        <div class="row split">
                            <div>
                                <label>Select Model</label>
                                <select id="interested_model" name="interested_model" class="buying-select" data-selected-model="{{ $selectedInterestedModel }}">
                                    <option value="">Select Model</option>
                                    @foreach($vehicleModels as $modelOption)
                                    <option value="{{ $modelOption }}" @selected($selectedInterestedModel===$modelOption)>{{ $modelOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label>Select Engine Type</label>
                                <select id="interested_engine" name="interested_engine" class="buying-select" data-selected-engine="{{ $selectedInterestedEngine }}">
                                    <option value="">Select Engine Type</option>
                                </select>
                            </div>
                        </div>

                        <div class="row buying-variant-row">
                            <div>
                                <label>Select Variant</label>
                                <select id="interested_variant" name="interested_variant" class="buying-select" data-selected-variant="{{ $selectedInterestedVariant }}">
                                    <option value="">Select Variant</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="buying-vehicle-pill-line">
                        <div id="vehicleReadPill" class="vehicle-pill-display">{{ $interestedVehicleLine }}</div>
                        <button type="button" class="vehicle-pill-remove" aria-label="Clear selected vehicle">&minus;</button>
                    </div>

                    <div class="row buying-color-row">
                        <label class="required-field-label" for="bookingVehicleColor">Color *</label>
                        <select id="bookingVehicleColor" name="interested_vehicle_color" class="vehicle-color-select">
                            <option value="">Select Color</option>
                            @foreach($vehicleColorOptions as $colorOption)
                            <option value="{{ $colorOption }}" @selected($selectedVehicleColor===$colorOption)>{{ $colorOption }}</option>
                            @endforeach
                            @if(!empty($selectedVehicleColor) && !in_array($selectedVehicleColor, $vehicleColorOptions, true))
                            <option value="{{ $selectedVehicleColor }}" selected>{{ $selectedVehicleColor }}</option>
                            @endif
                        </select>
                    </div>

                    <label>Did the customer take a quote?</label>
                    <div class="segment-row two buying-short-segment">
                        <label><input type="radio" name="quote_taken" value="yes" @checked($selectedQuote==='yes' )><span>Yes</span></label>
                        <label><input type="radio" name="quote_taken" value="no" @checked($selectedQuote==='no' )><span>No</span></label>
                    </div>

                    <div class="row conditional buying-date-row" id="quoteDateWrap">
                        <label>When?</label>
                        <input type="date" name="quote_date" value="{{ $selectedQuoteDate }}">
                    </div>

                    <label>Test Drive Given?</label>
                    <div class="segment-row two buying-short-segment">
                        <label><input type="radio" name="test_drive_given" value="yes" @checked($selectedTestDrive==='yes' )><span>Yes</span></label>
                        <label><input type="radio" name="test_drive_given" value="no" @checked($selectedTestDrive==='no' )><span>No</span></label>
                    </div>

                    <div class="conditional" id="testDriveYesWrap">
                        <div class="row buying-test-row">
                            <div>
                                <label>When?</label>
                                <input type="date" name="test_drive_date" value="{{ $selectedTestDriveDate }}">
                            </div>
                        </div>
                        <div class="row">
                            <label>Vehicle Used?</label>
                            <select name="test_drive_vehicle_model" id="bookingTestDriveVehicleSelect" class="buying-select">
                                <option value="">Select Model</option>
                                @foreach($testDriveVehicleOptions as $modelOption)
                                <option value="{{ $modelOption }}" @selected($selectedTestDriveVehicleSelect===$modelOption)>{{ $modelOption }}</option>
                                @endforeach
                                <option value="Other" @selected($selectedTestDriveVehicleSelect==='Other' )>Other</option>
                            </select>
                            <div id="bookingTestDriveVehicleOtherWrap" class="{{ $selectedTestDriveVehicleSelect === 'Other' ? '' : 'hidden' }}">
                                <label>Other Details</label>
                                <input type="text" name="test_drive_vehicle_model_other" value="{{ $selectedTestDriveVehicleOther }}" placeholder="Enter vehicle details">
                            </div>
                        </div>
                    </div>

                    <div class="row conditional" id="testDriveNoWrap">
                        <label>Why Not Given?</label>
                        <select name="test_drive_not_given_reason" id="bookingTestDriveNoReasonSelect" class="buying-select">
                            <option value="">Select reason</option>
                            @foreach($testDriveNoReasons as $reasonOption)
                            <option value="{{ $reasonOption }}" @selected($selectedTestDriveReason===$reasonOption)>{{ $reasonOption }}</option>
                            @endforeach
                        </select>
                        <div id="bookingTestDriveNoReasonOtherWrap" class="{{ $selectedTestDriveReason === 'Others' ? '' : 'hidden' }}">
                            <label>Other Details</label>
                            <input type="text" name="test_drive_not_given_reason_other" value="{{ $selectedTestDriveReasonOther }}" placeholder="Enter reason">
                        </div>
                    </div>

                    <label>Interested in Competition</label>
                    <div class="segment-row three buying-medium-segment">
                        <label><input type="radio" name="interested_in_competition" value="yes" @checked($selectedCompetition==='yes' )><span>Yes</span></label>
                        <label><input type="radio" name="interested_in_competition" value="no" @checked($selectedCompetition==='no' )><span>No</span></label>
                        <label><input type="radio" name="interested_in_competition" value="not_asked" @checked($selectedCompetition==='not_asked' )><span>I Did Not Ask</span></label>
                    </div>

                    <div class="conditional" id="competitionWrap">
                        <div class="row three buying-soft-row">
                            <div>
                                <select id="competition_brand" name="competition_brand" class="buying-select">
                                    <option value="">Select brand</option>
                                    @foreach($competitionBrands as $brandOption)
                                    <option value="{{ $brandOption }}" @selected($selectedCompetitionBrand===$brandOption)>{{ $brandOption }}</option>
                                    @endforeach
                                    @if(!empty($selectedCompetitionBrand) && !in_array($selectedCompetitionBrand, $competitionBrands, true))
                                    <option value="{{ $selectedCompetitionBrand }}" selected>{{ $selectedCompetitionBrand }}</option>
                                    @endif
                                </select>
                            </div>
                            <div>
                                <select id="competition_model" name="competition_model" class="buying-select" data-selected-model="{{ $selectedCompetitionModel }}">
                                    <option value="">Select model</option>
                                </select>
                            </div>
                            <div>
                                <select id="competition_model_year" name="competition_model_year" class="buying-select" aria-label="Competition model year">
                                    <option value="">Model year</option>
                                    @for($year = (int) now()->year + 1; $year >= 1950; $year--)
                                    <option value="{{ $year }}" @selected((string) $selectedCompetitionYear===(string) $year)>{{ $year }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>

                    <label>First time buyer?</label>
                    <div class="segment-row two buying-short-segment">
                        <label><input type="radio" name="first_time_buyer" value="yes" @checked($selectedFirstTimeBuyer==='yes' )><span>Yes</span></label>
                        <label><input type="radio" name="first_time_buyer" value="no" @checked($selectedFirstTimeBuyer==='no' )><span>No</span></label>
                    </div>

                    <div class="conditional" id="existingVehicleWrap">
                        <div class="row three buying-soft-row">
                            <div>
                                <select id="existing_vehicle_brand" name="existing_vehicle_brand" class="buying-select">
                                    <option value="">Select brand</option>
                                    @foreach($competitionBrands as $brandOption)
                                    <option value="{{ $brandOption }}" @selected($selectedExistingBrand===$brandOption)>{{ $brandOption }}</option>
                                    @endforeach
                                    @if(!empty($selectedExistingBrand) && !in_array($selectedExistingBrand, $competitionBrands, true))
                                    <option value="{{ $selectedExistingBrand }}" selected>{{ $selectedExistingBrand }}</option>
                                    @endif
                                </select>
                            </div>
                            <div>
                                <select id="existing_vehicle_model" name="existing_vehicle_model" class="buying-select" data-selected-model="{{ $selectedExistingModel }}">
                                    <option value="">Select model</option>
                                </select>
                            </div>
                            <div>
                                <select name="existing_vehicle_year" class="buying-select">
                                    <option value="">Model year</option>
                                    @for($year = now()->year + 1; $year >= 1950; $year--)
                                    <option value="{{ $year }}" @selected((string) $selectedExistingYear===(string) $year)>{{ $year }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>

                    <label>Mode of Purchase</label>
                    <div class="segment-row two buying-short-segment">
                        <label><input type="radio" name="purchase_mode" value="cash" @checked($selectedPurchaseMode==='cash' )><span>Cash</span></label>
                        <label><input type="radio" name="purchase_mode" value="finance" @checked($selectedPurchaseMode==='finance' )><span>Finance</span></label>
                    </div>

                    <div class="conditional" id="financeFormWrap">
                        <label>Finance From</label>
                        <div class="segment-row three buying-medium-segment">
                            <label><input type="radio" name="finance_form" value="in_house" @checked($selectedFinanceForm==='in_house' )><span>In-House</span></label>
                            <label><input type="radio" name="finance_form" value="self" @checked($selectedFinanceForm==='self' )><span>Self</span></label>
                            <label><input type="radio" name="finance_form" value="other" @checked($selectedFinanceForm==='other' )><span>Other</span></label>
                        </div>
                        <div id="financeBankWrap" class="{{ in_array($selectedFinanceForm, ['in_house', 'self'], true) ? '' : 'hidden' }}">
                            <label>Bank</label>
                            <select name="finance_bank" class="buying-select">
                                <option value="">Select bank</option>
                                @foreach($bankOptions as $bankOption)
                                <option value="{{ $bankOption }}" @selected($selectedFinanceBank===$bankOption)>{{ $bankOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="financeOtherWrap" class="{{ $selectedFinanceForm === 'other' ? '' : 'hidden' }}">
                            <label>Other Details</label>
                            <input type="text" name="finance_other_details" value="{{ $selectedFinanceOtherDetails }}" placeholder="Enter finance details">
                        </div>
                    </div>
                </div>
            </section>

            <section class="booking-section exchange-section {{ $currentStep === 3 ? 'active' : '' }}">
                <label class="exchange-top-label">Exchange Type</label>
                <div id="exchangeTypeRow" class="exchange-system-fields">
                    <label><input type="radio" name="exchange_type" value="in_house" @checked($selectedExchangeType !=='outhouse' )><span>In - House</span></label>
                    <label><input type="radio" name="exchange_type" value="outhouse" @checked($selectedExchangeType==='outhouse' )><span>Out- House</span></label>
                </div>

                <div id="exchangePurchaseRow" class="exchange-purchase-row {{ $selectedExchangeType === 'outhouse' ? 'hidden' : '' }}">
                    <label>Purchase Value</label>
                    <input id="exchangePurchaseValueInput" type="number" step="0.01" min="0" name="exchange_purchase_value" value="{{ $selectedExchangePurchaseValue }}">
                </div>

                <label class="exchange-question-label">Interested in Exchange?</label>
                <div class="segment-row two exchange-interest-segment">
                    <label><input type="radio" name="interested_in_exchange" value="yes" @checked($selectedInterestedExchange==='yes' )><span>Yes</span></label>
                    <label><input type="radio" name="interested_in_exchange" value="no" @checked($selectedInterestedExchange==='no' )><span>No</span></label>
                </div>

                <div id="exchangeDetailsWrap" class="exchange-detail-wrap {{ $showExchangeDetails ? '' : 'hidden' }}">
                    <div id="exchangeEditFields" class="exchange-edit-fields {{ $showExchangeDetails ? '' : 'hidden' }}">
                        <div class="row exchange-interested-row">
                            <label>Exchange Details</label>
                            <div class="vehicle-pill-display exchange-vehicle-pill">
                                <span>{{ strtoupper($exchangeVehicleLine) }}</span>
                                <label class="inline-edit-check">
                                    <input type="hidden" name="edit_exchange_details" value="0">
                                    <input type="checkbox" id="toggleExchangeEdit" name="edit_exchange_details" value="1" @checked($isExchangeEdit)>
                                    <span>Edit</span>
                                </label>
                            </div>
                        </div>

                        <div
                            id="exchangeBrandModelRow"
                            class="row split {{ $isExchangeEdit ? '' : 'hidden' }}"
                            data-requires-input="{{ $exchangeNeedsVehicleInput ? '1' : '0' }}">
                            <div>
                                <label>Select Brand</label>
                                <select id="exchange_vehicle_brand" name="exchange_vehicle_brand" class="buying-select">
                                    <option value="">Select Brand</option>
                                    @foreach($competitionBrands as $brandOption)
                                    <option value="{{ $brandOption }}" @selected($selectedExchangeBrand===$brandOption)>{{ $brandOption }}</option>
                                    @endforeach
                                    @if(!empty($selectedExchangeBrand) && !in_array($selectedExchangeBrand, $competitionBrands, true))
                                    <option value="{{ $selectedExchangeBrand }}" selected>{{ $selectedExchangeBrand }}</option>
                                    @endif
                                </select>
                            </div>
                            <div>
                                <label>Select Model</label>
                                <select id="exchange_vehicle_model" name="exchange_vehicle_model" class="buying-select" data-selected-model="{{ $selectedExchangeModel }}">
                                    <option value="">Select Model</option>
                                </select>
                                <input type="hidden" id="exchange_vehicle_model_backup" name="exchange_vehicle_model_backup" value="{{ $selectedExchangeModel }}">
                            </div>
                        </div>

                        <div class="row split">
                            <div>
                                <label>Model Year</label>
                                <select name="exchange_manufacture_year" class="buying-select">
                                    <option value="">Select Year</option>
                                    @for($year = now()->year + 1; $year >= 1950; $year--)
                                    <option value="{{ $year }}" @selected((string) $selectedExchangeYear===(string) $year)>{{ $year }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <label>Select Ownership</label>
                                <select name="exchange_ownership" class="buying-select">
                                    <option value="">Select</option>
                                    <option value="1st Owner" @selected($selectedExchangeOwnership==='1st Owner' )>1st Owner</option>
                                    <option value="2nd Owner" @selected($selectedExchangeOwnership==='2nd Owner' )>2nd Owner</option>
                                    <option value="3rd Owner" @selected($selectedExchangeOwnership==='3rd Owner' )>3rd Owner</option>
                                </select>
                            </div>
                        </div>

                        <div class="row exchange-wide-field exchange-insurance-row">
                            <div class="exchange-insurance-field">
                                <div id="exchange_insurance_validity_label" class="exchange-insurance-heading">Insurance Validity</div>
                                <input type="date" id="exchange_insurance_validity" name="exchange_insurance_validity" value="{{ $selectedExchangeInsuranceValidity }}" aria-labelledby="exchange_insurance_validity_label">
                            </div>
                        </div>

                        <div class="exchange-tyre-row">
                            <label>Tyre Replacement</label>
                            <label class="exchange-image-switch">
                                <input type="checkbox" checked>
                                <span></span>
                            </label>
                        </div>

                        <div class="segment-row four exchange-tyre-segment">
                            <label><input type="checkbox" name="exchange_tyre_replacements[]" value="front_lhs" @checked(in_array('front_lhs', $selectedExchangeTyreReplacements, true))><span>Front LHS</span></label>
                            <label><input type="checkbox" name="exchange_tyre_replacements[]" value="front_rhs" @checked(in_array('front_rhs', $selectedExchangeTyreReplacements, true))><span>Front RHS</span></label>
                            <label><input type="checkbox" name="exchange_tyre_replacements[]" value="rear_lhs" @checked(in_array('rear_lhs', $selectedExchangeTyreReplacements, true))><span>Rear LHS</span></label>
                            <label><input type="checkbox" name="exchange_tyre_replacements[]" value="rear_rhs" @checked(in_array('rear_rhs', $selectedExchangeTyreReplacements, true))><span>Rear RHS</span></label>
                        </div>

                        <div class="row split">
                            <div>
                                <label>Color</label>
                                <input type="text" name="exchange_color" value="{{ $selectedExchangeColor }}">
                            </div>
                            <div>
                                <label>Total KM</label>
                                <input type="number" min="0" name="exchange_mileage_km" value="{{ $selectedExchangeMileage }}">
                            </div>
                        </div>

                        <div class="row exchange-wide-field exchange-registration-row">
                            <div class="exchange-registration-field">
                                <div id="exchange_registration_no_label" class="exchange-visible-field-heading">Registration No</div>
                                <input type="text" id="exchange_registration_no" name="exchange_registration_no" value="{{ $selectedExchangeRegNo }}" aria-labelledby="exchange_registration_no_label">
                            </div>
                        </div>

                        <div class="row triple">
                            <div>
                                <input type="number" step="0.01" min="0" id="exchange_expected_price" name="exchange_expected_price" value="{{ $selectedExchangeExpectedPrice }}" placeholder="Expected Price">
                            </div>
                            <div>
                                <input type="number" step="0.01" min="0" id="exchange_quoted_price" name="exchange_quoted_price" value="{{ $selectedExchangeQuotedPrice }}" placeholder="Quoted Price">
                            </div>
                            <div>
                                <input type="number" step="0.01" id="exchange_price_difference" name="exchange_price_difference" class="exchange-difference-input" value="{{ $selectedExchangeDifference }}" placeholder="Difference" readonly>
                            </div>
                        </div>

                        <div class="exchange-image-section">
                            <div class="exchange-image-head">
                                <label>Add images</label>
                                <label class="exchange-image-switch">
                                    <input type="checkbox" id="bookingImagesToggle" checked>
                                    <span></span>
                                </label>
                            </div>

                            <div id="bookingImageBody">
                                <div class="exchange-upload-grid exchange-upload-grid-primary">
                                    @foreach($exchangeImageFields as $imageField)
                                    @php
                                    $existingImagePath = trim((string) ($imageField['path'] ?? ''));
                                    $existingImageUrl = $exchangeImageUrl($existingImagePath);
                                    $existingImageName = $existingImagePath !== '' ? basename($existingImagePath) : $imageField['label'];
                                    @endphp
                                    <div
                                        class="exchange-upload-tile {{ $existingImageUrl !== '' ? 'has-preview' : '' }}"
                                        data-existing-src="{{ $existingImageUrl }}"
                                        data-existing-name="{{ $existingImageName }}"
                                        data-default-title="{{ $imageField['label'] }}">
                                        <input type="hidden" class="exchange-remove-input" name="{{ $imageField['remove'] }}" value="0">
                                        <span class="exchange-upload-title">{{ $existingImageUrl !== '' ? $existingImageName : $imageField['label'] }}</span>
                                        <img
                                            class="exchange-upload-preview"
                                            alt="{{ $imageField['label'] }} preview"
                                            @if($existingImageUrl !=='' ) src="{{ $existingImageUrl }}" @else hidden @endif>
                                        <div class="exchange-upload-actions">
                                            <button type="button" class="exchange-preview-view" @disabled($existingImageUrl==='' )>View</button>
                                            <button type="button" class="exchange-preview-clear" @disabled($existingImageUrl==='' )>Remove</button>
                                        </div>
                                        <input class="exchange-file-input" type="file" name="{{ $imageField['name'] }}" accept=".jpg,.jpeg,.png,.webp">
                                    </div>
                                    @endforeach
                                </div>

                                <div class="exchange-more-head">
                                    <label>Add more images</label>
                                    <label class="exchange-image-switch">
                                        <input type="checkbox" id="bookingAddMoreImagesToggle" checked>
                                        <span></span>
                                    </label>
                                </div>

                                <div id="bookingExtraImageGrid" class="exchange-upload-grid exchange-upload-grid-extra">
                                    @for($extraIndex = 0; $extraIndex < $extraImageTileCount; $extraIndex++)
                                        @php
                                        $extraLabel='Car picture ' . ($extraIndex + 3);
                                        $existingExtraPath=trim((string) ($existingExtraExchangeImages[$extraIndex] ?? '' ));
                                        $existingExtraUrl=$exchangeImageUrl($existingExtraPath);
                                        $existingExtraName=$existingExtraPath !=='' ? basename($existingExtraPath) : $extraLabel;
                                        @endphp
                                        <div
                                        class="exchange-upload-tile exchange-upload-tile-extra {{ $existingExtraUrl !== '' ? 'has-preview' : '' }}"
                                        data-existing-src="{{ $existingExtraUrl }}"
                                        data-existing-name="{{ $existingExtraName }}"
                                        data-default-title="{{ $extraLabel }}">
                                        <button type="button" class="exchange-remove-btn" aria-label="Remove extra image slot">-</button>
                                        @if($existingExtraPath !== '')
                                        <input type="hidden" class="exchange-remove-existing" name="remove_exchange_extra_images[]" value="{{ $existingExtraPath }}" disabled>
                                        @endif
                                        <span class="exchange-upload-title">{{ $existingExtraUrl !== '' ? $existingExtraName : $extraLabel }}</span>
                                        <img
                                            class="exchange-upload-preview"
                                            alt="{{ $extraLabel }} preview"
                                            @if($existingExtraUrl !=='' ) src="{{ $existingExtraUrl }}" @else hidden @endif>
                                        <div class="exchange-upload-actions">
                                            <button type="button" class="exchange-preview-view" @disabled($existingExtraUrl==='' )>View</button>
                                            <button type="button" class="exchange-preview-clear" @disabled($existingExtraUrl==='' )>Remove</button>
                                        </div>
                                        <input class="exchange-file-input" type="file" name="extra_exchange_images[]" accept=".jpg,.jpeg,.png,.webp">
                                </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
</div>
</section>

<section class="booking-section offer-section {{ $currentStep === 4 ? 'active' : '' }}">
    <div class="section-head-inline">
        <h3 class="section-heading">Offer Details</h3>
        <label class="inline-edit-check offer-edit-toggle">
            <strong>Edit</strong>
            <input type="hidden" name="edit_offer_details" value="0">
            <input type="checkbox" id="toggleOfferEdit" name="edit_offer_details" value="1" @checked($isOfferEdit)>
            <span></span>
        </label>
    </div>

    <div class="offer-summary-panel" id="offerSummaryPanel">
        <div class="offer-summary-table">
            <div class="offer-summary-head">
                <span></span>
                <span>Cost</span>
                <span>Offer</span>
                <span>Payable</span>
            </div>
            <div class="offer-summary-row">
                <strong>VAT</strong>
                <span id="offerSummaryVatCost">{{ number_format((float) ($selectedOfferVatAmount ?? 0), 0) }}</span>
                <span id="offerSummaryVatOffer">{{ number_format((float) ($selectedOfferVatDiscount ?? 0), 0) }}</span>
                <span id="offerSummaryVatPayable">{{ number_format(max(0, (float) ($selectedOfferVatAmount ?? 0) - (float) ($selectedOfferVatDiscount ?? 0)), 0) }}</span>
            </div>
            <div class="offer-summary-row">
                <strong>Unit price (without vat)</strong>
                <span id="offerSummaryUnitCost">{{ number_format((float) ($selectedOfferUnitPrice ?? 0), 0) }}</span>
                <span id="offerSummaryUnitOffer">{{ number_format((float) ($selectedOfferUnitPriceDiscount ?? 0), 0) }}</span>
                <span id="offerSummaryUnitPayable">{{ number_format(max(0, (float) ($selectedOfferUnitPrice ?? 0) - (float) ($selectedOfferUnitPriceDiscount ?? 0)), 0) }}</span>
            </div>
        </div>

        <div class="offer-summary-total">
            <strong>Total</strong>
            <span id="offerSummaryTotalCost">{{ number_format((float) ($selectedOfferTotalCost ?? 0), 0) }}</span>
            <span id="offerSummaryTotalOffer">{{ number_format((float) ($selectedOfferTotalDiscount ?? 0), 0) }}</span>
            <span id="offerSummaryTotalPayable">{{ number_format((float) ($selectedOfferFinalPrice ?? 0), 0) }}</span>
        </div>

        <div class="offer-remarks">
            <label class="offer-remarks-toggle">
                <span>Add Remarks</span>
                <input type="checkbox" id="offerRemarksToggle" @checked($hasOfferRemark)>
                <i></i>
            </label>
            <textarea id="offerRemarksText" name="offer_remark" rows="4" placeholder="Type comment here......">{{ $selectedOfferRemark }}</textarea>
        </div>
    </div>

    <div class="offer-edit-group" id="offerEditGroup">
        <div class="offer-card">
            <div class="offer-card-title">Unit price (without vat)</div>
            <div class="offer-card-amount-row">
                <input type="number" step="0.01" min="0" name="offer_unit_price" id="offer_unit_price" value="{{ $selectedOfferUnitPrice }}">
            </div>
            <div class="offer-card-bottom-row">
                <label class="offer-free-check">
                    <input type="hidden" name="offer_unit_price_free" value="0">
                    <input type="checkbox" name="offer_unit_price_free" id="offer_unit_price_free" value="1" @checked($selectedOfferUnitPriceFree)>
                    <span>Free</span>
                </label>
                <input type="number" step="0.01" min="0" name="offer_unit_price_discount" id="offer_unit_price_discount" value="{{ $selectedOfferUnitPriceDiscount }}" placeholder="Discount amount">
            </div>
        </div>

        <div class="offer-card">
            <div class="offer-card-title">VAT</div>
            <div class="offer-card-amount-row">
                <input type="number" step="0.01" min="0" name="offer_vat_amount" id="offer_vat_amount" value="{{ $selectedOfferVatAmount }}">
            </div>
            <div class="offer-card-bottom-row">
                <label class="offer-free-check">
                    <input type="hidden" name="offer_vat_free" value="0">
                    <input type="checkbox" name="offer_vat_free" id="offer_vat_free" value="1" @checked($selectedOfferVatFree)>
                    <span>Free</span>
                </label>
                <input type="number" step="0.01" min="0" name="offer_vat_discount" id="offer_vat_discount" value="{{ $selectedOfferVatDiscount }}" placeholder="Discount amount">
            </div>
        </div>

        <div class="offer-total-panel">
            <div class="offer-total-head">
                <span>Total</span>
                <span>Cost</span>
                <span>Offer</span>
                <span>Final Offer Price</span>
            </div>
            <div class="offer-total-values">
                <span></span>
                <strong id="offerTotalCostDisplay">{{ number_format((float) ($selectedOfferTotalCost ?? 0), 0) }}</strong>
                <strong id="offerTotalDiscountDisplay">{{ number_format((float) ($selectedOfferTotalDiscount ?? 0), 0) }}</strong>
                <strong id="offerFinalPriceDisplay">{{ number_format((float) ($selectedOfferFinalPrice ?? 0), 0) }}</strong>
            </div>
        </div>

        <div class="offer-edit-save-row">
            <button type="button" class="offer-edit-save-btn" id="offerEditSaveBtn">Save</button>
        </div>

        <input type="hidden" name="offer_total_cost" id="offer_total_cost" value="{{ $selectedOfferTotalCost }}">
        <input type="hidden" name="offer_total_discount" id="offer_total_discount" value="{{ $selectedOfferTotalDiscount }}">
        <input type="hidden" name="offer_final_price" id="offer_final_price" value="{{ $selectedOfferFinalPrice }}">
    </div>
</section>

<section class="booking-section booking-form-section {{ $currentStep === 5 ? 'active' : '' }}">
    <div class="booking-form-review">
        <article class="booking-form-card">
            <h4>Booking Details</h4>
            <div class="booking-form-card-rows">
                <p><i></i><strong>SC Name</strong><em>:</em><span>{{ $bookingScName }}</span></p>
                <p><i></i><strong>Lead Source</strong><em>:</em><span>{{ $bookingLeadSource }}</span></p>
                <p><i></i><strong>Source of Information</strong><em>:</em><span>{{ $bookingSourceInfo }}</span></p>
                <p><i></i><strong>Model</strong><em>:</em><span>{{ strtoupper($selectedInterestedModel ?: 'N/A') }}</span></p>
                <p><i></i><strong>Variant</strong><em>:</em><span>{{ $selectedInterestedVariant ?: 'N/A' }}</span></p>
                <p><i></i><strong>Color</strong><em>:</em><span>{{ $selectedVehicleColor ?: 'N/A' }}</span></p>
            </div>
        </article>

        <article class="booking-form-card">
            <h4>Enquiry Details</h4>
            <div class="booking-form-card-rows compact">
                <p><i></i><strong>Date of Enquiry</strong><em>:</em><span>{{ $enquiryDateLabel }}</span></p>
                <p><i></i><strong>Name</strong><em>:</em><span>{{ $summaryName }}</span></p>
            </div>
        </article>

        <article class="booking-form-card">
            <h4>Personal Details</h4>
            <div class="booking-form-card-rows">
                <p><i></i><strong>Customer Name</strong><em>:</em><span>{{ $selectedName ?: $summaryName }}</span></p>
                <p><i></i><strong>Mobile No</strong><em>:</em><span>{{ $summaryMobile }}</span></p>
                <p><i></i><strong>Address</strong><em>:</em><span>{{ $summaryAddress ?: 'N/A' }}</span></p>
                <p><i></i><strong>Type of Customer</strong><em>:</em><span>{{ $selectedCustomerTypeLabel ?: 'N/A' }}</span></p>
                <p><i></i><strong>Profession</strong><em>:</em><span>{{ $selectedProfessionLabel ?: 'N/A' }}</span></p>
            </div>
        </article>

        <article class="booking-form-card">
            <h4>Buying Details</h4>
            <div class="booking-form-card-rows compact">
                <p><i></i><strong>First Time Buyer</strong><em>:</em><span>{{ $buyingYesNoLabel($selectedFirstTimeBuyer) ?: 'N/A' }}</span></p>
                <p><i></i><strong>Mode of Purchase</strong><em>:</em><span>{{ $purchaseModeLabel ?: 'N/A' }}</span></p>
                @if($selectedPurchaseMode === 'finance')
                <p><i></i><strong>Finance From</strong><em>:</em><span>{{ $financeDetailsLabel ?: 'N/A' }}</span></p>
                @endif
            </div>
        </article>

        <article class="booking-form-card">
            <h4>Exchange Details</h4>
            <div class="booking-form-card-rows compact">
                <p><i></i><strong>Interested in Exchange</strong><em>:</em><span>{{ $buyingYesNoLabel($selectedInterestedExchange) ?: 'N/A' }}</span></p>
                @if($selectedInterestedExchange === 'yes')
                <p><i></i><strong>Exchange Type</strong><em>:</em><span>{{ $exchangeTypeLabel ?: 'N/A' }}</span></p>
                <p><i></i><strong>Manufacture</strong><em>:</em><span>{{ $selectedExchangeBrand ?: 'N/A' }}</span></p>
                <p><i></i><strong>Model</strong><em>:</em><span>{{ $selectedExchangeModel ?: 'N/A' }}</span></p>
                <p><i></i><strong>Model Year</strong><em>:</em><span>{{ $selectedExchangeYear ?: 'N/A' }}</span></p>
                <p><i></i><strong>Ownership</strong><em>:</em><span>{{ $selectedExchangeOwnership ?: 'N/A' }}</span></p>
                <p><i></i><strong>Insurance Validity</strong><em>:</em><span>{{ $selectedExchangeInsuranceLabel ?: 'N/A' }}</span></p>
                <p><i></i><strong>Registration No</strong><em>:</em><span>{{ $selectedExchangeRegNo ?: 'N/A' }}</span></p>
                <p><i></i><strong>Total Km</strong><em>:</em><span>{{ $selectedExchangeMileage !== null && $selectedExchangeMileage !== '' ? number_format((float) $selectedExchangeMileage, 0) : 'N/A' }}</span></p>
                @endif
            </div>
        </article>

        <article class="booking-form-card offer-review-card">
            <h4>Offer Details</h4>
            <div class="booking-offer-review">
                <div class="booking-offer-head">
                    <span></span>
                    <span>Cost</span>
                    <span>Offer</span>
                    <span>Payable</span>
                </div>
                <div class="booking-offer-row">
                    <strong>Vat</strong>
                    <span>{{ number_format((float) ($selectedOfferVatAmount ?? 0), 0) }}</span>
                    <span>{{ number_format((float) ($selectedOfferVatDiscount ?? 0), 0) }}</span>
                    <span>{{ number_format(max(0, (float) ($selectedOfferVatAmount ?? 0) - (float) ($selectedOfferVatDiscount ?? 0)), 0) }}</span>
                </div>
                <div class="booking-offer-row">
                    <strong>Unit price (without vat)</strong>
                    <span>{{ number_format((float) ($selectedOfferUnitPrice ?? 0), 0) }}</span>
                    <span>{{ number_format((float) ($selectedOfferUnitPriceDiscount ?? 0), 0) }}</span>
                    <span>{{ number_format(max(0, (float) ($selectedOfferUnitPrice ?? 0) - (float) ($selectedOfferUnitPriceDiscount ?? 0)), 0) }}</span>
                </div>
                <div class="booking-offer-total">
                    <strong>Total</strong>
                    <span>{{ number_format((float) ($selectedOfferTotalCost ?? 0), 0) }}</span>
                    <span>{{ number_format((float) ($selectedOfferTotalDiscount ?? 0), 0) }}</span>
                    <span>{{ number_format((float) ($selectedOfferFinalPrice ?? 0), 0) }}</span>
                </div>
            </div>
        </article>
    </div>

    <div class="booking-form-fields">
        <div>
            <label>Expected Date of Delivery</label>
            <input type="date" name="expected_delivery_date" value="{{ $selectedExpectedDeliveryDate }}">
        </div>
        <div>
            <label>Date of Booking</label>
            <input type="date" name="booking_date" value="{{ $selectedBookingDate }}">
        </div>
        <div>
            <label>Amount Collected</label>
            <input id="amountCollectedInput" type="number" min="0" step="0.01" name="amount_collected" value="{{ $selectedAmountCollected }}" readonly>
        </div>
    </div>

    <div id="bookingReceiptModal" class="booking-receipt-modal hidden" aria-hidden="true">
        <div class="booking-receipt-card" role="dialog" aria-modal="true" aria-labelledby="bookingReceiptTitle">
            <div class="booking-receipt-head">
                <h4 id="bookingReceiptTitle">Booking Receipts</h4>
                <button type="button" id="bookingReceiptClose" class="booking-receipt-close" aria-label="Close receipt details">&times;</button>
            </div>

            <div id="bookingReceiptRows" class="booking-receipt-rows">
                @foreach($selectedBookingReceipts as $receiptIndex => $receipt)
                <div class="booking-receipt-row">
                    <div>
                        <label>Receipt Name/No</label>
                        <input type="text" name="booking_receipts[{{ $receiptIndex }}][receipt_name_no]" value="{{ $receipt['receipt_name_no'] ?? '' }}">
                    </div>
                    <div>
                        <label>Receipt Date</label>
                        <input type="date" name="booking_receipts[{{ $receiptIndex }}][receipt_date]" value="{{ $receipt['receipt_date'] ?? '' }}">
                    </div>
                    <div>
                        <label>Receipt Amount</label>
                        <input type="number" min="0" step="0.01" name="booking_receipts[{{ $receiptIndex }}][receipt_amount]" value="{{ $receipt['receipt_amount'] ?? '' }}" data-receipt-amount>
                    </div>
                    <div>
                        <label>Mode of Payment</label>
                        <select name="booking_receipts[{{ $receiptIndex }}][payment_mode]">
                            <option value="">Select mode</option>
                            @foreach($bookingReceiptPaymentModes as $paymentMode)
                            <option value="{{ $paymentMode }}" @selected(($receipt['payment_mode'] ?? '' )===$paymentMode)>{{ $paymentMode }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Receipt Type</label>
                        <input type="text" name="booking_receipts[{{ $receiptIndex }}][receipt_type]" value="Booking" readonly>
                    </div>
                    <button type="button" class="booking-receipt-remove" aria-label="Remove receipt">Remove</button>
                </div>
                @endforeach
            </div>

            <div class="booking-receipt-total">
                <span>Total Receipt Amount</span>
                <strong id="bookingReceiptTotalDisplay">{{ number_format((float) $selectedAmountCollected, 2) }}</strong>
            </div>

            <div class="booking-receipt-actions">
                <button type="button" id="bookingReceiptCancel">Cancel</button>
                <button type="button" id="bookingReceiptSave">Save</button>
                <button type="button" id="bookingReceiptAddMore">Add More</button>
            </div>
        </div>
    </div>
</section>

<div id="actionRow" class="action-row {{ $currentStep === 1 ? 'no-back' : '' }} {{ $currentStep === 5 ? 'step-five' : '' }}">
    @if($currentStep === 5)
    <a id="backAction" href="{{ $backUrl }}" class="booking-form-nav-btn">Back</a>
    <button type="submit" name="action_type" value="submit" class="booking-book-now-btn">Book Now</button>
    @else
    @if($currentStep > 1)
    <a id="backAction" href="{{ $backUrl }}" class="action-btn back-btn">Back</a>
    @endif
    <button id="saveExitAction" type="submit" name="action_type" value="save_exit" class="action-btn save-exit-btn">Save & Exit</button>
    <button type="submit" name="action_type" value="next" class="action-btn next-action-btn">Save & Next</button>
    @endif
</div>
</form>
</main>
</div>

@if(session('booking_submitted_popup'))
<div class="booking-submit-popup" id="bookingSubmitPopup" role="dialog" aria-modal="true" aria-labelledby="bookingSubmitTitle">
    <div class="booking-submit-popup-card">
        <div class="booking-submit-icon" aria-hidden="true">&#10003;</div>
        <h4 id="bookingSubmitTitle">Booking Submitted</h4>
        <p>{{ session('booking_submitted_message', 'Booking submitted successfully.') }}</p>
        <strong>Do you need to open Delivery now?</strong>
        <div class="booking-submit-popup-actions">
            <button type="button" class="booking-submit-popup-btn secondary" id="bookingSubmitPopupNo">No</button>
            <button type="button" class="booking-submit-popup-btn" id="bookingSubmitPopupYes">Yes</button>
        </div>
    </div>
</div>
@endif

<script type="application/json" id="bookingCompetitionMapJson">
    @json($competitionMap->toArray())
</script>
<script>
    (function initBookingCompetitionMap() {
        const mapEl = document.getElementById('bookingCompetitionMapJson');
        if (!mapEl) {
            window.BOOKING_COMPETITION_MAP = {};
            return;
        }

        try {
            window.BOOKING_COMPETITION_MAP = JSON.parse(mapEl.textContent || '{}');
        } catch (e) {
            window.BOOKING_COMPETITION_MAP = {};
        }
    })();

    (function() {
        const toggle = document.getElementById('sameAsToggle');
        const bookingForm = document.getElementById('bookingForm');
        const editBlock = document.getElementById('editBlock');
        const bookingSameAsCustomerInput = document.getElementById('bookingSameAsCustomer');
        const personalEditableInputs = document.querySelectorAll('[data-personal-editable]');
        const corporateNameRow = document.getElementById('corporateNameRow');
        const bookingContactList = document.getElementById('bookingContactList');
        const bookingMobileNumbersInput = document.getElementById('bookingMobileNumbers');
        const addBookingMobileBtn = document.getElementById('addBookingMobileBtn');
        const bookingSummaryCustomerName = document.getElementById('bookingSummaryCustomerName');
        const bookingSummaryAddress = document.getElementById('bookingSummaryAddress');
        const bookingSummaryMobile = document.getElementById('bookingSummaryMobile');
        const bookingSummaryEmail = document.getElementById('bookingSummaryEmail');
        const bookingSummaryCustomerType = document.getElementById('bookingSummaryCustomerType');
        const bookingSummaryCorporateRow = document.getElementById('bookingSummaryCorporateRow');
        const bookingSummaryCorporateName = document.getElementById('bookingSummaryCorporateName');
        const bookingSummaryProfession = document.getElementById('bookingSummaryProfession');
        const toggleBuyingVehicleEdit = document.getElementById('toggleBuyingVehicleEdit');
        const vehicleEditFields = document.getElementById('vehicleEditFields');
        const vehicleReadPill = document.getElementById('vehicleReadPill');
        const vehiclePillRemove = document.querySelector('.vehicle-pill-remove');
        const buyingSummaryCustomerName = document.getElementById('buyingSummaryCustomerName');
        const buyingSummaryAddress = document.getElementById('buyingSummaryAddress');
        const buyingSummaryMobile = document.getElementById('buyingSummaryMobile');
        const buyingSummaryInterested = document.getElementById('buyingSummaryInterested');
        const buyingSummaryColor = document.getElementById('buyingSummaryColor');
        const buyingSummaryQuote = document.getElementById('buyingSummaryQuote');
        const buyingSummaryTestDrive = document.getElementById('buyingSummaryTestDrive');
        const buyingSummaryTestDriveDetails = document.getElementById('buyingSummaryTestDriveDetails');
        const buyingSummaryCompetition = document.getElementById('buyingSummaryCompetition');
        const buyingSummaryFirstTime = document.getElementById('buyingSummaryFirstTime');
        const buyingSummaryPurchaseMode = document.getElementById('buyingSummaryPurchaseMode');
        const exchangeSummaryCustomerName = document.getElementById('exchangeSummaryCustomerName');
        const exchangeSummaryInterested = document.getElementById('exchangeSummaryInterested');
        const exchangeSummaryMobile = document.getElementById('exchangeSummaryMobile');
        const exchangeSummaryInterestedExchange = document.getElementById('exchangeSummaryInterestedExchange');
        const exchangeSummaryType = document.getElementById('exchangeSummaryType');
        const exchangeSummaryBrand = document.getElementById('exchangeSummaryBrand');
        const exchangeSummaryModel = document.getElementById('exchangeSummaryModel');
        const exchangeSummaryYear = document.getElementById('exchangeSummaryYear');
        const exchangeSummaryOwnership = document.getElementById('exchangeSummaryOwnership');
        const exchangeSummaryInsurance = document.getElementById('exchangeSummaryInsurance');
        const exchangeSummaryColor = document.getElementById('exchangeSummaryColor');
        const exchangeSummaryMileage = document.getElementById('exchangeSummaryMileage');
        const exchangeSummaryRegistration = document.getElementById('exchangeSummaryRegistration');
        const exchangeSummaryExpected = document.getElementById('exchangeSummaryExpected');
        const exchangeSummaryQuoted = document.getElementById('exchangeSummaryQuoted');
        const exchangeSummaryDifference = document.getElementById('exchangeSummaryDifference');
        const interestedModelInput = document.getElementById('interested_model');
        const interestedEngineInput = document.getElementById('interested_engine');
        const interestedVariantInput = document.getElementById('interested_variant');
        const quoteDateWrap = document.getElementById('quoteDateWrap');
        const testDriveYesWrap = document.getElementById('testDriveYesWrap');
        const testDriveNoWrap = document.getElementById('testDriveNoWrap');
        const bookingTestDriveVehicleSelect = document.getElementById('bookingTestDriveVehicleSelect');
        const bookingTestDriveVehicleOtherWrap = document.getElementById('bookingTestDriveVehicleOtherWrap');
        const bookingTestDriveNoReasonSelect = document.getElementById('bookingTestDriveNoReasonSelect');
        const bookingTestDriveNoReasonOtherWrap = document.getElementById('bookingTestDriveNoReasonOtherWrap');
        const financeFormWrap = document.getElementById('financeFormWrap');
        const financeBankWrap = document.getElementById('financeBankWrap');
        const financeOtherWrap = document.getElementById('financeOtherWrap');
        const competitionWrap = document.getElementById('competitionWrap');
        const competitionBrandSelect = document.getElementById('competition_brand');
        const competitionModelSelect = document.getElementById('competition_model');
        const competitionYearSelect = document.getElementById('competition_model_year');
        const existingVehicleWrap = document.getElementById('existingVehicleWrap');
        const existingVehicleBrandSelect = document.getElementById('existing_vehicle_brand');
        const existingVehicleModelSelect = document.getElementById('existing_vehicle_model');
        const exchangeTypeRow = document.getElementById('exchangeTypeRow');
        const exchangePurchaseRow = document.getElementById('exchangePurchaseRow');
        const exchangePurchaseValueInput = document.getElementById('exchangePurchaseValueInput');
        const exchangeDetailsWrap = document.getElementById('exchangeDetailsWrap');
        const toggleExchangeEdit = document.getElementById('toggleExchangeEdit');
        const exchangeEditFields = document.getElementById('exchangeEditFields');
        const exchangeBrandModelRow = document.getElementById('exchangeBrandModelRow');
        const exchangeBrandSelect = document.getElementById('exchange_vehicle_brand');
        const exchangeModelSelect = document.getElementById('exchange_vehicle_model');
        const exchangeModelBackupInput = document.getElementById('exchange_vehicle_model_backup');
        const exchangeExpectedPriceInput = document.getElementById('exchange_expected_price');
        const exchangeQuotedPriceInput = document.getElementById('exchange_quoted_price');
        const exchangeDifferenceInput = document.getElementById('exchange_price_difference');
        const bookingImagesToggle = document.getElementById('bookingImagesToggle');
        const bookingImageBody = document.getElementById('bookingImageBody');
        const bookingAddMoreImagesToggle = document.getElementById('bookingAddMoreImagesToggle');
        const bookingExtraImageGrid = document.getElementById('bookingExtraImageGrid');
        const exchangePreviewObjectUrls = new WeakMap();
        const amountCollectedInput = document.getElementById('amountCollectedInput');
        const bookingReceiptModal = document.getElementById('bookingReceiptModal');
        const bookingReceiptRows = document.getElementById('bookingReceiptRows');
        const bookingReceiptClose = document.getElementById('bookingReceiptClose');
        const bookingReceiptCancel = document.getElementById('bookingReceiptCancel');
        const bookingReceiptSave = document.getElementById('bookingReceiptSave');
        const bookingReceiptAddMore = document.getElementById('bookingReceiptAddMore');
        const bookingReceiptTotalDisplay = document.getElementById('bookingReceiptTotalDisplay');
        const bookingReceiptPaymentModes = @json($bookingReceiptPaymentModes);
        let bookingReceiptSnapshot = null;
        const purchaseOrderTile = document.getElementById('purchaseOrderTile');
        const purchaseOrderInput = document.getElementById('purchase_order_image');
        const purchaseOrderPreview = document.getElementById('purchaseOrderPreview');
        const purchaseOrderRemove = document.getElementById('purchaseOrderRemove');
        const purchaseOrderRemoveInput = document.getElementById('remove_purchase_order_image');
        const purchaseOrderAdd = document.getElementById('purchaseOrderAdd');
        const purchaseOrderView = document.getElementById('purchaseOrderView');
        const purchaseOrderClear = document.getElementById('purchaseOrderClear');
        let purchaseOrderObjectUrl = null;
        const toggleOfferEdit = document.getElementById('toggleOfferEdit');
        const offerEditGroup = document.getElementById('offerEditGroup');
        const offerUnitPriceInput = document.getElementById('offer_unit_price');
        const offerUnitPriceDiscountInput = document.getElementById('offer_unit_price_discount');
        const offerUnitPriceFreeInput = document.getElementById('offer_unit_price_free');
        const offerVatAmountInput = document.getElementById('offer_vat_amount');
        const offerVatDiscountInput = document.getElementById('offer_vat_discount');
        const offerVatFreeInput = document.getElementById('offer_vat_free');
        const offerTotalCostInput = document.getElementById('offer_total_cost');
        const offerTotalDiscountInput = document.getElementById('offer_total_discount');
        const offerFinalPriceInput = document.getElementById('offer_final_price');
        const offerTotalCostDisplay = document.getElementById('offerTotalCostDisplay');
        const offerTotalDiscountDisplay = document.getElementById('offerTotalDiscountDisplay');
        const offerFinalPriceDisplay = document.getElementById('offerFinalPriceDisplay');
        const offerSummaryPanel = document.getElementById('offerSummaryPanel');
        const offerSummaryVatCost = document.getElementById('offerSummaryVatCost');
        const offerSummaryVatOffer = document.getElementById('offerSummaryVatOffer');
        const offerSummaryVatPayable = document.getElementById('offerSummaryVatPayable');
        const offerSummaryUnitCost = document.getElementById('offerSummaryUnitCost');
        const offerSummaryUnitOffer = document.getElementById('offerSummaryUnitOffer');
        const offerSummaryUnitPayable = document.getElementById('offerSummaryUnitPayable');
        const offerSummaryTotalCost = document.getElementById('offerSummaryTotalCost');
        const offerSummaryTotalOffer = document.getElementById('offerSummaryTotalOffer');
        const offerSummaryTotalPayable = document.getElementById('offerSummaryTotalPayable');
        const offerRemarksToggle = document.getElementById('offerRemarksToggle');
        const offerRemarksText = document.getElementById('offerRemarksText');
        const offerEditSaveBtn = document.getElementById('offerEditSaveBtn');
        const bookingStepInput = document.querySelector('input[name="booking_step"]');
        const bookingActionTypeFallback = document.getElementById('bookingActionTypeFallback');
        const actionRow = document.getElementById('actionRow');
        const backAction = document.getElementById('backAction');
        const saveExitAction = document.getElementById('saveExitAction');
        const vehiclePriceMap = {};

        function syncEditState() {
            if (!toggle || !editBlock) return;
            const editable = toggle.checked;

            editBlock.classList.toggle('read-only', !editable);
            if (bookingSameAsCustomerInput) {
                bookingSameAsCustomerInput.value = editable ? '0' : '1';
            }

            personalEditableInputs.forEach((input) => {
                if (input.id === 'bookingMobileNumbers') {
                    return;
                }

                if (input.tagName === 'INPUT' && input.type === 'radio') {
                    input.disabled = !editable;
                    return;
                }

                if (input.tagName === 'SELECT') {
                    input.disabled = !editable;
                    return;
                }

                if (input.tagName === 'INPUT') {
                    input.readOnly = !editable;
                }
            });

            document.querySelectorAll('.booking-mobile-input').forEach((input) => {
                input.readOnly = !editable;
            });

            document.querySelectorAll('.mini-add-btn, .mini-remove-btn').forEach((button) => {
                button.disabled = !editable;
            });
        }

        function syncCorporateRow() {
            if (!corporateNameRow) return;
            corporateNameRow.classList.toggle('hidden', picked('customer_type') !== 'corporate');
        }

        function syncBookingMobileNumbers() {
            if (!bookingContactList || !bookingMobileNumbersInput) return;
            const numbers = Array.from(bookingContactList.querySelectorAll('.booking-mobile-input'))
                .map((input) => input.value.trim())
                .filter(Boolean);
            bookingMobileNumbersInput.value = numbers.join(', ');
        }

        function setBookingActionFallback(value) {
            if (bookingActionTypeFallback && value) {
                bookingActionTypeFallback.value = value;
            }
        }

        function prepareBookingSubmit(event) {
            const submitter = event?.submitter;
            if (submitter instanceof HTMLButtonElement && submitter.name === 'action_type') {
                setBookingActionFallback(submitter.value);
            }

            syncBookingMobileNumbers();
            bookingForm?.querySelectorAll('[disabled]').forEach((field) => {
                field.disabled = false;
            });
        }

        function fieldValue(name) {
            const field = document.querySelector(`[name="${name}"]`);
            return field ? field.value.trim() : '';
        }

        function checkedValue(name) {
            const field = document.querySelector(`input[name="${name}"]:checked`);
            return field ? field.value : '';
        }

        function setSummaryText(target, value) {
            if (target) {
                const normalized = String(value || '').trim();
                const isEmpty = normalized === '' || normalized === 'N/A';
                target.textContent = isEmpty ? '' : normalized;

                const buyingRow = target.closest('.buying-summary-row');
                if (buyingRow) {
                    buyingRow.classList.toggle('buying-summary-empty', isEmpty);
                }
            }
        }

        function syncBookingPersonalSummary() {
            const customerTypeLabels = {
                individual: 'Individual',
                corporate: 'Corporate',
            };
            const professionLabels = {
                salaried: 'Salaried',
                self_employed: 'Self Employed',
                other: 'Other',
                not_asked: 'I Did Not Ask',
            };

            syncBookingMobileNumbers();

            const customerName = [fieldValue('title'), fieldValue('name')]
                .filter(Boolean)
                .join(' ');
            const address = ['address1', 'address2', 'location', 'district', 'state']
                .map(fieldValue)
                .filter(Boolean)
                .join(', ');

            setSummaryText(bookingSummaryCustomerName, customerName);
            setSummaryText(bookingSummaryAddress, address);
            setSummaryText(bookingSummaryMobile, bookingMobileNumbersInput ? bookingMobileNumbersInput.value.trim() : '');
            setSummaryText(bookingSummaryEmail, fieldValue('email'));
            setSummaryText(buyingSummaryCustomerName, customerName);
            setSummaryText(buyingSummaryAddress, address);
            setSummaryText(buyingSummaryMobile, bookingMobileNumbersInput ? bookingMobileNumbersInput.value.trim() : '');
            setSummaryText(bookingSummaryCustomerType, customerTypeLabels[checkedValue('customer_type')] || '');
            const isCorporate = checkedValue('customer_type') === 'corporate';
            if (bookingSummaryCorporateRow) {
                bookingSummaryCorporateRow.classList.toggle('hidden', !isCorporate);
            }
            setSummaryText(bookingSummaryCorporateName, isCorporate ? fieldValue('corporate_name') : '');
            setSummaryText(bookingSummaryProfession, professionLabels[checkedValue('profession')] || '');
            syncExchangeDetailsSummary();
        }

        function addBookingMobileInput(value = '') {
            if (!bookingContactList) return;

            const row = document.createElement('div');
            row.className = 'contact-pill-wrap';
            row.innerHTML = `
                <input type="text" class="booking-mobile-input" value="">
                <button type="button" class="mini-remove-btn" aria-label="Remove contact">&times;</button>
            `;

            const input = row.querySelector('.booking-mobile-input');
            const removeButton = row.querySelector('.mini-remove-btn');
            if (input) {
                input.value = value;
                input.readOnly = toggle ? !toggle.checked : false;
                input.addEventListener('input', syncBookingPersonalSummary);
            }
            if (removeButton) {
                removeButton.disabled = toggle ? !toggle.checked : false;
            }

            bookingContactList.appendChild(row);
            syncBookingPersonalSummary();
            input?.focus();
        }

        function syncVehicleEditState() {
            if (!toggleBuyingVehicleEdit || !vehicleEditFields) return;
            vehicleEditFields.classList.toggle('hidden', !toggleBuyingVehicleEdit.checked);
        }

        function syncVehiclePill() {
            if (!vehicleReadPill) return;
            const parts = [
                interestedModelInput ? interestedModelInput.value.trim() : '',
                interestedEngineInput ? interestedEngineInput.value.trim() : '',
                interestedVariantInput ? interestedVariantInput.value.trim() : '',
            ].filter(Boolean);

            vehicleReadPill.textContent = parts.length ? parts.join(' / ') : 'Not selected';
            syncBuyingDetailsSummary();
        }

        function yesNoSummary(value) {
            if (value === 'yes') return 'Yes';
            if (value === 'no') return 'No';
            return '';
        }

        function selectText(name) {
            const select = document.querySelector(`[name="${name}"]`);
            if (!select || !select.value) return '';
            const option = select.options ? select.options[select.selectedIndex] : null;
            return option ? option.text.trim() : select.value.trim();
        }

        function formatShortDate(value) {
            if (!value) return '';
            const date = new Date(value + 'T00:00:00');
            if (Number.isNaN(date.getTime())) return value;
            const month = date.toLocaleString('en-US', {
                month: 'short'
            });
            const day = String(date.getDate()).padStart(2, '0');
            return `${month} ${day},${date.getFullYear()}`;
        }

        function formatSummaryNumber(value) {
            const normalized = String(value ?? '').trim();
            if (!normalized) return '';
            const number = Number(normalized.replace(/,/g, ''));
            if (Number.isNaN(number)) return normalized;
            return Math.round(number).toLocaleString('en-US');
        }

        function exchangeTypeSummary(value) {
            if (value === 'in_house') return 'In House';
            if (value === 'outhouse') return 'Out House';
            return '';
        }

        function setExchangeSummaryText(target, value) {
            if (!target) return;
            const normalized = String(value ?? '').trim();
            target.textContent = normalized && normalized !== 'N/A' && normalized !== 'Not selected' ? normalized : '-';
            const row = target.closest('.exchange-summary-row');
            if (row) {
                row.classList.toggle('exchange-summary-empty', target.textContent === '-');
            }
        }

        function syncExchangeDetailsSummary() {
            const interested = vehicleReadPill ? vehicleReadPill.textContent.trim() : '';
            const exchangeModel = selectText('exchange_vehicle_model')
                || (exchangeModelSelect ? exchangeModelSelect.dataset.selectedModel || '' : '')
                || (exchangeModelBackupInput ? exchangeModelBackupInput.value.trim() : '');

            setExchangeSummaryText(exchangeSummaryCustomerName, [fieldValue('title'), fieldValue('name')].filter(Boolean).join(' '));
            setExchangeSummaryText(exchangeSummaryInterested, interested && interested !== 'Not selected' ? interested.toUpperCase() : '');
            setExchangeSummaryText(exchangeSummaryMobile, bookingMobileNumbersInput ? bookingMobileNumbersInput.value.trim() : '');
            setExchangeSummaryText(exchangeSummaryInterestedExchange, yesNoSummary(picked('interested_in_exchange')));
            setExchangeSummaryText(exchangeSummaryType, exchangeTypeSummary(picked('exchange_type')));
            setExchangeSummaryText(exchangeSummaryBrand, selectText('exchange_vehicle_brand'));
            setExchangeSummaryText(exchangeSummaryModel, exchangeModel);
            setExchangeSummaryText(exchangeSummaryYear, selectText('exchange_manufacture_year'));
            setExchangeSummaryText(exchangeSummaryOwnership, selectText('exchange_ownership'));
            setExchangeSummaryText(exchangeSummaryInsurance, formatShortDate(fieldValue('exchange_insurance_validity')));
            setExchangeSummaryText(exchangeSummaryColor, fieldValue('exchange_color'));
            setExchangeSummaryText(exchangeSummaryMileage, formatSummaryNumber(fieldValue('exchange_mileage_km')));
            setExchangeSummaryText(exchangeSummaryRegistration, fieldValue('exchange_registration_no'));
            setExchangeSummaryText(exchangeSummaryExpected, formatSummaryNumber(fieldValue('exchange_expected_price')));
            setExchangeSummaryText(exchangeSummaryQuoted, formatSummaryNumber(fieldValue('exchange_quoted_price')));
            setExchangeSummaryText(exchangeSummaryDifference, formatSummaryNumber(fieldValue('exchange_price_difference')));
        }

        function syncBuyingDetailsSummary() {
            const interested = vehicleReadPill ? vehicleReadPill.textContent.trim() : '';
            const quote = picked('quote_taken');
            const testDrive = picked('test_drive_given');
            const competition = picked('interested_in_competition');
            const firstTimeBuyer = picked('first_time_buyer');
            const purchaseMode = picked('purchase_mode');

            setSummaryText(buyingSummaryInterested, interested && interested !== 'Not selected' ? interested : '');
            setSummaryText(buyingSummaryColor, selectText('interested_vehicle_color'));
            if (quote === 'yes') {
                setSummaryText(buyingSummaryQuote, `Yes - ${formatShortDate(fieldValue('quote_date'))}`);
            } else {
                setSummaryText(buyingSummaryQuote, yesNoSummary(quote));
            }
            setSummaryText(buyingSummaryTestDrive, yesNoSummary(testDrive));

            if (testDrive === 'yes') {
                const selectedVehicle = fieldValue('test_drive_vehicle_model');
                const vehicle = selectedVehicle === 'Other' ?
                    fieldValue('test_drive_vehicle_model_other') :
                    (selectText('test_drive_vehicle_model') || (interested && interested !== 'Not selected' ? interested : ''));
                const date = formatShortDate(fieldValue('test_drive_date'));
                setSummaryText(
                    buyingSummaryTestDriveDetails,
                    [
                        vehicle ? `By ${vehicle}` : '',
                        date ? `on ${date}` : '',
                    ].filter(Boolean).join(' ')
                );
            } else if (testDrive === 'no') {
                const noReason = fieldValue('test_drive_not_given_reason') === 'Others' ?
                    fieldValue('test_drive_not_given_reason_other') :
                    selectText('test_drive_not_given_reason');
                setSummaryText(buyingSummaryTestDriveDetails, noReason);
            } else {
                setSummaryText(buyingSummaryTestDriveDetails, '');
            }

            if (competition === 'yes') {
                const competitionVehicle = [selectText('competition_brand'), selectText('competition_model'), selectText('competition_model_year')].filter(Boolean).join(' ');
                setSummaryText(buyingSummaryCompetition, competitionVehicle ? `Yes - ${competitionVehicle}` : 'Yes');
            } else if (competition === 'not_asked') {
                setSummaryText(buyingSummaryCompetition, 'I did not ask');
            } else {
                setSummaryText(buyingSummaryCompetition, yesNoSummary(competition));
            }

            if (firstTimeBuyer === 'no') {
                const existingVehicle = [
                    selectText('existing_vehicle_brand'),
                    selectText('existing_vehicle_model'),
                    selectText('existing_vehicle_year'),
                ].filter(Boolean).join(' ');
                setSummaryText(buyingSummaryFirstTime, existingVehicle ? `No - ${existingVehicle}` : 'No');
            } else {
                setSummaryText(buyingSummaryFirstTime, yesNoSummary(firstTimeBuyer));
            }
            if (purchaseMode === 'finance') {
                const financeForm = checkedValue('finance_form');
                const financeLabel = {
                    in_house: 'In House',
                    self: 'Self',
                    other: 'Other',
                } [financeForm] || '';
                const financeDetail = ['in_house', 'self'].includes(financeForm) ?
                    selectText('finance_bank') :
                    fieldValue('finance_other_details');
                setSummaryText(buyingSummaryPurchaseMode, ['Finance', financeLabel, financeDetail].filter(Boolean).join(' - '));
            } else {
                setSummaryText(buyingSummaryPurchaseMode, purchaseMode === 'cash' ? 'Cash' : '');
            }

            syncExchangeDetailsSummary();
        }

        function setSelectOptions(selectEl, values, placeholder, selectedValue) {
            if (!selectEl) return;
            const safeSelected = selectedValue || '';

            const options = [new Option(placeholder, '')];
            values.forEach((value) => {
                const option = new Option(value, value);
                option.selected = value === safeSelected;
                options.push(option);
            });

            if (safeSelected && !values.includes(safeSelected)) {
                const selectedOption = new Option(safeSelected, safeSelected);
                selectedOption.selected = true;
                options.push(selectedOption);
            }

            selectEl.replaceChildren(...options);
        }

        async function loadEngines(model, selectedEngine) {
            if (!interestedEngineInput || !interestedVariantInput) return;
            if (!model) {
                setSelectOptions(interestedEngineInput, [], 'Select Engine Type', '');
                setSelectOptions(interestedVariantInput, [], 'Select Variant', '');
                syncVehiclePill();
                return;
            }

            try {
                const res = await fetch('/get-engines/' + encodeURIComponent(model));
                const data = await res.json();
                const engines = data.map((item) => item.engine_type).filter(Boolean);
                setSelectOptions(interestedEngineInput, engines, 'Select Engine Type', selectedEngine || '');
            } catch (e) {
                setSelectOptions(interestedEngineInput, [], 'Select Engine Type', selectedEngine || '');
            }

            syncVehiclePill();
        }

        async function loadVariants(model, engine, selectedVariant) {
            if (!interestedVariantInput) return;
            if (!model || !engine) {
                setSelectOptions(interestedVariantInput, [], 'Select Variant', '');
                syncVehiclePill();
                return;
            }

            try {
                const res = await fetch('/get-variants/' + encodeURIComponent(model) + '/' + encodeURIComponent(engine));
                const data = await res.json();
                const variants = data.map((item) => item.variant).filter(Boolean);
                Object.keys(vehiclePriceMap).forEach((key) => delete vehiclePriceMap[key]);
                data.forEach((item) => {
                    if (!item.variant) {
                        return;
                    }

                    vehiclePriceMap[item.variant] = {
                        unitPrice: parseFloat(item.unit_price || '0') || 0,
                        vatAmount: parseFloat(item.vat_amount || '0') || 0,
                    };
                });
                setSelectOptions(interestedVariantInput, variants, 'Select Variant', selectedVariant || '');
            } catch (e) {
                setSelectOptions(interestedVariantInput, [], 'Select Variant', selectedVariant || '');
            }

            syncVehiclePill();
            applySelectedVehiclePrice(false);
        }

        function applySelectedVehiclePrice(force = true) {
            if (!interestedVariantInput || !offerUnitPriceInput || !offerVatAmountInput) {
                return;
            }

            const selectedPrices = vehiclePriceMap[interestedVariantInput.value || ''];
            if (!selectedPrices) {
                return;
            }

            const shouldApplyUnit = force || toMoney(offerUnitPriceInput.value) <= 0;
            const shouldApplyVat = force || toMoney(offerVatAmountInput.value) <= 0;

            if (shouldApplyUnit) {
                offerUnitPriceInput.value = selectedPrices.unitPrice.toFixed(2);
            }

            if (shouldApplyVat) {
                offerVatAmountInput.value = selectedPrices.vatAmount.toFixed(2);
            }

            syncOfferTotals();
        }

        function picked(name) {
            const selected = document.querySelector('input[name="' + name + '"]:checked');
            return selected ? selected.value : '';
        }

        function escapeReceiptValue(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function receiptRowTemplate(index, values = {}) {
            const paymentOptions = ['<option value="">Select mode</option>']
                .concat(bookingReceiptPaymentModes.map((mode) => {
                    const selected = mode === (values.payment_mode || '') ? ' selected' : '';
                    return `<option value="${mode}"${selected}>${mode}</option>`;
                }))
                .join('');

            return `
                <div class="booking-receipt-row">
                    <div>
                        <label>Receipt Name/No</label>
                        <input type="text" name="booking_receipts[${index}][receipt_name_no]" value="${escapeReceiptValue(values.receipt_name_no)}">
                    </div>
                    <div>
                        <label>Receipt Date</label>
                        <input type="date" name="booking_receipts[${index}][receipt_date]" value="${escapeReceiptValue(values.receipt_date)}">
                    </div>
                    <div>
                        <label>Receipt Amount</label>
                        <input type="number" min="0" step="0.01" name="booking_receipts[${index}][receipt_amount]" value="${escapeReceiptValue(values.receipt_amount)}" data-receipt-amount>
                    </div>
                    <div>
                        <label>Mode of Payment</label>
                        <select name="booking_receipts[${index}][payment_mode]">${paymentOptions}</select>
                    </div>
                    <div>
                        <label>Receipt Type</label>
                        <input type="text" name="booking_receipts[${index}][receipt_type]" value="Booking" readonly>
                    </div>
                    <button type="button" class="booking-receipt-remove" aria-label="Remove receipt">Remove</button>
                </div>
            `;
        }

        function serializeReceiptRows() {
            if (!bookingReceiptRows) return [];

            return Array.from(bookingReceiptRows.querySelectorAll('.booking-receipt-row')).map((row) => ({
                receipt_name_no: row.querySelector('[name$="[receipt_name_no]"]')?.value || '',
                receipt_date: row.querySelector('[name$="[receipt_date]"]')?.value || '',
                receipt_amount: row.querySelector('[name$="[receipt_amount]"]')?.value || '',
                payment_mode: row.querySelector('[name$="[payment_mode]"]')?.value || '',
                receipt_type: 'Booking',
            }));
        }

        function renderReceiptRows(rows) {
            if (!bookingReceiptRows) return;
            const safeRows = rows.length ? rows : [{}];
            bookingReceiptRows.innerHTML = safeRows
                .map((row, index) => receiptRowTemplate(index, row))
                .join('');
            syncReceiptTotal();
        }

        function syncReceiptTotal() {
            if (!bookingReceiptRows) return;
            const total = Array.from(bookingReceiptRows.querySelectorAll('[data-receipt-amount]'))
                .reduce((sum, input) => sum + (parseFloat(input.value || '0') || 0), 0);

            if (amountCollectedInput) {
                amountCollectedInput.value = total.toFixed(2);
            }

            if (bookingReceiptTotalDisplay) {
                bookingReceiptTotalDisplay.textContent = total.toFixed(2);
            }
        }

        function openReceiptModal() {
            if (!bookingReceiptModal) return;
            bookingReceiptSnapshot = serializeReceiptRows();
            bookingReceiptModal.classList.remove('hidden');
            bookingReceiptModal.setAttribute('aria-hidden', 'false');
            bookingReceiptModal.querySelector('input, select, button')?.focus();
        }

        function closeReceiptModal() {
            if (!bookingReceiptModal) return;
            bookingReceiptModal.classList.add('hidden');
            bookingReceiptModal.setAttribute('aria-hidden', 'true');
        }

        function syncBuyingState() {
            if (quoteDateWrap) {
                quoteDateWrap.classList.toggle('hidden', picked('quote_taken') !== 'yes');
            }

            if (testDriveYesWrap) {
                testDriveYesWrap.classList.toggle('hidden', picked('test_drive_given') !== 'yes');
            }

            if (testDriveNoWrap) {
                testDriveNoWrap.classList.toggle('hidden', picked('test_drive_given') !== 'no');
            }

            if (bookingTestDriveNoReasonOtherWrap) {
                const showOtherReason = bookingTestDriveNoReasonSelect && bookingTestDriveNoReasonSelect.value === 'Others';
                bookingTestDriveNoReasonOtherWrap.classList.toggle('hidden', !showOtherReason);
                if (!showOtherReason) {
                    const otherInput = bookingTestDriveNoReasonOtherWrap.querySelector('input');
                    if (otherInput) {
                        otherInput.value = '';
                    }
                }
            }

            if (bookingTestDriveVehicleOtherWrap) {
                const showOtherVehicle = bookingTestDriveVehicleSelect && bookingTestDriveVehicleSelect.value === 'Other';
                bookingTestDriveVehicleOtherWrap.classList.toggle('hidden', !showOtherVehicle);
                if (!showOtherVehicle) {
                    const otherInput = bookingTestDriveVehicleOtherWrap.querySelector('input');
                    if (otherInput) {
                        otherInput.value = '';
                    }
                }
            }

            if (financeFormWrap) {
                financeFormWrap.classList.toggle('hidden', picked('purchase_mode') !== 'finance');
            }

            const selectedFinanceForm = checkedValue('finance_form');
            if (financeBankWrap) {
                const showBank = picked('purchase_mode') === 'finance' && ['in_house', 'self'].includes(selectedFinanceForm);
                financeBankWrap.classList.toggle('hidden', !showBank);
                if (!showBank) {
                    const bankSelect = financeBankWrap.querySelector('select');
                    if (bankSelect) {
                        bankSelect.value = '';
                    }
                }
            }

            if (financeOtherWrap) {
                const showOtherFinance = picked('purchase_mode') === 'finance' && selectedFinanceForm === 'other';
                financeOtherWrap.classList.toggle('hidden', !showOtherFinance);
                if (!showOtherFinance) {
                    const otherInput = financeOtherWrap.querySelector('input');
                    if (otherInput) {
                        otherInput.value = '';
                    }
                }
            }

            if (competitionWrap) {
                competitionWrap.classList.toggle('hidden', picked('interested_in_competition') !== 'yes');
            }

            if (existingVehicleWrap) {
                existingVehicleWrap.classList.toggle('hidden', picked('first_time_buyer') !== 'no');
            }

            if (exchangeTypeRow) {
                const exchangeTypeRadios = exchangeTypeRow.querySelectorAll('input[name="exchange_type"]');
                if (!picked('exchange_type')) {
                    const defaultExchangeType = exchangeTypeRow.querySelector('input[name="exchange_type"][value="in_house"]');
                    if (defaultExchangeType) {
                        defaultExchangeType.checked = true;
                    }
                }
            }

            if (exchangePurchaseRow) {
                const showPurchaseValue = picked('exchange_type') === 'in_house';
                exchangePurchaseRow.classList.toggle('hidden', !showPurchaseValue);
                if (!showPurchaseValue && exchangePurchaseValueInput) {
                    exchangePurchaseValueInput.value = '';
                }
            }

            if (exchangeDetailsWrap) {
                const showExchangeDetails = picked('interested_in_exchange') === 'yes' &&
                    ['in_house', 'outhouse'].includes(picked('exchange_type'));
                exchangeDetailsWrap.classList.toggle('hidden', !showExchangeDetails);

                // Show full exchange input fields when Yes + (In-House/Outhouse),
                // regardless of Edit checkbox state.
                if (exchangeEditFields) {
                    exchangeEditFields.classList.toggle('hidden', !showExchangeDetails);
                }
                syncExchangeEditState();
            }

            syncExchangeNoActionMode();
            syncBuyingDetailsSummary();
        }

        function syncExchangeEditState() {
            if (!exchangeDetailsWrap || !exchangeEditFields) return;
            const showExchangeDetails = !exchangeDetailsWrap.classList.contains('hidden');
            exchangeEditFields.classList.toggle('hidden', !showExchangeDetails);
            if (exchangeBrandModelRow) {
                const requiresInput = exchangeBrandModelRow.dataset.requiresInput === '1' ||
                    !((exchangeBrandSelect?.value || '').trim() && (exchangeModelSelect?.value || exchangeModelSelect?.dataset.selectedModel || '').trim());
                exchangeBrandModelRow.classList.toggle('hidden', !(showExchangeDetails && (requiresInput || (toggleExchangeEdit && toggleExchangeEdit.checked))));
            }
        }

        function syncCompetitionModels() {
            if (!competitionBrandSelect || !competitionModelSelect) return;

            const map = window.BOOKING_COMPETITION_MAP || {};
            const brand = competitionBrandSelect.value || '';
            const selectedModel = competitionModelSelect.dataset.selectedModel || competitionModelSelect.value || '';
            const models = Array.isArray(map[brand]) ? map[brand] : [];

            setSelectOptions(competitionModelSelect, models, 'Select Model', selectedModel);
            competitionModelSelect.dataset.selectedModel = '';
            syncBuyingDetailsSummary();
        }

        function syncExistingVehicleModels() {
            if (!existingVehicleBrandSelect || !existingVehicleModelSelect) return;

            const map = window.BOOKING_COMPETITION_MAP || {};
            const brand = existingVehicleBrandSelect.value || '';
            const selectedModel = existingVehicleModelSelect.dataset.selectedModel || existingVehicleModelSelect.value || '';
            const models = Array.isArray(map[brand]) ? map[brand] : [];

            setSelectOptions(existingVehicleModelSelect, models, 'Select Model', selectedModel);
            existingVehicleModelSelect.dataset.selectedModel = '';
            syncBuyingDetailsSummary();
        }

        function syncExchangeModels() {
            if (!exchangeBrandSelect || !exchangeModelSelect) return;

            const map = window.BOOKING_COMPETITION_MAP || {};
            const brand = exchangeBrandSelect.value || '';
            const selectedModel = exchangeModelSelect.dataset.selectedModel || exchangeModelSelect.value || '';
            const models = Array.isArray(map[brand]) ? map[brand] : [];

            setSelectOptions(exchangeModelSelect, models, 'Select Model', selectedModel);
            exchangeModelSelect.dataset.selectedModel = '';
            if (exchangeModelBackupInput) {
                exchangeModelBackupInput.value = exchangeModelSelect.value || selectedModel || '';
            }
            syncExchangeDetailsSummary();
        }

        function syncExchangeDifference() {
            if (!exchangeExpectedPriceInput || !exchangeQuotedPriceInput || !exchangeDifferenceInput) return;

            const expected = parseFloat(exchangeExpectedPriceInput.value || '0');
            const quoted = parseFloat(exchangeQuotedPriceInput.value || '0');

            if (Number.isNaN(expected) || Number.isNaN(quoted)) {
                exchangeDifferenceInput.value = '';
                syncExchangeDetailsSummary();
                return;
            }

            exchangeDifferenceInput.value = (expected - quoted).toFixed(2);
            syncExchangeDetailsSummary();
        }

        function renumberExtraImageTiles() {
            if (!bookingExtraImageGrid) return;
            const tiles = bookingExtraImageGrid.querySelectorAll('.exchange-upload-tile-extra');
            tiles.forEach((tile, index) => {
                const defaultTitle = `Car picture ${index + 3}`;
                tile.dataset.defaultTitle = defaultTitle;
                const title = tile.querySelector('.exchange-upload-title');
                if (title && !tile.classList.contains('has-preview')) {
                    title.textContent = defaultTitle;
                }
            });
        }

        function addExtraImageTile() {
            if (!bookingExtraImageGrid) return;

            const tile = document.createElement('div');
            tile.className = 'exchange-upload-tile exchange-upload-tile-extra';
            tile.dataset.defaultTitle = '';
            tile.dataset.existingSrc = '';
            tile.dataset.existingName = '';
            tile.innerHTML = `
                <button type="button" class="exchange-remove-btn" aria-label="Remove extra image slot">-</button>
                <span class="exchange-upload-title"></span>
                <img class="exchange-upload-preview" alt="Extra exchange preview" hidden>
                <div class="exchange-upload-actions">
                    <button type="button" class="exchange-preview-view" disabled>View</button>
                    <button type="button" class="exchange-preview-clear" disabled>Remove</button>
                </div>
                <input class="exchange-file-input" type="file" name="extra_exchange_images[]" accept=".jpg,.jpeg,.png,.webp">
            `;

            bookingExtraImageGrid.appendChild(tile);
            bindExchangePreviewImageError(tile.querySelector('.exchange-upload-preview'));
            const fileInput = tile.querySelector('.exchange-file-input');
            if (fileInput) {
                bindExchangeUploadPreview(fileInput);
            }
            renumberExtraImageTiles();
        }

        function applyExchangePreviewToTile(inputEl, sourceUrl, fileName = '') {
            const tile = inputEl.closest('.exchange-upload-tile');
            if (!tile) return;
            const previewEl = tile.querySelector('.exchange-upload-preview');
            const titleEl = tile.querySelector('.exchange-upload-title');
            const viewBtn = tile.querySelector('.exchange-preview-view');
            const clearBtn = tile.querySelector('.exchange-preview-clear');
            const defaultTitle = tile.dataset.defaultTitle || (titleEl ? titleEl.textContent : '');

            if (!previewEl || !sourceUrl) {
                tile.classList.remove('has-preview');
                tile.dataset.previewSrc = '';
                if (previewEl) {
                    previewEl.hidden = true;
                    previewEl.removeAttribute('src');
                }
                if (titleEl) {
                    titleEl.textContent = defaultTitle;
                }
                if (viewBtn) viewBtn.disabled = true;
                if (clearBtn) clearBtn.disabled = true;
                return;
            }

            previewEl.src = sourceUrl;
            previewEl.hidden = false;
            tile.dataset.previewSrc = sourceUrl;
            if (titleEl) {
                titleEl.textContent = fileName || tile.dataset.existingName || defaultTitle;
            }
            if (viewBtn) viewBtn.disabled = false;
            if (clearBtn) clearBtn.disabled = false;
            tile.classList.add('has-preview');
        }

        function resetExchangeTileAfterPreviewError(previewEl) {
            if (!previewEl) return;
            const tile = previewEl.closest('.exchange-upload-tile');
            const inputEl = tile ? tile.querySelector('.exchange-file-input') : null;
            if (!tile || !inputEl) return;

            tile.dataset.existingSrc = '';
            tile.dataset.existingName = '';
            applyExchangePreviewToTile(inputEl, '');
        }

        function bindExchangePreviewImageError(previewEl) {
            if (!previewEl) return;
            previewEl.addEventListener('error', () => {
                resetExchangeTileAfterPreviewError(previewEl);
            });
        }

        function clearExchangeUploadPreview(inputEl) {
            if (!inputEl) return;
            const tile = inputEl.closest('.exchange-upload-tile');
            const previousObjectUrl = exchangePreviewObjectUrls.get(inputEl);
            if (previousObjectUrl) {
                URL.revokeObjectURL(previousObjectUrl);
                exchangePreviewObjectUrls.delete(inputEl);
            }
            inputEl.value = '';
            if (tile) {
                const removeInput = tile.querySelector('.exchange-remove-input');
                const removeExistingInput = tile.querySelector('.exchange-remove-existing');
                if (removeInput) {
                    removeInput.value = '1';
                }
                if (removeExistingInput) {
                    removeExistingInput.disabled = false;
                }
                tile.dataset.existingSrc = '';
                tile.dataset.existingName = '';
            }
            applyExchangePreviewToTile(inputEl, '');
        }

        function currentExchangePreviewUrl(tile) {
            if (!tile) return '';
            const previewEl = tile.querySelector('.exchange-upload-preview');
            if (previewEl && !previewEl.hidden && previewEl.getAttribute('src')) {
                return previewEl.getAttribute('src');
            }

            return tile.dataset.previewSrc || tile.dataset.existingSrc || '';
        }

        function bindExchangeUploadPreview(inputEl) {
            if (!inputEl) return;

            inputEl.addEventListener('change', () => {
                const file = inputEl.files && inputEl.files[0] ? inputEl.files[0] : null;
                if (!file) {
                    clearExchangeUploadPreview(inputEl);
                    return;
                }

                if (!String(file.type || '').startsWith('image/')) {
                    alert('Please choose a valid image file.');
                    inputEl.value = '';
                    const tile = inputEl.closest('.exchange-upload-tile');
                    if (tile && tile.dataset.existingSrc) {
                        applyExchangePreviewToTile(inputEl, tile.dataset.existingSrc, tile.dataset.existingName || '');
                    } else {
                        clearExchangeUploadPreview(inputEl);
                    }
                    return;
                }

                const tile = inputEl.closest('.exchange-upload-tile');
                if (tile) {
                    const removeInput = tile.querySelector('.exchange-remove-input');
                    const removeExistingInput = tile.querySelector('.exchange-remove-existing');
                    if (removeInput) {
                        removeInput.value = '0';
                    }
                    if (removeExistingInput) {
                        removeExistingInput.disabled = false;
                    }
                    tile.dataset.existingSrc = '';
                    tile.dataset.existingName = '';
                }

                const reader = new FileReader();
                reader.onload = () => {
                    applyExchangePreviewToTile(inputEl, String(reader.result || ''), file.name);
                };
                reader.readAsDataURL(file);
            });
        }

        function clearPurchaseOrderObjectUrl() {
            if (purchaseOrderObjectUrl) {
                URL.revokeObjectURL(purchaseOrderObjectUrl);
                purchaseOrderObjectUrl = null;
            }
        }

        function setPurchaseOrderPreview(sourceUrl) {
            if (!purchaseOrderTile || !purchaseOrderPreview) return;

            if (!sourceUrl) {
                purchaseOrderTile.classList.remove('has-preview');
                purchaseOrderPreview.hidden = true;
                purchaseOrderPreview.removeAttribute('src');
                if (purchaseOrderView) purchaseOrderView.disabled = true;
                if (purchaseOrderClear) purchaseOrderClear.disabled = true;
                return;
            }

            purchaseOrderPreview.src = sourceUrl;
            purchaseOrderPreview.hidden = false;
            purchaseOrderTile.classList.add('has-preview');
            if (purchaseOrderView) purchaseOrderView.disabled = false;
            if (purchaseOrderClear) purchaseOrderClear.disabled = false;
        }

        function clearPurchaseOrderPreview() {
            clearPurchaseOrderObjectUrl();
            if (purchaseOrderInput) {
                purchaseOrderInput.value = '';
            }
            if (purchaseOrderRemoveInput) {
                purchaseOrderRemoveInput.value = '1';
            }
            if (purchaseOrderTile) {
                purchaseOrderTile.dataset.existingSrc = '';
            }
            setPurchaseOrderPreview('');
        }

        function currentPurchaseOrderPreviewUrl() {
            if (purchaseOrderPreview && !purchaseOrderPreview.hidden && purchaseOrderPreview.getAttribute('src')) {
                return purchaseOrderPreview.getAttribute('src');
            }

            return purchaseOrderTile?.dataset.existingSrc || '';
        }

        function syncBookingImageBody() {
            if (!bookingImagesToggle || !bookingImageBody) return;
            bookingImageBody.classList.toggle('hidden', !bookingImagesToggle.checked);
            syncBookingExtraImageBody();
        }

        function syncBookingExtraImageBody() {
            if (!bookingAddMoreImagesToggle || !bookingExtraImageGrid) return;
            bookingExtraImageGrid.classList.toggle('hidden', !bookingAddMoreImagesToggle.checked);
        }

        function syncExchangeNoActionMode() {
            if (!bookingStepInput || bookingStepInput.value !== '3') return;

            if (actionRow) {
                actionRow.classList.remove('next-only');
            }
            if (backAction) {
                backAction.classList.remove('hidden');
            }
            if (saveExitAction) {
                saveExitAction.classList.remove('hidden');
            }
        }

        function toMoney(value) {
            const n = parseFloat(value || '0');
            return Number.isNaN(n) ? 0 : Math.max(0, n);
        }

        function formatOfferMoney(value) {
            return Math.round(toMoney(value)).toLocaleString('en-US');
        }

        function syncOfferReadonlyState() {
            if (!toggleOfferEdit || !offerEditGroup) return;

            const editable = toggleOfferEdit.checked;
            offerEditGroup.classList.toggle('hidden', !editable);
            if (offerSummaryPanel) {
                offerSummaryPanel.classList.toggle('hidden', editable);
            }

            const targets = offerEditGroup.querySelectorAll(
                'input[type="number"], input[type="checkbox"]'
            );

            targets.forEach((el) => {
                if (el === offerTotalCostInput || el === offerTotalDiscountInput || el === offerFinalPriceInput) {
                    el.readOnly = true;
                    return;
                }

                if (el.type === 'checkbox') {
                    el.disabled = false;
                } else {
                    el.readOnly = !editable;
                }
            });
        }

        function syncOfferRemarksState() {
            if (!offerRemarksToggle || !offerRemarksText) return;

            offerRemarksText.classList.toggle('hidden', !offerRemarksToggle.checked);
        }

        function syncOfferTotals() {
            if (!offerUnitPriceInput || !offerVatAmountInput) return;

            const unit = toMoney(offerUnitPriceInput.value);
            const vat = toMoney(offerVatAmountInput.value);
            let unitDiscount = toMoney(offerUnitPriceDiscountInput ? offerUnitPriceDiscountInput.value : 0);
            let vatDiscount = toMoney(offerVatDiscountInput ? offerVatDiscountInput.value : 0);
            const unitFree = !!(offerUnitPriceFreeInput && offerUnitPriceFreeInput.checked);
            const vatFree = !!(offerVatFreeInput && offerVatFreeInput.checked);

            if (unitFree) {
                unitDiscount = unit;
                if (offerUnitPriceDiscountInput) {
                    offerUnitPriceDiscountInput.value = unit.toFixed(2);
                }
            } else {
                if (unitDiscount > unit) {
                    unitDiscount = unit;
                    if (offerUnitPriceDiscountInput) {
                        offerUnitPriceDiscountInput.value = unitDiscount.toFixed(2);
                    }
                }
            }

            if (vatFree) {
                vatDiscount = vat;
                if (offerVatDiscountInput) {
                    offerVatDiscountInput.value = vat.toFixed(2);
                }
            } else {
                if (vatDiscount > vat) {
                    vatDiscount = vat;
                    if (offerVatDiscountInput) {
                        offerVatDiscountInput.value = vatDiscount.toFixed(2);
                    }
                }
            }

            const totalCost = unit + vat;
            const totalDiscount = unitDiscount + vatDiscount;
            const finalPrice = Math.max(0, totalCost - totalDiscount);

            if (offerTotalCostInput) offerTotalCostInput.value = totalCost.toFixed(2);
            if (offerTotalDiscountInput) offerTotalDiscountInput.value = totalDiscount.toFixed(2);
            if (offerFinalPriceInput) offerFinalPriceInput.value = finalPrice.toFixed(2);

            if (offerTotalCostDisplay) offerTotalCostDisplay.textContent = formatOfferMoney(totalCost);
            if (offerTotalDiscountDisplay) offerTotalDiscountDisplay.textContent = formatOfferMoney(totalDiscount);
            if (offerFinalPriceDisplay) offerFinalPriceDisplay.textContent = formatOfferMoney(finalPrice);

            if (offerSummaryVatCost) offerSummaryVatCost.textContent = formatOfferMoney(vat);
            if (offerSummaryVatOffer) offerSummaryVatOffer.textContent = formatOfferMoney(vatDiscount);
            if (offerSummaryVatPayable) offerSummaryVatPayable.textContent = formatOfferMoney(Math.max(0, vat - vatDiscount));
            if (offerSummaryUnitCost) offerSummaryUnitCost.textContent = formatOfferMoney(unit);
            if (offerSummaryUnitOffer) offerSummaryUnitOffer.textContent = formatOfferMoney(unitDiscount);
            if (offerSummaryUnitPayable) offerSummaryUnitPayable.textContent = formatOfferMoney(Math.max(0, unit - unitDiscount));
            if (offerSummaryTotalCost) offerSummaryTotalCost.textContent = formatOfferMoney(totalCost);
            if (offerSummaryTotalOffer) offerSummaryTotalOffer.textContent = formatOfferMoney(totalDiscount);
            if (offerSummaryTotalPayable) offerSummaryTotalPayable.textContent = formatOfferMoney(finalPrice);
        }

        if (toggle) {
            toggle.addEventListener('change', syncEditState);
            syncEditState();
        }

        if (bookingContactList) {
            bookingContactList.querySelectorAll('.booking-mobile-input').forEach((input) => {
                input.addEventListener('input', syncBookingPersonalSummary);
            });

            bookingContactList.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof Element)) return;

                const removeButton = target.closest('.mini-remove-btn');
                if (!removeButton) return;

                event.preventDefault();
                removeButton.closest('.contact-pill-wrap')?.remove();
                syncBookingPersonalSummary();
            });
        }

        if (addBookingMobileBtn) {
            addBookingMobileBtn.addEventListener('click', () => {
                addBookingMobileInput('');
            });
        }

        if (bookingForm) {
            bookingForm.querySelectorAll('button[name="action_type"]').forEach((button) => {
                button.addEventListener('click', () => setBookingActionFallback(button.value));
            });

            bookingForm.addEventListener('submit', prepareBookingSubmit);
        }

        document.querySelectorAll(
            '[name="title"], [name="name"], [name="district"], [name="location"], [name="state"], [name="address1"], [name="address2"], [name="corporate_name"], input[name="customer_type"], input[name="profession"]'
        ).forEach((input) => {
            input.addEventListener('input', syncBookingPersonalSummary);
            input.addEventListener('change', syncBookingPersonalSummary);
        });

        syncBookingPersonalSummary();

        document.querySelectorAll('input[name="customer_type"]').forEach((input) => {
            input.addEventListener('change', syncCorporateRow);
        });

        if (toggleBuyingVehicleEdit) {
            toggleBuyingVehicleEdit.addEventListener('change', syncVehicleEditState);
            syncVehicleEditState();
        }

        if (interestedModelInput) {
            interestedModelInput.addEventListener('change', async function() {
                await loadEngines(this.value, '');
                await loadVariants(this.value, '', '');
                syncVehiclePill();
            });
        }

        if (interestedEngineInput) {
            interestedEngineInput.addEventListener('change', async function() {
                const model = interestedModelInput ? interestedModelInput.value : '';
                await loadVariants(model, this.value, '');
                syncVehiclePill();
            });
        }

        if (interestedVariantInput) {
            interestedVariantInput.addEventListener('change', () => {
                applySelectedVehiclePrice(true);
                syncVehiclePill();
            });
        }

        if (vehiclePillRemove) {
            vehiclePillRemove.addEventListener('click', async () => {
                if (interestedModelInput) {
                    interestedModelInput.value = '';
                }
                await loadEngines('', '');
                syncVehiclePill();
            });
        }

        document.querySelectorAll('.buying-section input, .buying-section select').forEach((input) => {
            input.addEventListener('input', syncBuyingDetailsSummary);
            input.addEventListener('change', syncBuyingDetailsSummary);
        });

        (async function initVehicleDropdowns() {
            if (!interestedModelInput) return;

            const initialModel = interestedModelInput.dataset.selectedModel || interestedModelInput.value || '';
            const initialEngine = interestedEngineInput ? (interestedEngineInput.dataset.selectedEngine || '') : '';
            const initialVariant = interestedVariantInput ? (interestedVariantInput.dataset.selectedVariant || '') : '';

            if (initialModel) {
                interestedModelInput.value = initialModel;
                await loadEngines(initialModel, initialEngine);
                if (initialEngine) {
                    await loadVariants(initialModel, initialEngine, initialVariant);
                    applySelectedVehiclePrice(false);
                }
            } else {
                setSelectOptions(interestedEngineInput, [], 'Select Engine Type', '');
                setSelectOptions(interestedVariantInput, [], 'Select Variant', '');
            }

            syncVehiclePill();
        })();

        document.querySelectorAll(
            'input[name="quote_taken"], input[name="test_drive_given"], input[name="purchase_mode"], input[name="finance_form"], input[name="interested_in_exchange"], input[name="exchange_type"], input[name="interested_in_competition"], input[name="first_time_buyer"]'
        ).forEach((input) => {
            input.addEventListener('change', syncBuyingState);
        });

        bookingTestDriveNoReasonSelect?.addEventListener('change', syncBuyingState);

        if (competitionBrandSelect) {
            competitionBrandSelect.addEventListener('change', function() {
                if (competitionModelSelect) {
                    competitionModelSelect.dataset.selectedModel = '';
                    competitionModelSelect.value = '';
                }
                syncCompetitionModels();
            });
        }

        competitionModelSelect?.addEventListener('change', syncBuyingDetailsSummary);
        competitionYearSelect?.addEventListener('change', syncBuyingDetailsSummary);

        if (existingVehicleBrandSelect) {
            existingVehicleBrandSelect.addEventListener('change', function() {
                if (existingVehicleModelSelect) {
                    existingVehicleModelSelect.dataset.selectedModel = '';
                    existingVehicleModelSelect.value = '';
                }
                syncExistingVehicleModels();
            });
        }

        if (existingVehicleModelSelect) {
            existingVehicleModelSelect.addEventListener('change', syncBuyingDetailsSummary);
        }

        if (bookingTestDriveVehicleSelect) {
            bookingTestDriveVehicleSelect.addEventListener('change', () => {
                syncBuyingState();
                syncBuyingDetailsSummary();
            });
        }

        if (toggleExchangeEdit) {
            toggleExchangeEdit.addEventListener('change', syncExchangeEditState);
            syncExchangeEditState();
        }

        if (exchangeBrandSelect) {
            exchangeBrandSelect.addEventListener('change', function() {
                if (exchangeModelSelect) {
                    exchangeModelSelect.dataset.selectedModel = '';
                    exchangeModelSelect.value = '';
                }
                if (exchangeModelBackupInput) {
                    exchangeModelBackupInput.value = '';
                }
                syncExchangeModels();
            });
        }

        exchangeModelSelect?.addEventListener('change', () => {
            if (exchangeModelBackupInput) {
                exchangeModelBackupInput.value = exchangeModelSelect.value || '';
            }
            if (exchangeBrandModelRow && (exchangeModelSelect.value || '').trim()) {
                exchangeBrandModelRow.dataset.requiresInput = '0';
            }
            syncExchangeDetailsSummary();
        });

        document.querySelectorAll('.exchange-section input, .exchange-section select').forEach((input) => {
            input.addEventListener('input', syncExchangeDetailsSummary);
            input.addEventListener('change', syncExchangeDetailsSummary);
        });

        if (bookingAddMoreImagesToggle) {
            bookingAddMoreImagesToggle.addEventListener('change', syncBookingExtraImageBody);
        }

        if (bookingImagesToggle) {
            bookingImagesToggle.addEventListener('change', syncBookingImageBody);
        }

        if (purchaseOrderInput) {
            purchaseOrderInput.addEventListener('change', () => {
                clearPurchaseOrderObjectUrl();
                const file = purchaseOrderInput.files && purchaseOrderInput.files[0] ? purchaseOrderInput.files[0] : null;

                if (!file) {
                    return;
                }

                purchaseOrderObjectUrl = URL.createObjectURL(file);
                if (purchaseOrderRemoveInput) {
                    purchaseOrderRemoveInput.value = '0';
                }
                if (purchaseOrderTile) {
                    purchaseOrderTile.dataset.existingSrc = '';
                }
                setPurchaseOrderPreview(purchaseOrderObjectUrl);
            });
        }

        if (purchaseOrderAdd) {
            purchaseOrderAdd.addEventListener('click', () => {
                purchaseOrderInput?.click();
            });
        }

        if (purchaseOrderView) {
            purchaseOrderView.addEventListener('click', () => {
                const previewUrl = currentPurchaseOrderPreviewUrl();
                if (!previewUrl) return;
                window.open(previewUrl, '_blank', 'noopener');
            });
        }

        if (purchaseOrderClear) {
            purchaseOrderClear.addEventListener('click', (event) => {
                event.preventDefault();
                clearPurchaseOrderPreview();
            });
        }

        if (purchaseOrderRemove) {
            purchaseOrderRemove.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                clearPurchaseOrderPreview();
            });
        }

        if (bookingExtraImageGrid) {
            bookingExtraImageGrid.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof Element)) return;

                const viewBtn = target.closest('.exchange-preview-view');
                if (viewBtn) {
                    event.preventDefault();
                    event.stopPropagation();
                    const tile = viewBtn.closest('.exchange-upload-tile');
                    const previewUrl = currentExchangePreviewUrl(tile);
                    if (previewUrl) {
                        window.open(previewUrl, '_blank', 'noopener');
                    }
                    return;
                }

                const clearBtn = target.closest('.exchange-preview-clear');
                if (clearBtn) {
                    event.preventDefault();
                    event.stopPropagation();
                    const tile = clearBtn.closest('.exchange-upload-tile');
                    const fileInput = tile ? tile.querySelector('.exchange-file-input') : null;
                    if (fileInput) {
                        clearExchangeUploadPreview(fileInput);
                    }
                    return;
                }

                const removeBtn = target.closest('.exchange-remove-btn');
                if (!removeBtn) return;

                event.preventDefault();
                event.stopPropagation();

                const tile = removeBtn.closest('.exchange-upload-tile-extra');
                if (!tile) return;
                const fileInput = tile.querySelector('.exchange-file-input');
                const removeExistingInput = tile.querySelector('.exchange-remove-existing');
                if (fileInput) {
                    clearExchangeUploadPreview(fileInput);
                }
                if (removeExistingInput && bookingImageBody) {
                    const persistedRemoveInput = document.createElement('input');
                    persistedRemoveInput.type = 'hidden';
                    persistedRemoveInput.name = removeExistingInput.name;
                    persistedRemoveInput.value = removeExistingInput.value;
                    bookingImageBody.appendChild(persistedRemoveInput);
                }
                tile.remove();
                renumberExtraImageTiles();
            });
        }

        if (bookingImageBody) {
            bookingImageBody.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof Element)) return;
                if (target.closest('#bookingExtraImageGrid')) return;

                const viewBtn = target.closest('.exchange-preview-view');
                if (viewBtn) {
                    event.preventDefault();
                    event.stopPropagation();
                    const tile = viewBtn.closest('.exchange-upload-tile');
                    const previewUrl = currentExchangePreviewUrl(tile);
                    if (previewUrl) {
                        window.open(previewUrl, '_blank', 'noopener');
                    }
                    return;
                }

                const clearBtn = target.closest('.exchange-preview-clear');
                if (!clearBtn) return;

                event.preventDefault();
                event.stopPropagation();

                const tile = clearBtn.closest('.exchange-upload-tile');
                const fileInput = tile ? tile.querySelector('.exchange-file-input') : null;
                if (fileInput) {
                    clearExchangeUploadPreview(fileInput);
                }
            });
        }

        amountCollectedInput?.addEventListener('click', openReceiptModal);
        amountCollectedInput?.addEventListener('focus', openReceiptModal);

        bookingReceiptRows?.addEventListener('input', (event) => {
            if (event.target instanceof Element && event.target.matches('[data-receipt-amount]')) {
                syncReceiptTotal();
            }
        });

        bookingReceiptRows?.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) return;
            const removeButton = target.closest('.booking-receipt-remove');
            if (!removeButton) return;

            const row = removeButton.closest('.booking-receipt-row');
            const rows = bookingReceiptRows.querySelectorAll('.booking-receipt-row');
            if (row && rows.length > 1) {
                row.remove();
            } else if (row) {
                row.querySelectorAll('input:not([readonly]), select').forEach((field) => {
                    field.value = '';
                });
            }

            renderReceiptRows(serializeReceiptRows());
        });

        bookingReceiptAddMore?.addEventListener('click', () => {
            const rows = serializeReceiptRows();
            rows.push({});
            renderReceiptRows(rows);
        });

        bookingReceiptSave?.addEventListener('click', () => {
            syncReceiptTotal();
            closeReceiptModal();
        });

        function cancelReceiptChanges() {
            renderReceiptRows(Array.isArray(bookingReceiptSnapshot) ? bookingReceiptSnapshot : []);
            closeReceiptModal();
        }

        bookingReceiptCancel?.addEventListener('click', cancelReceiptChanges);
        bookingReceiptClose?.addEventListener('click', cancelReceiptChanges);

        bookingReceiptModal?.addEventListener('click', (event) => {
            if (event.target === bookingReceiptModal) {
                cancelReceiptChanges();
            }
        });

        [exchangeExpectedPriceInput, exchangeQuotedPriceInput].forEach((el) => {
            if (el) {
                el.addEventListener('input', syncExchangeDifference);
            }
        });

        [offerUnitPriceInput, offerUnitPriceDiscountInput, offerVatAmountInput, offerVatDiscountInput].forEach((el) => {
            if (el) {
                el.addEventListener('input', syncOfferTotals);
            }
        });

        [offerUnitPriceFreeInput, offerVatFreeInput].forEach((el) => {
            if (el) {
                el.addEventListener('change', syncOfferTotals);
            }
        });

        if (toggleOfferEdit) {
            toggleOfferEdit.addEventListener('change', syncOfferReadonlyState);
            syncOfferReadonlyState();
        }

        if (offerEditSaveBtn) {
            offerEditSaveBtn.addEventListener('click', () => {
                syncOfferTotals();
                if (toggleOfferEdit) {
                    toggleOfferEdit.checked = false;
                }
                syncOfferReadonlyState();
            });
        }

        if (offerRemarksToggle) {
            offerRemarksToggle.addEventListener('change', syncOfferRemarksState);
            syncOfferRemarksState();
        }

        bookingForm?.addEventListener('submit', () => {
            exchangeBrandSelect?.removeAttribute('disabled');
            exchangeModelSelect?.removeAttribute('disabled');
            if (exchangeModelBackupInput && exchangeModelSelect) {
                exchangeModelBackupInput.value = exchangeModelSelect.value || exchangeModelBackupInput.value || '';
            }
            syncReceiptTotal();
        });

        syncBuyingState();
        syncCompetitionModels();
        syncExistingVehicleModels();
        syncExchangeModels();
        syncCorporateRow();
        syncExchangeDifference();
        syncOfferTotals();
        syncExchangeNoActionMode();
        renumberExtraImageTiles();
        syncBookingImageBody();
        syncReceiptTotal();
        document.querySelectorAll('.exchange-upload-preview').forEach((previewEl) => {
            bindExchangePreviewImageError(previewEl);
        });
        document.querySelectorAll('.exchange-file-input').forEach((inputEl) => {
            bindExchangeUploadPreview(inputEl);
        });
    })();
</script>
@if(session('booking_submitted_popup'))
<script>
    (() => {
        const popup = document.getElementById('bookingSubmitPopup');
        if (!popup) return;

        const yesBtn = document.getElementById('bookingSubmitPopupYes');
        const noBtn = document.getElementById('bookingSubmitPopupNo');
        const deliveryUrl = @json(session('booking_delivery_url'));
        const dashboardUrl = @json(route('dashboard.main'));

        const closePopup = () => {
            popup.remove();
            document.body.classList.remove('booking-modal-open');
        };

        document.body.classList.add('booking-modal-open');
        yesBtn?.addEventListener('click', () => {
            if (deliveryUrl) {
                window.location.href = deliveryUrl;
                return;
            }

            closePopup();
        });
        noBtn?.addEventListener('click', () => {
            window.location.href = dashboardUrl;
        });
    })();
</script>
@endif
@endsection
