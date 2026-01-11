<aside class="main-sidebar sidebar-dark-primary elevation-4 modern-sidebar">
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
        <!-- Buscador de menús -->
        <div class="sidebar-search-wrapper px-3 py-3">
            <div class="input-group input-group-sm sidebar-search-input">
                <input type="text"
                       id="sidebar-search"
                       class="form-control"
                       placeholder="Buscar menú..."
                       autocomplete="off">
                <div class="input-group-append">
                    <span class="input-group-text bg-transparent border-left-0">
                        <i class="fas fa-search"></i>
                    </span>
                </div>
            </div>
            <div id="search-results" class="search-results-dropdown" style="display: none;"></div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false" id="sidebar-menu">
                @include('layouts.menu')
            </ul>
        </nav>
    </div>

</aside>

@push('third_party_scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('sidebar-search');
    const searchResults = document.getElementById('search-results');
    const sidebarMenu = document.getElementById('sidebar-menu');

    // Obtener todos los elementos del menú
    const menuItems = [];
    sidebarMenu.querySelectorAll('a.nav-link').forEach(link => {
        const text = link.textContent.trim();
        const icon = link.querySelector('i');
        const iconClass = icon ? icon.className : '';
        const href = link.getAttribute('href');

        if (text && href && href !== '#') {
            menuItems.push({
                text: text,
                icon: iconClass,
                href: href,
                element: link.closest('li')
            });
        }
    });

    // Función de búsqueda
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();

        if (query.length === 0) {
            searchResults.style.display = 'none';
            // Mostrar todos los elementos del menú
            sidebarMenu.querySelectorAll('.nav-item').forEach(item => {
                item.style.display = '';
            });
            return;
        }

        // Filtrar resultados
        const filtered = menuItems.filter(item =>
            item.text.toLowerCase().includes(query)
        );

        if (filtered.length > 0) {
            // Mostrar resultados en dropdown
            searchResults.innerHTML = filtered.map(item => `
                <a href="${item.href}" class="search-result-item">
                    <i class="${item.icon}"></i>
                    <span>${highlightMatch(item.text, query)}</span>
                </a>
            `).join('');
            searchResults.style.display = 'block';

            // Ocultar elementos que no coinciden en el menú principal
            sidebarMenu.querySelectorAll('.nav-item').forEach(item => {
                const link = item.querySelector('a.nav-link');
                if (link) {
                    const text = link.textContent.trim().toLowerCase();
                    if (text.includes(query) || link.getAttribute('href') === '#') {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                }
            });
        } else {
            searchResults.innerHTML = '<div class="search-no-results">No se encontraron resultados</div>';
            searchResults.style.display = 'block';

            // Ocultar todos los items
            sidebarMenu.querySelectorAll('.nav-item').forEach(item => {
                item.style.display = 'none';
            });
        }
    });

    // Función para resaltar coincidencias
    function highlightMatch(text, query) {
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<strong>$1</strong>');
    }

    // Cerrar resultados al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.sidebar-search-wrapper')) {
            searchResults.style.display = 'none';
        }
    });

    // Limpiar búsqueda con ESC
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            searchResults.style.display = 'none';
            sidebarMenu.querySelectorAll('.nav-item').forEach(item => {
                item.style.display = '';
            });
        }
    });

    // Expandir sidebar al hacer clic en el icono de búsqueda cuando está colapsado
    const searchIconWrapper = document.querySelector('.sidebar-search-input .input-group-text');
    if (searchIconWrapper) {
        searchIconWrapper.addEventListener('click', function() {
            // Solo enfocar el input, el hover ya expande el sidebar visualmente
            setTimeout(() => searchInput.focus(), 100);
        });
    }
});
</script>
@endpush
