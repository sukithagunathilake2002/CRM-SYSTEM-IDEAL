@extends('layouts.portal')

@section('content')
<section class="card auth-card narrow">
    <h1>{{ $roleLabel }} Login</h1>

    <form method="POST" action="{{ route('auth.login.submit', $roleSlug) }}" class="form-grid">
        @csrf

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

    <div class="helper-links">
        <a href="{{ route('auth.register.form', $roleSlug) }}">Create {{ $roleLabel }} account</a>
        <a href="{{ route('auth.roles') }}">Back to all roles</a>
    </div>
</section>
@endsection

