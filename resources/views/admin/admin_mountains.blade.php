@extends('layouts.app')

@section('content')
<div class="p-2 p-md-3 p-lg-5">
    <div class="card admin-card admin-trainers col-12 col-md-10 col-lg-8 mx-auto">
      <div class="card-header row p-3">
        <div class="col-6">
            ΒΟΥΝΑ
        </div>
        <div class="col-6 text-end">
            <i class="bi bi-plus add-mountain fs-1 border border-dark p-1" style="border-radius: 10%; cursor:pointer;"></i>
        </div>
      </div>

      <div class="card-body admin-card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @forelse ($mountains as $m)
            <div class="admin-mountain admin-mountain-{{$m->id}} row mt-3 p-2 align-items-center">
                <div class="col-9 col-md-8 d-flex align-items-center">
                    <div>
                        <div class="fw-bold">
                            <span class="mountain-name" id="mountainName{{$m->id}}">{{ $m->mountain_name }}</span>
                        </div>
                        <small class="text-muted d-block">
                            Lat: <span id="mountainLat{{$m->id}}">{{ $m->latitude }}</span>,
                            Lng: <span id="mountainLng{{$m->id}}">{{ $m->longitude }}</span>
                        </small>
                    </div>
                </div>

                <div class="col-3 col-md-4 d-flex align-items-center justify-content-end">
                    <div class="dropdown">
                        <div class="dropdown-toggle" type="button" id="dropdownMenuButton{{$m->id}}" data-bs-toggle="dropdown" aria-expanded="false" style="cursor:pointer;">
                            Επιλογές
                        </div>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton{{$m->id}}">
                            <li>
                                <a class="dropdown-item" href="#"
                                   onclick="openEdit({{ $m->id }}, '{{ addslashes($m->mountain_name) }}', '{{ $m->latitude }}', '{{ $m->longitude }}')">
                                    Επεξεργασία
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="#" onclick="deleteMountain({{ $m->id }})">
                                    Διαγραφή
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <hr>
        @empty
            <p class="text-muted">Δεν υπάρχουν βουνά ακόμη.</p>
        @endforelse
      </div>
    </div>

    {{-- Popup: Νέο Βουνό --}}
    <div class="pop-up-new-mountain" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.35); z-index:9999;">
        <div class="bg-white p-4 rounded shadow col-11 col-md-6 col-lg-4 mx-auto mt-5 position-relative">
            <button type="button" class="btn-close close-pop-mountains" style="position:absolute; top:10px; right:10px;"></button>
            <h5 class="mb-3">Προσθήκη Βουνού</h5>
            <form action="{{ route('admin.mountains') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Όνομα</label>
                    <input type="text" name="mountain_name" class="form-control" required maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label">Γεωγραφικό Πλάτος (Latitude)</label>
                    <input type="number" name="latitude" class="form-control" step="any" min="-90" max="90" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Γεωγραφικό Μήκος (Longitude)</label>
                    <input type="number" name="longitude" class="form-control" step="any" min="-180" max="180" required>
                </div>
                <div class="text-end">
                    <button type="button" class="btn btn-secondary close-pop-mountains">Άκυρο</button>
                    <button type="submit" class="btn btn-primary">Αποθήκευση</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Popup: Επεξεργασία (Όνομα + Συντεταγμένες) --}}
    <div class="pop-up-edit-mountain" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.35); z-index:9999;">
        <div class="bg-white p-4 rounded shadow col-11 col-md-6 col-lg-4 mx-auto mt-5 position-relative">
            <button type="button" class="btn-close close-pop-edit" style="position:absolute; top:10px; right:10px;"></button>
            <h5 class="mb-3">Επεξεργασία Βουνού</h5>
            <input type="hidden" id="editMountainId">
            <div class="mb-3">
                <label class="form-label">Όνομα</label>
                <input type="text" id="editMountainName" class="form-control" maxlength="255" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Γεωγραφικό Πλάτος (Latitude)</label>
                <input type="number" id="editMountainLat" class="form-control" step="any" min="-90" max="90" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Γεωγραφικό Μήκος (Longitude)</label>
                <input type="number" id="editMountainLng" class="form-control" step="any" min="-180" max="180" required>
            </div>
            <div class="text-end">
                <button type="button" class="btn btn-secondary close-pop-edit">Άκυρο</button>
                <button type="button" class="btn btn-primary" onclick="submitEdit()">Αποθήκευση</button>
            </div>
        </div>
    </div>

</div>
@endsection


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function openEdit(id, currentName, lat, lng){
        $('#editMountainId').val(id);
        $('#editMountainName').val(currentName);
        $('#editMountainLat').val(lat);
        $('#editMountainLng').val(lng);
        $('.pop-up-edit-mountain').fadeIn();
    }

    function submitEdit(){
        const id   = $('#editMountainId').val();
        const name = $('#editMountainName').val().trim();
        const lat  = $('#editMountainLat').val().trim();
        const lng  = $('#editMountainLng').val().trim();

        if(!name){ alert('Συμπληρώστε όνομα.'); return; }
        if(lat === '' || isNaN(lat) || lat < -90 || lat > 90){ alert('Μη έγκυρο Latitude.'); return; }
        if(lng === '' || isNaN(lng) || lng < -180 || lng > 180){ alert('Μη έγκυρο Longitude.'); return; }

        $.ajax({
            url: '{{ route('updateMountain') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                mountain_id: id,
                mountain_name: name,
                latitude: lat,
                longitude: lng
            },
            success: function(res){
                if(res.success){
                    $('#mountainName'+id).text(res.name);
                    $('#mountainLat'+id).text(res.lat);
                    $('#mountainLng'+id).text(res.lng);
                    $('.pop-up-edit-mountain').fadeOut();
                } else {
                    alert('Σφάλμα ενημέρωσης.');
                }
            },
            error: function(xhr){
                if(xhr.responseJSON && xhr.responseJSON.errors){
                    const firstErr = Object.values(xhr.responseJSON.errors)[0][0];
                    alert(firstErr);
                } else {
                    alert('Παρουσιάστηκε σφάλμα. Προσπαθήστε ξανά.');
                }
            }
        });
    }

    function deleteMountain(id){
        if(!confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτό το βουνό;')) return;

        $.ajax({
            url: '{{ route('deleteMountain') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                mountain_id: id
            },
            success: function(res){
                if(res.success){
                    $('.admin-mountain-'+id).remove();
                }else{
                    alert('Σφάλμα κατά τη διαγραφή.');
                }
            },
            error: function(){
                alert('Παρουσιάστηκε σφάλμα. Προσπαθήστε ξανά.');
            }
        });
    }

    $(function(){
        $('.add-mountain').on('click', function(){ $('.pop-up-new-mountain').fadeIn(); });
        $('.close-pop-mountains').on('click', function(){ $('.pop-up-new-mountain').fadeOut(); });

        $('.close-pop-edit').on('click', function(){ $('.pop-up-edit-mountain').fadeOut(); });
    });
</script>
