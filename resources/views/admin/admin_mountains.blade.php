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
            <div class="admin-mountain admin-mountain-{{$m->id}} row mt-3 p-2 align-items-start">
                <div class="col-9 col-md-8">
                    <div class="fw-bold">
                        <span class="mountain-name" id="mountainName{{$m->id}}">
                            <a href="{{ route('mountain', $m->id) }}">{{ $m->mountain_name }}</a>
                        </span>
                    </div>

                    <small class="text-muted d-block">
                        Lat: <span id="mountainLat{{$m->id}}">{{ $m->latitude }}</span>,
                        Lng: <span id="mountainLng{{$m->id}}">{{ $m->longitude }}</span>
                    </small>

                    <div class="small text-secondary mt-1" id="mountainDesc{{$m->id}}">
                        {{ $m->description ? \Illuminate\Support\Str::limit($m->description, 120) : '' }}
                    </div>

                    <div class="d-flex gap-2 mt-2 flex-wrap">
                        {{-- Image 1 preview / placeholder --}}
                        @if($m->image_1)
                            <img id="mountainImg1{{$m->id}}"
                                 src="{{ asset('storage/'.$m->image_1) }}"
                                 alt="image 1"
                                 style="width:60px; height:60px; object-fit:cover; border-radius:6px; border:1px solid #ccc;">
                        @else
                            <div id="mountainImg1{{$m->id}}"
                                 style="width:60px; height:60px; background:#efefef; border:1px solid #ccc; border-radius:6px; font-size:10px; display:flex; align-items:center; justify-content:center;">
                                No img
                            </div>
                        @endif

                        {{-- Image 2 preview / placeholder --}}
                        @if($m->image_2)
                            <img id="mountainImg2{{$m->id}}"
                                 src="{{ asset('storage/'.$m->image_2) }}"
                                 alt="image 2"
                                 style="width:60px; height:60px; object-fit:cover; border-radius:6px; border:1px solid #ccc;">
                        @else
                            <div id="mountainImg2{{$m->id}}"
                                 style="width:60px; height:60px; background:#efefef; border:1px solid #ccc; border-radius:6px; font-size:10px; display:flex; align-items:center; justify-content:center;">
                                No img
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-3 col-md-4 d-flex align-items-start justify-content-end">
                    <div class="dropdown">
                        <div class="dropdown-toggle" type="button" id="dropdownMenuButton{{$m->id}}" data-bs-toggle="dropdown" aria-expanded="false" style="cursor:pointer;">
                            Επιλογές
                        </div>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton{{$m->id}}">
                            <li>
                                <a class="dropdown-item" href="#"
                                   onclick="openEdit(
                                       {{ $m->id }},
                                       '{{ addslashes($m->mountain_name) }}',
                                       '{{ $m->latitude }}',
                                       '{{ $m->longitude }}',
                                       `{{ addslashes($m->description ?? '') }}`,
                                       '{{ $m->image_1 ? asset('storage/'.$m->image_1) : '' }}',
                                       '{{ $m->image_2 ? asset('storage/'.$m->image_2) : '' }}'
                                   )">
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

            <form action="{{ route('admin.mountains') }}" method="POST" enctype="multipart/form-data">
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

                <div class="mb-3">
                    <label class="form-label">Περιγραφή</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Λίγα λόγια για το βουνό..."></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Εικόνα 1</label>
                    <input type="file" name="image_1" class="form-control" accept="image/*">
                </div>

                <div class="mb-3">
                    <label class="form-label">Εικόνα 2</label>
                    <input type="file" name="image_2" class="form-control" accept="image/*">
                </div>

                <div class="text-end">
                    <button type="button" class="btn btn-secondary close-pop-mountains">Άκυρο</button>
                    <button type="submit" class="btn btn-primary">Αποθήκευση</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Popup: Επεξεργασία --}}
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

            <div class="mb-3">
                <label class="form-label">Περιγραφή</label>
                <textarea id="editMountainDesc" class="form-control" rows="3" placeholder="Λίγα λόγια για το βουνό..."></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label d-block">Εικόνα 1 (προεπισκόπηση)</label>
                <img id="editPreviewImg1"
                     src=""
                     alt="preview 1"
                     style="max-width:100px; max-height:100px; object-fit:cover; border:1px solid #ccc; border-radius:6px; display:none;">
                <input type="file" id="editImage1" class="form-control mt-2" accept="image/*">
            </div>

            <div class="mb-3">
                <label class="form-label d-block">Εικόνα 2 (προεπισκόπηση)</label>
                <img id="editPreviewImg2"
                     src=""
                     alt="preview 2"
                     style="max-width:100px; max-height:100px; object-fit:cover; border:1px solid #ccc; border-radius:6px; display:none;">
                <input type="file" id="editImage2" class="form-control mt-2" accept="image/*">
            </div>

            <div class="text-end">
                <button type="button" class="btn btn-secondary close-pop-edit">Άκυρο</button>
                <button type="button" class="btn btn-primary" onclick="submitEdit()">Αποθήκευση</button>
            </div>
        </div>
    </div>

