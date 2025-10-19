    @if(Auth::check())
            @if (Auth::user()->super_admin === 'Y')
                <div class="super-admin">
                    <nav class="navbar navbar-expand-md navbar-dark bg-dark shadow-sm">
                        <div class="container">
                            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#adminNavbar" aria-controls="adminNavbar" 
                                aria-expanded="false" aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon"></span>
                            </button>
                
                            <div class="collapse navbar-collapse" id="adminNavbar">
                                <ul class="navbar-nav me-auto">
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('admin.trainers') }}">Προπονητές</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('admin.mountains') }}">Χιονοδρομικά</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('admin.bookings') }}">Κρατήσεις</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </nav>
                </div>
            @endif
    @endif