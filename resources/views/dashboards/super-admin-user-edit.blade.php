@extends('layouts.portal')

@section('content')
<section class="card auth-card narrow">
    <h1>Edit User</h1>
    <p>Update role, profile details, reporting manager, and password.</p>

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
            Employee Number
            <input
                type="text"
                name="employee_number"
                value="{{ old('employee_number', $managedUser->employee_number) }}"
                maxlength="6"
                pattern="M[0-9]{5}"
                placeholder="M00000"
                title="Employee number must start with M followed by exactly 5 digits"
            >
        </label>

        <label>
            Phone
            <input
                type="text"
                name="phone"
                value="{{ old('phone', $managedUser->phone) }}"
                inputmode="numeric"
                maxlength="10"
                pattern="0[0-9]{9}"
                title="Phone number must start with 0 and contain exactly 10 digits"
            >
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
        const parentRoleByRole = @json($parentRoleByRole);
        const roleLabels = @json($roleLabels);

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
        };

        roleSelect.addEventListener('change', applyManagerRules);
        applyManagerRules();
    })();
</script>
@endsection
