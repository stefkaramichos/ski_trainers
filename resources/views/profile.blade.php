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
                        @include('includes.success-error-message')
                        @include('includes.edit-form-profile')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
