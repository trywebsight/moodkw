<header class="menu-header">
    <div class="menu-header-inner">
        <a href="{{ route('home') }}" class="menu-header-logo">
            @if ($logo)
                <img src="{{ $logo }}" alt="{{ $name }}" width="40" height="40">
            @endif
            <span class="menu-header-name">{{ $name }}</span>
        </a>
        <div class="menu-lang">
            <a href="{{ route('locale.switch', 'en') }}" @class(['is-active' => app()->getLocale() === 'en'])">EN</a>
            <a href="{{ route('locale.switch', 'ar') }}" @class(['is-active' => app()->getLocale() === 'ar'])">عربي</a>
        </div>
    </div>
</header>
