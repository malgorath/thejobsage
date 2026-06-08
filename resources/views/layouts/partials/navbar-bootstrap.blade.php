{{-- resources/views/layouts/partials/navbar-bootstrap.blade.php --}}
<nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('home') }}">
            {{ config('app.name', 'Laravel') }}
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
            aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('jobs.*') ? 'active' : '' }}"
                       href="{{ route('jobs.index') }}">
                        <i class="bi bi-briefcase"></i> Job Listings
                    </a>
                </li>

                @auth
                    @if(auth()->user()->isRecruiter())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('recruiter.*') ? 'active' : '' }}"
                               href="{{ route('recruiter.jobs.index') }}">
                                <i class="bi bi-people"></i> Screening
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()->isHr())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('hr.*') ? 'active' : '' }}"
                               href="{{ route('hr.jobs.index') }}">
                                <i class="bi bi-person-lines-fill"></i> HR Review
                            </a>
                        </li>
                    @endif
                @endauth
            </ul>

            <ul class="navbar-nav ms-auto">
                @auth
                    @if(auth()->user()->isAdmin())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}"
                               href="{{ route('admin.dashboard') }}">
                                <i class="bi bi-shield-check"></i> Admin Panel
                            </a>
                        </li>
                    @endif

                    <li class="nav-item dropdown">
                        <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#"
                           role="button" data-bs-toggle="dropdown" aria-haspopup="true"
                           aria-expanded="false">
                            <i class="bi bi-person-fill"></i> {{ Auth::user()->name }}
                        </a>

                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            @if(auth()->user()->isAdmin())
                                <a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                    <i class="bi bi-shield-check"></i> Admin Dashboard
                                </a>
                                <a class="dropdown-item" href="{{ route('admin.prompts.index') }}">
                                    <i class="bi bi-braces-asterisk"></i> AI Prompts
                                </a>
                                <hr class="dropdown-divider">
                            @endif
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="bi bi-gear"></i> Settings
                            </a>
                            <hr class="dropdown-divider">
                            <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                               onclick="event.preventDefault();
                                        document.getElementById('logout-form').submit();">
                                <i class="bi bi-box-arrow-right"></i> Log Out
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}"
                                  method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </li>
                @endauth

                @guest
                    @if (Route::has('login'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                    @endif
                    @if (Route::has('register'))
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary text-white ms-2"
                               href="{{ route('register') }}"
                               style="padding: 0.375rem 1rem;">Sign Up</a>
                        </li>
                    @endif
                @endguest
            </ul>
        </div>
    </div>
</nav>
