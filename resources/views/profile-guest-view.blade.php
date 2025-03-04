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
                                @if ($user->image)
                                    <a href="{{ asset('storage/' . $user->image) }}" data-lightbox="profile">
                                        <img src="{{ asset('storage/' . $user->image) }}" alt="Profile Image" >
                                    </a>
                                @else
                                    <img width="50" src="{{ asset('storage/profile-default.png') }}" alt="Profile Image" >
                                @endif
                               
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

                            <div class="profile-availability col-12 col-md-4">
                                <label for="availability-date">Αναζητήστε Διαθεσιμότητα:</label>
                                <input type="date" id="availability-date" class="form-control">
                                <ul id="availability-list" class="list-group mt-2"></ul>
                            </div>
                        </div> 
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        $('#availability-date').on('change', function () {
            let selectedDate = $(this).val();
            let userId = {{ $user->id }};
            
            $.ajax({
                url: '/get-availability',
                type: 'GET',
                data: { user_id: userId, date: selectedDate },
                dataType: 'json',
                success: function (data) {
                    let list = $('#availability-list');
                    list.empty();
                    if (data.length > 0) {
                        $.each(data, function (index, time) {
                            list.append('<li class="list-group-item">' + time + '</li>');
                        });
                    } else {
                        list.append('<li class="list-group-item">Δεν υπάρχει διαθέσιμη ώρα</li>');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error:', error);
                }
            });
        });
    });
</script>
