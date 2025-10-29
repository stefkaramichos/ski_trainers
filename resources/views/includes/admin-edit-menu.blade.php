<div class="admin-edit-menu">
    <ul>
        <li class="{{ request()->routeIs('profile') ? 'active' : '' }}">
            <a href="{{ route('profile', $user->id) }}">
                <img width="25" src="{{ asset('storage/user-icon.png') }}" alt="Προφίλ">
            </a>
        </li>

        <li class="{{ request()->routeIs('profile.date') ? 'active' : '' }}">
            <a title="Ρυθμίστε τις Διαθεσιμότητες" href="{{ route('profile.date', $user->id) }}">
                <img width="25" src="{{ asset('storage/calendar.png') }}" alt="Διαθεσιμότητα">
            </a>
        </li>

        <li class="{{ request()->routeIs('subscription.show') ? 'active' : '' }}">
            <a title="Συνδρομή / Πληρωμή" href="{{ route('subscription.show', $user->id) }}">
                <img width="25" src="{{ asset('storage/subscription.png') }}" alt="Συνδρομή">
            </a>
        </li>
    </ul>
</div>
