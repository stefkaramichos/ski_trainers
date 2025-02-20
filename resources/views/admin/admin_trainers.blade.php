@extends('layouts.app')

@section('content')
<div class="p-2 p-md-3 p-lg-5">
    <div class="card admin-card admin-trainers col-12 col-md-10 col-lg-8 mx-auto">
      <div class="card-header row p-3">
        <div class="col-6">
            ΠΡΟΠΟΝΗΤΕΣ
        </div>
        <div class="col-6 text-end">
            <i class="bi bi-plus add-trainer fs-1 border border-dark p-1 " style="border-radius: 10%;"></i>
        </div>
      </div>
      <div class="card-body admin-card-body">
        @foreach ($users as $user)
            <div class="admin-user admin-user-{{$user->id}} row mt-3 p-2">
                <div class="img-profile col-3">
                    <img  src="{{ asset('storage/' . $user->image) }}" alt="Profile Image" >
                </div>
                <div class="col-7 col-md-4 d-flex align-items-center">
                    <a href=" {{ route('profile', $user->id) }} ">{{ $user->name }}</a>
                </div>
                <div class="col-md-7 col-3 d-flex align-items-center justify-content-end">
                    <!-- Dropdown -->
                    <div class="dropdown">
                        <div class="dropdown-toggle" type="button" id="dropdownMenuButton{{ $user->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                            @if ($user->status == 'A')
                                Ενεργό
                            @else 
                                Απενεργοποιημένο
                            @endif
                        </div>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton{{ $user->id }}">
                            <li>
                                <a class="dropdown-item @if($user->status == 'A') disabled @endif" 
                                   href="#" id="activeLink{{ $user->id }}" 
                                   onclick="updateStatus({{ $user->id }}, 'A')" 
                                   @if($user->status == 'A') style="pointer-events: none;" @endif>
                                   Ενεργό
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item @if($user->status == 'D') disabled @endif" 
                                   href="#" id="disabledLink{{ $user->id }}" 
                                   onclick="updateStatus({{ $user->id }}, 'D')" 
                                   @if($user->status == 'D') style="pointer-events: none;" @endif>
                                   Απενεργοποιημένο
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" 
                                   href="#" 
                                   onclick="deleteUser({{ $user->id }})">
                                   Διαγραφή
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    
                </div>
            </div> 
            <hr>
        @endforeach
      </div>
    </div>
    @include('admin.includes.popup-trainers')

</div>
@endsection 
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                    
                    // Update the text of the dropdown button
                    var dropdownButton = $('#dropdownMenuButton' + userId); // Target the specific button by user ID
                    
                    // Update the text of the dropdown button based on status
                    if (status === 'A') {
                        dropdownButton.text('Ενεργό'); // Update to Ενεργό if status is 'A'
                    } else if (status === 'D') {
                        dropdownButton.text('Απενεργοποιημένο'); // Update to Απενεργοποιημένο if status is 'D'
                    }

                    // Disable or enable the appropriate <a> tags in the dropdown
                    var activeLink = $('#activeLink' + userId); // The link for 'Ενεργό'
                    var disabledLink = $('#disabledLink' + userId); // The link for 'Απενεργοποιημένο'

                    if (status === 'A') {
                        activeLink.css("pointer-events", "none"); // Disable the "Ενεργό" link
                        activeLink.addClass('disabled'); // Optional, add disabled class for styling

                        disabledLink.css("pointer-events", "auto"); // Enable the "Απενεργοποιημένο" link
                        disabledLink.removeClass('disabled');
                    } else if (status === 'D') {
                        activeLink.css("pointer-events", "auto"); // Enable the "Ενεργό" link
                        activeLink.removeClass('disabled');

                        disabledLink.css("pointer-events", "none"); // Disable the "Απενεργοποιημένο" link
                        disabledLink.addClass('disabled'); // Optional, add disabled class for styling
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
            return; // Cancel deletion if user clicks "No"
        } 

        $.ajax({
            url: '{{ route('deleteUser') }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    alert("Ο χρήστης διαγράφηκε επιτυχώς!");
                    $(".admin-user-" + userId).remove();
                } else {
                    alert("Σφάλμα κατά τη διαγραφή του χρήστη.");
                }
            },
            error: function(xhr, status, error) {
                console.log(xhr.responseText); // Debugging - See server error response
                alert("Παρουσιάστηκε σφάλμα. Προσπαθήστε ξανά.");
            }
        });
 
    }
</script>
<script>
    $('document').ready(function(){
        $('.add-trainer').click(function(){
            $('.pop-up-new-trainer').fadeIn();
        })
        $('.close-pop-trainers').click(function(){
            $('.pop-up-new-trainer').fadeOut();
        })
    })
</script>
