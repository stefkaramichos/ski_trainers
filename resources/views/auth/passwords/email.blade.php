@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-center main-login-form align-items-center">
    <div class="login-card">
        <div class="login-header">{{ __('Reset Password 🔐') }}</div>

        @if (session('status'))
            <div class="alert alert-success text-center fw-semibold" role="alert">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="mb-4">
                <label for="email" class="form-label">{{ __('Email Address') }}</label>
                <input id="email" type="email"
                    class="form-control @error('email') is-invalid @enderror"
                    name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                    {{ __('Send Password Reset Link') }}
                </button>
            </div>

            <div class="text-center mt-4">
                <a class="btn-link" href="{{ route('login') }}">
                    ← {{ __('Back to Login') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
