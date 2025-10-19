
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
/**
 * Single JS file:
 * - Shows a centered page loader GIF during async actions (no calendar fade/disable).
 * - Handles date select, month nav, delete saved (DB) and delete session (pending).
 * - Updates all fragments returned by the server.
 * - Keeps simple helpers only; no UI dimming except the loader overlay.
 *
 * Requirements in your Blade/layout (HTML + CSS not included here):
 *   <div id="page-loader" class="page-loader d-none"> ... </div>
 * and CSS that toggles .active to show it (as we discussed).
 */
document.addEventListener('DOMContentLoaded', () => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  // ---- Elements
  const $cal     = document.getElementById('calendar-wrap');
  const $time    = document.getElementById('time-wrap');
  const $db      = document.getElementById('db-wrap');
  const $session = document.getElementById('session-wrap');
  const $flash   = document.getElementById('flash');

  // ---- Loader (centered overlay). We ONLY toggle this — no other UI fading.
  function showLoader(show = true) {
    const el = document.getElementById('page-loader');
    if (!el) return;
    el.classList.toggle('active', !!show);
    el.classList.toggle('d-none', !show);
  }

  // ---- Flash helper (fallback to alert if container missing)
  function flash(type, msg) {
    if (!$flash) { alert(msg); return; }
    const div = document.createElement('div');
    div.className = `alert alert-${type}`;
    div.setAttribute('role','status');
    div.textContent = msg;
    $flash.prepend(div);
    setTimeout(() => div.remove(), 4000);
  }

  // ---- Basic POST JSON helper
  async function postJson(url, payload) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload || {})
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return await res.json();
  }

  // ---- Update fragments returned by backend
  function updateAllFragments(data) {
    if (data.calendar && $cal)        $cal.innerHTML = data.calendar;
    if (data.timeSelection && $time)  $time.innerHTML = data.timeSelection;
    if (data.dbListHtml && $db)       $db.innerHTML = data.dbListHtml;
    if (data.sessionListHtml && $session) $session.innerHTML = data.sessionListHtml;
    refreshPendingCount();
    reinitEnhancements();
  }

  // ---- Optional re-initialization hook (tooltips/popovers etc.)
  function reinitEnhancements() {
    if (typeof window.initPopoverHover === 'function') {
      window.initPopoverHover($cal);
      window.initPopoverHover($time);
      window.initPopoverHover($db);
      window.initPopoverHover($session);
    }
  }

  // ---- Pending list count + save-all button state
  function refreshPendingCount() {
    const list = document.getElementById('pending-list');
    if (!list) return;
    const n = list.querySelectorAll('li').length;
    const countEl = document.getElementById('pending-count');
    const saveBtn = document.getElementById('save-all-btn');
    if (countEl) countEl.textContent = n;
    if (saveBtn) saveBtn.disabled = n === 0;
  }
  refreshPendingCount();

  // ---- If a row is removed, re-enable time button when not blocked by DB/session
  function enableTimeIfPossible(date, time) {
    const btn = document.querySelector(`button[name="selected_time"][value="${time}"][data-date="${date}"]`);
    if (!btn) return;
    const dbBlocked   = btn.getAttribute('data-disabled-db') === '1';
    const sesBlocked  = btn.getAttribute('data-disabled-session') === '1';
    if (!dbBlocked && !sesBlocked) {
      btn.disabled = false;
      btn.classList.remove('disabled');
    }
  }

  // ---- Get select-date URL from wrapper attribute
  function getSelectUrl() {
    return document.getElementById('calendar-wrap')?.dataset.selectDateUrl || null;
  }

  // ---- Select a date (from day click or month nav)
  async function handleSelectDate(targetDate) {
    const url = getSelectUrl();
    if (!url) { console.error('Missing data-select-date-url on #calendar-wrap'); return; }
    showLoader(true);
    try {
      const data = await postJson(url, { selected_date: targetDate });
      if (data?.success) updateAllFragments(data);
      else flash('danger','Αποτυχία ενημέρωσης ημερολογίου.');
    } catch (err) {
      console.error(err);
      flash('danger','Σφάλμα κατά την ενημέρωση.');
    } finally {
      showLoader(false);
    }
  }

  // ---- Delegated clicks
  document.body.addEventListener('click', async (e) => {
    // Month navigation
    const calNav = e.target.closest('.js-cal-nav');
    if (calNav) {
      const y = String(calNav.getAttribute('data-year') || '');
      const m = String(calNav.getAttribute('data-month') || '');
      if (y && m) {
        const monthStr = m.padStart(2, '0');
        await handleSelectDate(`${y}-${monthStr}-01`);
      }
      return;
    }

    // Day selection
    const dayBtn = e.target.closest('.js-select-date');
    if (dayBtn) {
      const date = dayBtn.getAttribute('data-date'); // Y-m-d
      if (date) await handleSelectDate(date);
      return;
    }

    // Delete saved (DB)
    const btnSaved = e.target.closest('.js-delete-saved');
    if (btnSaved) {
      const id   = btnSaved.getAttribute('data-id');
      const date = btnSaved.getAttribute('data-date');
      const time = btnSaved.getAttribute('data-time');
      const url  = btnSaved.getAttribute('data-url'); // server renders this
      if (!url || !id) return;

      showLoader(true);
      try {
        const res = await postJson(url, { id });
        if (res?.success) {
          const row = document.getElementById('saved-item-' + id);
          row?.remove();
          enableTimeIfPossible(date, time);
          refreshPendingCount();
        } else {
          flash('danger', res?.message || 'Αποτυχία διαγραφής.');
        }
      } catch (err) {
        console.error(err);
        flash('danger','Παρουσιάστηκε σφάλμα. Προσπαθήστε ξανά.');
      } finally {
        showLoader(false);
      }
      return;
    }

    // Delete session (pending)
    const btnSess = e.target.closest('.js-delete-session');
    if (btnSess) {
      const index = btnSess.getAttribute('data-index');
      const date  = btnSess.getAttribute('data-date');
      const time  = btnSess.getAttribute('data-time');
      const url   = "{{ route('profile-date.delete') }}";
      if (!index) return;

      showLoader(true);
      try {
        const res = await postJson(url, { delete_index: index, date, time });
        if (res?.success) {
          // Replace the session list HTML so indices stay correct
          // Replace the session list HTML even if it's empty
          if ($session) {
            $session.innerHTML = (res.sessionListHtml ?? '');
          }
          refreshPendingCount();

          // Re-enable the time button if possible
          const btn = document.querySelector(`button[name="selected_time"][value="${time}"][data-date="${date}"]`);
          if (btn && btn.getAttribute('data-disabled-session') === '1') {
            btn.setAttribute('data-disabled-session', '0');
            enableTimeIfPossible(date, time);
          }
          refreshPendingCount();
        } else {
          flash('danger', res?.message || 'Αποτυχία διαγραφής.');
        }
      } catch (err) {
        console.error(err);
        flash('danger','Παρουσιάστηκε σφάλμα. Προσπαθήστε ξανά.');
      } finally {
        showLoader(false);
      }
    }
  });

  // ---- Clean query params once (preserve hash)
  (function cleanQueryOnce() {
    const url = new URL(window.location.href);
    if (url.search) {
      url.search = '';
      window.history.replaceState({}, document.title, url.toString());
    }
  })();

  // ---- Initial enhancement re-init (if needed)
  reinitEnhancements();
});
</script>
