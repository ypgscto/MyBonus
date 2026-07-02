@php
    $menuItems = config('menu.' . auth()->user()->role->value, []);
    $currentRoute = request()->route()?->getName() ?? '';
@endphp

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        BONUSKU
        <small>Aplikasi Presenter Mahasiswa PMB</small>
    </div>
    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            @foreach ($menuItems as $item)
                @php
                    $routePrefix = $item['route_prefix'] ?? null;
                    $isActive = $routePrefix && (
                        str_starts_with($currentRoute, $routePrefix)
                        || $currentRoute === $routePrefix
                    );
                    $isDisabled = empty($item['route']);
                @endphp
                <li class="nav-item">
                    @if ($isDisabled)
                        <span class="nav-link disabled">
                            <i class="bi {{ $item['icon'] }}"></i>
                            {{ $item['label'] }}
                        </span>
                    @else
                        <a href="{{ route($item['route']) }}"
                           class="nav-link {{ $isActive ? 'active' : '' }}">
                            <i class="bi {{ $item['icon'] }}"></i>
                            {{ $item['label'] }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
</aside>
