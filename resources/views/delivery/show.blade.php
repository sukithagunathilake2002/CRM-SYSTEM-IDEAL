@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/delivery.css') }}">

@php
    $summaryName = trim(($customer?->title ? $customer->title . ' ' : '') . ($customer?->name ?? 'N/A'));
    $summaryMobile = collect($customer?->mobile_numbers ?? [])->filter()->values()->implode(', ') ?: 'N/A';
    $summaryAddress = collect([$customer?->address1, $customer?->address2, $customer?->location, $customer?->district, $customer?->state])->filter()->implode(', ');
    $summaryVehicle = collect([$booking?->interested_model ?: $vehicle?->model, $booking?->interested_engine ?: $vehicle?->engine_type, $booking?->interested_variant ?: $vehicle?->variant])->filter()->implode(' / ');

    $selectedTitle = old('title', $defaultValues['title']);
    $selectedName = old('name', $defaultValues['name']);
    $selectedContactType = old('contact_type', $defaultValues['contact_type']);
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
    $selectedTestDriveToWhom = old('test_drive_to_whom', $defaultValues['test_drive_to_whom']);
    $selectedTestDriveReason = old('test_drive_not_given_reason', $defaultValues['test_drive_not_given_reason']);
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
    $selectedExchangeBrand = old('exchange_vehicle_brand', $defaultValues['exchange_vehicle_brand']);
    $selectedExchangeModel = old('exchange_vehicle_model', $defaultValues['exchange_vehicle_model']);
    $selectedExchangeYear = old('exchange_manufacture_year', $defaultValues['exchange_manufacture_year']);
    $selectedExchangeColor = old('exchange_color', $defaultValues['exchange_color']);
    $selectedExchangeMileage = old('exchange_mileage_km', $defaultValues['exchange_mileage_km']);
    $selectedExchangeRegistration = old('exchange_registration_no', $defaultValues['exchange_registration_no']);
    $selectedExchangeExpectedPrice = old('exchange_expected_price', $defaultValues['exchange_expected_price']);
    $selectedExchangeQuotedPrice = old('exchange_quoted_price', $defaultValues['exchange_quoted_price']);
    $selectedExchangeDifference = old('exchange_price_difference', $defaultValues['exchange_price_difference']);

    $vehicleColorOptions = ['White', 'Black', 'Silver', 'Grey', 'Red', 'Blue', 'Green', 'Brown', 'Orange', 'Other'];
    $testDriveNoReasons = [
        'Not interested',
        'Vehicle not available',
        'Vehicle damaged/under repair',
        'Not met in person',
        'Already driven',
        'I Did Not Offer',
        'Others',
    ];
    $competitionMap = collect($competitionMap ?? []);
    $competitionBrands = $competitionMap->keys()->values()->all();

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
@endphp

