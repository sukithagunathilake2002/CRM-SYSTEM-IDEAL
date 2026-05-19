@extends('layouts.portal')

@section('content')
<section class="card auth-card narrow">
    <h1>Edit User</h1>
    <p>Update role, profile details, reporting manager, and password.</p>
    @php
        $selectedPermittedDistricts = old('permitted_districts', $managedUser->permitted_districts ?? []);
        $selectedPermittedDistricts = is_array($selectedPermittedDistricts) ? $selectedPermittedDistricts : [];
        $provinceDistrictMap = \App\Models\User::PROVINCE_DISTRICT_MAP;
        $provinceOptions = array_keys($provinceDistrictMap);
        $selectedDistrictProvinces = old('district_provinces', []);
        $selectedDistrictProvinces = is_array($selectedDistrictProvinces) ? $selectedDistrictProvinces : [];
        $districtProvinceMap = [];
        foreach ($provinceDistrictMap as $province => $districts) {
            foreach ($districts as $district) {
                $districtProvinceMap[$district] = $province;
            }
        }
        $rolesWithDistrictPermissions = [
            \App\Models\User::ROLE_REGIONAL_MANAGER,
            \App\Models\User::ROLE_AREA_MANAGER,
            \App\Models\User::ROLE_SALES_CONSULTANT,
        ];
        if (empty($selectedDistrictProvinces) && !empty($selectedPermittedDistricts)) {
            $selectedDistrictProvinces = collect($selectedPermittedDistricts)
                ->map(fn($district) => $districtProvinceMap[$district] ?? null)
                ->filter()
                ->unique()
                ->values()
                ->all();
        }
    @endphp

    <form method="POST" action="{{ route('dashboard.super_admin.users.update', $managedUser) }}" class="form-grid">
        @csrf
        @method('PUT')

        <label>
            Full Name
            <input type="text" name="name" value="{{ old('name', $managedUser->name) }}">
        </label>

        <label>
            Email
            <input type="email" name="email" value="{{ old('email', $managedUser->email) }}">
        </label>

        <label>
            Phone
            <input type="text" name="phone" value="{{ old('phone', $managedUser->phone) }}">
        </label>

        <label>
            Role
            <select name="role" id="role" required>
                @foreach($roles as $role)
                    <option value="{{ $role }}" @selected(old('role', $managedUser->role) === $role)>{{ $roleLabels[$role] }}</option>
                @endforeach
            </select>
        </label>

        <label>
            Manager
            <select name="manager_id" id="manager_id">
                <option value="">No manager (top role)</option>
                @foreach($managerOptions as $manager)
                    <option
                        value="{{ $manager->id }}"
                        data-role="{{ $manager->role }}"
                        @selected((string) old('manager_id', $managedUser->manager_id) === (string) $manager->id)
                    >
                        {{ $manager->name }} ({{ $manager->email }}) - {{ $roleLabels[$manager->role] ?? $manager->role }}
                    </option>
                @endforeach
            </select>
            <small id="manager-hint"></small>
        </label>

        <div id="districtPermissionsField" class="district-permissions-field">
            <span class="district-permissions-title">Permitted Districts (Regional/Area/Sales)</span>
            <div id="district_provinces" class="province-checkbox-grid">
                @foreach($provinceOptions as $province)
                    @php $provinceKey = preg_replace('/[^a-z0-9]+/i', '_', strtolower($province)); @endphp
                    <div class="province-checkbox-item">
                        <input
                            id="province_{{ $provinceKey }}"
                            type="checkbox"
                            name="district_provinces[]"
                            value="{{ $province }}"
                            @checked(in_array($province, $selectedDistrictProvinces, true))
                        >
                        <label for="province_{{ $provinceKey }}">{{ $province }}</label>
                    </div>
                @endforeach
            </div>
            <small>Select one or more provinces first. District list will show only matching provinces.</small>
            <div id="permitted_districts" class="district-checkbox-grid">
                @foreach($districtOptions as $district)
                    @php $districtKey = preg_replace('/[^a-z0-9]+/i', '_', strtolower($district)); @endphp
                    <div class="district-checkbox-item" data-province="{{ $districtProvinceMap[$district] ?? '' }}">
                        <input
                            id="district_{{ $districtKey }}"
                            type="checkbox"
                            name="permitted_districts[]"
                            value="{{ $district }}"
                            @checked(in_array($district, $selectedPermittedDistricts, true))
                        >
                        <label for="district_{{ $districtKey }}">{{ $district }}</label>
                    </div>
                @endforeach
            </div>
            <small id="district-hint">Select one or more districts. Leave empty to allow all districts.</small>
        </div>

        <label>
            New Password
            <input type="password" name="password" autocomplete="new-password">
        </label>

        <label>
            Confirm New Password
            <input type="password" name="password_confirmation" autocomplete="new-password">
        </label>

        <button type="submit" class="btn-primary">Update User</button>
    </form>

    <div class="helper-links">
        <p>Leave name, email, and password unchanged if you do not want to update them.</p>
        <a href="{{ route('dashboard.super_admin') }}">Back to Super Admin Dashboard</a>
    </div>
