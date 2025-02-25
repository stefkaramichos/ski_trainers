
@section('content')
<div class="main-form">
    <div class="container">
        <div class="row ">
            @include('includes.profile-header')
            @include('includes.admin-edit-menu')
            <div class="mb-3 availability">
                <div class="card">
                    <div class="card-header">
                        Διαθεσιμότητα
                    </div>

                    <div class="card-body">
                        <div class="container mt-5">
                            {!! $calendar !!}
                            {!! $timeSelection !!}
                            @if (!empty($selectedDatetimes['dates']))
                                <div class="card mt-4">
                                    <div class="card-header bg-light text-dark text-center">
                                        <h3>Επιλεγμένες Ημερομηνίες και Ώρες</h3>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group">
                                            @for ($i = 0; $i < count($selectedDatetimes['dates']); $i++)
                                                <li class="list-group-item">
                                                    Ημερομηνία: {{ $selectedDatetimes['dates'][$i] }},
                                                    Ώρα: {{ $selectedDatetimes['times'][$i] }}
                                                </li>
                                            @endfor
                                        </ul>
                                        <form method="POST" action="{{ route('submit.selected.datetimes', $user) }}" class="mt-3">
                                            @csrf
                                            @foreach ($selectedDatetimes['dates'] as $index => $date)
                                                <input type="hidden" name="selected_datetimes[{{ $index }}][date]" value="{{ $date }}">
                                                <input type="hidden" name="selected_datetimes[{{ $index }}][time]" value="{{ $selectedDatetimes['times'][$index] }}">
                                            @endforeach
                                            <button type="submit" class="btn btn-success">Υποβολή Επιλεγμένων Ημερομηνιών και Ωρών</button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </div>
                    
                    </div>
                    
                </div> 
            </div>


            @extends('layouts.app')
        </div>
    </div>
</div>
@endsection