<div class="delivery-page">
    <header class="delivery-topbar booking-topbar">
        <a href="{{ route('dashboard.main') }}" class="brand-logo-link" aria-label="Go to dashboard">
            <img src="{{ asset('icons/logo.png') }}" alt="Ideal Motors" class="brand-logo">
        </a>
    </header>

    <div class="delivery-stepper" aria-label="Delivery workflow">
        @foreach([
            1 => 'Personal Detail',
            2 => 'Buying Details',
            3 => 'Exchange Details',
            4 => 'Offer Details',
            5 => 'Plan Followup',
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

        @if($currentStep === 2)
            <section class="delivery-summary">
                <div><span>Interested In</span><strong>{{ $summaryVehicle ?: 'N/A' }}</strong></div>
                <div><span>Colour</span><strong>{{ $selectedInterestedColor ?: 'N/A' }}</strong></div>
                <div><span>Test Driven Given</span><strong>{{ $selectedTestDrive ? ucfirst($selectedTestDrive) : 'N/A' }}</strong></div>
                <div><span>With Test Driven</span><strong>{{ $selectedTestDriveModel ?: 'Vehicle not available' }}</strong></div>
                <div><span>Interested In Competition</span><strong>{{ $selectedCompetition ? ucwords(str_replace('_', ' ', $selectedCompetition)) : 'N/A' }}</strong></div>
                <div><span>First Time Buyer</span><strong>{{ $selectedFirstTimeBuyer ? ucfirst($selectedFirstTimeBuyer) : 'N/A' }}</strong></div>
                <div><span>Mode of Purchase</span><strong>{{ $selectedPurchaseMode ? ucfirst($selectedPurchaseMode) : 'N/A' }}</strong></div>
                <div><span>Finance Form</span><strong>{{ $selectedFinanceForm ? ucwords(str_replace('_', ' ', $selectedFinanceForm)) : 'N/A' }}</strong></div>
            </section>
        @elseif($currentStep === 3)
            <section class="delivery-summary">
                <div><span>Interested in Exchange?</span><strong>{{ $selectedInterestedExchange ? ucfirst($selectedInterestedExchange) : 'N/A' }}</strong></div>
                <div><span>Exchange Type</span><strong>{{ $selectedExchangeType ? ucwords(str_replace('_', ' ', $selectedExchangeType)) : 'N/A' }}</strong></div>
                <div><span>Vehicle</span><strong>{{ trim(($selectedExchangeBrand ?: '') . ' ' . ($selectedExchangeModel ?: '')) ?: 'N/A' }}</strong></div>
                <div><span>Manufacture Year</span><strong>{{ $selectedExchangeYear ?: 'N/A' }}</strong></div>
                <div><span>Registration No</span><strong>{{ $selectedExchangeRegistration ?: 'N/A' }}</strong></div>
                <div><span>Expected Price</span><strong>{{ $selectedExchangeExpectedPrice !== null && $selectedExchangeExpectedPrice !== '' ? number_format((float) $selectedExchangeExpectedPrice, 2) : 'N/A' }}</strong></div>
                <div><span>Quoted Price</span><strong>{{ $selectedExchangeQuotedPrice !== null && $selectedExchangeQuotedPrice !== '' ? number_format((float) $selectedExchangeQuotedPrice, 2) : 'N/A' }}</strong></div>
                <div><span>Difference</span><strong>{{ $selectedExchangeDifference !== null && $selectedExchangeDifference !== '' ? number_format((float) $selectedExchangeDifference, 2) : 'N/A' }}</strong></div>
            </section>
        @else
            <section class="delivery-summary">
                <div><span>Name</span><strong>{{ $summaryName }}</strong></div>
                <div><span>Interested In</span><strong>{{ $summaryVehicle ?: 'N/A' }}</strong></div>
                <div><span>Mobile No.</span><strong>{{ $summaryMobile }}</strong></div>
                <div><span>Address</span><strong>{{ $summaryAddress ?: 'N/A' }}</strong></div>
                <div><span>Type of Customer</span><strong>{{ ucfirst((string) ($selectedCustomerType ?: 'N/A')) }}</strong></div>
                <div><span>Profession</span><strong>{{ ucwords(str_replace('_', ' ', (string) ($selectedProfession ?: 'N/A'))) }}</strong></div>
            </section>
        @endif

        <form method="POST" action="{{ route('delivery.store', $enquiry->id) }}" enctype="multipart/form-data" class="delivery-form">
            @csrf
            <input type="hidden" name="delivery_step" value="{{ $currentStep }}">

            @if($currentStep === 1)
            <div class="delivery-section-head">
                <h2>Personal Details</h2>
                <label class="delivery-switch">
                    <input type="checkbox" id="deliveryEditToggle">
                    <span>Edit Personal Detail</span>
                </label>
            </div>

            <div class="delivery-grid">
                <label class="delivery-pill delivery-title">
                    <span>Title</span>
                    <select name="title" data-lockable>
                        @foreach(['Mr', 'Mrs', 'Ms', 'Dr'] as $title)
                            <option value="{{ $title }}" @selected($selectedTitle === $title)>{{ $title }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="delivery-pill">
                    <span>Name</span>
                    <input type="text" name="name" value="{{ $selectedName }}" data-lockable>
                </label>

                <label class="delivery-pill">
                    <span>Contact</span>
                    <select name="contact_type" data-lockable>
                        @foreach(['Mobile', 'Home', 'Office'] as $contactType)
                            <option value="{{ $contactType }}" @selected($selectedContactType === $contactType)>{{ $contactType }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="delivery-pill">
                    <span>Phone</span>
                    <input type="text" name="mobile_numbers" value="{{ $selectedMobile }}" data-lockable>
                </label>

                <label class="delivery-pill">
                    <span>District</span>
                    <input type="text" name="district" value="{{ $selectedDistrict }}" data-lockable>
                </label>

                <label class="delivery-pill">
                    <span>Location</span>
                    <input type="text" name="location" value="{{ $selectedLocation }}" data-lockable>
                </label>

                <label class="delivery-pill delivery-wide">
                    <span>Address Line 1</span>
                    <input type="text" name="address1" value="{{ $selectedAddress1 }}" data-lockable>
                </label>

                <label class="delivery-pill delivery-wide">
                    <span>Address Line 2</span>
                    <input type="text" name="address2" value="{{ $selectedAddress2 }}" data-lockable>
                </label>

                <label class="delivery-pill delivery-wide">
                    <span>State</span>
                    <input type="text" name="state" value="{{ $selectedState }}" data-lockable>
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
                @php($existingExtraImages = is_array($delivery->extra_images) ? $delivery->extra_images : [])
                @if(!empty($existingExtraImages))
                    <div class="delivery-extra-grid delivery-existing-extra-grid">
                        @foreach($existingExtraImages as $extraImagePath)
                            @php($extraImageUrl = asset('storage/' . $extraImagePath))
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
            @elseif($currentStep === 2)
            <div class="delivery-section-head">
                <h2>Buying Details</h2>
                <label class="delivery-switch">
                    <input type="checkbox" id="deliveryBuyingEditToggle">
                    <span>Edit Buying Details</span>
                </label>
            </div>

            <div class="delivery-buying-grid">
                <label class="delivery-pill delivery-wide">
                    <span>Interested In</span>
                    <select name="interested_model" data-buying-lockable>
                        <option value="">Select Model</option>
                        @foreach($vehicleModels as $modelOption)
                            <option value="{{ $modelOption }}" @selected($selectedInterestedModel === $modelOption)>{{ $modelOption }}</option>
                        @endforeach
                        @if(!empty($selectedInterestedModel) && !$vehicleModels->contains($selectedInterestedModel))
                            <option value="{{ $selectedInterestedModel }}" selected>{{ $selectedInterestedModel }}</option>
                        @endif
                    </select>
                </label>

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
                        <select name="test_drive_vehicle_model">
                            <option value="">Select Model</option>
                            @foreach($vehicleModels as $modelOption)
                                <option value="{{ $modelOption }}" @selected($selectedTestDriveModel === $modelOption)>{{ $modelOption }}</option>
                            @endforeach
                            @if(!empty($selectedTestDriveModel) && !$vehicleModels->contains($selectedTestDriveModel))
                                <option value="{{ $selectedTestDriveModel }}" selected>{{ $selectedTestDriveModel }}</option>
                            @endif
                        </select>
                    </label>
                    <label class="delivery-pill delivery-wide">
                        <span>To Whom?</span>
                        <input type="text" name="test_drive_to_whom" value="{{ $selectedTestDriveToWhom }}">
                    </label>
                </div>
                <label class="delivery-pill delivery-conditional" id="deliveryTestDriveNoWrap">
                    <span>Why?</span>
                    <select name="test_drive_not_given_reason">
                        <option value="">Select Reason</option>
                        @foreach($testDriveNoReasons as $reasonOption)
                            <option value="{{ $reasonOption }}" @selected($selectedTestDriveReason === $reasonOption)>{{ $reasonOption }}</option>
                        @endforeach
                    </select>
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
            @else
            <div class="delivery-section-head">
                <h2>Exchange Details</h2>
                <label class="delivery-switch">
                    <input type="checkbox" id="deliveryExchangeEditToggle">
                    <span>Edit Exchange Details</span>
                </label>
            </div>

            <div class="delivery-question">
                <p>Interested in Exchange?</p>
                <div class="delivery-segment">
                    <label><input type="radio" name="interested_in_exchange" value="yes" @checked($selectedInterestedExchange === 'yes')><span>Yes</span></label>
                    <label><input type="radio" name="interested_in_exchange" value="no" @checked($selectedInterestedExchange === 'no')><span>No</span></label>
                </div>
            </div>

            <div id="deliveryExchangeDetailsWrap" class="delivery-exchange-wrap">
                <div class="delivery-buying-grid">
                    <label class="delivery-pill delivery-wide">
                        <span>Vehicle</span>
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
                    <label class="delivery-exchange-edit-inline">
                        <input type="checkbox" id="deliveryExchangeInlineEdit">
                        <span>Edit</span>
                    </label>
                    <label class="delivery-pill">
                        <span>Type</span>
                        <select name="exchange_type" data-exchange-lockable>
                            <option value="in_house" @selected($selectedExchangeType === 'in_house')>In House</option>
                            <option value="outhouse" @selected($selectedExchangeType === 'outhouse')>Outhouse</option>
                        </select>
                    </label>
                    <label class="delivery-pill">
                        <span>Model</span>
                        <select name="exchange_vehicle_model" id="deliveryExchangeModel" data-selected-model="{{ $selectedExchangeModel }}" data-exchange-lockable>
                            <option value="">Select Model</option>
                        </select>
                    </label>
                    <label class="delivery-pill">
                        <span>Year</span>
                        <input type="number" name="exchange_manufacture_year" min="1950" max="2100" value="{{ $selectedExchangeYear }}" data-exchange-lockable>
                    </label>
                    <label class="delivery-pill">
                        <span>Color</span>
                        <input type="text" name="exchange_color" value="{{ $selectedExchangeColor }}" data-exchange-lockable>
                    </label>
                    <label class="delivery-pill">
                        <span>Mileage Km</span>
                        <input type="number" name="exchange_mileage_km" min="0" value="{{ $selectedExchangeMileage }}" data-exchange-lockable>
                    </label>
                    <label class="delivery-pill">
                        <span>Registration No.</span>
                        <input type="text" name="exchange_registration_no" value="{{ $selectedExchangeRegistration }}" data-exchange-lockable>
                    </label>
                    <label class="delivery-pill">
                        <span>Expected Price</span>
                        <input type="number" step="0.01" min="0" name="exchange_expected_price" id="deliveryExchangeExpectedPrice" value="{{ $selectedExchangeExpectedPrice }}" data-exchange-lockable>
                    </label>
                    <label class="delivery-pill">
                        <span>Quoted Price</span>
                        <input type="number" step="0.01" min="0" name="exchange_quoted_price" id="deliveryExchangeQuotedPrice" value="{{ $selectedExchangeQuotedPrice }}" data-exchange-lockable>
                    </label>
                    <label class="delivery-pill">
                        <span>Difference</span>
                        <input type="number" step="0.01" name="exchange_price_difference" id="deliveryExchangeDifference" value="{{ $selectedExchangeDifference }}" readonly>
                    </label>
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
                    @php($existingExchangeExtraImages = is_array($delivery->exchange_extra_images) ? $delivery->exchange_extra_images : [])
                    @if(!empty($existingExchangeExtraImages))
                        <div class="delivery-extra-grid delivery-existing-extra-grid">
                            @foreach($existingExchangeExtraImages as $extraImagePath)
                                @php($extraImageUrl = asset('storage/' . $extraImagePath))
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
            @endif

            <div class="delivery-actions">
                <a href="{{ $currentStep > 1 ? route('delivery.show', ['enquiry' => $enquiry->id, 'step' => $currentStep - 1]) : route('booking.show', ['enquiry' => $enquiry->id, 'step' => 5]) }}" class="delivery-action back">Back</a>
                <button type="submit" name="action_type" value="save_exit" class="delivery-action save-exit">Save &amp; Exit</button>
                <button type="submit" name="action_type" value="save_next" class="delivery-action save-next">Save &amp; Next</button>
            </div>
        </form>
    </main>
</div>

<script>
(() => {
    const editToggle = document.getElementById('deliveryEditToggle');
    const lockableFields = Array.from(document.querySelectorAll('[data-lockable]'));

    const syncLockable = () => {
        lockableFields.forEach((field) => {
            field.classList.toggle('delivery-locked', !editToggle.checked);
            if (field.tagName.toLowerCase() === 'input') {
                field.readOnly = !editToggle.checked;
            }
        });
    };

    if (editToggle) {
        editToggle.addEventListener('change', syncLockable);
        syncLockable();
    }

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
    };

    document
        .querySelectorAll('input[name="quote_taken"], input[name="test_drive_given"], input[name="interested_in_competition"], input[name="first_time_buyer"], input[name="purchase_mode"]')
        .forEach((input) => input.addEventListener('change', syncBuyingDetails));
    syncBuyingDetails();

    const exchangeWrap = document.getElementById('deliveryExchangeDetailsWrap');
    const exchangeTypeInputs = Array.from(document.querySelectorAll('input[name="interested_in_exchange"]'));
    const exchangeEditToggle = document.getElementById('deliveryExchangeEditToggle');
    const exchangeInlineEdit = document.getElementById('deliveryExchangeInlineEdit');
    const exchangeLockableFields = Array.from(document.querySelectorAll('[data-exchange-lockable]'));

    const syncExchangeLockable = () => {
        const isEditable = Boolean(exchangeEditToggle?.checked || exchangeInlineEdit?.checked);
        exchangeLockableFields.forEach((field) => {
            field.classList.toggle('delivery-locked', !isEditable);
            if (field.tagName.toLowerCase() === 'input') {
                field.readOnly = !isEditable || field.id === 'deliveryExchangeDifference';
            }
        });
    };

    const syncExchangeDetails = () => {
        const isExchange = picked('interested_in_exchange') === 'yes';
        if (exchangeWrap) {
            exchangeWrap.classList.toggle('hidden', !isExchange);
        }
        syncExchangeLockable();
    };

    exchangeTypeInputs.forEach((input) => input.addEventListener('change', syncExchangeDetails));
    exchangeEditToggle?.addEventListener('change', syncExchangeLockable);
    exchangeInlineEdit?.addEventListener('change', () => {
        if (exchangeEditToggle) {
            exchangeEditToggle.checked = exchangeInlineEdit.checked;
        }
        syncExchangeLockable();
    });

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
        }
        fillModelSelect('deliveryCompetitionBrand', 'deliveryCompetitionModel');
    });
    document.getElementById('deliveryExistingBrand')?.addEventListener('change', () => {
        const modelSelect = document.getElementById('deliveryExistingModel');
        if (modelSelect) {
            modelSelect.dataset.selectedModel = '';
        }
        fillModelSelect('deliveryExistingBrand', 'deliveryExistingModel');
    });
    document.getElementById('deliveryExchangeBrand')?.addEventListener('change', () => {
        const modelSelect = document.getElementById('deliveryExchangeModel');
        if (modelSelect) {
            modelSelect.dataset.selectedModel = '';
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
})();
</script>
@endsection