</section>

<script>
    (function () {
        const roleSelect = document.getElementById('role');
        const managerSelect = document.getElementById('manager_id');
        const managerHint = document.getElementById('manager-hint');
        const districtField = document.getElementById('districtPermissionsField');
        const districtSelect = document.getElementById('permitted_districts');
        const provinceSelect = document.getElementById('district_provinces');
        const districtHint = document.getElementById('district-hint');
        const parentRoleByRole = @json($parentRoleByRole);
        const roleLabels = @json($roleLabels);
        const provinceDistrictMap = @json($provinceDistrictMap);
        const rolesWithDistrictPermissions = @json($rolesWithDistrictPermissions);

        const applyProvinceFilter = () => {
            if (!districtSelect || !provinceSelect) {
                return { visible: 0, total: 0 };
            }

            const selectedProvinces = Array.from(
                provinceSelect.querySelectorAll('input[type="checkbox"]:checked')
            ).map((checkbox) => String(checkbox.value || ''));
            const districtRows = Array.from(districtSelect.querySelectorAll('.district-checkbox-item'));
            const hasProvinceFilter = selectedProvinces.length > 0;
            let visibleCount = 0;

            districtRows.forEach((row) => {
                const rowProvince = String(row.dataset.province || '');
                const checkbox = row.querySelector('input[type="checkbox"]');
                const isVisible = hasProvinceFilter && selectedProvinces.includes(rowProvince);

                row.hidden = !isVisible;
                row.style.display = isVisible ? '' : 'none';
                if (checkbox) {
                    checkbox.disabled = !isVisible;
                    if (!isVisible) {
                        checkbox.checked = false;
                    }
                }

                if (isVisible) {
                    visibleCount++;
                }
            });

            return { visible: visibleCount, total: districtRows.length };
        };

        const applyManagerRules = () => {
            const selectedRole = roleSelect.value;
            const requiredParentRole = parentRoleByRole[selectedRole] || null;
            let hasVisibleSelectedManager = false;

            Array.from(managerSelect.options).forEach((option) => {
                if (option.value === '') {
                    option.hidden = false;
                    option.textContent = requiredParentRole
                        ? `Select ${roleLabels[requiredParentRole] || 'Manager'}`
                        : 'No manager (top role)';
                    return;
                }

                const isVisible = !requiredParentRole || option.dataset.role === requiredParentRole;
                option.hidden = !isVisible;
                if (isVisible && option.selected) {
                    hasVisibleSelectedManager = true;
                }
            });

            managerSelect.required = Boolean(requiredParentRole);
            if (requiredParentRole) {
                managerHint.textContent = `Required manager role: ${roleLabels[requiredParentRole] || requiredParentRole}`;
                if (!hasVisibleSelectedManager) {
                    managerSelect.value = '';
                }
            } else {
                managerHint.textContent = 'Manager is not required for this role.';
                managerSelect.value = '';
            }

            const supportsDistrictPermissions = rolesWithDistrictPermissions.includes(selectedRole);
            if (districtField) {
                districtField.hidden = !supportsDistrictPermissions;
            }
            if (districtSelect && !supportsDistrictPermissions) {
                Array.from(districtSelect.querySelectorAll('input[type="checkbox"]')).forEach((checkbox) => {
                    checkbox.checked = false;
                    checkbox.disabled = true;
                });
            }
            if (provinceSelect && !supportsDistrictPermissions) {
                Array.from(provinceSelect.querySelectorAll('input[type="checkbox"]')).forEach((checkbox) => {
                    checkbox.checked = false;
                });
            }
            if (districtHint) {
                const { visible, total } = applyProvinceFilter();
                districtHint.textContent = supportsDistrictPermissions
                    ? (visible === 0
                        ? 'Select province first, then select district(s). Leave district list empty to allow all districts.'
                        : (Array.from(provinceSelect.querySelectorAll('input[type="checkbox"]:checked')).length === 0
                            ? 'Select province first, then select district(s). Leave district list empty to allow all districts.'
                            : `Select one or more districts (${visible}/${total} available). Leave empty to allow all districts.`))
                    : 'District permissions are managed for Regional/Area/Sales roles only.';
            }
        };

        roleSelect.addEventListener('change', applyManagerRules);
        if (provinceSelect) {
            provinceSelect.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
                checkbox.addEventListener('change', applyManagerRules);
            });
        }
        applyManagerRules();
    })();
</script>
@endsection
