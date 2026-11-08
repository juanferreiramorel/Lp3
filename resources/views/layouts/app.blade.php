<x-laravel-ui-adminlte::adminlte-layout>
    @push('third_party_stylesheets')
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/x-icon" href="">
        <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
        {{-- The cache-buster is the file's own mtime: a hand-maintained ?v=
             only works while someone remembers to bump it, and a stale
             stylesheet looks exactly like a bug that was never fixed. --}}
        @php
            $cssVersion = fn ($file) => @filemtime(public_path($file)) ?: 1;
        @endphp
        <link rel="stylesheet" href="{{ asset('css/app-styles.css') }}?v={{ $cssVersion('css/app-styles.css') }}">
        <link rel="stylesheet" href="{{ asset('css/navbar-custom.css') }}?v={{ $cssVersion('css/navbar-custom.css') }}">
        {{-- Loaded last: the token layer that both files above resolve against. --}}
        <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ $cssVersion('css/theme.css') }}">

        {{-- Manifest, icons and service worker registration: makes the app
             installable on a phone at the counter. --}}
        @include('layouts.pwa')

        {{-- Resolve the theme before the first paint. Running this in <head>
             is what prevents the light flash dark-mode users used to get on
             every page load, because the old script sat at the end of <body>. --}}
        <script>
            (function () {
                try {
                    var saved = localStorage.getItem('adminlte-theme');
                    var dark = saved === 'dark'
                        || (saved === null
                            && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    if (dark) document.documentElement.classList.add('dark-mode');
                } catch (e) { /* private mode: fall through to light */ }
            })();
        </script>
    @endpush
    
    <body class="hold-transition sidebar-mini layout-fixed text-sm">
        {{-- Mirror the decision taken in <head> onto <body>, which is where
             AdminLTE and our own .dark-mode rules are keyed. Runs before any
             content is parsed, so nothing paints in the wrong theme. --}}
        <script>
            if (document.documentElement.classList.contains('dark-mode')) {
                document.body.classList.add('dark-mode');
            }
        </script>

        {{-- This stack was never rendered, so every @push('styles') in the app
             was silently discarded (home, recibossimples/detalle, fields and
             modal_cliente). The <head> is owned by the vendor component, which
             only renders its own pushes, so the stack lives here instead. --}}
        @stack('styles')

        @php
            $helpTopicsForRoute = ($helpTopicsForRoute ?? collect());
            $hasHelpTopics = $helpTopicsForRoute->isNotEmpty();
            $firstHelpTopic = $hasHelpTopics ? $helpTopicsForRoute->first() : null;
            $helpTopicsCount = $helpTopicsForRoute->count();
        @endphp
        <div class="wrapper">

            <!-- NAVBAR -->
            <nav class="main-header navbar navbar-expand shadcn-navbar" role="navigation" aria-label="Barra de navegación principal">
                <!-- Left -->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link pushmenu-btn" data-widget="pushmenu" href="#" role="button" aria-label="Alternar menú lateral">
                            <span class="hamburger-icon" aria-hidden="true">
                                <span class="line"></span>
                                <span class="line"></span>
                                <span class="line"></span>
                            </span>
                        </a>
                    </li>
                    
                    <!-- Separator -->
                    <li class="nav-item d-none d-sm-inline-block">
                        <div class="navbar-separator"></div>
                    </li>

                    @hasSection('content_header')
                        <li class="nav-item d-none d-md-inline-block">
                            <span class="navbar-text navbar-breadcrumb ml-2">
                                <span class="text-muted">Dashboard</span> 
                                <span class="mx-1">/</span>
                                <span class="text-primary">@yield('content_header')</span>
                            </span>
                        </li>
                    @endif

                    @php
                        $user = auth()->user();
                        $shortcuts = [];
                        $modeText = 'Recientes';

                        if ($user && $user->shortcut_mode === 'manual') {
                            $modeText = 'Accesos Directos';
                            $savedShortcuts = json_decode($user->shortcuts) ?? [];
                            // Re-map titles for manual shortcuts (could be optimized)
                            $titles = [
                                'ventas.index' => 'Ventas',
                                'apertura-cierre-caja.index' => 'Arqueo de Caja',
                                'pedidos.index' => 'Pedidos',
                                'clientes.index' => 'Clientes',
                                'cuentasacobrar.index' => 'Cuentas a Cobrar',
                                'recibossimples.index' => 'Recibos Simples',
                                'compras.index' => 'Compras',
                                'ordencompra.index' => 'Ordenes de Compra',
                                'proveedores.index' => 'Proveedores',
                                'cuentasapagar.index' => 'Cuentas a Pagar',
                                'productos.index' => 'Productos',
                                'stock.index' => 'Stock',
                                'marcas.index' => 'Marcas',
                                'plantillas_contratos.index' => 'Plantillas Contratos',
                                'users.index' => 'Usuarios',
                                'sucursales.index' => 'Sucursales',
                            ];
                            
                            foreach ($savedShortcuts as $route) {
                                if (Route::has($route)) {
                                    $shortcuts[] = [
                                        'url' => route($route),
                                        'title' => $titles[$route] ?? $route
                                    ];
                                }
                            }
                        } else {
                            $shortcuts = session('recent_access', []);
                        }
                    @endphp

                </ul>

                <!-- SEARCH -->
                <form class="ml-3 d-none d-md-flex align-items-center" method="GET" action="{{ route('search') }}">
                    <div class="input-group input-group-sm search-input-group">
                        <div class="input-group-prepend">
                            <span class="search-icon-wrapper">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                        <input class="form-control form-control-navbar" type="search" name="q"
                               placeholder="Buscar..." aria-label="Buscar">
                    </div>
                </form>

                <!-- Shortcuts (After Search) -->
                @if(count($shortcuts) > 0)
                    <ul class="navbar-nav ml-2">
                        @foreach($shortcuts as $access)
                            <li class="nav-item d-none d-md-block mr-1">
                                <a href="{{ $access['url'] }}" class="navbar-btn-icon" title="{{ $access['title'] }}">
                                    <span>{{ $access['title'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <!-- Right -->
                <ul class="navbar-nav ml-auto align-items-center">

                    @if ($hasHelpTopics)
                        <li class="nav-item">
                            <button class="nav-link btn btn-link navbar-btn-icon" type="button"
                                    data-toggle="modal" data-target="#helpTopicModal"
                                    aria-label="Abrir ayuda"
                                    title="Ayuda">
                                <i class="fas fa-question-circle"></i>
                                <span class="d-none d-lg-inline ml-1">Ayuda</span>
                            </button>
                        </li>
                    @endif

                    <!-- Fullscreen -->
                    <li class="nav-item">
                        <a class="nav-link navbar-btn-icon" data-widget="fullscreen" href="#" role="button"
                           aria-label="Pantalla completa" title="Pantalla completa">
                            <i class="fas fa-expand-arrows-alt"></i>
                        </a>
                    </li>

                    <!-- Dark mode toggle -->
                    <li class="nav-item">
                        <a class="nav-link navbar-btn-icon" href="#" id="darkModeToggle"
                           aria-label="Alternar modo oscuro" title="Modo oscuro">
                            <i class="far fa-moon"></i>
                        </a>
                    </li>

                    <!-- User -->
                    <li class="nav-item dropdown user-menu">
                        <a href="#" class="nav-link dropdown-toggle user-dropdown-toggle"
                           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                           aria-label="Menú de usuario">
                            <div class="user-avatar-wrapper">
                                @if(Auth::user()->avatar_url)
                                    <img src="{{ Auth::user()->avatar_url }}"
                                         class="user-image img-circle" alt="User Image">
                                @else
                                    <div class="user-avatar-letter user-image img-circle" aria-hidden="true">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                                @endif
                            </div>
                            <span class="d-none d-md-inline ml-2">{{ Auth::user()->name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                            <!-- User Header (List Style) -->
                            <li class="user-header-list">
                                <div class="d-flex align-items-center px-3 py-3">
                                    @if(Auth::user()->avatar_url)
                                        <img src="{{ Auth::user()->avatar_url }}" class="user-image-dropdown mr-3" alt="User Image">
                                    @else
                                        <div class="user-avatar-letter user-image-dropdown mr-3" aria-hidden="true">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                                    @endif
                                    <div class="d-flex flex-column text-truncate">
                                        <span class="user-name-dropdown text-truncate">{{ Auth::user()->name }}</span>
                                        <small class="user-role-dropdown text-muted text-truncate">Miembro desde {{ Auth::user()->created_at?->format('M. Y') }}</small>
                                    </div>
                                </div>
                            </li>
                            
                            <li class="dropdown-divider m-0"></li>
                            
                            <!-- Menu Items -->
                            <li>
                                <a href="{{ url('perfil') }}" class="dropdown-item">
                                    <i class="fas fa-user mr-2"></i> 
                                    <span>Perfil</span>
                                </a>
                            </li>
                            
                            <li class="dropdown-divider m-0"></li>
                            
                            <li>
                                <a href="#" class="dropdown-item dropdown-item-danger" 
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt mr-2"></i>
                                    <span>Cerrar Sesión</span>
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
                const root = document.documentElement;
                const body = document.body;
                const toggle = document.getElementById('darkModeToggle');

                // The theme was already resolved in <head>. This only keeps the
                // control in sync with it and writes the user's choice back.
                function paint(dark) {
                    root.classList.toggle('dark-mode', dark);
                    body.classList.toggle('dark-mode', dark);

                    if (!toggle) return;
                    const icon = toggle.querySelector('i');
                    if (icon) icon.className = dark ? 'fas fa-sun' : 'far fa-moon';
                    const label = dark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';
                    toggle.setAttribute('aria-pressed', dark ? 'true' : 'false');
                    toggle.setAttribute('aria-label', label);
                    toggle.setAttribute('title', label);
                }

                paint(root.classList.contains('dark-mode'));

                if (toggle) {
                    toggle.addEventListener('click', function (e) {
                        e.preventDefault();
                        const dark = !root.classList.contains('dark-mode');
                        paint(dark);
                        try {
                            localStorage.setItem(key, dark ? 'dark' : 'light');
                        } catch (err) { /* private mode: the choice will not persist */ }
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
            /**
             * Every simple combo in the app, initialised in one place.
             *
             * Any <select> rendered with .form-control or .select2 is picked up
             * automatically, so a new screen gets the house style without
             * adding a line of JavaScript. Opt a select out with
             * data-select2-skip when it needs to stay native.
             *
             * Call window.initSelects() again after injecting markup via AJAX.
             */
            window.initSelects = function (root) {
                if (!$.fn.select2) return;

                $(root || document)
                    .find('select.form-control, select.select2')
                    // Already initialised: Select2 marks the original element.
                    .not('.select2-hidden-accessible')
                    .not('[data-select2-skip]')
                    // The advanced-search mode picker keeps its own compact treatment.
                    .not('.search-mode-select')
                    .each(function () {
                        const $select = $(this);

                        // allowClear throws unless there is a placeholder to
                        // clear back to, and a placeholder only makes sense
                        // when an empty option exists to represent it.
                        const $empty = $select.find('option[value=""]').first();
                        const placeholder = $select.data('placeholder')
                            || ($empty.length ? $.trim($empty.text()) : '')
                            || 'Seleccioná una opción';
                        const clearable = $empty.length > 0 && !$select.prop('multiple');

                        // A search box over five options is noise, not help.
                        const options = $select.find('option').length;

                        $select.select2({
                            theme: 'bootstrap4',
                            width: '100%',
                            placeholder: placeholder,
                            allowClear: clearable,
                            minimumResultsForSearch: options > 8 ? 0 : Infinity,
                            // A dropdown opened inside a modal must live in the
                            // modal, or the overlay clips it and steals focus.
                            dropdownParent: $select.closest('.modal').length
                                ? $select.closest('.modal')
                                : $(document.body),
                        });

                        // Select2 tags its own container with `select2`. Left
                        // alone, the next pass would treat that container as a
                        // select and rebuild it into an empty combo.
                        $select.next('.select2-container').removeClass('select2');
                    });
            };

            $(document).ready(function() {
                window.initSelects();

                //sweetalert para confirmacion de borrado - usa delegación de eventos para AJAX
                $(document).on('click', '.alert-delete', function(event) {
                    var form = $(this).closest("form");
                    event.preventDefault();
                    let valor = $(this).data("mensaje") || "este registro";
                    let accion = $(this).data("accion") || "borrar";
                    
                    Swal.fire({
                            title: "Atención",
                            text: `¿Desea ${accion} ${valor}?`,
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

                //sweetalert para confirmacion de activar/inactivar - usa delegación de eventos para AJAX
                $(document).on('click', '.alert-toggle', function(event) {
                    var form = $(this).closest("form");
                    event.preventDefault();
                    let title = $(this).data("title") || "Atención";
                    let message = $(this).data("mensage") || "¿Está seguro?";
                    Swal.fire({
                            title: title,
                            text: message,
                            icon: 'question',
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
