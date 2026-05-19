@extends('layouts.portal')

@section('content')
<section class="card auth-card narrow">
    <h1>{{ $roleLabel }} Registration</h1>
    @php
        $selectedPermittedDistricts = old('permitted_districts', []);
        $selectedPermittedDistricts = is_array($selectedPermittedDistricts) ? $selectedPermittedDistricts : [];
        $selectedDistrictProvinces = old('district_provinces', []);
        $selectedDistrictProvinces = is_array($selectedDistrictProvinces) ? $selectedDistrictProvinces : [];
        $districtProvinceMap = [];
        foreach (($provinceDistrictMap ?? []) as $province => $districts) {
            foreach ($districts as $district) {
                $districtProvinceMap[$district] = $province;
            }
        }
        if (empty($selectedDistrictProvinces) && !empty($selectedPermittedDistricts)) {
            $selectedDistrictProvinces = collect($selectedPermittedDistricts)
                ->map(fn($district) => $districtProvinceMap[$district] ?? null)
                ->filter()
                ->unique()
                ->values()
                ->all();
        }
    @endphp

    @if($parentRole && $managerOptions->isEmpty())
        <div class="portal-flash error">
            No {{ $parentRoleLabel }} account exists yet. Create one first, then register {{ $roleLabel }}.
        </div>
    @endif

    <form method="POST" action="{{ route('auth.register.submit', $roleSlug) }}" class="form-grid">
        @csrf

        <label>
            Full Name
            <input type="text" name="name" value="{{ old('name') }}" required>
        </label>

        <label>
            Email
            <input type="email" name="email" value="{{ old('email') }}" required>
        </label>

        <label>
            Phone
            <input type="text" name="phone" value="{{ old('phone') }}">
        </label>

        @if($parentRole)
            <label>
                Assign {{ $parentRoleLabel }}
                <select name="manager_id" required>
                    <option value="">Select {{ $parentRoleLabel }}</option>
                    @foreach($managerOptions as $manager)
                        <option value="{{ $manager->id }}" @selected((string) old('manager_id') === (string) $manager->id)>
                            {{ $manager->name }} ({{ $manager->email }})
                        </option>
                    @endforeach
                </select>
            </label>
        @endif

        @if($supportsDistrictPermissions ?? false)
            <div class="district-permissions-field">
                <span class="district-permissions-title">Access Province(s)</span>
                <div id="district_provinces" class="province-checkbox-grid">
                    @foreach(array_keys($provinceDistrictMap ?? []) as $province)
                        @php $provinceKey = preg_replace('/[^a-z0-9]+/i', '_', strtolower($province)); @endphp
                        <div class="province-checkbox-item">
                            <input
                                id="register_province_{{ $provinceKey }}"
                                type="checkbox"
                                name="district_provinces[]"
                                value="{{ $province }}"
                                @checked(in_array($province, $selectedDistrictProvinces, true))
                            >
                            <label for="register_province_{{ $provinceKey }}">{{ $province }}</label>
                        </div>
                    @endforeach
                </div>
                <small>Select one or more provinces first.</small>

                <span class="district-permissions-title">Access District(s)</span>
                <div id="permitted_districts" class="district-checkbox-grid">
                    @foreach(($districtOptions ?? []) as $district)
                        @php $districtKey = preg_replace('/[^a-z0-9]+/i', '_', strtolower($district)); @endphp
                        <div class="district-checkbox-item" data-province="{{ $districtProvinceMap[$district] ?? '' }}">
                            <input
                                id="register_district_{{ $districtKey }}"
                                type="checkbox"
                                name="permitted_districts[]"
                                value="{{ $district }}"
                                @checked(in_array($district, $selectedPermittedDistricts, true))
                            >
                            <label for="register_district_{{ $districtKey }}">{{ $district }}</label>
                        </div>
                    @endforeach
                </div>
                <small id="district-hint">Select province first, then choose district(s). Leave districts empty to allow all available districts.</small>
            </div>
        @endif

        <label>
            Password
            <input type="password" name="password" required>
        </label>

        <label>
            Confirm Password
            <input type="password" name="password_confirmation" required>
        </label>

        <button type="submit" class="btn-primary" @disabled($parentRole && $managerOptions->isEmpty())>Register</button>
    </form>

    <div class="helper-links">
        <a href="{{ route('auth.login.form', $roleSlug) }}">Already have {{ $roleLabel }} login?</a>
        <a href="{{ route('auth.roles') }}">Back to all roles</a>
    </div>
</section>

@if($supportsDistrictPermissions ?? false)
<script>
    (function () {
        const managerSelect = document.querySelector('select[name="manager_id"]');
        const provinceWrap = document.getElementById('district_provinces');
        const districtWrap = document.getElementById('permitted_districts');
        const districtHint = document.getElementById('district-hint');
        if (!provinceWrap || !districtWrap) {
            return;
        }

        const managerPermittedDistrictMap = @json($managerPermittedDistrictMap ?? []);
        const requiresManager = @json((bool) $parentRole);

        const getManagerAllowedDistricts = () => {
            if (!requiresManager || !managerSelect) {
                return @json($districtOptions ?? []);
            }

            const managerId = String(managerSelect.value || '');
            if (managerId === '') {
                return [];
            }

            const districts = managerPermittedDistrictMap[managerId];
            return Array.isArray(districts) ? districts : [];
        };

        const applyDistrictFilter = () => {
            const selectedProvinces = Array.from(
                provinceWrap.querySelectorAll('input[type="checkbox"]:checked')
            ).map((checkbox) => String(checkbox.value || ''));
            const managerAllowed = new Set(getManagerAllowedDistricts());
            const districtRows = Array.from(districtWrap.querySelectorAll('.district-checkbox-item'));
            const hasProvinceFilter = selectedProvinces.length > 0;

            let visibleCount = 0;
            districtRows.forEach((row) => {
                const rowProvince = String(row.dataset.province || '');
                const checkbox = row.querySelector('input[type="checkbox"]');
                if (!checkbox) {
                    return;
                }

                const district = String(checkbox.value || '');
                const provinceMatch = hasProvinceFilter && selectedProvinces.includes(rowProvince);
                const managerMatch = managerAllowed.has(district);
                const visible = provinceMatch && managerMatch;

                row.hidden = !visible;
                row.style.display = visible ? '' : 'none';
                checkbox.disabled = !visible;
                if (!visible) {
                    checkbox.checked = false;
                }

                if (visible) {
                    visibleCount++;
                }
            });

            if (districtHint) {
                if (requiresManager && managerAllowed.size === 0) {
                    districtHint.textContent = 'Select manager first, then province(s), then district(s).';
                } else if (!hasProvinceFilter) {
                    districtHint.textContent = 'Select province first, then choose district(s).';
                } else {
                    districtHint.textContent = `Select one or more districts (${visibleCount} available). Leave empty to allow all available districts.`;
                }
            }
        };

        provinceWrap.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
            checkbox.addEventListener('change', applyDistrictFilter);
        });

        if (managerSelect) {
            managerSelect.addEventListener('change', applyDistrictFilter);
        }

        applyDistrictFilter();
    })();
</script>
@endif
@endsection
