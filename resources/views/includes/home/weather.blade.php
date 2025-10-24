<section class="weather-container mt-5">
    <div class="container py-5"> 
        <h2 class=" mb-4"><strong>🌤 Καιρός στα Βουνά</strong></h2>
        <div class="underline underline-booking"></div>

        @if(isset($mountains) && $mountains->count())
            <div class="row g-4">
                @foreach($mountains as $m)
                    @php $w = $weatherData[$m->id] ?? null; @endphp
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="p-4 rounded-3 h-100 weather-mountain">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <img width="35" class="img-ski-location" src="{{ asset('storage/ski-center.png') }}" alt="ski location">
                                <h4 class="mb-0">
                                    <a href="{{ route('mountain', $m->id) }}">{{ $m->mountain_name }}</a>
                                </h4>
                                @if($w && isset($w['is_day']))
                                    <div class="dropdown">
                                        <button class="btn btn-sm dropdown-toggle {{ $w['is_day'] ? 'btn-warning text-dark' : 'btn-secondary' }}" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            {{ $w['is_day'] ? 'Ημέρα' : 'Νύχτα' }}
                                        </button>
                                        <ul class="dropdown-menu p-3" id="forecastMenu{{ $m->id }}" style="min-width: 250px;">
                                            <li class="text-muted">Φόρτωση...</li>
                                        </ul>
                                    </div>
                                @endif
                            </div>


                       @if($w)
                            @php
                                // Map weather_code to description + emoji
                                $code = $w['weather_code'] ?? null;
                                $conditions = [
                                    0 => ['☀️', 'Καθαρός ουρανός'],
                                    1 => ['🌤️', 'Κυρίως καθαρός'],
                                    2 => ['⛅', 'Μερικώς νεφελώδης'],
                                    3 => ['☁️', 'Νεφελώδης'],
                                    45 => ['🌫️', 'Ομίχλη'],
                                    48 => ['🌫️', 'Παγωμένη ομίχλη'],
                                    51 => ['🌦️', 'Ψιλή βροχή ελαφριά'],
                                    53 => ['🌦️', 'Ψιλή βροχή μέτρια'],
                                    55 => ['🌧️', 'Ψιλή βροχή έντονη'],
                                    56 => ['🌧️❄️', 'Παγωμένη ψιλή βροχή ελαφριά'],
                                    57 => ['🌧️❄️', 'Παγωμένη ψιλή βροχή έντονη'],
                                    61 => ['🌧️', 'Βροχή ελαφριά'],
                                    63 => ['🌧️', 'Βροχή μέτρια'],
                                    65 => ['🌧️', 'Βροχή έντονη'],
                                    66 => ['🌧️❄️', 'Παγωμένη βροχή ελαφριά'],
                                    67 => ['🌧️❄️', 'Παγωμένη βροχή έντονη'],
                                    71 => ['❄️', 'Χιόνι ελαφρύ'],
                                    73 => ['❄️', 'Χιόνι μέτριο'],
                                    75 => ['❄️', 'Χιόνι έντονο'],
                                    77 => ['🌨️', 'Κόκκοι χιονιού'],
                                    80 => ['🌦️', 'Μπόρες ελαφριές'],
                                    81 => ['🌧️', 'Μπόρες μέτριες'],
                                    82 => ['⛈️', 'Μπόρες έντονες'],
                                    85 => ['❄️', 'Μπόρες χιονιού ελαφρές'],
                                    86 => ['❄️', 'Μπόρες χιονιού έντονες'],
                                    95 => ['⛈️', 'Καταιγίδες'],
                                    96 => ['⛈️', 'Καταιγίδες με χαλάζι (ελαφρές)'],
                                    99 => ['🌩️', 'Καταιγίδες με χαλάζι (έντονες)'],
                                ];
                                $weatherText = $conditions[$code][1] ?? 'Άγνωστη κατάσταση';
                                $weatherIcon = $conditions[$code][0] ?? '❓';
                            @endphp

                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Καιρική Κατάσταση</span>
                                    <strong>{{ $weatherIcon }} {{ $weatherText }}</strong>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <span>Θερμοκρασία</span>
                                    <strong>{{ $w['temperature_2m'] ?? '-' }} °C</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Αίσθηση</span>
                                    <strong>{{ $w['apparent_temperature'] ?? '-' }} °C</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Υγρασία</span>
                                    <strong>{{ $w['relative_humidity_2m'] ?? '-' }}%</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Άνεμος</span>
                                    <strong>{{ $w['wind_speed_10m'] ?? '-' }} km/h</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Διεύθυνση Ανέμου</span>
                                    <strong>{{ $w['wind_direction_10m'] ?? '-' }}°</strong>
                                </div>
                            </div>
                        @else
                            <p class="mt-3 text-muted">Δεν υπάρχουν διαθέσιμα δεδομένα καιρού για αυτό το βουνό.</p>
                        @endif

                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-center text-muted">Δεν βρέθηκαν βουνά με συντεταγμένες.</p>
        @endif
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[id^="forecastMenu"]').forEach(menu => {
        const id = menu.id.replace('forecastMenu','');
        const parentButton = menu.parentElement.querySelector('button');

        parentButton.addEventListener('click', () => {
            if (menu.dataset.loaded === '1') return; // already loaded once
            fetch(`{{ url('/mountain-forecast') }}/${id}`)
                .then(res => res.json())
                .then(data => {
                    menu.innerHTML = '';
                    if (data.success && data.data && data.data.time) {
                        const days = data.data.time;
                        const max = data.data.temperature_2m_max;
                        const min = data.data.temperature_2m_min;
                        const rain = data.data.precipitation_sum;

                        days.forEach((day, i) => {
                            const li = document.createElement('li');
                            li.classList.add('mb-2');
                            li.innerHTML = `
                                <div><strong>${day}</strong></div>
                                <small>Μέγιστη: ${max[i]}°C | Ελάχιστη: ${min[i]}°C</small><br>
                                <small>Βροχή: ${rain[i]} mm</small>
                            `;
                            menu.appendChild(li);
                        });
                    } else {
                        menu.innerHTML = '<li class="text-muted">Δε βρέθηκαν δεδομένα</li>';
                    }
                    menu.dataset.loaded = '1';
                })
                .catch(() => {
                    menu.innerHTML = '<li class="text-danger">Σφάλμα φόρτωσης</li>';
                });
        });
    });
});
</script>