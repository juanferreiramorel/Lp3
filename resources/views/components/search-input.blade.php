<style>
    .search-container {
        margin-bottom: 24px;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    /* Subtle Card Background with Animation */
    .search-card {
        background: #ffffff;
        padding: 24px;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        border: 1px solid #f1f5f9;
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        animation: slideUpFade 0.6s ease-out;
    }

    .search-card:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
        transform: translateY(-2px);
    }

    .search-header {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 20px;
    }

    .search-header h6 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #0f172a;
        margin: 0;
        letter-spacing: -0.015em;
    }

    .search-header p {
        font-size: 0.875rem;
        color: #64748b;
        margin: 0;
    }

    /* Smooth Input Group */
    .input-group-search-modern {
        display: flex;
        align-items: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        padding: 4px;
    }

    .input-group-search-modern:focus-within {
        background: #ffffff;
        border-color: #3b82f6; /* Soft Blue Focus */
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); /* Blue ring */
        transform: scale(1.005);
    }

    .search-mode-select {
        background: transparent;
        border: none;
        padding: 0.75rem 1.25rem;
        font-size: 0.875rem;
        color: #334155;
        font-weight: 500;
        border-right: 1px solid #e2e8f0;
        outline: none;
        cursor: pointer;
        transition: background-color 0.2s;
        border-radius: 8px 0 0 8px;
    }

    .search-mode-select:hover {
        background-color: #f1f5f9;
        color: #0f172a;
    }

    .buscar-input-modern {
        flex: 1;
        border: none;
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
        color: #0f172a;
        outline: none;
        background: transparent;
        transition: all 0.2s;
    }

    .buscar-input-modern::placeholder {
        color: #94a3b8;
        transition: opacity 0.2s;
    }

    .buscar-input-modern:focus::placeholder {
        opacity: 0.7;
    }

    .btn-buscar-modern {
        background: #0f172a;
        color: #ffffff;
        border: none;
        padding: 0.75rem 1.75rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .btn-buscar-modern:hover {
        background: #1e293b; /* Slate 800 */
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
        transform: translateY(-1px);
    }

    .btn-buscar-modern:active {
        transform: translateY(0);
        background: #020617; /* Slate 950 */
    }

    .btn-buscar-modern i {
        transition: transform 0.3s ease;
    }

    .btn-buscar-modern:hover i {
        transform: scale(1.1) rotate(-10deg);
    }

    @keyframes slideUpFade {
        from {
            opacity: 0;
            transform: translateY(16px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 640px) {
        .search-card { padding: 16px; }
        .input-group-search-modern { flex-direction: column; padding: 6px; gap: 6px; }
        .search-mode-select { width: 100%; border-right: none; border-bottom: 1px solid #e2e8f0; border-radius: 6px; }
        .btn-buscar-modern { width: 100%; justify-content: center; }
    }
</style>

<div class="search-container">
    <div class="search-card">
        <div class="search-header">
            <h6>Búsqueda Avanzada</h6>
            <p>Filtra por ID o texto general para encontrar registros.</p>
        </div>
        
        <form class="form-busqueda-texto">
            <div class="input-group-search-modern">
                <select class="search-mode search-mode-select" name="search_mode">
                    <option value="all" {{ request()->get('search_mode') == 'all' || !request()->get('search_mode') ? 'selected' : '' }}>
                        Todos los campos
                    </option>
                    <option value="id" {{ request()->get('search_mode') == 'id' ? 'selected' : '' }}>
                        Solo ID
                    </option>
                </select>
                <input type="text"
                    class="buscar buscar-input-modern"
                    name="buscar"
                    value="{{ request()->get('buscar', null) }}"
                    placeholder="Escribe tu búsqueda..."
                    data-url="{{ url()->current() }}"
                    autocomplete="off">
                <button class="btn-buscar-modern" type="submit">
                    <i class="fas fa-search"></i>
                    <span>Buscar</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // The mode picker was the only native <select> left in the app. A native
        // dropdown is drawn by the operating system, so no stylesheet can reach
        // it — it is the one control that never matched the rest of the UI.
        // Select2 is already what every other combo here uses.
        if ($.fn.select2) {
            $('.search-mode-select').each(function() {
                const $select = $(this);
                if ($select.data('select2')) return;

                $select.select2({
                    theme: 'bootstrap4',
                    // Two options never need a search box.
                    minimumResultsForSearch: Infinity,
                    width: '100%',
                });

                // The container keeps the `select2` class Select2 adds: closing
                // on an outside click is implemented by looking for
                // `.select2.select2-container--open`, so stripping it leaves a
                // dropdown that never closes.
            });
        }

        // Debounce setup for real-time search
        let searchTimeout = null;

        $('.buscar').on('input', function() {
            clearTimeout(searchTimeout);
            var $form = $(this).closest('.form-busqueda-texto');
            
            searchTimeout = setTimeout(function() {
                $form.trigger('submit');
            }, 600); // Wait 600ms after user stops typing
        });

        $('.form-busqueda-texto').on('submit', function(e) {
            e.preventDefault();
            var buscar = $(this).find('.buscar').val();
            var searchMode = $(this).find('.search-mode').val();
            var url = '{{ url()->current() }}';

            var params = new URLSearchParams(window.location.search);
            if (buscar) {
                params.set('buscar', buscar);
            } else {
                params.delete('buscar');
            }
            if (searchMode) {
                params.set('search_mode', searchMode);
            }

            // Reset page to 1 when searching
            params.delete('page');

            window.location.href = url + '?' + params.toString();
        });
    });
</script>
@endpush