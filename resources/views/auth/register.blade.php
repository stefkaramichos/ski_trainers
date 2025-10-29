@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-center main-login-form align-items-center">
    <div class="login-card m-2">
        <div class="login-header">{{ __('Εγγραφή ✨') }}</div>

        <form method="POST" action="{{ route('register.post') }}" enctype="multipart/form-data">
            @csrf

            {{-- Name --}}
            <div class="mb-3">
                <label for="name" class="form-label">{{ __('auth.name') }}</label>
                <input id="name" type="text"
                    class="form-control @error('name') is-invalid @enderror"
                    name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            {{-- Mountains --}}
            <div class="mb-3">
                <label for="mountains" class="form-label">{{ __('auth.mountains') }}</label>
                <select id="mountains"
                        class="form-control @error('mountains') is-invalid @enderror"
                        name="mountains[]">
                    @foreach ($mountains as $mountain)
                        <option value="{{ $mountain->id }}"
                            @if(collect(old('mountains', []))->contains($mountain->id)) selected @endif>
                            {{ $mountain->mountain_name }}
                        </option>
                    @endforeach
                </select>
                @error('mountains')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            {{-- Email --}}
            <div class="mb-3">
                <label for="email" class="form-label">{{ __('auth.Email_Address') }}</label>
                <input id="email" type="email"
                       class="form-control @error('email') is-invalid @enderror"
                       name="email" value="{{ old('email') }}" required autocomplete="email">
                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            {{-- Description --}}
            <div class="mb-3">
                <label for="description" class="form-label">{{ __('auth.description') }}</label>
                <select id="description"
                        class="form-control @error('description') is-invalid @enderror"
                        name="description" required>
                    <option value="sk" {{ old('description') === 'sk' ? 'selected' : '' }}>Προπονητής Σκι</option>
                    <option value="sn" {{ old('description') === 'sn' ? 'selected' : '' }}>Προπονητής Snowboard</option>
                </select>
                @error('description')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            {{-- Image --}}
            <div class="mb-3">
                <label for="image" class="form-label">{{ __('auth.profile_image') }}</label>
                <input id="image" type="file"
                       class="form-control @error('image') is-invalid @enderror"
                       name="image" accept="image/*">
                @error('image')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            {{-- Password --}}
            <div class="mb-3">
                <label for="password" class="form-label">{{ __('Password') }}</label>
                <input id="password" type="password"
                       class="form-control @error('password') is-invalid @enderror"
                       name="password" required autocomplete="new-password">
                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            {{-- Confirm Password --}}
            <div class="mb-4">
                <label for="password-confirm" class="form-label">{{ __('Confirm Password') }}</label>
                <input id="password-confirm" type="password"
                       class="form-control"
                       name="password_confirmation" required autocomplete="new-password">
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                    {{ __('auth.register') }}
                </button>
            </div>

            <div class="text-center mt-3">
                <a class="btn-link" href="{{ route('login') }}">{{ __('Already have an account? Log in') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
