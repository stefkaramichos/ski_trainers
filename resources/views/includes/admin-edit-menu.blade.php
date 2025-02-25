<div class="admin-edit-menu">
    <ul>
        <li class="{{ request()->routeIs('profile') ? 'active' : '' }}">
            <a href="{{ route('profile', $user->id) }}">Στοιχεία Προφίλ</a>
        </li>
        <li class="{{ request()->routeIs('profile.date') ? 'active' : '' }}">
            <a href="{{ route('profile.date', $user->id) }}">Διαθεσιμότα</a>
        </li>
        <li class="{{ request()->routeIs('profile.programm') ? 'active' : '' }}">
            <a href="{{ route('profile.programm', $user->id) }}">Πρόγραμμα</a>
        </li>
    </ul>
</div>
