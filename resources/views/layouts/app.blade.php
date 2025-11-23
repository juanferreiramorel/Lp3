<x-laravel-ui-adminlte::adminlte-layout>
    @push('third_party_stylesheets')
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/x-icon" href="">
        <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
        <style>
            .select2-container .select2-selection--single {
                box-sizing: border-box;
                cursor: pointer;
                display: block;
                height: 38px;
                user-select: none;
                -webkit-user-select: none;
            }

            .select2-container--default .select2-selection--multiple .select2-selection__rendered {
                box-sizing: border-box;
                list-style: none;
                margin: 0;
                padding: 4px 5px;
                width: 100%;
            }

            .select2-container--default .select2-selection--multiple .select2-selection__choice {
                background-color: #3c8dbc;
                border-color: #367fa9;
                padding: 1px 10px;
                color: #fff;
            }

            .select2-container--default .select2-selection--single {
                background-color: #fff;
                border-radius: 3px;
            }

            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 35px;
                position: absolute;
                top: 1px;
                right: 1px;
                width: 20px;
            }

            :root {
                --nav-ink: #0f172a;
                --nav-sky: #48c6ef;
                --nav-mint: #10b981;
                --nav-cloud: #f8fafc;
                --nav-border: #dce3f0;
            }

            /* Sidebar brand adjustments */
            .brand-link-elevated {
                padding: 14px 18px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                background: radial-gradient(circle at 20% 40%, rgba(255, 255, 255, 0.12), transparent 60%);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
            }
            .brand-logo-wrapper {
                width: 42px;
                height: 42px;
                border-radius: 14px;
                background: rgba(255, 255, 255, 0.12);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 4px;
                box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
            }
            .brand-image-rounded {
                width: 100%;
                height: 100%;
                object-fit: contain;
                display: block;
            }
            .brand-text-wrapper {
                line-height: 1.1;
            }
            .brand-title {
                font-weight: 700;
                letter-spacing: 0.04em;
                font-size: 1rem;
            }
            .brand-title-primary { color: #5cd0ff; }
            .brand-title-accent { color: #1dd3a8; }
            .brand-tagline {
                color: rgba(255, 255, 255, 0.7);
                font-size: 0.72rem;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            /* ===== ESTILOS MODERNOS PARA SIDEBAR ===== */
            .modern-sidebar {
                background: linear-gradient(180deg, #1a1d29 0%, #252a3d 100%);
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.15);
            }

            /* Buscador del sidebar */
            .sidebar-search-wrapper {
                position: relative;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                margin-bottom: 10px;
            }

            .sidebar-search-input .form-control {
                background-color: rgba(255, 255, 255, 0.08);
                border: 1px solid rgba(255, 255, 255, 0.12);
                color: #fff;
                border-radius: 8px;
                padding: 8px 12px;
                transition: all 0.3s ease;
            }

            .sidebar-search-input .form-control:focus {
                background-color: rgba(255, 255, 255, 0.12);
                border-color: #5cd0ff;
                box-shadow: 0 0 0 0.2rem rgba(92, 208, 255, 0.25);
                color: #fff;
            }

            .sidebar-search-input .form-control::placeholder {
                color: rgba(255, 255, 255, 0.5);
            }

            .sidebar-search-input .input-group-text {
                background-color: transparent;
                border: 1px solid rgba(255, 255, 255, 0.12);
                border-left: 0;
                color: rgba(255, 255, 255, 0.6);
                border-radius: 0 8px 8px 0;
            }

            /* Resultados de búsqueda */
            .search-results-dropdown {
                position: absolute;
                top: 100%;
                left: 15px;
                right: 15px;
                background: #2d3348;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                margin-top: 8px;
                max-height: 300px;
                overflow-y: auto;
                z-index: 1000;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            .search-result-item {
                display: flex;
                align-items: center;
                padding: 10px 15px;
                color: #fff;
                text-decoration: none;
                transition: all 0.2s ease;
                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            }

            .search-result-item:last-child {
                border-bottom: none;
            }

            .search-result-item:hover {
                background-color: rgba(92, 208, 255, 0.15);
                padding-left: 20px;
            }

            .search-result-item i {
                margin-right: 10px;
                width: 20px;
                text-align: center;
                color: #5cd0ff;
            }

            .search-result-item strong {
                color: #1dd3a8;
                font-weight: 600;
            }

            .search-no-results {
                padding: 15px;
                text-align: center;
                color: rgba(255, 255, 255, 0.6);
                font-size: 0.9rem;
            }

            /* Estilos mejorados para items del menú */
            .modern-sidebar .nav-sidebar .nav-link {
                border-radius: 8px;
                margin: 4px 8px;
                padding: 10px 15px;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }

            .modern-sidebar .nav-sidebar .nav-link::before {
                content: '';
                position: absolute;
                left: 0;
                top: 0;
                height: 100%;
                width: 3px;
                background: linear-gradient(180deg, #5cd0ff 0%, #1dd3a8 100%);
                transform: scaleY(0);
                transition: transform 0.3s ease;
            }

            .modern-sidebar .nav-sidebar .nav-link:hover {
                background-color: rgba(255, 255, 255, 0.08);
                padding-left: 20px;
            }

            .modern-sidebar .nav-sidebar .nav-link:hover::before {
                transform: scaleY(1);
            }

            .modern-sidebar .nav-sidebar .nav-link.active {
                background: linear-gradient(90deg, rgba(92, 208, 255, 0.2) 0%, rgba(29, 211, 168, 0.1) 100%);
                color: #5cd0ff;
                font-weight: 600;
                box-shadow: 0 2px 8px rgba(92, 208, 255, 0.2);
            }

            .modern-sidebar .nav-sidebar .nav-link.active::before {
                transform: scaleY(1);
            }

            .modern-sidebar .nav-sidebar .nav-link.active i {
                color: #1dd3a8;
            }

            /* Menú treeview mejorado */
            .modern-sidebar .nav-treeview {
                background-color: rgba(0, 0, 0, 0.15);
                border-radius: 8px;
                margin: 4px 8px 8px 8px;
                padding: 8px 0;
            }

            .modern-sidebar .nav-treeview .nav-link {
                margin: 2px 8px;
                padding-left: 40px;
                font-size: 0.9rem;
            }

            .modern-sidebar .nav-treeview .nav-link i {
                font-size: 0.85rem;
            }

            /* Iconos del menú */
            .modern-sidebar .nav-icon {
                margin-right: 10px;
                font-size: 1rem;
                color: rgba(255, 255, 255, 0.7);
                transition: color 0.3s ease;
            }

            .modern-sidebar .nav-link:hover .nav-icon {
                color: #5cd0ff;
            }

            /* Scrollbar personalizado para el sidebar */
            .modern-sidebar .sidebar::-webkit-scrollbar {
                width: 6px;
            }

            .modern-sidebar .sidebar::-webkit-scrollbar-track {
                background: rgba(0, 0, 0, 0.1);
            }

            .modern-sidebar .sidebar::-webkit-scrollbar-thumb {
                background: rgba(255, 255, 255, 0.2);
                border-radius: 3px;
            }

            .modern-sidebar .sidebar::-webkit-scrollbar-thumb:hover {
                background: rgba(255, 255, 255, 0.3);
            }

            /* Animación para los items del menú */
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateX(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }

            .modern-sidebar .nav-item {
                animation: slideIn 0.3s ease forwards;
            }

            .modern-sidebar .nav-item:nth-child(1) { animation-delay: 0.05s; }
            .modern-sidebar .nav-item:nth-child(2) { animation-delay: 0.1s; }
            .modern-sidebar .nav-item:nth-child(3) { animation-delay: 0.15s; }
            .modern-sidebar .nav-item:nth-child(4) { animation-delay: 0.2s; }
            .modern-sidebar .nav-item:nth-child(5) { animation-delay: 0.25s; }

            /* Responsive */
            @media (max-width: 768px) {
                .sidebar-search-wrapper {
                    padding: 10px !important;
                }
            }

            /* ===== ESTILOS MODERNOS PARA NAVBAR ===== */
            .main-header.navbar {
                --halo: rgba(72, 198, 239, 0.22);
                background:
                    radial-gradient(circle at 12% 18%, var(--halo), transparent 32%),
                    radial-gradient(circle at 86% 10%, rgba(16, 185, 129, 0.2), transparent 26%),
                    linear-gradient(125deg, #ffffff 0%, #f5f7fb 55%, #eaf3ff 100%);
                border: 1px solid rgba(220, 227, 240, 0.9);
                box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
                padding: 10px 22px;
                height: 68px;
                backdrop-filter: blur(10px);
                position: relative;
                overflow: hidden;
            }

            .main-header.navbar::after {
                content: '';
                position: absolute;
                inset: 0;
                background: linear-gradient(120deg, rgba(72, 198, 239, 0.08) 0%, rgba(16, 185, 129, 0.08) 40%, transparent 70%);
                pointer-events: none;
                mix-blend-mode: screen;
            }

            .main-header.navbar .nav-item {
                position: relative;
                z-index: 1;
            }

            /* Boton de menu hamburguesa */
            .main-header .nav-link[data-widget="pushmenu"] {
                width: 44px;
                height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 14px;
                background: linear-gradient(135deg, var(--nav-sky) 0%, var(--nav-mint) 100%);
                color: white !important;
                transition: all 0.3s ease;
                box-shadow: 0 6px 18px rgba(72, 198, 239, 0.35);
                border: 1px solid rgba(255, 255, 255, 0.35);
            }

            .main-header .nav-link[data-widget="pushmenu"]:hover {
                transform: translateY(-2px) scale(1.02);
                box-shadow: 0 10px 26px rgba(72, 198, 239, 0.45);
            }

            .main-header .nav-link[data-widget="pushmenu"] i {
                font-size: 18px;
            }

            /* Titulo de contenido en navbar */
            .navbar-text {
                font-size: 1.1rem;
                font-weight: 700;
                color: var(--nav-ink);
                display: flex;
                align-items: center;
                letter-spacing: 0.01em;
            }

            /* Buscador del navbar */
            .form-control-navbar {
                background-color: rgba(255, 255, 255, 0.85);
                border: 1.5px solid #d5dbe7;
                border-radius: 14px;
                padding: 10px 16px;
                color: var(--nav-ink);
                transition: all 0.3s ease;
                min-width: 260px;
                box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
                backdrop-filter: blur(4px);
            }

            .form-control-navbar:focus {
                background-color: #fff;
                border-color: var(--nav-sky);
                box-shadow: 0 0 0 4px rgba(72, 198, 239, 0.18);
                color: var(--nav-ink);
            }

            .form-control-navbar::placeholder {
                color: #94a3b8;
            }

            .btn-navbar {
                background: linear-gradient(135deg, var(--nav-sky) 0%, var(--nav-mint) 100%);
                border: none;
                border-radius: 12px;
                color: white;
                padding: 10px 16px;
                margin-left: 10px;
                transition: all 0.3s ease;
                box-shadow: 0 8px 18px rgba(72, 198, 239, 0.25);
                font-weight: 600;
            }

            .btn-navbar:hover {
                transform: translateY(-2px) scale(1.01);
                box-shadow: 0 12px 26px rgba(72, 198, 239, 0.35);
                color: white;
            }

            /* Items del navbar derecho */
            .navbar-nav.ml-auto .nav-item .nav-link {
                color: #475569;
                border-radius: 12px;
                padding: 10px 14px;
                margin: 0 4px;
                transition: all 0.25s ease;
                position: relative;
                background: rgba(255, 255, 255, 0.75);
                border: 1px solid rgba(220, 227, 240, 0.9);
                box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
                backdrop-filter: blur(6px);
            }

            .navbar-nav.ml-auto .nav-item .nav-link:hover {
                background: linear-gradient(135deg, rgba(72, 198, 239, 0.12) 0%, rgba(16, 185, 129, 0.12) 100%);
                color: var(--nav-sky);
                transform: translateY(-2px);
                border-color: rgba(72, 198, 239, 0.4);
            }

            .navbar-nav.ml-auto .nav-item .nav-link i {
                font-size: 18px;
            }

            /* Boton de ayuda */
            .navbar-nav .nav-item .btn-link {
                color: #475569;
                text-decoration: none;
                border-radius: 12px;
                padding: 10px 16px;
                transition: all 0.25s ease;
                border: 1px solid transparent;
            }

            .navbar-nav .nav-item .btn-link:hover {
                background: linear-gradient(135deg, rgba(72, 198, 239, 0.12) 0%, rgba(16, 185, 129, 0.12) 100%);
                color: var(--nav-sky);
                transform: translateY(-2px);
                border-color: rgba(72, 198, 239, 0.4);
            }

            /* Boton de pantalla completa */
            .nav-link[data-widget="fullscreen"] {
                position: relative;
            }

            .nav-link[data-widget="fullscreen"]:hover {
                background: linear-gradient(135deg, rgba(92, 208, 255, 0.12) 0%, rgba(29, 211, 168, 0.12) 100%);
            }

            /* Dark mode toggle */
            #darkModeToggle {
                position: relative;
            }

            #darkModeToggle:hover {
                background: linear-gradient(135deg, rgba(92, 208, 255, 0.12) 0%, rgba(29, 211, 168, 0.12) 100%);
            }

            /* Menu de usuario */
            .user-menu .nav-link {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 8px 18px;
                border-radius: 30px;
                background: rgba(15, 23, 42, 0.035);
                border: 1.5px solid var(--nav-border);
                transition: all 0.3s ease;
                box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
                color: var(--nav-ink);
                font-weight: 600;
            }

            .user-menu .nav-link:hover {
                background: linear-gradient(135deg, rgba(72, 198, 239, 0.12) 0%, rgba(16, 185, 129, 0.12) 100%);
                border-color: rgba(72, 198, 239, 0.45);
                transform: translateY(-2px);
                box-shadow: 0 12px 28px rgba(72, 198, 239, 0.25);
            }

            .user-menu .nav-link .user-name {
                color: var(--nav-ink);
                letter-spacing: 0.01em;
            }

            .user-avatar {
                width: 38px;
                height: 38px;
                border-radius: 14px;
                padding: 3px;
                background: conic-gradient(from 210deg, #48c6ef, #06b6d4, #10b981, #48c6ef);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                position: relative;
                box-shadow: 0 8px 18px rgba(72, 198, 239, 0.28);
                animation: auroraShift 8s ease infinite;
            }

            .user-avatar-img {
                width: 100%;
                height: 100%;
                border-radius: 11px;
                object-fit: cover;
                background: #fff;
                box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18);
            }

            .user-status-badge {
                position: absolute;
                width: 9px;
                height: 9px;
                background: #22c55e;
                border: 2px solid #fff;
                border-radius: 50%;
                bottom: 4px;
                right: 4px;
                box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15);
            }

            .user-menu .dropdown-toggle::after {
                margin-left: 10px;
                color: var(--nav-sky);
            }

            /* Dropdown del usuario */
            .user-menu .dropdown-menu {
                border: 1px solid rgba(220, 227, 240, 0.85);
                border-radius: 16px;
                box-shadow: 0 14px 40px rgba(15, 23, 42, 0.15);
                margin-top: 10px;
                overflow: hidden;
                backdrop-filter: blur(10px);
            }

            .user-menu .user-header {
                background: linear-gradient(135deg, #0f172a 0%, #0ea5e9 50%, #10b981 100%);
                padding: 28px;
                text-align: center;
            }

            .user-avatar-img-lg {
                width: 88px;
                height: 88px;
                border-radius: 24px;
                object-fit: cover;
                background: #fff;
                padding: 6px;
                box-shadow: 0 10px 28px rgba(15, 23, 42, 0.25);
            }

            .user-menu .user-header p {
                color: white;
                margin-top: 16px;
                font-weight: 700;
                font-size: 1.1rem;
                letter-spacing: 0.01em;
            }

            .user-menu .user-header small {
                color: rgba(255, 255, 255, 0.92);
                font-size: 0.88rem;
            }

            .user-menu .user-footer {
                padding: 16px;
                background-color: #f8fafc;
                display: flex;
                justify-content: space-between;
                gap: 10px;
            }

            .user-menu .user-footer .btn {
                border-radius: 10px;
                padding: 10px 20px;
                font-weight: 600;
                transition: all 0.25s ease;
                border: 1.5px solid #e2e8f0;
                color: var(--nav-ink);
                background: white;
            }

            .user-menu .user-footer .btn:hover {
                background: linear-gradient(135deg, var(--nav-sky) 0%, var(--nav-mint) 100%);
                color: white;
                border-color: transparent;
                transform: translateY(-1px);
                box-shadow: 0 10px 22px rgba(72, 198, 239, 0.2);
            }

            /* Badge de notificaciones (si se agrega en el futuro) */
            .navbar-badge {
                position: absolute;
                top: 5px;
                right: 5px;
                font-size: 0.6rem;
                padding: 2px 5px;
                border-radius: 10px;
                background: linear-gradient(135deg, #ff6b6b 0%, #ff4757 100%);
                color: white;
                box-shadow: 0 2px 8px rgba(255, 75, 87, 0.4);
            }

            /* Animaciones */
            @keyframes auroraShift {
                0% { transform: rotate(0deg); }
                50% { transform: rotate(180deg); }
                100% { transform: rotate(360deg); }
            }

            @keyframes navPulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }

            .nav-link[data-widget="pushmenu"]:active {
                animation: navPulse 0.3s ease;
            }

            /* Responsive navbar */
            @media (max-width: 768px) {
                .main-header.navbar {
                    padding: 8px 12px;
                    height: 64px;
                }

                .form-control-navbar {
                    min-width: 160px;
                    font-size: 0.9rem;
                }
                .btn-navbar {
                    padding: 10px 12px;
                    margin-left: 8px;
                }

                .navbar-text {
                    font-size: 0.95rem;
                }

                .user-menu .nav-link span {
                    display: none !important;
                }

                .user-avatar {
                    width: 32px;
                    height: 32px;
                }
            }

            /* Efecto glassmorphism opcional */
            @supports (backdrop-filter: blur(10px)) {
                .main-header.navbar {
                    background: rgba(255, 255, 255, 0.9);
                    backdrop-filter: blur(12px);
                }
            }
        </style>
    @endpush
    
    <body class="hold-transition sidebar-mini layout-fixed text-sm">
        @php
            $helpTopicsForRoute = ($helpTopicsForRoute ?? collect());
            $hasHelpTopics = $helpTopicsForRoute->isNotEmpty();
            $firstHelpTopic = $hasHelpTopics ? $helpTopicsForRoute->first() : null;
            $helpTopicsCount = $helpTopicsForRoute->count();
        @endphp
        <div class="wrapper">

            <!-- NAVBAR -->
            <nav class="main-header navbar navbar-expand navbar-white navbar-light" role="navigation" aria-label="Barra de navegación principal">
                <!-- Left -->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Alternar menú lateral">
                            <i class="fas fa-bars"></i>
                        </a>
                    </li>
                    @hasSection('content_header')
                        <li class="nav-item d-none d-md-inline-block">
                            <span class="navbar-text ml-2">@yield('content_header')</span>
                        </li>
                    @endif
                </ul>

                <!-- SEARCH -->
                <form class="form-inline ml-3 d-none d-md-flex" method="GET" action="{{ route('search') }}">
                    <div class="input-group input-group-sm">
                        <input class="form-control form-control-navbar" type="search" name="q"
                               placeholder="Buscar…" aria-label="Buscar">
                        <div class="input-group-append">
                            <button class="btn btn-navbar" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Right -->
                <ul class="navbar-nav ml-auto align-items-center">

                    @if ($hasHelpTopics)
                        <li class="nav-item d-none d-sm-inline-block">
                            <button class="nav-link btn btn-link p-0" type="button"
                                    data-toggle="modal" data-target="#helpTopicModal"
                                    aria-label="Abrir ayuda">
                                <i class="fas fa-question-circle"></i> Ayuda 
                            </button>
                        </li>
                    @endif

                    <!-- Fullscreen -->
                    <li class="nav-item d-none d-sm-inline-block">
                        <a class="nav-link" data-widget="fullscreen" href="#" role="button" aria-label="Pantalla completa">
                            <i class="fas fa-expand-arrows-alt"></i>
                        </a>
                    </li>

                    <!-- Dark mode toggle -->
                    <li class="nav-item d-none d-sm-inline-block">
                        <a class="nav-link" href="#" id="darkModeToggle" aria-label="Alternar modo oscuro">
                            <i class="far fa-moon"></i>
                        </a>
                    </li>

                    <!-- NOTIFICACIONES (removido) -->
                    {{-- <li class="nav-item dropdown"> ... </li> --}}

                    <!-- User -->
                    <li class="nav-item dropdown user-menu">
                        <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Menú de usuario">
                            <img src="{{ Auth::user()->avatar_url ?? 'https://assets.infyom.com/logo/blue_logo_150x150.png' }}"
                                 class="user-image img-circle elevation-2" alt="User Image">
                            <span class="d-none d-md-inline">{{ Auth::user()->name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                            <li class="user-header bg-primary">
                                <img src="{{ Auth::user()->avatar_url ?? 'https://assets.infyom.com/logo/blue_logo_150x150.png' }}"
                                     class="img-circle elevation-2" alt="User Image">
                                <p>
                                    {{ Auth::user()->name }}
                                    <small>Miembro desde {{ Auth::user()->created_at?->format('M. Y') }}</small>
                                </p>
                            </li>
                            <li class="user-footer">
                                <a href="{{ url('perfil') }}" class="btn btn-default btn-flat">Perfil</a>
                                <a href="#" class="btn btn-default btn-flat float-right"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    Salir
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </nav>

            <!-- SIDEBAR -->
            @include('layouts.sidebar')

            <!-- CONFIRM DELETE MODAL -->
            @include('components.confirm-delete-modal')
            <script defer src="{{ asset('js/modal-confirm.js') }}"></script>
            <!-- CONFIRM MODAL -->
            @include('components.confirm-modal')

            <!-- CONTENT WRAPPER -->
            <div class="content-wrapper">
                @yield('content')
            </div>

            <!-- FOOTER -->
            <footer class="main-footer">
                <div class="float-right d-none d-sm-inline">
                    <b>Versión</b> {{ config('app.version', '3.1.0') }}
                </div>
                <strong>&copy; {{ now()->year }} {{ config('app.name', 'Aplicación') }}.</strong> Todos los derechos reservados.
        </footer>
    </div>

    @include('components.help-modal', [
        'hasHelpTopics' => $hasHelpTopics,
        'helpTopicsCount' => $helpTopicsCount,
        'helpTopicsForRoute' => $helpTopicsForRoute,
        'firstHelpTopic' => $firstHelpTopic,
    ])

    <!-- REQUIRED SCRIPTS (Cargar primero jQuery y Bootstrap) -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
        <!-- AdminLTE App -->
        <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.1.0/dist/js/adminlte.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <!-- Verificar que Swal esté disponible -->
        <script>
            if (typeof Swal === 'undefined') {
                console.error('SweetAlert2 no se cargó correctamente');
            }
        </script>

        <!-- librerias js select2 -->
        <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

        <!-- Sweet Alert Script -->
        @include('sweetalert::alert', ['cdn' => false])

        <!-- Dark mode toggle -->
        <script>
            (function () {
                const key = 'adminlte-theme';
                const body = document.body;
                const toggle = document.getElementById('darkModeToggle');

                const saved = localStorage.getItem(key);
                if (saved === 'dark') body.classList.add('dark-mode');

                if (toggle) {
                    toggle.addEventListener('click', function (e) {
                        e.preventDefault();
                        body.classList.toggle('dark-mode');
                        localStorage.setItem(key, body.classList.contains('dark-mode') ? 'dark' : 'light');
                    });
                }
            })();
        </script>

        <!-- Inicializar AdminLTE y Bootstrap dropdowns -->
        <script>
            $(document).ready(function() {
                // Asegurar que los dropdowns de Bootstrap funcionen correctamente
                $('.dropdown-toggle').each(function() {
                    $(this).dropdown();
                });

                // Fallback para PushMenu si AdminLTE no est� disponible
                (function initPushMenuFallback() {
                    const pluginAvailable = $.fn && typeof $.fn.PushMenu === 'function';
                    if (pluginAvailable) {
                        return; // AdminLTE ya maneja el toggle mediante data-widget
                    }

                    const $body = $('body');
                    const $toggles = $('[data-widget="pushmenu"]');

                    const handleSidebarToggle = () => {
                        const isMobile = window.matchMedia('(max-width: 991.98px)').matches;

                        if (isMobile) {
                            $body.toggleClass('sidebar-open');
                            $body.removeClass('sidebar-collapse');
                        } else {
                            $body.toggleClass('sidebar-collapse');
                            $body.removeClass('sidebar-open');
                        }
                    };

                    $toggles.off('click').on('click', function(e) {
                        e.preventDefault();
                        handleSidebarToggle();
                    });
                })();

                // Asegurar que el dropdown del usuario funcione
                $('.user-menu .dropdown-toggle').off('click').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var $parent = $(this).parent();
                    var isOpen = $parent.hasClass('show');

                    // Cerrar todos los dropdowns
                    $('.dropdown').removeClass('show');
                    $('.dropdown-menu').removeClass('show');

                    // Toggle el dropdown actual
                    if (!isOpen) {
                        $parent.addClass('show');
                        $parent.find('.dropdown-menu').addClass('show');
                    }
                });

                // Cerrar dropdown al hacer clic fuera
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('.dropdown').length) {
                        $('.dropdown').removeClass('show');
                        $('.dropdown-menu').removeClass('show');
                    }
                });

                // Prevenir que el dropdown se cierre al hacer clic dentro
                $('.dropdown-menu').on('click', function(e) {
                    e.stopPropagation();
                });
            });
        </script>

        <!-- cargar codigo javascript desde los blade -->
        @stack('scripts')

        <!-- CUSTOM SCRIPTS -->
        <script>
            $(document).ready(function() {
                // Inicializar select2 en los elementos con la clase .select2
                $('.select2').select2({
                    placeholder: "Selecciona una opción", // Placeholder
                    allowClear: true, // Permite limpiar la selección
                    width: '100%' // Ancho del select
                });

                //sweetalert para confirmacion de borrado - usa delegación de eventos para AJAX
                $(document).on('click', '.alert-delete', function(event) {
                    var form = $(this).closest("form");
                    event.preventDefault();
                    let valor = $(this).data("mensaje") ||
                    "este registro"; // Valor por defecto si no hay data-mensaje
                    Swal.fire({
                            title: "Atención",
                            text: `Desea borrar ${valor}?`, // valor recibido de data-mensaje del boton borrar
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: "Confirmar",
                            cancelButtonText: "Cancelar",
                        })
                        .then(resultado => {
                            if (resultado.value) {
                                form.submit();
                            }
                        });
                });

                // Función para manejar confirmación de eliminación en Livewire
                window.handleLivewireDelete = function(recordId, button) {
                    Swal.fire({
                        title: "Atención",
                        text: "¿Está seguro que desea eliminar este registro?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: "Confirmar",
                        cancelButtonText: "Cancelar",
                    }).then(resultado => {
                        if (resultado.value) {
                            // Disparar el evento Livewire
                            const event = new CustomEvent('livewire:emit', {
                                detail: ['deleteRecord', recordId]
                            });
                            button.dispatchEvent(event);
                        }
                    });
                };

                /** bucador mediante peticiones fetch*/
                $('.buscar').on('keyup', function() {
                    var query = this.value; // valor del input buscar
                    var searchMode = $('.search-mode').val() || 'all'; // obtener modo de búsqueda
                    // Obtener el data-url del parametro input
                    var url = this.getAttribute('data-url');
                    // Fetch para realizar peticion de busqueda
                    fetch(url + '?buscar=' + encodeURIComponent(query) + '&search_mode=' + searchMode, {
                                method: 'GET',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest' // Este encabezado indica una solicitud AJAX
                                }
                            })
                        // respuesta del servidor
                        .then(response => {
                            if (!response.ok) {
                                alert('Error en la consulta');
                                throw new Error('Error en la respuesta del servidor');
                            }
                            // se espera un HTML como respuesta
                            return response.text();
                        })
                        .then(data => {
                            // cargar devuelta el html tabla según lo filtrado
                            $('.tabla-container').html(data);
                        })
                        .catch(error => { // manejar si hay errores en la consulta
                            console.error('Hubo un problema con la solicitud Fetch:', error);
                        });
                });

                // Trigger search cuando cambia el modo de búsqueda
                $(document).on('change', '.search-mode', function() {
                    if ($('.buscar').val()) {
                        $('.buscar').trigger('keyup');
                    }
                });

                // FUNCIONALIDAD: Click en cabecera ID para activar búsqueda por ID
                $(document).on('click', 'table thead tr th:first-child', function() {
                    console.log('Click en cabecera de columna ID');

                    // Buscar el input de búsqueda
                    var $searchInput = $('.buscar');
                    var $searchMode = $('.search-mode');

                    if ($searchInput.length && $searchMode.length) {
                        // Cambiar el modo de búsqueda a "Solo ID"
                        $searchMode.val('id');

                        // Hacer scroll al input de búsqueda y enfocarlo
                        $('html, body').animate({
                            scrollTop: $searchInput.offset().top - 100
                        }, 300);

                        // Enfocar el input con un pequeño delay
                        setTimeout(function() {
                            $searchInput.focus();
                            // Seleccionar todo el texto si hay algo
                            if ($searchInput.val()) {
                                $searchInput.select();
                            }
                        }, 350);

                        // Feedback visual
                        $searchMode.addClass('border-info');
                        setTimeout(function() {
                            $searchMode.removeClass('border-info');
                        }, 1000);
                    }
                });

                // Agregar cursor pointer y hover a la cabecera de ID
                $(document).on('mouseenter', 'table thead tr th:first-child', function() {
                    $(this).css('cursor', 'pointer');
                    $(this).css('background-color', '#d1ecf1');
                }).on('mouseleave', 'table thead tr th:first-child', function() {
                    $(this).css('background-color', '');
                });
            });

            //formato de numeros separador de miles
            function format(input) {
                // Eliminar puntos previos para evitar problemas con el replace
                var num = input.value.replace(/\./g, '');

                // Verificar si el valor es un número válido
                if (!isNaN(num)) {
                    // Invertir el string y aplicar la lógica del separador de miles
                    num = num.split('').reverse().join('') // Invertir el número
                        .replace(/(\d{3})(?=\d)/g, '$1.') // Agregar el punto cada 3 dígitos
                        .split('').reverse().join(''); // Volver a invertir

                    // Asignar el valor formateado al campo de entrada
                    input.value = num;
                } else {
                    // Mostrar alerta y limpiar caracteres no numéricos
                    alert("Por favor, introduce un número válido");
                    input.value = input.value.replace(/[^\d]/g, ''); // Limpiar cualquier carácter no numérico
                }
            }
        </script>
    </body>
</x-laravel-ui-adminlte::adminlte-layout>