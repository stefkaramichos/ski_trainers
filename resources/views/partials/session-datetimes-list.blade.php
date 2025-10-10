{{-- partial: resources/views/partials/session-datetimes-list.blade.php --}}
@if (!empty($sessionDatetimesAll))
  <div class="mb-4">
    <h5 class="mb-3 text-center">
      Προσωρινές επιλογές προς καταχώρηση (<span id="pending-count">{{ count($sessionDatetimesAll) }}</span>)
    </h5>

    <form id="save-all-form" method="POST" action="{{ route('submit.selected.datetimes', $user) }}">
      @csrf
      <ul id="pending-list" class="list-group mb-3">
        @foreach ($sessionDatetimesAll as $i => $item)
          <li id="session-item-{{ $item['index'] }}" class="list-group-item d-flex justify-content-between align-items-center bg-warning">
            <div>
              (Νέα επιλογή)
              Ημερομηνία: {{ \Carbon\Carbon::parse($item['date'])->format('d-m-Y') }},
              Ώρα: {{ $item['time'] }}
            </div>

            <div class="d-flex gap-2">
              {{-- AJAX delete temporary (session) --}}
              <button
                type="button"
                class="btn btn-danger btn-sm js-delete-session"
                data-index="{{ $item['index'] }}"
                data-date="{{ $item['date'] }}"
                data-time="{{ $item['time'] }}"
                data-url="{{ route('profile-date.delete') }}"
                title="Αφαίρεση προσωρινής επιλογής"
              >
                <i class="fa fa-trash-o"></i>
              </button>
            </div>

            {{-- Hidden inputs for the “Save All” form --}}
            <input type="hidden" name="selected_datetimes[{{ $i }}][date]" value="{{ $item['date'] }}">
            <input type="hidden" name="selected_datetimes[{{ $i }}][time]" value="{{ $item['time'] }}">
          </li>
        @endforeach
      </ul>

      <div class="">
        <button id="save-all-btn" type="submit" class="shadow-lg p-3 mb-5 rounded btn btn-success">
          <i class="fa fa-save"></i> Καταχώρηση Νέων Ημερομηνιών & Ωρών
        </button>
      </div>
    </form>
  </div>
@endif
