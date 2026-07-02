@php
    $user = auth()->user();
    $initials = collect(explode(' ', $user->name))
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');
@endphp

<header class="topbar">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-link text-dark d-lg-none p-0" id="sidebarToggle" type="button">
            <i class="bi bi-list fs-4"></i>
        </button>
        <h5 class="mb-0 text-muted d-none d-md-block">@yield('title', 'Dashboard')</h5>
    </div>

    <div class="dropdown">
        <button class="btn btn-link text-dark text-decoration-none dropdown-toggle d-flex align-items-center gap-2"
                type="button"
                data-bs-toggle="dropdown"
                aria-expanded="false">
            <div class="user-avatar">{{ strtoupper($initials) }}</div>
            <div class="text-start d-none d-sm-block">
                <div class="fw-semibold small">{{ $user->name }}</div>
                <div class="text-muted" style="font-size: 0.75rem;">{{ $user->role->label() }}</div>
            </div>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow">
            <li>
                <span class="dropdown-item-text">
                    <strong>{{ $user->name }}</strong><br>
                    <small class="text-muted">{{ $user->email }}</small><br>
                    <small class="text-muted">{{ $user->phone }}</small>
                </span>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <span class="dropdown-item-text small">
                    Status:
                    <span class="badge {{ $user->status->value === 'aktif' ? 'bg-success' : 'bg-secondary' }}">
                        {{ $user->status->label() }}
                    </span>
                </span>
            </li>
            @if ($user->last_login_at)
                <li>
                    <span class="dropdown-item-text small text-muted">
                        Login terakhir: {{ $user->last_login_at->format('d M Y H:i') }}
                    </span>
                </li>
            @endif
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </button>
                </form>
            </li>
        </ul>
    </div>
</header>
