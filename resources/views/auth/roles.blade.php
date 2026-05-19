@extends('layouts.portal')

@section('content')
<section class="card auth-card">
    <h1>Select Login / Registration Role</h1>
    <p>Use the correct role portal to login or create an account.</p>

    <div class="role-grid">
        @foreach($roles as $role)
            @php
                $slug = $slugs[$role];
                $label = $labels[$role];
            @endphp
            <div class="role-item">
                <h3>{{ $label }}</h3>
                <div class="role-actions">
                    <a href="{{ route('auth.login.form', $slug) }}" class="btn-link">Login</a>
                    <a href="{{ route('auth.register.form', $slug) }}" class="btn-link alt">Register</a>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endsection

