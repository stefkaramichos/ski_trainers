<form method="POST" action="{{ route('profile', $user->id) }}" enctype="multipart/form-data">
    @csrf

    <div class="row mb-3">
        <label for="name" class="col-md-4 col-form-label text-md-end">{{ __('auth.name') }}</label>

        <div class="col-md-6">
            <input id="name" value="{{ $user->name }}" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>

            @error('name')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>

    <div class="row mb-3">
        <label for="mountains" class="col-md-4 col-form-label text-md-end">{{ __('auth.mountains') }}</label>
    
        <div class="col-md-6">
            <select id="mountains" class="form-control @error('mountains') is-invalid @enderror" name="mountains[]" >
                @foreach ($mountains as $mountain)
                    <option value="{{ $mountain->id }}" {{ in_array($mountain->id, $userMountains) ? 'selected' : '' }}>
                        {{ $mountain->mountain_name }}
                    </option>
                @endforeach
            </select>
    
            @error('mountains')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>
    

    <div class="row mb-3">
        <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('auth.Email_Address') }}</label>

        <div class="col-md-6">
            <input id="email" value="{{ $user->email }}"  type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">

            @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>

    <div class="row mb-3">
        <label for="description" class="col-md-4 col-form-label text-md-end">{{ __('auth.description') }}</label>
    
        <div class="col-md-6">
            <textarea id="description" class="form-control @error('description') is-invalid @enderror" name="description" required>{{ $user->description }}</textarea>
    
            @error('description')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>
    
    <div class="row mb-3">
        <label for="image" class="col-md-4 col-form-label text-md-end">{{ __('auth.profile_image') }}</label>
    
        <div class="col-md-6">
            <input id="image" type="file" class="form-control @error('image') is-invalid @enderror" name="image">
    
            @error('image')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>
    <div class="row mb-0">
        <div class="col-md-6 offset-md-4">
            <button type="submit" class="btn btn-primary">
                {{ __('auth.allagi_stoixion') }}
            </button>
        </div>
    </div>
</form>