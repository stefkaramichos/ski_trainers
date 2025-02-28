
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
                               
                                    <div class="card-body">
                                        <ul class="list-group">
                                            @for ($i = 0; $i < count($selectedDatetimes['dates']); $i++)
                                                <li class="list-group-item d-flex justify-content-between selected-date-for-submit align-items-center">
                                                    Ημερομηνία: {{ $selectedDatetimes['dates'][$i] }}, 
                                                    Ώρα: {{ $selectedDatetimes['times'][$i] }}

                                                    <form method="POST" action="{{ route('profile-date.delete') }}" class="d-flex align-items-center" style="display:inline;">
                                                        @csrf
                                                        <input type="hidden" name="delete_index" value="{{ $i }}">
                                                        <button type="submit" class="btn delete-selected-dates btn-danger btn-sm"><i class="fa fa-trash-o" style="font-size:16px"></i></button>
                                                    </form>
                                                </li>
                                            @endfor
                                        </ul>
                                        <form method="POST" action="{{ route('submit.selected.datetimes', $user) }}" class="mt-3">
                                            @csrf
                                            @foreach ($selectedDatetimes['dates'] as $index => $date)
                                                <input type="hidden" name="selected_datetimes[{{ $index }}][date]" value="{{ $date }}">
                                                <input type="hidden" name="selected_datetimes[{{ $index }}][time]" value="{{ $selectedDatetimes['times'][$index] }}">
                                            @endforeach
                                            <button type="submit" title="Καταχώρηση ημερομηνιών στο πρόγραμμά μου" class="btn btn-success">Υποβολή Επιλεγμένων Ημερομηνιών και Ωρών</button>
                                        </form>
                                    </div>
                                
                            @endif
                            </div>
                        </div>
                    
                    </div>
                    
                </div> 
            </div>


            @extends('layouts.app')
        </div>
    </div>
</div>
@endsection

