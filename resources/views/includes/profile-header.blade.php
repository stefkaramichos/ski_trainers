<div class="row mb-4">
    @if ($user->image)
        <div class="img-profile">
            <img src="{{ asset('storage/' . $user->image) }}" alt="Profile Image" >
        </div>
    @endif
        <div class="name-profile col-8 d-flex align-items-center">
            <h3>{{ $user->name }}</h3>
        </div>
</div>