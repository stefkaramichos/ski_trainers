@if(!empty($currentSelectedDate))
  <h3 class="mb-3 gray-color font-weight-bold text-left">
    <b>{{ \Carbon\Carbon::parse($currentSelectedDate)->translatedFormat('l d-m-Y') }}</b>
  </h3>
  <h5 class="gray-color mb-3 availability-text">Διαθεσιμότητα</h5>
@endif

<ul class="list-group hour-list">
  @forelse ($dbDatetimesForSelectedDate as $item)
    <li id="saved-item-{{ $item->id }}" 
        class="list-group-item font-s-16 d-flex justify-content-between align-items-center">
      {{ \Illuminate\Support\Str::of($item->selected_time)->substr(0,5) }}
      <div class="d-flex gap-2">
        <button
          type="button"
          class="btn btn-light btn-sm js-delete-saved"
          data-id="{{ $item->id }}"
          data-date="{{ $item->selected_date }}"
          data-time="{{ \Illuminate\Support\Str::of($item->selected_time)->substr(0,5) }}"
          data-url="{{ route('profile-date.deleteSaved', $user) }}"
          title="Διαγραφή αποθηκευμένης ώρας"
        >
          <i class="fa fa-trash-o text-danger"></i>
        </button>
      </div>
    </li>
  @empty
    <li class="list-group-item gray-color text-muted">
      Δεν υπάρχουν αποθηκευμένες ώρες για αυτή την ημερομηνία.
    </li>
  @endforelse
</ul>
