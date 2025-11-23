<x-laravel-ui-adminlte::adminlte-layout>
    @push('third_party_stylesheets')
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/x-icon" href="">
        <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="{{ asset('css/app-styles.css') }}">
        <link rel="stylesheet" href="{{ asset('css/navbar-modern.css') }}">
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
                            <span class="pushmenu-icon" aria-hidden="true"></span>
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
