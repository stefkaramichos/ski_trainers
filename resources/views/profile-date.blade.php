
@extends('layouts.app')
@section('content')
<div class="main-form">
    <div class="container">
      <div id="flash">
        @if (session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        @error('selected_datetimes')
          <div class="alert alert-danger">{{ $message }}</div>
        @enderror
      </div>
        <div class="row ">
            @include('includes.profile-header')
            @include('includes.admin-edit-menu')
            <div class="mb-3 availability">
                <div class="card">
                    <div class="card-header">
                        Διαθεσιμότητα
                    </div>

                    <div class="card-body">
                        {{-- === SESSION (ALL PENDING) === --}}
                        {{-- Session (yellow) list + Save All --}}
                            <div id="session-wrap">
                                @include('partials.session-datetimes-list', [
                                'sessionDatetimesAll' => $sessionDatetimesAll,
                                'user' => $user
                                ])
                            </div>


                        <div class="container mt-5">

                            {{-- Calendar + Time --}}
                            <div id="calendar-wrap" data-select-date-url="{{ route('profile-date.selectDate', $user) }}">
                                {!! $calendar !!}
                            </div>

                            <div id="time-wrap">
                                {!! $timeSelection !!}
                            </div>

                            
                            {{-- Saved (green) list --}}
                            <div id="db-wrap">
                                @include('partials.db-datetimes-list', [
                                'dbDatetimesForSelectedDate' => $dbDatetimesForSelectedDate,
                                'currentSelectedDate' => $currentSelectedDate ?? null,
                                'user' => $user,  {{-- needed so the partial can build /profile/{user}/delete-saved --}}
                                ])

                            </div>

                        </div>

                        
                    
                    </div>
                    
                </div> 
            </div>


          
        </div>
    </div>
</div>
@endsection



<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  async function postJson(url, payload) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    });
    return await res.json();
  }

  function refreshPendingCount() {
    const list = document.getElementById('pending-list');
    const countEl = document.getElementById('pending-count');
    const saveBtn = document.getElementById('save-all-btn');
    if (!list) return;
    const n = list.querySelectorAll('li').length;
    if (countEl) countEl.textContent = n;
    if (saveBtn) saveBtn.disabled = n === 0;
  }

  document.body.addEventListener('click', async function (e) {
    // Month navigation (AJAX)
    const navBtn = e.target.closest('.js-cal-nav');
    if (navBtn) {
      const y = navBtn.getAttribute('data-year');
      const m = navBtn.getAttribute('data-month');
      const yNum = parseInt(y, 10);
      const mNum = parseInt(m, 10);
      const monthStr = String(mNum).padStart(2, '0');
      const targetDate = `${yNum}-${monthStr}-01`; // first day of target month

      const selectUrl = document.getElementById('calendar-wrap')?.dataset.selectDateUrl;
      if (!selectUrl) {
        console.error('Missing data-select-date-url on #calendar-wrap');
        return;
      }

      try {
        const data = await postJson(selectUrl, { selected_date: targetDate });
        if (data.success) {
          document.getElementById('calendar-wrap').innerHTML = data.calendar;
          document.getElementById('time-wrap').innerHTML     = data.timeSelection;
          document.getElementById('db-wrap').innerHTML       = data.dbListHtml;
          document.getElementById('session-wrap').innerHTML  = data.sessionListHtml;

          initPopoverHover(document.getElementById('db-wrap'));
          initPopoverHover(document.getElementById('time-wrap'));
          initPopoverHover(document.getElementById('calendar-wrap'));
          initPopoverHover(document.getElementById('session-wrap'));
          refreshPendingCount();
        } else {
          alert('Αποτυχία ενημέρωσης ημερολογίου.');
        }
      } catch (err) {
        console.error(err);
        alert('Σφάλμα κατά την ενημέρωση.');
      }
      return;
    }

    // ... keep your existing handlers for .js-select-date, .js-delete-saved, .js-delete-session ...
  });
});
</script>


<script>
    document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  async function postJson(url, payload) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    });
    return await res.json();
  }

  document.body.addEventListener('click', async function (e) {
    const dayBtn = e.target.closest('.js-select-date');
    if (!dayBtn) return;

    const date = dayBtn.getAttribute('data-date'); // Y-m-d

    // POST to /profile/{user}/select-date
    // Safer: set data-url on a wrapper or read from a data attribute you render with the user id
    const url = dayBtn.dataset.url || dayBtn.closest('[data-select-date-url]')?.dataset.selectDateUrl;
    if (!url) {
      console.error('Missing select-date URL. Add data-select-date-url on a parent wrapper.');
      return;
    }

    try {
      const data = await postJson(url, { selected_date: date });
      if (data.success) {
        // Replace fragments
        document.getElementById('calendar-wrap').innerHTML = data.calendar;
        document.getElementById('time-wrap').innerHTML     = data.timeSelection;
        document.getElementById('db-wrap').innerHTML       = data.dbListHtml;
        document.getElementById('session-wrap').innerHTML  = data.sessionListHtml;

        initPopoverHover(document.getElementById('db-wrap'));
        initPopoverHover(document.getElementById('time-wrap'));
        initPopoverHover(document.getElementById('calendar-wrap'));
        initPopoverHover(document.getElementById('session-wrap'));
      } else {
        alert('Αποτυχία ενημέρωσης ημερολογίου.');
      }
    } catch (err) {
      console.error(err);
      alert('Σφάλμα κατά την ενημέρωση.');
    }
  });
});

