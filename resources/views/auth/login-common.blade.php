@extends('layouts.portal')

@section('bodyClass', 'login-page')

@section('content')
<div class="login-ui">
    <section class="login-ui-left">
        <div class="login-ui-form-wrap">
            <img src="{{ asset('icons/logo.png') }}" alt="Ideal Motors Logo" class="login-ui-logo">
            @if(session('success'))
                <div class="login-ui-inline-flash success auto-dismiss" data-auto-dismiss="10000">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="login-ui-inline-flash error auto-dismiss" data-auto-dismiss="10000">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('auth.login.common.submit') }}" class="login-ui-form">
                @csrf

                <label class="login-ui-field">
                    <span class="sr-only">Username</span>
                    <span class="login-ui-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 12c2.21 0 4-1.8 4-4s-1.79-4-4-4-4 1.8-4 4 1.79 4 4 4Z"></path>
                            <path d="M4.5 20c.55-3.45 3.47-6 7.5-6s6.95 2.55 7.5 6H4.5Z"></path>
                        </svg>
                    </span>
                    <input type="text" name="email" value="{{ old('email') }}" placeholder="Username" required>
                </label>

                <div class="login-ui-field login-ui-password-field">
                    <label class="sr-only" for="commonLoginPassword">Password</label>
                    <span class="login-ui-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-7 0V7a2 2 0 0 1 4 0v2h-4Z"></path>
                        </svg>
                    </span>
                    <input id="commonLoginPassword" type="password" name="password" placeholder="Password" required>
                    <button type="button" class="login-ui-password-toggle" data-password-toggle data-password-target="commonLoginPassword" aria-label="Show password" aria-pressed="false" title="Show password">
                        <svg class="password-toggle-eye" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="password-toggle-eye-off" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M3 3l18 18"></path>
                            <path d="M10.6 10.6A2 2 0 0 0 13.4 13.4"></path>
                            <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6 0 9.5 7 9.5 7a17.8 17.8 0 0 1-2.6 3.4"></path>
                            <path d="M6.5 6.5C3.9 8.2 2.5 12 2.5 12s3.5 7 9.5 7a9.7 9.7 0 0 0 4.1-.9"></path>
                        </svg>
                    </button>
                </div>

                <button type="submit" class="login-ui-submit">Login</button>
            </form>
        </div>
    </section>

    <aside class="login-ui-right">
        <div class="login-ui-hero">
            <p>Manage customers.<br>Grow business.<br>Build trust.</p>
            <img src="{{ asset('icons/login.png') }}" alt="Ideal Motors CRM login visual">
        </div>
    </aside>
</div>
@endsection
