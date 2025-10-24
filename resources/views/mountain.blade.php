@extends('layouts.app')

@section('content')

<div class="container py-4 py-md-5">

    {{-- HERO SECTION --}}
    @php
        $heroImage = $mountain->image_1
            ? asset('storage/'.$mountain->image_1)
            : ($mountain->image_2
                ? asset('storage/'.$mountain->image_2)
                : 'https://via.placeholder.com/1200x600?text=No+Image');
    @endphp

    <div class="mountain-hero mb-4 mb-md-5" style="background-image:url('{{ $heroImage }}');">
        <div class="mountain-hero-overlay">

            <div class="mountain-hero-badge mb-2">
                <i class="bi bi-geo-alt-fill"></i>
                <span>{{ number_format($mountain->latitude, 6, '.', '') }}, {{ number_format($mountain->longitude, 6, '.', '') }}</span>
            </div>

            <h1 class="text-white fw-bold mb-1" style="text-shadow:0 10px 30px rgba(0,0,0,.8);">
                {{ $mountain->mountain_name }}
            </h1>

            <div class="text-white-50 small" style="max-width:600px;">
                @if($mountain->description)
                    {{-- just show first ~140 chars in hero --}}
                    {{ \Illuminate\Support\Str::limit($mountain->description, 140) }}
                @else
                    Περιγραφή δεν είναι διαθέσιμη ακόμη.
                @endif
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- LEFT COLUMN: DETAILS / DESCRIPTION --}}
        <div class="col-12 col-lg-8">

            <div class="card mountain-card mb-4">
                <div class="card-body p-4 p-md-4">
                    <h2 class="h5 fw-bold mb-3">Περιγραφή</h2>

                    @if($mountain->description)
                        <div class="text-muted" style="white-space:pre-line;">
                            {!! nl2br(e($mountain->description)) !!}
                        </div>
                    @else
                        <div class="text-muted fst-italic">
                            Δεν έχει δοθεί ακόμη περιγραφή για αυτό το βουνό.
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mountain-card mb-4">
                <div class="card-body p-4 p-md-4">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                        <div>
                            <h2 class="h5 fw-bold mb-2">Συντεταγμένες</h2>
                            <div class="coords-badge d-inline-block">
                                <div><strong>Latitude:</strong> {{ $mountain->latitude }}</div>
                                <div><strong>Longitude:</strong> {{ $mountain->longitude }}</div>
                            </div>
                        </div>

                        {{-- Tiny copy button --}}
                        <button
                            class="btn btn-outline-secondary btn-sm"
                            onclick="navigator.clipboard.writeText('{{ $mountain->latitude }}, {{ $mountain->longitude }}'); this.innerText='Αντιγράφηκε!'; setTimeout(()=>this.innerText='Αντιγραφή',1500);">
                            Αντιγραφή
                        </button>
                    </div>

                    {{-- MAP PREVIEW --}}
                    <div class="mt-4">
                        <h3 class="h6 fw-semibold mb-2">
                            Χάρτης
                        </h3>
                        <iframe
                            class="map-frame"
                            loading="lazy"
                            allowfullscreen
                            referrerpolicy="no-referrer-when-downgrade"
                            src="https://www.google.com/maps?q={{ $mountain->latitude }},{{ $mountain->longitude }}&hl=el&z=12&output=embed">
                        </iframe>
                        <div class="text-muted small mt-2">
                            Η τοποθεσία βασίζεται στις αποθηκευμένες συντεταγμένες.
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- RIGHT COLUMN: GALLERY / META --}}
        <div class="col-12 col-lg-4">

            <div class="card mountain-card mb-4">
                <div class="card-body p-4 p-md-4">
                    <h2 class="h5 fw-bold mb-3">Φωτογραφίες</h2>

                    <div class="row g-3">
                        {{-- Image 1 --}}
                        <div class="col-6 col-lg-12">
                            @if($mountain->image_1)
                                <img
                                    src="{{ asset('storage/'.$mountain->image_1) }}"
                                    alt="Εικόνα 1 - {{ $mountain->mountain_name }}"
                                    class="mountain-thumb w-100">
                            @else
                                <div class="mountain-thumb d-flex align-items-center justify-content-center text-muted small">
                                    Χωρίς εικόνα
                                </div>
                            @endif
                        </div>

                        {{-- Image 2 --}}
                        <div class="col-6 col-lg-12">
                            @if($mountain->image_2)
                                <img
                                    src="{{ asset('storage/'.$mountain->image_2) }}"
                                    alt="Εικόνα 2 - {{ $mountain->mountain_name }}"
                                    class="mountain-thumb w-100">
                            @else
                                <div class="mountain-thumb d-flex align-items-center justify-content-center text-muted small">
                                    Χωρίς εικόνα
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

            <div class="card mountain-card">
                <div class="card-body p-4 p-md-4">
                    <h2 class="h5 fw-bold mb-3">Γρήγορες Πληροφορίες</h2>

                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2 d-flex">
                            <div class="me-2 text-secondary"><i class="bi bi-flag-fill"></i></div>
                            <div>
                                <div class="text-dark fw-semibold">Όνομα</div>
                                <div class="text-muted">{{ $mountain->mountain_name }}</div>
                            </div>
                        </li>

                        <li class="mb-2 d-flex">
                            <div class="me-2 text-secondary"><i class="bi bi-geo-alt-fill"></i></div>
                            <div>
                                <div class="text-dark fw-semibold">Σημείο</div>
                                <div class="text-muted">{{ $mountain->latitude }}, {{ $mountain->longitude }}</div>
                            </div>
                        </li>

                        <li class="mb-2 d-flex">
                            <div class="me-2 text-secondary"><i class="bi bi-calendar-event"></i></div>
                            <div>
                                <div class="text-dark fw-semibold">Δημιουργήθηκε</div>
                                <div class="text-muted">
                                    {{ $mountain->created_at ? $mountain->created_at->format('d/m/Y H:i') : '-' }}
                                </div>
                            </div>
                        </li>

                        <li class="d-flex">
                            <div class="me-2 text-secondary"><i class="bi bi-arrow-repeat"></i></div>
                            <div>
                                <div class="text-dark fw-semibold">Τελευταία ενημέρωση</div>
                                <div class="text-muted">
                                    {{ $mountain->updated_at ? $mountain->updated_at->format('d/m/Y H:i') : '-' }}
                                </div>
                            </div>
                        </li>
                    </ul>

                </div>
            </div>

        </div>
    </div>

</div>
@endsection
