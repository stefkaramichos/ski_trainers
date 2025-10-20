@extends('layouts.app')

@section('content')
<div>
  

    @include('includes.home.booking-section')
    @include('includes.home.book-instructor')
    @include('includes.home.weather')
    {{-- Weather for each Mountain --}}


    {{-- @include('includes.home.carousel-trainers')  --}}
</div>
@endsection
