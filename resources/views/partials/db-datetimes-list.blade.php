{{-- partial: resources/views/partials/db-datetimes-list.blade.php --}}
<ul class="list-group hour-list">
  @if(!empty($currentSelectedDate))
    <h3 class="mb-3 text-left">
        <strong>{{ \Carbon\Carbon::parse($currentSelectedDate)->format('d-m-Y') }}</strong>
    </h3>
  @endif
  @forelse ($dbDatetimesForSelectedDate as $item)
    <li id="saved-item-{{ $item->id }}" class="list-group-item d-flex justify-content-between align-items-center bg-success text-white">
      Ώρα: {{ \Illuminate\Support\Str::of($item->selected_time)->substr(0,5) }}

      <div class="d-flex gap-2">
        {{-- AJAX delete saved (DB) --}}
        <button
            type="button"
            class="btn btn-light btn-sm js-delete-saved"
            data-id="{{ $item->id }}"
            data-date="{{ $item->selected_date }}"
            data-time="{{ \Illuminate\Support\Str::of($item->selected_time)->substr(0,5) }}"
            data-url="{{ route('profile-date.deleteSaved', $user) }}"   {{-- ✅ pass $user --}}
            title="Διαγραφή αποθηκευμένης ώρας"
        >
            <i class="fa fa-trash-o text-danger"></i>
        </button>
      </div>
    </li>
  @empty
    <li class="list-group-item text-muted">
      Δεν υπάρχουν αποθηκευμένες ώρες για αυτή την ημερομηνία.
    </li>
  @endforelse
</ul>
