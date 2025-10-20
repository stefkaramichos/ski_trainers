@extends('layouts.app')

@section('content')
<div class="main-form">
    <div class="container">
        <div class="row ">
            @include('includes.profile-header')
            @include('includes.admin-edit-menu')
            <div class="mb-3 ">
                <div class="card ">
                    <div class="card-header">
                        Στοιχεία Λογαριασμού
                    </div>

                    <div class="card-body edit-profile-info edit-profile">   
                        @include('includes.success-error-message')
                        @include('includes.edit-form-profile')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
