@extends('layouts.app')

@section('content')
<div class="main-form">
    <div class="container">
        <div class="row ">
            @include('includes.profile-header')
            @if ($accessLevel == 'A')
                @include('includes.admin-edit-menu')
            @endif
            
            <div class="mb-3">
                <div class="card profile profile-{{ $user->id }}">
                    <div class="card-header">
                        ΠΡΟΦΙΛ
                    </div>

                    <div class="card-body profile-wrapper"> 
                        <div class="profile-inner row">
                            <div class="profile-image col-12 col-md-4">
                                <img src="{{ asset('storage/' . $user->image) }}" alt="Profile Image" >
                            </div> 

                            <div class="profile-info col-12 col-md-4">
                                <div class="profile-desc d-flex justify-content-center align-items-center">
                                    <div class="col-4">
                                        <img width="40" src="{{ asset('storage/skier.png') }}" alt="ski-center">
                                    </div>
                                    <div class="col-8">
                                        <span> {{ $user->description }} </span>
                                    </div>
                                </div>
                                <hr>
                                <div class="profile-mountain d-flex justify-content-center align-items-center">
                                    <div class="col-4">
                                        <img width="50" src="{{ asset('storage/ski-center.png') }}" alt="ski-center">
                                    </div>
                                    <div class="col-8">
                                        @foreach ($user->mountains as $m)
                                            <span>{{$m->mountain_name}}</span>
                                        @endforeach
                                    </div>
                                </div>
                                <hr>
                                
                            </div>

                            <div class="profile-image col-12 col-md-4">
                                asdasd ascascascasc sveqv d vvwec asdasd ascascascasc sveqv d vvwecasdasd ascascascasc sveqv d vvwecasdasd ascascascasc sveqv d vvwecasdasd ascascascasc sveqv d vvwecasdasd ascascascasc sveqv d vvwecasdasd ascascascasc sveqv d vvwecasdasd ascascascasc sveqv d vvwecasdasd ascascascasc sveqv d vvwecasdasd ascascascasc sveqv d vvwec
                            </div> 
                        </div> 
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
