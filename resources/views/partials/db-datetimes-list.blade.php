@if(!empty($currentSelectedDate))
  <h3 class="mb-3 gray-color font-weight-bold text-left">
    <b>{{ \Carbon\Carbon::parse($currentSelectedDate)->translatedFormat('l d-m-Y') }}</b>
  </h3>
  <h5 class="gray-color mb-3 availability-text">Διαθεσιμότητα</h5>
@endif

<ul class="list-group hour-list" id="available-times">
  @forelse ($dbDatetimesForSelectedDate as $item)
    @php
      $timeHi = substr($item->selected_time, 0, 5);

      // existing booking lookup...
      $bk = null;
      if (isset($bookingsByTime)) {
        if ($bookingsByTime instanceof \Illuminate\Support\Collection) {
          $bk = $bookingsByTime->get($timeHi);
        } elseif (is_array($bookingsByTime)) {
          $bk = $bookingsByTime[$timeHi] ?? null;
        }
      }

      // NEW: claim payload for this time (if any)
      $claimPayload = $claimsByTime[$timeHi] ?? null;
    @endphp

    <li id="saved-item-{{ $item->id }}"
        class="list-group-item font-s-16 d-flex justify-content-between align-items-center {{ $item->is_reserved ? 'is_reserved' : '' }}">
      <div class="d-flex align-items-center gap-2">
        <span>{{ $timeHi }}</span>

        {{-- show paper-plane if a claim was sent and it’s not yet reserved --}}
        @if(!$item->is_reserved && $claimPayload)
          <a
            class="ms-1"
            title="Κατοχυρώστε την κράτηση"
            href="{{ route('booking.claim', ['booking' => $claimPayload['booking'], 'token' => $claimPayload['token']]) }}"
          >
            <i class="fa fa-paper-plane text-warning" aria-hidden="true"></i>
          </a>
        @endif
      </div>

      <div class="d-flex gap-2">
        @if(!$item->is_reserved)
          <button
            type="button"
            class="btn btn-light btn-sm js-delete-saved"
            data-id="{{ $item->id }}"
            data-date="{{ $item->selected_date }}"
            data-time="{{ $timeHi }}"
            data-url="{{ route('profile-date.deleteSaved', $user) }}"
            title="Διαγραφή αποθηκευμένης ώρας">
            <i class="fa fa-trash-o text-danger"></i>
          </button>
        @endif

        @if($item->is_reserved)
          {{-- Info icon that references the dynamic template id (no Bootstrap) --}}
          <span class="plain-popover-trigger"
                role="button"
                tabindex="0"
                aria-label="Πληροφορίες κράτησης"
                data-popover-template="#popover-{{ $item->id }}">
            <i class="fa fa-info-circle text-primary"></i>
          </span>

          {{-- Dynamic popover template --}}
          <template id="popover-{{ $item->id }}">
            <div class="text-start">
              <div class="fw-bold mb-1">Κράτηση</div>
              <div><strong>Πελάτης:</strong> {{ e($bk->customer_name ?? '—') }}</div>

              <div>
                <strong>Email:</strong>
                @if(!empty($bk->customer_email))
                  <a href="mailto:{{ e($bk->customer_email) }}">{{ e($bk->customer_email) }}</a>
                @else
                  —
                @endif
              </div>

              <div>
                <strong>Τηλέφωνο:</strong>
                @if(!empty($bk->customer_phone))
                  <a href="tel:{{ e($bk->customer_phone) }}">{{ e($bk->customer_phone) }}</a>
                @else
                  —
                @endif
              </div>

              <div><strong>Άτομα:</strong> {{ e($bk->people_count ?? 1) }}</div>
              <div><strong>Επίπεδο:</strong> {{ e($bk->level ?? '—') }}</div>
            </div>
          </template>
        @endif
      </div>
    </li>
  @empty
    <li class="list-group-item gray-color text-muted">
      Δεν υπάρχουν αποθηκευμένες ώρες για αυτή την ημερομηνία.
    </li>
  @endforelse
</ul>

<script>
/**
 * Vanilla sticky popovers that read content from <template id="...">.
 * - Show on hover/focus
 * - Positioned centered ABOVE the trigger
 * - Stay open while hovering the popover
 * - Hide when leaving both trigger and popover (with a tiny delay)
 * - Repositions on scroll/resize
 */
window.initPopoverHover = function (root) {
  const OFFSET_Y = 10;        // px gap above trigger
  const ENTER_DELAY = 80;
  const LEAVE_DELAY = 120;

  (root || document).querySelectorAll('[data-popover-template]').forEach(trigger => {
    if (trigger.__vpopInit) return;
    trigger.__vpopInit = true;

    const tplSel = trigger.getAttribute('data-popover-template');

    let tip = null;
    let showTimer, hideTimer;
    let overTrigger = false, overTip = false;

    function createTip() {
      if (tip) return tip;
      const tpl = document.querySelector(tplSel);
      const html = tpl ? tpl.innerHTML : '<div>—</div>';

      tip = document.createElement('div');
      tip.className = 'vpop';
      tip.setAttribute('data-hidden', 'true');
      tip.innerHTML = html;
      document.body.appendChild(tip);

      tip.addEventListener('mouseenter', () => {
        overTip = true;
        clearTimeout(hideTimer);
      });
      tip.addEventListener('mouseleave', () => {
        overTip = false;
        queueHide();
      });

      return tip;
    }

    function positionTip() {
      if (!tip) return;
      const tr = trigger.getBoundingClientRect();
      tip.style.left = (tr.left + tr.width / 2 - tip.offsetWidth / 2 + window.scrollX) + 'px';
      tip.style.top  = (tr.top - tip.offsetHeight - OFFSET_Y + window.scrollY) + 'px';
    }

    function show() {
      clearTimeout(hideTimer);
      if (showTimer) return;
      showTimer = setTimeout(() => {
        createTip();
        tip.setAttribute('data-hidden', 'false');
        tip.style.display = 'block';
        requestAnimationFrame(positionTip);
        showTimer = null;
      }, ENTER_DELAY);
    }

    function hide() {
      clearTimeout(showTimer);
      if (tip) {
        tip.setAttribute('data-hidden', 'true');
        tip.style.display = 'none';
      }
    }

    function queueHide() {
      clearTimeout(hideTimer);
      hideTimer = setTimeout(() => {
        if (!overTrigger && !overTip) hide();
      }, LEAVE_DELAY);
    }

    trigger.addEventListener('mouseenter', () => { overTrigger = true; show(); });
    trigger.addEventListener('mouseleave', () => { overTrigger = false; queueHide(); });
    trigger.addEventListener('focus',      () => { overTrigger = true; show(); });
    trigger.addEventListener('blur',       () => { overTrigger = false; queueHide(); });

    const onReflow = () => { if (tip && tip.getAttribute('data-hidden') !== 'true') positionTip(); };
    window.addEventListener('scroll', onReflow, true);
    window.addEventListener('resize', onReflow, true);
  });
};

document.addEventListener('DOMContentLoaded', () => {
  if (window.initPopoverHover) window.initPopoverHover(document);
});
</script>
