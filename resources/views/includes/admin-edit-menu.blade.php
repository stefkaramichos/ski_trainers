<div class="admin-edit-menu">
    <ul>
        <li class="{{ request()->routeIs('profile.view') ? 'active' : '' }}">
            <a href="{{ route('profile.view', $user->id) }}">
                <img width="25" src="{{ asset('storage/user-icon.png') }}" alt="ski icon">
            </a>
        </li>
        <li class="{{ request()->routeIs('profile') ? 'active' : '' }}">
            <a href="{{ route('profile', $user->id) }}">
                <img width="25" src="{{ asset('storage/settings.png') }}" alt="ski icon">
            </a>
        </li>
        <li class="{{ request()->routeIs('profile.date') ? 'active' : '' }}">
            <a title="Ρυθμίστε τις Διαθεσιμότητες" href="{{ route('profile.date', $user->id) }}">
                <img width="25" src="{{ asset('storage/calendar.png') }}" alt="ski icon">
            </a>
        {{-- </li>
        <li class="{{ request()->routeIs('profile.programm') ? 'active' : '' }}">
            <a href="{{ route('profile.programm', $user->id) }}">Πρόγραμμα</a>
        </li> --}}
    </ul>
</div>