</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  // existing helpers...
  function enableTimeIfPossible(date, time) {
    // Only re-enable if the calendar pane currently shows this date
    const btn = document.querySelector(`button[name="selected_time"][value="${time}"][data-date="${date}"]`);
    if (!btn) return;

    // Flip the DB flag; if session flag not set, enable the button
    const dbBlocked = btn.getAttribute('data-disabled-db') === '1';
    if (dbBlocked) {
      btn.setAttribute('data-disabled-db', '0');
      const sessionBlocked = btn.getAttribute('data-disabled-session') === '1';
      if (!sessionBlocked) {
        btn.disabled = false;
        btn.classList.remove('disabled');
      }
    }
  }

  document.body.addEventListener('click', async function (e) {
    // --- Saved (DB) delete
        const btnSaved = e.target.closest('.js-delete-saved');
        if (btnSaved) {
        const id   = btnSaved.getAttribute('data-id');
        const date = btnSaved.getAttribute('data-date');
        const time = btnSaved.getAttribute('data-time');
        const url  = btnSaved.getAttribute('data-url'); // <-- use this
        btnSaved.disabled = true;

        try {
            const res  = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id })
            });

        const data = await res.json();

        if (data.success) {
          // Remove row from list
          const row = document.getElementById('saved-item-' + id);
          if (row) row.remove();

          // Re-enable the time button if this date is currently shown
          enableTimeIfPossible(date, time);
        } else {
          alert(data.message || 'Αποτυχία διαγραφής.');
          btnSaved.disabled = false;
        }
      } catch (err) {
        console.error(err);
        alert('Παρουσιάστηκε σφάλμα. Προσπαθήστε ξανά.');
        btnSaved.disabled = false;
      }
      return; // prevent falling through
    }

    // --- Session delete (you already have this handler) ---
    const btnSess = e.target.closest('.js-delete-session');
    if (btnSess) {
      const index = btnSess.getAttribute('data-index');
      const date  = btnSess.getAttribute('data-date');
      const time  = btnSess.getAttribute('data-time');
      btnSess.disabled = true;

      try {
        const res = await fetch("{{ route('profile-date.delete') }}", {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ delete_index: index, date, time })
        });
        const data = await res.json();

        if (data.success) {
          const row = document.getElementById('session-item-' + index);
          if (row) row.remove();

          // Clear session-based disable on the time button
          const btn = document.querySelector(`button[name="selected_time"][value="${time}"][data-date="${date}"]`);
          if (btn) {
            const sessionBlocked = btn.getAttribute('data-disabled-session') === '1';
            if (sessionBlocked) {
              btn.setAttribute('data-disabled-session', '0');
              const dbBlocked = btn.getAttribute('data-disabled-db') === '1';
              if (!dbBlocked) {
                btn.disabled = false;
                btn.classList.remove('disabled');
              }
            }
          }
        } else {
          alert(data.message || 'Αποτυχία διαγραφής.');
          btnSess.disabled = false;
        }
      } catch (err) {
        console.error(err);
        alert('Παρουσιάστηκε σφάλμα. Προσπαθήστε ξανά.');
        btnSess.disabled = false;
      }
    }
  });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const url = new URL(window.location.href);
    // Only clean if 'selected_date' or other parameters exist
    if (url.search) {
        url.search = ''; // remove all query parameters
        // Preserve anchor like #available-times
        const cleanUrl = url.toString();
        // Replace the current history entry (no reload)
        window.history.replaceState({}, document.title, cleanUrl);
    }
});
</script>

 
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const list = document.getElementById('pending-list');
  const countEl = document.getElementById('pending-count');
  const saveBtn = document.getElementById('save-all-btn');

  function refreshCount() {
    if (!list) return;
    const n = list.querySelectorAll('li').length;
    if (countEl) countEl.textContent = n;
    if (saveBtn) saveBtn.disabled = n === 0;
  }
  refreshCount();

  document.body.addEventListener('click', async function (e) {
    const btn = e.target.closest('.js-delete-session');
    if (!btn) return;

    const index = btn.getAttribute('data-index');
    const date  = btn.getAttribute('data-date'); // Y-m-d
    const time  = btn.getAttribute('data-time'); // HH:MM
    if (index === null) return;

    btn.disabled = true;

    try {
      const res = await fetch("{{ route('profile-date.delete') }}", {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ delete_index: index, date, time })
      });

      const data = await res.json();

      if (data.success) {
        // 1) Remove the LI (removes hidden inputs)
        const row = document.getElementById('session-item-' + index);
        if (row) row.remove();

        // 2) Enable the corresponding time button if DB isn't blocking it
        const selector = `button[name="selected_time"][value="${time}"][data-date="${date}"]`;
        const timeBtn = document.querySelector(selector);
        if (timeBtn) {
          // Only session was disabling it? Flip the flag
          const hadSessionBlock = timeBtn.getAttribute('data-disabled-session') === '1';
          if (hadSessionBlock) {
            timeBtn.setAttribute('data-disabled-session', '0');
            const dbBlock = timeBtn.getAttribute('data-disabled-db') === '1';
            if (!dbBlock) {
              timeBtn.disabled = false;
              timeBtn.classList.remove('disabled');
            }
          }
        }

        refreshCount();
      } else {
        alert(data.message || 'Αποτυχία διαγραφής.');
        btn.disabled = false;
      }
    } catch (err) {
      console.error(err);
      alert('Παρουσιάστηκε σφάλμα. Προσπαθήστε ξανά.');
      btn.disabled = false;
    }
  });
});
</script>
