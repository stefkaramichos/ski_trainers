@extends('layouts.app')

@section('content')
<div>
  

    @include('includes.home.booking-section')
    @include('includes.home.book-instructor')
    @include('includes.home.text-section-users')
    @include('includes.home.text-section-instructors')
    @include('includes.home.text-section-infos')

    


    @include('includes.home.weather')
    {{-- Weather for each Mountain --}}

   

    {{-- @include('includes.home.carousel-trainers')  --}}
</div>
@endsection
