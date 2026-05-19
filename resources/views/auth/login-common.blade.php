@extends('layouts.portal')

@section('content')
<section class="card auth-card narrow">
    <h1>Common Login</h1>
    <p>Select user type and login.</p>

    <form method="POST" action="{{ route('auth.login.common.submit') }}" class="form-grid">
        @csrf

        <label>
            User Type
            <select name="role" required>
                <option value="">Select user type</option>
                @foreach($roles as $role)
                    @php
                        $slug = $slugs[$role];
                        $label = $labels[$role];
                    @endphp
                    <option value="{{ $slug }}" @selected(old('role') === $slug)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label>
            Email
            <input type="email" name="email" value="{{ old('email') }}" required>
        </label>

        <label>
            Password
            <input type="password" name="password" required>
        </label>

        <label class="inline-check">
            <input type="checkbox" name="remember" value="1">
            Remember me
        </label>

        <button type="submit" class="btn-primary">Login</button>
    </form>

</section>
@endsection
