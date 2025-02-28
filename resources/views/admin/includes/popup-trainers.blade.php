<div class="pop-up-new-trainer">
    <div class="pop-up-new-trainer-in col-12 col-md-10 col-lg-9 mx-auto p-1 d-flex align-items-center justify-content-center">
        
        <div class="pop-up-new-trainer-box p-1 p-md-3 p-md-5 bg-light col-11 col-md-7">
            <h2 class="close-pop-trainers">X</h2>
            <div class="">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">Προσθέστε έναν προπονητή</div>
                
                                <div class="card-body">
                                    <form method="POST" action="{{ route('admin.trainers')}}" enctype="multipart/form-data">
                                        @csrf
                
                                        <div class="row mb-3">
                                            <label for="name" class="col-md-4 col-form-label text-md-end">{{ __('auth.name') }}</label>
                
                                            <div class="col-md-6">
                                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                
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
                                                        <option value="{{ $mountain->id }}">{{ $mountain->mountain_name }}</option>
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
                                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">
                
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
                                                <textarea id="description" class="form-control @error('description') is-invalid @enderror" name="description" required>{{ old('description') }}</textarea>
                                        
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
                                        
                                        <div class="row mb-3">
                                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>
                
                                            <div class="col-md-6">
                                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                
                                                @error('password')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                
                
                                        <div class="row mb-0">
                                            <div class="col-md-6 offset-md-4">
                                                <button type="submit" class="btn btn-primary">
                                                    {{ __('auth.register') }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>