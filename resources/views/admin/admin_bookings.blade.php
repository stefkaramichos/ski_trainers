@extends('layouts.app')

@section('content')
<div class="p-2 p-md-3 p-lg-5">
    <div class="card admin-card col-12 col-xl-11 mx-auto">
        <div class="card-header p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <h5 class="m-0">Ραντεβού</h5>

                <form class="d-flex flex-wrap gap-2" action="{{ route('admin.bookings') }}" method="GET">
                    <input class="form-control form-control-sm" type="date" name="date_from" value="{{ $dateFrom }}">
                    <input class="form-control form-control-sm" type="date" name="date_to" value="{{ $dateTo }}">

                    <select class="form-select form-select-sm" name="status">
                        <option value="">Κατάσταση: Όλες</option>
                        <option value="pending" {{ $status==='pending' ? 'selected' : '' }}>pending</option>
                        <option value="claimed" {{ $status==='claimed' ? 'selected' : '' }}>claimed</option>
                    </select>

                    <select class="form-select form-select-sm" name="mountain_id">
                        <option value="">Βουνό: Όλα</option>
                        @foreach($mountains as $m)
                            <option value="{{ $m->id }}" {{ (string)$mountainId===(string)$m->id ? 'selected' : '' }}>
                                {{ $m->mountain_name }}
                            </option>
                        @endforeach
                    </select>

                    <select class="form-select form-select-sm" name="instructor_id">
                        <option value="">Εκπαιδευτής: Όλοι</option>
                        @foreach($instructors as $ins)
                            <option value="{{ $ins->id }}" {{ (string)$instructorId===(string)$ins->id ? 'selected' : '' }}>
                                {{ $ins->name }}
                            </option>
                        @endforeach
                    </select>

                    <input class="form-control form-control-sm" type="text" name="q" placeholder="Αναζήτηση πελάτη/email/τηλ..." value="{{ $q }}">
                    <button class="btn btn-sm btn-primary">Φίλτρα</button>
                </form>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ημ/νία</th>
                            <th>Ώρα</th>
                            <th>Πελάτης</th>
                            <th>Άτομα</th>
                            <th>Επίπεδο</th>
                            <th>Βουνό</th>
                            <th>Εκπαιδευτής</th>
                            <th>Κατάσταση</th>
                            <th>Σημειώσεις</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $b)
                        <tr id="booking-row-{{ $b->id }}">
                            <td>{{ $b->id }}</td>
                            <td>{{ \Carbon\Carbon::parse($b->selected_date)->format('d/m/Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($b->selected_time)->format('H:i') }}</td>
                            <td>
                                <div class="fw-semibold">{{ $b->customer_name }}</div>
                                <div class="text-muted small">{{ $b->customer_email }} @if($b->customer_phone) • {{ $b->customer_phone }} @endif</div>
                            </td>
                            <td>{{ $b->people_count }}</td>
                            <td>{{ $b->level ?? '-' }}</td>
                            <td>{{ $b->mountain->mountain_name ?? '-' }}</td>
                            <td style="min-width: 220px;">
                                <select class="form-select form-select-sm assign-instructor"
                                        data-booking="{{ $b->id }}">
                                    <option value="">— Χωρίς —</option>
                                    @foreach($instructors as $ins)
                                        <option value="{{ $ins->id }}"
                                            {{ (int)optional($b->instructor)->id === (int)$ins->id ? 'selected' : '' }}>
                                            {{ $ins->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                            data-bs-toggle="dropdown" id="statusBtn{{ $b->id }}">
                                        {{ $b->status }}
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item set-status" href="#" data-booking="{{ $b->id }}" data-status="pending">pending</a></li>
                                        <li><a class="dropdown-item set-status" href="#" data-booking="{{ $b->id }}" data-status="claimed">claimed</a></li>
                                    </ul>
                                </div>
                            </td>
                            <td class="text-truncate" style="max-width:220px;" title="{{ $b->notes }}">{{ $b->notes }}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-danger delete-booking" data-booking="{{ $b->id }}">Διαγραφή</button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="11" class="text-center text-muted">Δεν βρέθηκαν ραντεβού.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $bookings->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Change status
    document.querySelectorAll('.set-status').forEach(el => {
        el.addEventListener('click', function(e){
            e.preventDefault();
            const id = this.dataset.booking, status = this.dataset.status;
            fetch('{{ route('admin.bookings.status') }}', {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN': csrf,'Accept':'application/json'},
                body: JSON.stringify({ booking_id: id, status })
            }).then(r => r.json()).then(res => {
                if(res.success){ document.getElementById('statusBtn'+id).innerText = res.status; }
                else{ alert('Σφάλμα ενημέρωσης κατάστασης'); }
            }).catch(()=> alert('Σφάλμα. Προσπαθήστε ξανά.'));
        });
    });

    // Assign / Unassign instructor
    document.querySelectorAll('.assign-instructor').forEach(sel => {
        sel.addEventListener('change', function(){
            const id = this.dataset.booking;
            const instructor_id = this.value || null;

            fetch('{{ route('admin.bookings.assign') }}', {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN': csrf,'Accept':'application/json'},
                body: JSON.stringify({ booking_id: id, instructor_id })
            }).then(async r => {
                const data = await r.json();
                if(r.ok && data.success){
                    document.getElementById('statusBtn'+id).innerText = data.status;
                } else {
                    alert(data.message || 'Σφάλμα ανάθεσης εκπαιδευτή.');
                    // Optional: revert select if failed
                    this.selectedIndex = 0;
                }
            }).catch(()=> alert('Σφάλμα. Προσπαθήστε ξανά.'));
        });
    });

    // Delete booking
    document.querySelectorAll('.delete-booking').forEach(btn => {
        btn.addEventListener('click', function(){
            if(!confirm('Σίγουρα θέλετε να διαγράψετε το ραντεβού;')) return;
            const id = this.dataset.booking;

            fetch('{{ route('admin.bookings.delete') }}', {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN': csrf,'Accept':'application/json'},
                body: JSON.stringify({ booking_id: id })
            }).then(r => r.json()).then(res => {
                if(res.success){ document.getElementById('booking-row-'+id)?.remove(); }
                else{ alert('Σφάλμα διαγραφής.'); }
            }).catch(()=> alert('Σφάλμα. Προσπαθήστε ξανά.'));
        });
    });
})();
</script>
@endpush
