@extends('layouts.app')

@section('content')
<div class="p-2 p-md-3 p-lg-5">
    <div class="card admin-card admin-trainers col-12 col-xl-10 mx-auto">
      <div class="card-header row p-3 align-items-center">
        <div class="col-6 d-flex gap-2 align-items-center">
            <span class="fw-bold">ΠΡΟΠΟΝΗΤΕΣ</span>
            <span class="badge bg-secondary">{{ $users->count() }}</span>
        </div>
        <div class="col-6 text-end">
            <i class="bi bi-plus add-trainer fs-1 border border-dark p-1" style="border-radius: 10%; cursor:pointer"></i>
        </div>
      </div>

      <div class="card-body admin-card-body">
        @foreach ($users as $user)
            @php
                $today = \Carbon\Carbon::today()->toDateString();

                $upcoming = $user->bookings
                    ->where('selected_date', '>=', $today)
                    ->sortBy('selected_date')
                    ->sortBy('selected_time')
                    ->first();

                $totalBookings = $user->bookings->count();
                $openTickets = $user->tickets->whereIn('status', ['open','pending'])->count();

                // pending claims = claimed_at NULL + invalidated_at NULL + booking date >= today
                $pendingClaimsCollection = $user->bookingClaims
                    ->filter(function ($claim) use ($today) {
                        if ($claim->claimed_at !== null) return false;
                        if ($claim->invalidated_at !== null) return false;
                        if (!$claim->booking) return false;
                        return $claim->booking->selected_date >= $today;
                    });

                $pendingClaimsCount = $pendingClaimsCollection->count();

                // HTML που θα δείχνει το popover (δεν το βάζουμε ως data-bs-content!)
                $claimsHtml = '';
                $i = 0;
                foreach ($pendingClaimsCollection as $claim) {
                    $i++;
                    $b = $claim->booking;
                    $claimsHtml .= '<div class="mb-1" style="border-bottom:1px solid #eee; padding-bottom:2px;'.($i==1 ? 'background:#fff4c4;' : '').'">';
                    $claimsHtml .= '<strong>#' . e($b->id) . '</strong> — ';
                    $claimsHtml .= e(\Carbon\Carbon::parse($b->selected_date)->format('d/m/Y'));
                    $claimsHtml .= ' ';
                    $claimsHtml .= e(\Carbon\Carbon::parse($b->selected_time)->format('H:i'));
                    if ($b->mountain) {
                        $claimsHtml .= ' — ' . e($b->mountain->mountain_name);
                    }
                    $claimsHtml .= '</div>';
                }

                if ($claimsHtml === '') {
                    $claimsHtml = '<em>Καμία εκκρεμότητα</em>';
                }
            @endphp

            <div class="admin-user admin-user-{{ $user->id }} row g-3 mt-3 p-3 border rounded-3 bg-light">
                {{-- avatar --}}
                <div class="col-12 col-sm-3 col-md-2 d-flex justify-content-center align-items-start">
                    <div class="img-profile" style="width:110px; height:110px; overflow:hidden; border-radius:12px; background:#fff">
                        @if($user->image)
                            <img src="{{ asset('storage/' . $user->image) }}" alt="Profile Image" class="w-100 h-100 object-fit-cover">
                        @else
                            <div class="w-100 h-100 d-flex justify-content-center align-items-center bg-secondary text-white">
                                {{ mb_substr($user->name,0,1) }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- main info --}}
                <div class="col-12 col-sm-9 col-md-4 d-flex flex-column justify-content-between gap-1">
                    <div>
                        <a href="{{ route('profile', $user->id) }}" class="fw-bold text-decoration-none">{{ $user->name }}</a>
                        <div class="text-muted small">{{ $user->email }}</div>
                    </div>

                    <div class="mt-2">
                        <span class="text-muted small d-block">Περιγραφή</span>
                        <p class="mb-0 small">
                            {{ $user->description ?: '—' }}
                        </p>
                    </div>

                    <div class="mt-2 d-flex gap-2 flex-wrap">
                        <span class="badge bg-light text-dark border">ID: {{ $user->id }}</span>
                        <span class="badge bg-light text-dark border">Δημιουργία: {{ $user->created_at?->format('d/m/Y') }}</span>
                        <span class="badge bg-light text-dark border">Τελευταία ενημ.: {{ $user->updated_at?->format('d/m/Y') }}</span>
                    </div>
                </div>

                {{-- mountains + bookings --}}
                <div class="col-12 col-md-3">
                    <div class="mb-2">
                        <span class="text-muted small d-block mb-1">Βουνά / Κέντρα</span>
                        @if($user->mountains && $user->mountains->count())
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($user->mountains as $m)
                                    <span class="badge bg-primary-subtle text-primary border">{{ $m->mountain_name }}</span>
                                @endforeach
                            </div>
                        @else
                            <span class="small text-muted">Δεν έχει οριστεί βουνό</span>
                        @endif
                    </div>

                    <div class="mb-2">
                        <span class="text-muted small d-block mb-1">Κρατήσεις (instructor)</span>
                        <span class="badge bg-info text-dark">{{ $totalBookings }} συνολικά</span>
                    </div>

                    <div>
                        <span class="text-muted small d-block mb-1">Επόμενη κράτηση</span>
                        @if($upcoming)
                            <div class="small">
                                <strong>{{ \Carbon\Carbon::parse($upcoming->selected_date)->format('d/m/Y') }}</strong>
                                στις
                                <strong>{{ \Carbon\Carbon::parse($upcoming->selected_time)->format('H:i') }}</strong>
                                @if($upcoming->mountain)
                                    – {{ $upcoming->mountain->mountain_name }}
                                @endif
                                <div class="text-muted">
                                    Πελάτης: {{ $upcoming->customer_name }}
                                </div>
                            </div>
                        @else
                            <span class="small text-muted">Καμία προγραμματισμένη</span>
                        @endif
                    </div>
                </div>

                {{-- status + actions --}}
                <div class="col-12 col-md-3 d-flex flex-column align-items-md-end gap-2">
                    {{-- status dropdown --}}
                    <div class="dropdown">
                        <button class="btn btn-sm @if($user->status == 'A') btn-success @else btn-outline-secondary @endif dropdown-toggle"
                                type="button"
                                id="dropdownMenuButton{{ $user->id }}"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">
                            @if ($user->status == 'A')
                                Ενεργό
                            @else 
                                Ανενεργό
                            @endif
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton{{ $user->id }}">
                            <li>
                                <a class="dropdown-item @if($user->status == 'A') disabled @endif"
                                   href="#"
                                   id="activeLink{{ $user->id }}"
                                   onclick="updateStatus({{ $user->id }}, 'A')"
                                   @if($user->status == 'A') style="pointer-events: none;" @endif>
                                   Ενεργό
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item @if($user->status == 'D') disabled @endif"
                                   href="#"
                                   id="disabledLink{{ $user->id }}"
                                   onclick="updateStatus({{ $user->id }}, 'D')"
                                   @if($user->status == 'D') style="pointer-events: none;" @endif>
                                   Ανενεργό
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger"
                                   href="#"
                                   onclick="deleteUser({{ $user->id }})">
                                   Διαγραφή
                                </a>
                            </li>
                        </ul>
                    </div>

                    {{-- tickets --}}
                    <div>
                        <span class="text-muted small d-block">Tickets</span>
                        <span class="badge @if($openTickets) bg-danger @else bg-secondary @endif">
                            {{ $openTickets }} ανοικτά
                        </span>
                    </div>

                    {{-- booking claim requests (hover) --}}
                    <div>
                        <span class="text-muted small d-block">Αιτήματα κράτησης</span>
                        <span
                            class="badge @if($pendingClaimsCount) bg-warning text-dark @else bg-secondary @endif claim-popover"
                            data-bs-toggle="popover"
                            data-bs-trigger="hover focus"
                            data-bs-placement="left"
                            data-bs-html="true"
                            data-bs-title="Εκκρεμείς αιτήσεις"
                            data-claims='{!! $claimsHtml !!}'
                            style="cursor:pointer;"
                        >
                            {{ $pendingClaimsCount }} σε εκκρεμότητα
                        </span>
                    </div>

                    {{-- stripe info --}}
                    <div>
                        <span class="text-muted small d-block">Stripe</span>
                        @if($user->stripe_customer_id)
                            <span class="badge bg-success-subtle text-success border">Customer</span>
                        @else
                            <span class="badge bg-light text-muted border">—</span>
                        @endif

                        @if($user->stripe_subscription_id)
                            <span class="badge bg-success-subtle text-success border">Active sub</span>
                        @endif
                    </div>

                    {{-- profile --}}
                    <a href="{{ route('profile', $user->id) }}" class="btn btn-sm btn-outline-primary">
                        Προβολή προφίλ
                    </a>
                </div>
            </div> 
        @endforeach
      </div>
    </div>

    @include('admin.includes.popup-trainers')

