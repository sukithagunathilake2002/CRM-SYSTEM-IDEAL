@extends('layouts.portal')

@section('content')
<section class="card auth-card narrow">
    <h1>{{ $roleLabel }} Registration</h1>

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
            Employee Number
            <input
                type="text"
                name="employee_number"
                value="{{ old('employee_number', 'M') }}"
                maxlength="6"
                pattern="M[0-9]{5}"
                placeholder="M00000"
                title="Employee number must start with M followed by exactly 5 digits"
                required
            >
        </label>

        <label>
            Phone
            <input
                type="text"
                name="phone"
                value="{{ old('phone') }}"
                inputmode="numeric"
                maxlength="10"
                pattern="0[0-9]{9}"
                title="Phone number must start with 0 and contain exactly 10 digits"
            >
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
@endsection
