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
        <!-- Buscador de menús -->
        <div class="sidebar-search-wrapper">
            <div class="sidebar-search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text"
                       id="sidebar-search"
                       class="form-control"
                       placeholder="Buscar menú..."
                       autocomplete="off">
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

        // Buscar categoría padre si existe
        let category = '';
        const parentItem = link.closest('.nav-treeview');
        if (parentItem) {
            const parentLink = parentItem.previousElementSibling;
            if (parentLink && parentLink.classList.contains('nav-link')) {
                category = parentLink.textContent.trim();
            }
        }

        if (text && href && href !== '#') {
            menuItems.push({
                text: text,
                category: category,
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
            // Cerrar menús abiertos que no estaban abiertos inicialmente (opcional, por ahora simple)
            return;
        }

        // Filtrar resultados
        const filtered = menuItems.filter(item =>
            item.text.toLowerCase().includes(query) || 
            item.category.toLowerCase().includes(query)
        );

        if (filtered.length > 0) {
            // Mostrar resultados en dropdown
            searchResults.innerHTML = filtered.map(item => `
                <a href="${item.href}" class="search-result-item">
                    <div class="d-flex flex-column">
                        ${item.category ? `<small class="search-category-text">${item.category} <i class="fas fa-chevron-right mx-1" style="font-size: 0.6rem;"></i></small>` : ''}
                        <div class="d-flex align-items-center">
                            <i class="${item.icon}"></i>
                            <span>${highlightMatch(item.text, query)}</span>
                        </div>
                    </div>
                </a>
            `).join('');
            searchResults.style.display = 'block';

            // Ocultar elementos que no coinciden en el menú principal
            sidebarMenu.querySelectorAll('.nav-item').forEach(item => {
                // Lógica simplificada: si hay búsqueda, ocultamos el menú principal para enfocar en los resultados del dropdown
                // O si prefieres mantener el filtrado visual del árbol:
                
                const link = item.querySelector('a.nav-link');
                if (link) {
                    const text = link.textContent.trim().toLowerCase();
                    // Check if this item is a parent of a matching child
                    const isParentOfMatch = Array.from(item.querySelectorAll('.nav-link')).some(childLink => {
                         return childLink.textContent.trim().toLowerCase().includes(query);
                    });

                    if (text.includes(query) || link.getAttribute('href') === '#' && isParentOfMatch) {
                        item.style.display = '';
                         // Si es padre y tiene coincidencias, abrirlo
                        if (link.getAttribute('href') === '#' && isParentOfMatch) {
                            item.classList.add('menu-open');
                        }
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
    const searchIconWrapper = document.querySelector('.sidebar-search-container .search-icon');
    if (searchIconWrapper) {
        searchIconWrapper.addEventListener('click', function() {
            // Solo enfocar el input, el hover ya expande el sidebar visualmente
            setTimeout(() => searchInput.focus(), 100);
        });
    }
});
</script>
@endpush
