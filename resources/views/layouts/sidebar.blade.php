<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="{{ route('home') }}" class="brand-link brand-link-elevated d-flex align-items-center">
        <span class="mr-2">
            <img src="{{ asset('img/mintapererp.svg') }}"
                 alt="{{ config('app.name') }} logo"
                 class="brand-image brand-image-rounded">
        </span>
        <span class="brand-text-wrapper">
            <span class="brand-title d-block">
                <span class="brand-title-primary">TAP</span>
                <span class="brand-title-accent">ERP</span>
            </span>
            <small class="brand-tagline">Gestión integrada</small>
        </span>
    </a>

    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                @include('layouts.menu')
            </ul>
        </nav>
    </div>

</aside>
