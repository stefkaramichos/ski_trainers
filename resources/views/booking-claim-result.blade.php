{{-- resources/views/booking-claim-result.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-7">
      <div class="card shadow-sm">
        <div class="card-body text-center">
          @if(!empty($ok))
            @if($ok)
              <h3 class="text-success mb-3">Επιτυχής ανάθεση!</h3>
              <p>Η κράτηση ανατέθηκε σε εσάς.</p>
            @else
              <h3 class="text-danger mb-3">Αποτυχία</h3>
              <p>{{ $message ?? 'Ο σύνδεσμος δεν είναι έγκυρος.' }}</p>
            @endif
          @else
            <h3 class="mb-3">Αποτέλεσμα</h3>
            <p>{{ $message ?? '—' }}</p>
          @endif

          @isset($booking)
            <hr>
            <p class="mb-1"><strong>Ημερομηνία:</strong> {{ $booking->selected_date }}</p>
            <p class="mb-1"><strong>Ώρα:</strong> {{ \Illuminate\Support\Str::of($booking->selected_time)->substr(0,5) }}</p>
            <p class="mb-0"><strong>Κατάσταση:</strong> {{ $booking->status }}</p>
          @endisset
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
