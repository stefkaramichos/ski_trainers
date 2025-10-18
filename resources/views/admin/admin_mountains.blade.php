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
                    <span class="mountain-name" id="mountainName{{$m->id}}">{{ $m->mountain_name }}</span>
                </div>

                <div class="col-3 col-md-4 d-flex align-items-center justify-content-end">
                    <div class="dropdown">
                        <div class="dropdown-toggle" type="button" id="dropdownMenuButton{{$m->id}}" data-bs-toggle="dropdown" aria-expanded="false" style="cursor:pointer;">
                            Επιλογές
                        </div>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton{{$m->id}}">
                            <li>
                                <a class="dropdown-item" href="#" onclick="openRename({{ $m->id }}, '{{ addslashes($m->mountain_name) }}')">
                                    Μετονομασία
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
                <div class="text-end">
                    <button type="button" class="btn btn-secondary close-pop-mountains">Άκυρο</button>
                    <button type="submit" class="btn btn-primary">Αποθήκευση</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Popup: Μετονομασία --}}
    <div class="pop-up-rename-mountain" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.35); z-index:9999;">
        <div class="bg-white p-4 rounded shadow col-11 col-md-6 col-lg-4 mx-auto mt-5 position-relative">
            <button type="button" class="btn-close close-pop-rename" style="position:absolute; top:10px; right:10px;"></button>
            <h5 class="mb-3">Μετονομασία Βουνού</h5>
            <input type="hidden" id="renameMountainId">
            <div class="mb-3">
                <label class="form-label">Νέο Όνομα</label>
                <input type="text" id="renameMountainName" class="form-control" maxlength="255">
            </div>
            <div class="text-end">
                <button type="button" class="btn btn-secondary close-pop-rename">Άκυρο</button>
                <button type="button" class="btn btn-primary" onclick="submitRename()">Αποθήκευση</button>
            </div>
        </div>
    </div>

</div>
@endsection


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function openRename(id, currentName){
        $('#renameMountainId').val(id);
        $('#renameMountainName').val(currentName);
        $('.pop-up-rename-mountain').fadeIn();
    }

    function submitRename(){
        const id = $('#renameMountainId').val();
        const name = $('#renameMountainName').val().trim();

        if(!name){ alert('Συμπληρώστε όνομα.'); return; }

        $.ajax({
            url: '{{ route('updateMountain') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                mountain_id: id,
                mountain_name: name
            },
            success: function(res){
                if(res.success){
                    $('#mountainName'+id).text(res.name);
                    $('.pop-up-rename-mountain').fadeOut();
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

        $('.close-pop-rename').on('click', function(){ $('.pop-up-rename-mountain').fadeOut(); });
    });
</script>

