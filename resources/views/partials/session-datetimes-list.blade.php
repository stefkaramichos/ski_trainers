{{-- partial: resources/views/partials/session-datetimes-list.blade.php --}}
@php
  /** @var array<int,array{index:int,date:string,time:string}>|Illuminate\Support\Collection $sessionDatetimesAll */
  $sessionItems = $sessionDatetimesAll ?? [];
  $pendingCount = is_countable($sessionItems) ? count($sessionItems) : 0;
@endphp

@if ($pendingCount)
<div class="mb-4">
  <h3 class="mb-3 gray-color font-weight-bold text-left">
    <b>
      Προσωρινές επιλογές προς καταχώρηση
      (<span id="pending-count">{{ $pendingCount }}</span>)
    </b>
  </h3>
 

  <form id="save-all-form" method="POST" action="{{ route('submit.selected.datetimes', $user) }}">
    @csrf

    <ul id="pending-list" class="list-group session-list mb-3">
      @forelse ($sessionItems as $i => $item)
        @php
          $dateYmd = $item['date'] ?? '';
          // Try to format safely; if parse fails, show raw
          try {
            $dateLabel = \Carbon\Carbon::parse($dateYmd)->translatedFormat('l d-m-Y');
          } catch (\Throwable $e) {
            $dateLabel = $dateYmd;
          }
          $timeHi = $item['time'] ?? '';
        @endphp

        <li id="session-item-{{ $item['index'] }}"
            class="list-group-item font-s-16 d-flex justify-content-between align-items-center bg-warning">
          <div>
            (Νέα επιλογή)
            <b>{{ $dateLabel }}</b>, <b>{{ $timeHi }}</b>
          </div>

          <div class="d-flex gap-2">
            {{-- AJAX delete temporary (session) --}}
            <button
              type="button"
              class="btn btn-light btn-sm js-delete-session"
              data-index="{{ $item['index'] }}"
              data-date="{{ $dateYmd }}"
              data-time="{{ $timeHi }}"
              data-url="{{ route('profile-date.delete') }}"
              title="Αφαίρεση προσωρινής επιλογής"
            >
              <i class="fa fa-trash-o text-danger"></i>
            </button>
          </div>

          {{-- Hidden inputs for the “Save All” form --}}
          <input type="hidden" name="selected_datetimes[{{ $i }}][date]" value="{{ $dateYmd }}">
          <input type="hidden" name="selected_datetimes[{{ $i }}][time]" value="{{ $timeHi }}">
        </li>
      @empty
        <li class="list-group-item text-muted">
          Δεν υπάρχουν προσωρινές επιλογές.
        </li>
      @endforelse
    </ul>

    <div class="submit-session-list">
      <button id="save-all-btn"
              type="submit"
              class="button-shandow-st p-3 mb-5 rounded btn btn-success"
              @if ($pendingCount === 0) disabled @endif>
        <i class="fa fa-save"></i> Καταχώρηση Νέων Διαθεσιμοτήτων
      </button>
    </div>
  </form>
</div>
 @endif