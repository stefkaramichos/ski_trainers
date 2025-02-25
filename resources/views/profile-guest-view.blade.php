@extends('layouts.app')

@section('content')
<div class="main-form">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                    @include('includes.profile-header')
                    </div>

                    <div class="card-body">   
                        <div>
                            <div class="row mb-3">
                                <label for="name" class="col-md-4 col-form-label text-md-end">{{ __('auth.name') }}</label>
                        
                                <div class="col-md-6">
                                    {{ $user->name }}
                                </div>
                            </div>
                        
                            <div class="row mb-3">
                                <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('auth.Email_Address') }}</label>
                        
                                <div class="col-md-6">
                                    <input id="email" value="{{ $user->email }}"  type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">
                        
                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        
                            <div class="row mb-3">
                                <label for="description" class="col-md-4 col-form-label text-md-end">{{ __('auth.description') }}</label>
                            
                                <div class="col-md-6">
                                    <textarea id="description" class="form-control @error('description') is-invalid @enderror" name="description" required>{{ $user->description }}</textarea>
                            
                                    @error('description')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    @if (Auth::check() && (Auth::user()->id === $user->id  || Auth::user()->super_admin === 'Y'))
                        <div class="edit-ptofile p-3">
                            <a href=" {{ route('profile', $user->id) }} ">
                                <i title="Επεξεργαρία προφίλ" class="fa fa-edit" style="font-size:24px"></i>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
