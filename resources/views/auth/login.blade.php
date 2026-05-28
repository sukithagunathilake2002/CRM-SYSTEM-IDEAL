@extends('layouts.portal')

@section('bodyClass', 'login-page')

@section('content')
<div class="login-ui">
    <section class="login-ui-left">
        <div class="login-ui-form-wrap">
            <img src="{{ asset('icons/logo.png') }}" alt="Ideal Motors Logo" class="login-ui-logo">
            <h1>LOGIN</h1>
            <p class="login-ui-role">{{ $roleLabel }}</p>
            @if(session('success'))
                <div class="login-ui-inline-flash success auto-dismiss" data-auto-dismiss="10000">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="login-ui-inline-flash error auto-dismiss" data-auto-dismiss="10000">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('auth.login.submit', $roleSlug) }}" class="login-ui-form">
                @csrf

                <label class="login-ui-field">
                    <span class="sr-only">Email</span>
                    <span class="login-ui-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M4 8l8 5 8-5"></path><rect x="4" y="6" width="16" height="12" rx="2"></rect></svg>
                    </span>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="Username" required>
                </label>

                <label class="login-ui-field">
                    <span class="sr-only">Password</span>
                    <span class="login-ui-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="9" rx="2"></rect><path d="M8 11V8a4 4 0 0 1 8 0v3"></path></svg>
                    </span>
                    <input type="password" name="password" placeholder="Password" required>
                </label>

                <label class="login-ui-remember">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    Remember me
                </label>

                <button type="submit" class="login-ui-submit">Login</button>
            </form>

            <div class="login-ui-links">
                <a href="{{ route('auth.register.form', $roleSlug) }}">Create {{ $roleLabel }} account</a>
                <a href="{{ route('auth.roles') }}">Back to all roles</a>
            </div>
        </div>
    </section>

    <aside class="login-ui-right">
        <div class="login-ui-hero">
            <p>Manage customers.<br>Grow business.<br>Build trust.</p>
            <img src="{{ asset('icons/login.jpg') }}" alt="Ideal Motors CRM login visual">
        </div>
    </aside>
</div>
@endsection
