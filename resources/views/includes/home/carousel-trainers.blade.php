<div id="trainersCarousel" class="carousel slide mt-5" data-bs-ride="carousel">
    <h2 class="container text-center">Η ΟΜΑΔΑ ΜΑΣ</h2>
    <div class="underline container"></div>

    <div class="carousel-inner">
        @foreach ($users->chunk(4) as $chunkIndex => $userChunk)
            <div class="carousel-item @if($chunkIndex === 0) active @endif">
                <div class="container">
                    <div class="row justify-content-center">
                        @foreach ($userChunk as $user)
                            <div class="col-md-3">
                                <div class="card m-3 p-2 trainer" style="width: 18rem;">
                                    @if ($user->image)
                                        <div class="img-profile mx-auto pt-4">
                                            <img src="{{ asset('storage/' . $user->image) }}" alt="Profile Image" class="img-fluid">
                                        </div>
                                    @endif
                                    <div class="card-body">
                                        <h5 class="card-title trainer-name mt-3">
                                            <a href="{{ route('profile', $user->id) }}">{{ $user->name }}</a>
                                        </h5>
                                        <p class="card-text">
                                            <img width="15" src="{{ asset('storage/skier.png') }}" alt="ski icon">&nbsp; 
                                            {{ $user->description }}
                                        </p>
                                    </div>
                                    <ul class="list-group list-group-flush">
                                        @foreach ($user->mountains as $mountain)
                                            <li class="list-group-item">
                                                <img width="15" class="img-ski-location" src="{{ asset('storage/location.jpg') }}" alt="ski location">&nbsp;{{ $mountain->mountain_name }}
                                            </li>
                                        @endforeach
                                    </ul>
                                    <div class="card-body">
                                        <a href="#" class="card-link">Card link</a>
                                        <a href="#" class="card-link">Another link</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#trainersCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#trainersCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>
