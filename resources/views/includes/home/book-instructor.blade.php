{{-- resources/views/partials/home-booking-2step.blade.php --}}
@php
  \Carbon\Carbon::setLocale('el');
  $today     = now()->format('Y-m-d');
  $todayHuman= \Carbon\Carbon::parse($today)->translatedFormat('l d-m-Y');
  $mountains = \App\Models\Mountain::orderBy('mountain_name')->get(['id','mountain_name']);
@endphp



<div class="container px-0 px-sm-3">
  <div class="card hb-card mt-4">
    <div class="card-header hb-header d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <i class="fa fa-snowflake-o"></i>
        <strong>Κράτηση Μαθήματος</strong>
      </div>
     
    </div>

    <div class="card-body">

      {{-- tiny stepper --}}
      <div class="hb-stepper d-flex align-items-center gap-3 mb-4">
        <div class="step">
          <span class="bullet bg-primary text-white">1</span>
          <span class="fw-semibold">Ημερομηνία & Χιονοδρομικό</span>
        </div>
        <div class="flex-grow-1 line"></div>
        <div class="step">
          <span class="bullet bg-secondary text-white">2</span>
          <span class="fw-semibold hb-muted">Στοιχεία Επικοινωνίας</span>
        </div>
      </div>

      {{-- STEP 1 --}}
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold"><i class="fa fa-calendar"></i> Ημερομηνία*</label>
          <input type="date" id="hb-date" class="form-control" value="{{ $today }}" min="{{ $today }}" required>
          <small class="text-muted">
            Επιλεγμένη: <span id="hb-date-human">{{ $todayHuman }}</span>
          </small>
        </div>

        <div class="col-md-5">
          <label class="form-label fw-semibold"><i class="fa fa-mountain"></i> Χιονοδρομικό*</label>
          <select id="hb-mountain" class="form-select" required>
            <option value="">— Επιλέξτε —</option>
            @foreach($mountains as $m)
              <option value="{{ $m->id }}">{{ $m->mountain_name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label fw-semibold"><i class="fa fa-clock-o"></i> Ώρα (διαθέσιμες)*</label>
          <div class="position-relative">
            <select id="hb-time" class="form-select" disabled required>
              <option value="">— Επιλέξτε —</option>
            </select>
            {{-- inline spinner during fetch --}}
            <div id="hb-time-spinner" class="position-absolute top-50 end-0 translate-middle-y me-3 d-none">
              <div class="spinner-border spinner-border-sm" role="status"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-3 d-flex align-items-center gap-2">
        <button id="hb-next" type="button" class="btn btn-primary btn-lg" disabled>
          Προχωρήστε στο τελευταίο βήμα
        </button>
        <span class="hb-muted small">Ενεργοποιείται όταν επιλέξετε διαθέσιμη ώρα</span>
      </div>

      {{-- STEP 2 --}}
      <form id="hb-final" method="POST" action="{{ route('home.book') }}" class="mt-4 d-none hb-ghost p-3 rounded-3">
        @csrf
        <input type="hidden" name="selected_date" id="hb-final-date" value="">
        <input type="hidden" name="mountain_id"   id="hb-final-mountain" value="">
        <input type="hidden" name="selected_time" id="hb-final-time" value="">

        {{-- toast-style feedback via SweetAlert2 --}}
        @if($errors->any())
          <script>
            document.addEventListener('DOMContentLoaded', function () {
              Swal.fire({
                position: 'top-end',
                icon: 'error',
                title: 'Σφάλμα!',
                html: `
                  <ul style="text-align:left;margin:0;padding-left:18px">
                    @foreach($errors->all() as $err)
                      <li>{{ $err }}</li>
                    @endforeach
                  </ul>
                `,
                showConfirmButton: false,
                timer: 5500,
                timerProgressBar: true,
                toast: true
              });
            });
          </script>
        @endif

        @if(session('success'))
          <script>
            document.addEventListener('DOMContentLoaded', function () {
              Swal.fire({
                position: 'top-end',
                icon: 'success',
                title: 'Επιτυχία!',
                text: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                toast: true
              });
            });
          </script>
        @endif

        <div class="row g-3">
          <div class="col-12">
            <div class="alert alert-info py-2 mb-0">
              <i class="fa fa-info-circle"></i>
              <span class="ms-1">Έχετε επιλέξει:
                <strong id="hb-summary-date">—</strong>,
                <strong id="hb-summary-mountain">—</strong>,
                <strong id="hb-summary-time">—</strong>
              </span>
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Ονοματεπώνυμο*</label>
            <input type="text" name="customer_name" class="form-control form-control-lg" value="{{ old('customer_name') }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email*</label>
            <input type="email" name="customer_email" class="form-control form-control-lg" value="{{ old('customer_email') }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Τηλέφωνο</label>
            <input type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone') }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Άτομα</label>
            <input type="number" name="people_count" class="form-control" min="1" max="10" value="{{ old('people_count', 1) }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Επίπεδο</label>
            <select name="level" class="form-select">
              <option value="">— Επιλέξτε —</option>
              @foreach(['Beginner'=>'Αρχάριος','Intermediate'=>'Μέσος','Advanced'=>'Προχωρημένος'] as $k=>$lbl)
                <option value="{{ $k }}" @selected(old('level')===$k)>{{ $lbl }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Σχόλια</label>
            <textarea name="notes" rows="3" class="form-control" placeholder="Οτιδήποτε θέλετε να γνωρίζουμε...">{{ old('notes') }}</textarea>
          </div>

          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-success btn-lg">
              <i class="fa fa-check"></i> Ολοκλήρωση κράτησης
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf      = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  const dateEl    = document.getElementById('hb-date');
  const dateTxt   = document.getElementById('hb-date-human');
  const mntEl     = document.getElementById('hb-mountain');
  const timeEl    = document.getElementById('hb-time');
  const timeSpin  = document.getElementById('hb-time-spinner');
  const nextBtn   = document.getElementById('hb-next');

  const finalForm = document.getElementById('hb-final');
  const finalDate = document.getElementById('hb-final-date');
  const finalMnt  = document.getElementById('hb-final-mountain');
  const finalTime = document.getElementById('hb-final-time');

  const sumDate   = document.getElementById('hb-summary-date');
  const sumMnt    = document.getElementById('hb-summary-mountain');
  const sumTime   = document.getElementById('hb-summary-time');

  function toHuman(ymd) {
    if (!ymd) return '';
    const [y,m,d] = ymd.split('-');
    return `${d}-${m}-${y}`;
  }

  function resetTimes(showLoading=false) {
    timeEl.innerHTML = '<option value="">— Επιλέξτε —</option>';
    timeEl.disabled = true;
    nextBtn.disabled = true;
    if (showLoading) timeSpin.classList.remove('d-none'); else timeSpin.classList.add('d-none');
  }

  async function loadTimes() {
    const mountain_id   = mntEl.value;
    const selected_date = dateEl.value;

    resetTimes(true);
    if (!mountain_id || !selected_date) {
      resetTimes(false);
      return;
    }

    try {
      const res = await fetch("{{ route('availability.timesByMountain') }}", {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ mountain_id, selected_date })
      });
      const data = await res.json();

      timeSpin.classList.add('d-none');

      if (data && data.success) {
        const avail = Array.isArray(data.available) ? data.available : [];
        if (avail.length) {
          timeEl.innerHTML = '<option value="">— Επιλέξτε —</option>' +
            avail.map(t => `<option value="${t}">${t}</option>`).join('');
          timeEl.disabled = false;
        } else {
          timeEl.innerHTML = '<option value="">Δεν υπάρχουν διαθέσιμες ώρες</option>';
          timeEl.disabled = true;
        }
      } else {
        timeEl.innerHTML = '<option value="">Αποτυχία φόρτωσης</option>';
        timeEl.disabled = true;
        Swal.fire({position:'top-end',icon:'error',title:'Σφάλμα',text:'Αποτυχία φόρτωσης διαθέσιμων ωρών.',toast:true,showConfirmButton:false,timer:3000});
      }
    } catch (e) {
      console.error(e);
      timeSpin.classList.add('d-none');
      timeEl.innerHTML = '<option value="">Σφάλμα</option>';
      timeEl.disabled = true;
      Swal.fire({position:'top-end',icon:'error',title:'Σφάλμα',text:'Παρουσιάστηκε σφάλμα κατά την ανάκτηση ωρών.',toast:true,showConfirmButton:false,timer:3000});
    }
  }

  // step 1 updates
  dateEl.addEventListener('change', () => {
    dateTxt.textContent = toHuman(dateEl.value);
    loadTimes();
  });
  mntEl.addEventListener('change', loadTimes);

  // enable next when a time chosen
  timeEl.addEventListener('change', () => {
    nextBtn.disabled = (timeEl.value === '');
  });

  // step 2 reveal & summary
  nextBtn.addEventListener('click', () => {
    finalDate.value = dateEl.value;
    finalMnt.value  = mntEl.value;
    finalTime.value = timeEl.value;

    sumDate.textContent = toHuman(dateEl.value);
    sumMnt.textContent  = mntEl.options[mntEl.selectedIndex]?.text || '';
    sumTime.textContent = timeEl.value || '';

    // visual stepper update
    const bullets = document.querySelectorAll('.hb-stepper .bullet');
    if (bullets[0]) bullets[0].classList.replace('bg-primary','bg-success');
    if (bullets[1]) bullets[1].classList.replace('bg-secondary','bg-primary');

    finalForm.classList.remove('d-none');
    finalForm.scrollIntoView({ behavior:'smooth', block:'start' });
  });

  // initial fetch once
  loadTimes();
});
</script>
