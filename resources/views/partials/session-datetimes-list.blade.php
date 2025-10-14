{{-- partial: resources/views/partials/session-datetimes-list.blade.php --}}
@if (!empty($sessionDatetimesAll))
  <div class="mb-4">
    <h3 class="mb-3 gray-color font-weight-bold text-left">
      <b>Προσωρινές επιλογές προς καταχώρηση (<span id="pending-count">{{ count($sessionDatetimesAll) }}</span>)</b>
    </h3>

    <form id="save-all-form" method="POST" action="{{ route('submit.selected.datetimes', $user) }}">
      @csrf
      <ul id="pending-list" class="list-group session-list mb-3">
        @foreach ($sessionDatetimesAll as $i => $item)
          <li id="session-item-{{ $item['index'] }}" class="list-group-item font-s-16 d-flex justify-content-between align-items-center bg-warning">
            <div>
              (Νέα επιλογή)
              <b> {{ \Carbon\Carbon::parse($item['date'])->translatedFormat('l d-m-Y') }}</b>,
             <b> {{ $item['time'] }}</b>
            </div>

            <div class="d-flex gap-2">
              {{-- AJAX delete temporary (session) --}}
              <button
                type="button"
                class="btn btn-light btn-sm js-delete-session"
                data-index="{{ $item['index'] }}"
                data-date="{{ $item['date'] }}"
                data-time="{{ $item['time'] }}"
                data-url="{{ route('profile-date.delete') }}"
                title="Αφαίρεση προσωρινής επιλογής"
              >
                <i class="fa fa-trash-o text-danger"></i>
              </button>
            </div>

            {{-- Hidden inputs for the “Save All” form --}}
            <input type="hidden" name="selected_datetimes[{{ $i }}][date]" value="{{ $item['date'] }}">
            <input type="hidden" name="selected_datetimes[{{ $i }}][time]" value="{{ $item['time'] }}">
          </li>
        @endforeach
      </ul>

      <div class="submit-session-list">
        <button id="save-all-btn" type="submit" class="button-shandow-st p-3 mb-5 rounded btn btn-success">
          <i class="fa fa-save"></i> Καταχώρηση Νέων Ημερομηνιών & Ωρών
        </button>
      </div>
    </form>
  </div>
@endif
