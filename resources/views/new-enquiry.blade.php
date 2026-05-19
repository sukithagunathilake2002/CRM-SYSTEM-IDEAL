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

        <form class="enquiry-form" method="POST" action="{{ route('save.customer') }}">
            @csrf

            <div class="field-row triple">
                <select id="model" name="model" class="input-pill">
                    <option value="">Select model</option>
                    @foreach($models as $m)
                    <option value="{{ $m->model }}">{{ $m->model }}</option>
                    @endforeach
                </select>

                <select id="engine" name="engine" class="input-pill">
                    <option value="">Select engine type</option>
                </select>

                <select id="variant" name="variant" class="input-pill">
                    <option value="">Select variant</option>
                </select>
            </div>

            <h4 class="section-title">Lead Source</h4>
            <input type="hidden" id="lead_source" name="lead_source" value="Walk-In">
            <div class="segmented-row six-col" id="leadSourceGroup">
                <button type="button" class="segment-btn source-btn active" onclick="selectSource(this)">Walk-In</button>
                <button type="button" class="segment-btn source-btn" onclick="selectSource(this)">Tele-In</button>
                <button type="button" class="segment-btn source-btn" onclick="selectSource(this)">Activity</button>
                <button type="button" class="segment-btn source-btn" onclick="selectSource(this)">Digital</button>
                <button type="button" class="segment-btn source-btn" onclick="selectSource(this)">Referral</button>
                <button type="button" class="segment-btn source-btn" onclick="selectSource(this)">Press</button>
            </div>

            <div class="field-row split name-contact-row">
                <div class="name-block">
                    <div class="field-row title-name-row">
                        <select name="title" class="input-pill title-select">
                            <option>Mr</option>
                            <option>Mrs</option>
                            <option>Ms</option>
                        </select>
                        <input type="text" name="name" class="input-pill" placeholder="Name">
                    </div>
                </div>

                <div id="mobile-section" class="mobile-block">
                    <div class="mobile-row">
                        <input type="text" name="mobiles[]" class="input-pill" placeholder="Contact No">
                        <button type="button" class="icon-add" onclick="addMobile()">+</button>
                    </div>
                </div>
            </div>

            <div class="field-row triple district-filter-row">
                <select name="province" id="provinceSelect" class="input-pill">
                    <option value="">Select Province</option>
                    @foreach($provinceOptions as $provinceOption)
                        <option value="{{ $provinceOption }}" @selected($selectedProvince === $provinceOption)>{{ $provinceOption }}</option>
                    @endforeach
                </select>
                <select name="district" id="districtSelect" class="input-pill">
                    <option value="">Select District</option>
                </select>
                <input type="text" name="location" class="input-pill" placeholder="Location">
            </div>
            <input type="hidden" name="latitude" id="enquiryLatitude">
            <input type="hidden" name="longitude" id="enquiryLongitude">
            <input type="hidden" name="location_captured_at" id="locationCapturedAt">
            <p id="geoStatus" class="geo-status">Trying to capture current location...</p>

            <p class="add-address" onclick="toggleAddress()">+ Add Full Address</p>

            <div id="address" class="address-block" style="display:none;">
                <div class="field-row split">
                    <input type="text" name="state" class="input-pill" placeholder="State">
                    <input type="text" name="address1" class="input-pill" placeholder="Address Line 1">
                </div>
                <input type="text" name="address2" class="input-pill" placeholder="Address Line 2">
            </div>

            <h4 class="section-title">Plan Follow Up</h4>
            <input type="hidden" id="follow_type" name="follow_type" value="Home Visit">
            <div class="segmented-row three-col" id="followGroup">
                <button type="button" class="segment-btn follow-btn active" onclick="selectFollow(this)">Home Visit</button>
                <button type="button" class="segment-btn follow-btn" onclick="selectFollow(this)">Showroom Visit</button>
                <button type="button" class="segment-btn follow-btn" onclick="selectFollow(this)">Call</button>
            </div>

            <div class="field-row triple">
                <input type="date" name="follow_date" class="input-pill" placeholder="Followup Date">
                <input type="time" name="follow_time" class="input-pill" placeholder="Followup Time">
                <input type="date" name="date_of_inquiry" class="input-pill" value="{{ now()->format('Y-m-d') }}">
            </div>

            <div class="switch-row">
                <label class="switch-item">
                    <span>Exchange</span>
                    <span class="switch-wrap">
                        <input type="checkbox" name="exchange">
                        <span class="slider"></span>
                    </span>
                </label>

                <label class="switch-item switch-item-right">
                    <span>Finance</span>
                    <span class="switch-wrap">
                        <input type="checkbox" name="finance">
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
    document.getElementById('model').addEventListener('change', function() {
        const model = this.value;
        if (!model) return;

        fetch('/get-engines/' + encodeURIComponent(model))
            .then(res => res.json())
            .then(data => {
                const engine = document.getElementById('engine');
                engine.innerHTML = '<option value="">Select engine type</option>';
                document.getElementById('variant').innerHTML = '<option value="">Select variant</option>';

                data.forEach(e => {
                    engine.innerHTML += `<option value="${e.engine_type}">${e.engine_type}</option>`;
                });
            });
    });

    document.getElementById('engine').addEventListener('change', function() {
        const model = document.getElementById('model').value;
        const engine = this.value;
        if (!engine) return;

        fetch('/get-variants/' + encodeURIComponent(model) + '/' + encodeURIComponent(engine))
            .then(res => res.json())
            .then(data => {
                const variant = document.getElementById('variant');
                variant.innerHTML = '<option value="">Select variant</option>';

                data.forEach(v => {
                    variant.innerHTML += `<option value="${v.variant}">${v.variant}</option>`;
                });
            });
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
                <input type="text" name="mobiles[]" class="input-pill" placeholder="Contact No">
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
        document.getElementById('lead_source').value = btn.innerText;
    }

    function selectFollow(btn) {
        document.querySelectorAll('.follow-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('follow_type').value = btn.innerText;
    }

    function captureCurrentLocation() {
        const statusEl = document.getElementById('geoStatus');
        const latitudeEl = document.getElementById('enquiryLatitude');
        const longitudeEl = document.getElementById('enquiryLongitude');
        const capturedAtEl = document.getElementById('locationCapturedAt');

        if (!navigator.geolocation) {
            if (statusEl) {
                statusEl.textContent = 'Current location is not supported on this device.';
                statusEl.classList.add('error');
            }
            return;
        }

        navigator.geolocation.getCurrentPosition(function (position) {
            if (latitudeEl) latitudeEl.value = position.coords.latitude.toFixed(7);
            if (longitudeEl) longitudeEl.value = position.coords.longitude.toFixed(7);
            if (capturedAtEl) capturedAtEl.value = new Date().toISOString();

            if (statusEl) {
                statusEl.textContent = 'Current location captured successfully.';
                statusEl.classList.remove('error');
                statusEl.classList.add('success');
            }
        }, function () {
            if (statusEl) {
                statusEl.textContent = 'Location permission denied. Enquiry will save without GPS.';
                statusEl.classList.add('error');
            }
        }, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 300000
        });
    }

    captureCurrentLocation();
</script>
@endsection

