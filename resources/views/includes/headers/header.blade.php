<nav class="navbar ski-instructor-header navbar-dark bg-dark  navbar-expand-md 
    {{ !Auth::user() ? 'navbar-dark bg-dark shadow-sm border-0' : 'navbar-light bg-white shadow-sm' }}">
    <div class="container py-2">
        <!-- Brand -->
        <a class="navbar-brand text-uppercase fw-bold fs-4 d-flex align-items-center gap-2
            {{ Request::is('login') || Request::is('register') ? 'text-warning' : 'text-primary' }}"
            href="{{ url('/') }}">
            <i class="bi bi-lightning-charge-fill"></i>
            {{ config('app.name', 'Laravel') }}
        </a>


        <!-- Navbar content -->
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto"></ul>

            <ul class="navbar-nav ms-auto align-items-center">
                @guest
                    @if (Route::has('login'))
                        <li class="nav-item mx-1">
                            <a class="nav-link px-3 rounded {{ Request::is('login') ? 'active-link' : '' }}"
                                href="{{ route('login') }}">
                                <i class="bi bi-box-arrow-in-right me-1"></i>{{ __('Login') }}
                            </a>
                        </li>
                    @endif
                    @if (Route::has('register'))
                        <li class="nav-item mx-1">
                            <a class="nav-link px-3 rounded {{ Request::is('register') ? 'active-link' : '' }}"
                                href="{{ route('register') }}">
                                <i class="bi bi-person-plus me-1"></i>{{ __('Register') }}
                            </a>
                        </li>
                    @endif
                @else
                    <li class="nav-item dropdown mx-1">
                        <a id="navbarDropdown"
                           class="nav-link dropdown-toggle text-light d-flex align-items-center gap-2
                           {{ Request::is('login') || Request::is('register') ? 'text-light' : '' }}"
                           href="#" role="button" data-bs-toggle="dropdown"
                           aria-haspopup="true" aria-expanded="false" v-pre>
                            <i class="bi bi-person-circle fs-5"></i> {{ Auth::user()->name }}
                        </a>

                        <div class="dropdown-menu dropdown-menu-end shadow-sm rounded-3 border-0"
                             aria-labelledby="navbarDropdown">
                            <a 
                                href="{{  route('profile', Auth::user()->id) }}" 
                                class="dropdown-item py-2 d-flex align-items-center {{ Auth::user()->status === 'D' ? 'text-muted disabled-link' : '' }}">
                                <i class="bi bi-person-lines-fill me-2"></i>{{ __('auth.my_profile') }}
                            </a>

                            <a 
                                href="{{ route('profile.date', Auth::user()->id) }}" 
                                class="dropdown-item py-2 d-flex align-items-center {{ Auth::user()->status === 'D' ? 'text-muted disabled-link' : '' }}" >
                                <img width="12" src="{{ asset('storage/calendar.png') }}" alt="ski icon" class="me-2">
                                Το Πρόγραμμά μου
                            </a>

                            <a 
                                href="{{  route('tickets.index') }}" 
                                class="dropdown-item py-2 d-flex align-items-center {{ Auth::user()->status === 'D' ? 'text-muted disabled-link' : '' }}" >
                                <i class="bi bi-envelope-fill"></i>
                                &nbsp;&nbsp;Επικοινωνία
                            </a>

                            <div class="dropdown-divider"></div>

                            <a class="dropdown-item py-2 text-danger" href="{{ route('logout') }}"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="bi bi-box-arrow-right me-2"></i>{{ __('Logout') }}
                            </a>

                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>