</div>
@endsection


{{-- JS --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Open edit modal and fill fields
    function openEdit(id, currentName, lat, lng, desc, img1Url, img2Url){
        $('#editMountainId').val(id);
        $('#editMountainName').val(currentName);
        $('#editMountainLat').val(lat);
        $('#editMountainLng').val(lng);
        $('#editMountainDesc').val(desc);

        if (img1Url) {
            $('#editPreviewImg1').attr('src', img1Url).show();
        } else {
            $('#editPreviewImg1').attr('src', '').hide();
        }

        if (img2Url) {
            $('#editPreviewImg2').attr('src', img2Url).show();
        } else {
            $('#editPreviewImg2').attr('src', '').hide();
        }

        // clear file inputs from previous edit
        $('#editImage1').val('');
        $('#editImage2').val('');

        $('.pop-up-edit-mountain').fadeIn();
    }

    // Submit edit via AJAX (with FormData so we can send images)
    function submitEdit(){
        const id   = $('#editMountainId').val();
        const name = $('#editMountainName').val().trim();
        const lat  = $('#editMountainLat').val().trim();
        const lng  = $('#editMountainLng').val().trim();
        const desc = $('#editMountainDesc').val().trim();

        if(!name){
            alert('Συμπληρώστε όνομα.');
            return;
        }

        if(lat === '' || isNaN(lat) || lat < -90 || lat > 90){
            alert('Μη έγκυρο Latitude.');
            return;
        }

        if(lng === '' || isNaN(lng) || lng < -180 || lng > 180){
            alert('Μη έγκυρο Longitude.');
            return;
        }

        const fd = new FormData();
        fd.append('_token', '{{ csrf_token() }}');
        fd.append('mountain_id', id);
        fd.append('mountain_name', name);
        fd.append('latitude', lat);
        fd.append('longitude', lng);
        fd.append('description', desc);

        if($('#editImage1')[0].files[0]){
            fd.append('image_1', $('#editImage1')[0].files[0]);
        }
        if($('#editImage2')[0].files[0]){
            fd.append('image_2', $('#editImage2')[0].files[0]);
        }

        $.ajax({
            url: '{{ route('updateMountain') }}',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res){
                if(res.success){
                    // Update name / coords
                    $('#mountainName'+id).text(res.name);
                    $('#mountainLat'+id).text(res.lat);
                    $('#mountainLng'+id).text(res.lng);

                    // Update description
                    $('#mountainDesc'+id).text(res.description ?? '');

                    // Update image 1
                    if(res.image_1_url){
                        let img1El = $('#mountainImg1'+id);
                        if(img1El.is('img')){
                            img1El.attr('src', res.image_1_url);
                        } else {
                            img1El.replaceWith(
                                `<img id="mountainImg1${id}" src="${res.image_1_url}" alt="image 1" style="width:60px; height:60px; object-fit:cover; border-radius:6px; border:1px solid #ccc;">`
                            );
                        }
                    }

                    // Update image 2
                    if(res.image_2_url){
                        let img2El = $('#mountainImg2'+id);
                        if(img2El.is('img')){
                            img2El.attr('src', res.image_2_url);
                        } else {
                            img2El.replaceWith(
                                `<img id="mountainImg2${id}" src="${res.image_2_url}" alt="image 2" style="width:60px; height:60px; object-fit:cover; border-radius:6px; border:1px solid #ccc;">`
                            );
                        }
                    }

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

    // Delete mountain
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

    // Open/close popups
    $(function(){
        $('.add-mountain').on('click', function(){
            $('.pop-up-new-mountain').fadeIn();
        });

        $('.close-pop-mountains').on('click', function(){
            $('.pop-up-new-mountain').fadeOut();
        });

        $('.close-pop-edit').on('click', function(){
            $('.pop-up-edit-mountain').fadeOut();
        });
    });
</script>