</div>
@endsection

@push('scripts')

<script>
    function updateStatus(userId, status) {
        $.ajax({
            url: '{{ route('updateUserStatus') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                user_id: userId,
                status: status
            },
            success: function(response) {
                if(response.success) {
                    var dropdownButton = $('#dropdownMenuButton' + userId);

                    if (status === 'A') {
                        dropdownButton.text('Ενεργό').removeClass('btn-outline-secondary').addClass('btn-success');
                    } else if (status === 'D') {
                        dropdownButton.text('Ανενεργό').removeClass('btn-success').addClass('btn-outline-secondary');
                    }

                    var activeLink = $('#activeLink' + userId);
                    var disabledLink = $('#disabledLink' + userId);

                    if (status === 'A') {
                        activeLink.css("pointer-events", "none").addClass('disabled');
                        disabledLink.css("pointer-events", "auto").removeClass('disabled');
                    } else {
                        activeLink.css("pointer-events", "auto").removeClass('disabled');
                        disabledLink.css("pointer-events", "none").addClass('disabled');
                    }
                } else {
                    alert('Error updating status');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    }

    function deleteUser(userId) {
        if (!confirm("Είστε σίγουροι ότι θέλετε να διαγράψετε αυτόν τον χρήστη;")) {
            return;
        }

        $.ajax({
            url: '{{ route('deleteUser') }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    $(".admin-user-" + userId).remove();
                } else {
                    alert("Σφάλμα κατά τη διαγραφή του χρήστη.");
                }
            },
            error: function(xhr, status, error) {
                console.log(xhr.responseText);
                alert("Παρουσιάστηκε σφάλμα. Προσπαθήστε ξανά.");
            }
        });
    }

    $(function(){
        $('.add-trainer').click(function(){
            $('.pop-up-new-trainer').fadeIn();
        });
        $('.close-pop-trainers').click(function(){
            $('.pop-up-new-trainer').fadeOut();
        });

        // ενεργοποίηση Bootstrap popovers με dynamic content
        document.querySelectorAll('.claim-popover').forEach(function (el) {
            const claimsHtml = el.getAttribute('data-claims') || '<em>Καμία εκκρεμότητα</em>';
            new bootstrap.Popover(el, {
                html: true,
                placement: el.getAttribute('data-bs-placement') || 'left',
                trigger: el.getAttribute('data-bs-trigger') || 'hover focus',
                title: el.getAttribute('data-bs-title') || 'Εκκρεμείς αιτήσεις',
                content: claimsHtml
            });
        });
    });
</script>
@endpush
