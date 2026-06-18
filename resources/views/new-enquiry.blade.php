@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/enquiry.css') }}">

<div class="enquiry-page">
    @php
        $districtOptions = is_array($districtOptions ?? null) && !empty($districtOptions)
            ? array_values($districtOptions)
            : \App\Models\User::DISTRICT_OPTIONS;
        $rawProvinceDistrictMap = \App\Models\User::PROVINCE_DISTRICT_MAP;
        $permittedDistrictLookup = array_fill_keys($districtOptions, true);
        $provinceDistrictMap = [];
        foreach ($rawProvinceDistrictMap as $province => $districts) {
            $allowedDistricts = array_values(array_filter(
                $districts,
                fn(string $district): bool => isset($permittedDistrictLookup[$district])
            ));
            if (!empty($allowedDistricts)) {
                $provinceDistrictMap[$province] = $allowedDistricts;
            }
        }
        $provinceOptions = array_keys($provinceDistrictMap);
        $selectedDistrict = old('district', '');
        $selectedProvince = trim((string) old('province', \App\Models\User::provinceForDistrict($selectedDistrict) ?? ''));
        if ($selectedProvince !== '' && !array_key_exists($selectedProvince, $provinceDistrictMap)) {
            $selectedProvince = '';
        }
        $oldMobiles = old('mobiles', ['']);
        if (!is_array($oldMobiles) || count($oldMobiles) === 0) {
            $oldMobiles = [''];
        }
        $hasAddressValues = trim((string) old('state')) !== ''
            || trim((string) old('address1')) !== ''
            || trim((string) old('address2')) !== '';
        $sourceInfoMap = is_array($sourceInfoMap ?? null) ? $sourceInfoMap : [
            'Walk-In' => ['Showroom Visit', 'Road Show', 'Display', 'Existing Customer', 'Other'],
            'Tele-In' => ['Call Center', 'Hotline', 'Inbound Call', 'Missed Call', 'Other'],
            'Activity' => ['Event', 'Mall Display', 'Corporate Visit', 'Canvasing', 'Other'],
            'Digital' => ['Facebook', 'Instagram', 'Google', 'Website', 'YouTube', 'TikTok', 'Other'],
            'Referral' => ['Customer Referral', 'Employee Referral', 'Dealer Referral', 'Friends/Family', 'Other'],
            'Press' => ['Newspaper', 'Magazine', 'Radio', 'TV', 'Other'],
        ];
        $selectedSourceInformation = old('source_of_information', '');
        $selectedFollowTime = old('follow_time')
            ? substr((string) old('follow_time'), 0, 5)
            : \Carbon\Carbon::now('Asia/Colombo')->format('H:i');
    @endphp

    <header class="topbar">
        <a href="{{ route('dashboard.main') }}" class="brand-logo-link" aria-label="Go to dashboard">
            <img src="{{ asset('icons/logo.png') }}" alt="Ideal Motors" class="brand-logo">
        </a>
        <div class="top-icons top-icons-right"></div>
    </header>

    <div class="enquiry-shell">
        @if(session('success'))
            <div class="form-flash success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="form-flash error">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="form-flash error">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="enquiry-form" method="POST" action="{{ route('save.customer') }}" id="enquiryForm">
            @csrf

            <div class="field-row triple">
                <select id="model" name="model" class="input-pill" required>
                    <option value="">Select model</option>
                    @foreach($models as $m)
                    <option value="{{ $m->model }}" @selected(old('model') === $m->model)>{{ $m->model }}</option>
                    @endforeach
                </select>

                <select id="engine" name="engine" class="input-pill" required>
                    <option value="">Select engine type</option>
                </select>

                <select id="variant" name="variant" class="input-pill" required>
                    <option value="">Select variant</option>
                </select>
            </div>

            <h4 class="section-title">Lead Source</h4>
            <input type="hidden" id="lead_source" name="lead_source" value="{{ old('lead_source', 'Walk-In') }}">
            <div class="segmented-row six-col" id="leadSourceGroup">
                <button type="button" class="segment-btn source-btn" data-value="Walk-In" onclick="selectSource(this)">Walk-In</button>
                <button type="button" class="segment-btn source-btn" data-value="Tele-In" onclick="selectSource(this)">Tele-In</button>
                <button type="button" class="segment-btn source-btn" data-value="Activity" onclick="selectSource(this)">Activity</button>
                <button type="button" class="segment-btn source-btn" data-value="Digital" onclick="selectSource(this)">Digital</button>
                <button type="button" class="segment-btn source-btn" data-value="Referral" onclick="selectSource(this)">Referral</button>
                <button type="button" class="segment-btn source-btn" data-value="Press" onclick="selectSource(this)">Press</button>
            </div>

            <div class="field-row">
                <div class="stack-field full-width">
                    <label class="stack-label" for="sourceOfInformationSelect">Source Of Information</label>
                    <select
                        id="sourceOfInformationSelect"
                        name="source_of_information"
                        class="input-pill"
                        data-selected-source-info="{{ $selectedSourceInformation }}"
                        required
                    >
                        <option value="">Select Source of Information</option>
                    </select>
                </div>
            </div>

            <div class="field-row split name-contact-row">
                <div class="name-block">
                    <div class="field-row title-name-row">
                        <select name="title" class="input-pill title-select">
                            <option value="Mr" @selected(old('title', 'Mr') === 'Mr')>Mr</option>
                            <option value="Mrs" @selected(old('title') === 'Mrs')>Mrs</option>
                            <option value="Ms" @selected(old('title') === 'Ms')>Ms</option>
                        </select>
                        <input type="text" name="name" class="input-pill" placeholder="Name" value="{{ old('name') }}" required>
                    </div>
                </div>

                <div id="mobile-section" class="mobile-block">
                    @foreach($oldMobiles as $index => $oldMobile)
                        <div class="mobile-row">
                            <input
                                type="text"
                                name="mobiles[]"
                                class="input-pill"
                                placeholder="Contact No"
                                value="{{ $oldMobile }}"
                                inputmode="numeric"
                                maxlength="10"
                                minlength="10"
                                pattern="0\d{9}"
                                title="Contact number must be 10 digits and start with 0."
                                oninput="this.value = this.value.replace(/\D/g, '').slice(0, 10);"
                                @if($index === 0) required @endif
                            >
                            @if($index === 0)
                                <button type="button" class="icon-add" onclick="addMobile()">+</button>
                            @else
                                <button type="button" class="icon-remove" onclick="removeMobile(this)">-</button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="field-row triple district-filter-row">
                <select name="province" id="provinceSelect" class="input-pill" required>
                    <option value="">Select Province</option>
                    @foreach($provinceOptions as $provinceOption)
                        <option value="{{ $provinceOption }}" @selected($selectedProvince === $provinceOption)>{{ $provinceOption }}</option>
                    @endforeach
                </select>
                <select name="district" id="districtSelect" class="input-pill" required>
                    <option value="">Select District</option>
                </select>
                <input type="text" name="location" class="input-pill" placeholder="Location" value="{{ old('location') }}">
            </div>

            <button type="button" class="add-address-btn" onclick="toggleAddress()">
                <span class="add-address-plus" aria-hidden="true">+</span>
                <span>Add Full Address</span>
            </button>

            <div id="address" class="address-block" style="display:{{ $hasAddressValues ? 'block' : 'none' }};">
                <div class="field-row split">
                    <input type="text" name="state" class="input-pill" placeholder="State" value="{{ old('state') }}">
                    <input type="text" name="address1" class="input-pill" placeholder="Address Line 1" value="{{ old('address1') }}">
                </div>
                <input type="text" name="address2" class="input-pill" placeholder="Address Line 2" value="{{ old('address2') }}">
            </div>

            <h4 class="section-title">Plan Follow Up</h4>
            <input type="hidden" id="follow_type" name="follow_type" value="{{ old('follow_type', 'Home Visit') }}">
            <div class="segmented-row three-col" id="followGroup">
                <button type="button" class="segment-btn follow-btn" data-value="Home Visit" onclick="selectFollow(this)">Home Visit</button>
                <button type="button" class="segment-btn follow-btn" data-value="Showroom Visit" onclick="selectFollow(this)">Showroom Visit</button>
                <button type="button" class="segment-btn follow-btn" data-value="Call" onclick="selectFollow(this)">Call</button>
            </div>

            <div class="field-row triple">
                <div class="stack-field">
                    <label class="stack-label" for="followDateInput">Follow up Date</label>
                    <input id="followDateInput" type="date" name="follow_date" class="input-pill" placeholder="Followup Date" value="{{ old('follow_date') }}" required>
                </div>
                <div class="stack-field">
                    <label class="stack-label" for="followTimeInput">Follow up Time</label>
                    <input id="followTimeInput" type="time" name="follow_time" class="input-pill" placeholder="Followup Time" value="{{ $selectedFollowTime }}" required>
                </div>
                <div class="stack-field">
                    <label class="stack-label" for="inquiryDateInput">Date of Inquiry</label>
                    <input id="inquiryDateInput" type="date" class="input-pill" value="{{ now()->format('Y-m-d') }}" readonly>
                </div>
            </div>

            <div class="switch-row">
                <label class="switch-item">
                    <span>Exchange</span>
                    <span class="switch-wrap">
                        <input type="checkbox" name="exchange" @checked(old('exchange'))>
                        <span class="slider"></span>
                    </span>
                </label>

                <label class="switch-item switch-item-right">
                    <span>Finance</span>
                    <span class="switch-wrap">
                        <input type="checkbox" name="finance" @checked(old('finance'))>
                        <span class="slider"></span>
                    </span>
                </label>
            </div>

            <div class="action-row">
                <button class="btn-epr" type="submit">EPR</button>
                <button class="btn-cancel" type="reset">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modelSelect = document.getElementById('model');
    const engineSelect = document.getElementById('engine');
    const variantSelect = document.getElementById('variant');
    const oldModel = @json(old('model'));
    const oldEngine = @json(old('engine'));
    const oldVariant = @json(old('variant'));
    const sourceInfoMap = @json($sourceInfoMap);

    function resetSelect(selectEl, placeholder) {
        selectEl.innerHTML = `<option value="">${placeholder}</option>`;
    }

    function loadEngines(model, selectedEngine = '') {
        if (!model) {
            resetSelect(engineSelect, 'Select engine type');
            resetSelect(variantSelect, 'Select variant');
            return Promise.resolve();
        }

        return fetch('/get-engines/' + encodeURIComponent(model))
            .then(res => res.json())
            .then(data => {
                resetSelect(engineSelect, 'Select engine type');
                resetSelect(variantSelect, 'Select variant');

                data.forEach(e => {
                    const option = document.createElement('option');
                    option.value = e.engine_type;
                    option.textContent = e.engine_type;
                    if (selectedEngine && selectedEngine === e.engine_type) {
                        option.selected = true;
                    }
                    engineSelect.appendChild(option);
                });
            });
    }

    function loadVariants(model, engine, selectedVariant = '') {
        if (!model || !engine) {
            resetSelect(variantSelect, 'Select variant');
            return Promise.resolve();
        }

        return fetch('/get-variants/' + encodeURIComponent(model) + '/' + encodeURIComponent(engine))
            .then(res => res.json())
            .then(data => {
                resetSelect(variantSelect, 'Select variant');
                data.forEach(v => {
                    const option = document.createElement('option');
                    option.value = v.variant;
                    option.textContent = v.variant;
                    if (selectedVariant && selectedVariant === v.variant) {
                        option.selected = true;
                    }
                    variantSelect.appendChild(option);
                });
            });
    }

    modelSelect.addEventListener('change', function() {
        const model = this.value;
        loadEngines(model);
    });

    engineSelect.addEventListener('change', function() {
        const model = modelSelect.value;
        const engine = this.value;
        loadVariants(model, engine);
    });

    (function () {
        const provinceSelect = document.getElementById('provinceSelect');
        const districtSelect = document.getElementById('districtSelect');
        if (!provinceSelect || !districtSelect) {
            return;
        }

        const provinceDistrictMap = @json($provinceDistrictMap);
        const selectedDistrict = @json($selectedDistrict);

        const populateDistricts = () => {
            const selectedProvince = String(provinceSelect.value || '');
            districtSelect.innerHTML = '';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = selectedProvince === '' ? 'Select Province first' : 'Select District';
            districtSelect.appendChild(placeholder);

            if (selectedProvince === '' || !Array.isArray(provinceDistrictMap[selectedProvince])) {
                districtSelect.value = '';
                districtSelect.disabled = true;
                return;
            }

            const districts = provinceDistrictMap[selectedProvince];
            districts.forEach((district) => {
                const option = document.createElement('option');
                option.value = district;
                option.textContent = district;
                if (selectedDistrict && selectedDistrict === district) {
                    option.selected = true;
                }
                districtSelect.appendChild(option);
            });

            districtSelect.disabled = false;
        };

        provinceSelect.addEventListener('change', () => {
            const previousSelectedDistrict = districtSelect.value;
            populateDistricts();
            if (previousSelectedDistrict !== '' && districtSelect.querySelector(`option[value="${previousSelectedDistrict}"]`)) {
                districtSelect.value = previousSelectedDistrict;
            }
        });
        populateDistricts();
    })();

    function toggleAddress() {
        const div = document.getElementById('address');
        div.style.display = div.style.display === 'none' ? 'block' : 'none';
    }

    function addMobile() {
        const container = document.getElementById('mobile-section');
        const html = `
            <div class="mobile-row">
                <input
                    type="text"
                    name="mobiles[]"
                    class="input-pill"
                    placeholder="Contact No"
                    inputmode="numeric"
                    maxlength="10"
                    minlength="10"
                    pattern="0\\d{9}"
                    title="Contact number must be 10 digits and start with 0."
                    oninput="this.value = this.value.replace(/\\D/g, '').slice(0, 10);"
                >
                <button type="button" class="icon-remove" onclick="removeMobile(this)">-</button>
            </div>`;
        container.insertAdjacentHTML('beforeend', html);
    }

    function removeMobile(btn) {
        btn.parentElement.remove();
    }

    function selectSource(btn) {
        document.querySelectorAll('.source-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('lead_source').value = btn.dataset.value || btn.innerText;
        updateSourceInformationOptions();
    }

    function selectFollow(btn) {
        document.querySelectorAll('.follow-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('follow_type').value = btn.dataset.value || btn.innerText;
    }

    function updateSourceInformationOptions() {
        const sourceSelect = document.getElementById('sourceOfInformationSelect');
        const leadSourceInput = document.getElementById('lead_source');
        if (!sourceSelect || !leadSourceInput) {
            return;
        }

        const selectedLeadSource = String(leadSourceInput.value || '');
        const selectedFromServer = sourceSelect.dataset.selectedSourceInfo || sourceSelect.value;
        const options = Array.isArray(sourceInfoMap[selectedLeadSource]) ? sourceInfoMap[selectedLeadSource] : [];

        sourceSelect.innerHTML = '<option value="">Select Source of Information</option>';
        options.forEach((sourceOption) => {
            const option = document.createElement('option');
            option.value = sourceOption;
            option.textContent = sourceOption;
            if (sourceOption === selectedFromServer) {
                option.selected = true;
            }
            sourceSelect.appendChild(option);
        });

        sourceSelect.disabled = options.length === 0;
        sourceSelect.dataset.selectedSourceInfo = '';
    }

    (function initializeSegmentedSelections() {
        const selectedSource = String(document.getElementById('lead_source')?.value || '');
        const selectedFollow = String(document.getElementById('follow_type')?.value || '');

        const sourceBtn = Array.from(document.querySelectorAll('.source-btn'))
            .find((btn) => (btn.dataset.value || btn.innerText).trim() === selectedSource.trim());
        const followBtn = Array.from(document.querySelectorAll('.follow-btn'))
            .find((btn) => (btn.dataset.value || btn.innerText).trim() === selectedFollow.trim());

        if (sourceBtn) {
            selectSource(sourceBtn);
        } else {
            const fallback = document.querySelector('.source-btn');
            if (fallback) selectSource(fallback);
        }
        updateSourceInformationOptions();

        if (followBtn) {
            selectFollow(followBtn);
        } else {
            const fallback = document.querySelector('.follow-btn');
            if (fallback) selectFollow(fallback);
        }
    })();

    (function initializeVehicleSelections() {
        if (!oldModel) {
            return;
        }

        modelSelect.value = oldModel;
        loadEngines(oldModel, oldEngine).then(() => {
            if (oldModel && oldEngine) {
                loadVariants(oldModel, oldEngine, oldVariant);
            }
        });
    })();

    document.getElementById('enquiryForm').addEventListener('submit', function () {
        const firstMobile = document.querySelector('input[name="mobiles[]"]');
        if (firstMobile) {
            firstMobile.value = firstMobile.value.trim();
        }
    });

</script>
@endsection
