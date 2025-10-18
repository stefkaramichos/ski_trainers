@extends('layouts.app')

@section('content')
<div>
    @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

    @include('includes.home.booking-section')
    @include('includes.home.book-instructor')
    @include('includes.home.carousel-trainers') 
</div>
@endsection
