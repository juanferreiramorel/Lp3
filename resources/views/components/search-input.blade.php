<style>
    .search-container {
        margin-bottom: 20px;
    }
    .search-card {
        background: #fff;
        border-radius: 4px;
        padding: 16px 20px;
        box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        border: 1px solid #dee2e6;
    }
    .search-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }
    .search-icon-wrapper {
        width: 40px;
        height: 40px;
        background: #007bff;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 1.1rem;
    }
    .search-header-text h6 {
        margin: 0;
        font-weight: 700;
        color: #0f172a;
        font-size: 1rem;
    }
    .search-header-text p {
        margin: 0;
        font-size: 0.85rem;
        color: #64748b;
    }

    .input-group-search-modern {
        position: relative;
        display: flex;
        border-radius: 4px;
        overflow: hidden;
        border: 1px solid #ced4da;
        transition: all 0.15s ease;
    }
    .input-group-search-modern:focus-within {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }

    .search-mode-select {
        border: none;
        background: #f8f9fa;
        color: #495057;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 10px 14px;
        border-right: 1px solid #ced4da;
        cursor: pointer;
        outline: none;
        min-width: 150px;
    }
    .search-mode-select:focus {
        outline: none;
        background: #e9ecef;
    }

    .buscar-input-modern {
        flex: 1;
        border: none;
        padding: 12px 18px;
        font-size: 0.95rem;
        color: #0f172a;
        background: #ffffff;
        outline: none;
    }
    .buscar-input-modern::placeholder {
        color: #94a3b8;
    }
    .buscar-input-modern:focus {
        outline: none;
        background: #fafafa;
    }

    .btn-buscar-modern {
        background: #007bff;
        color: #ffffff;
        border: 1px solid #007bff;
        padding: 10px 24px;
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.15s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        white-space: nowrap;
    }
    .btn-buscar-modern:hover {
        background: #0056b3;
        border-color: #004085;
        color: #ffffff;
    }
    .btn-buscar-modern i {
        font-size: 1rem;
    }

    @media (max-width: 768px) {
        .search-card {
            padding: 16px 18px;
        }
        .search-mode-select {
            min-width: 120px;
            font-size: 0.85rem;
            padding: 10px 12px;
        }
        .buscar-input-modern {
            font-size: 0.9rem;
            padding: 10px 14px;
        }
        .btn-buscar-modern {
            padding: 10px 20px;
            font-size: 0.9rem;
        }
        .btn-buscar-modern span {
            display: none;
        }
    }
</style>

<div class="clearfix search-container">
    <div class="search-card">
        <div class="search-header">
            <div class="search-icon-wrapper">
                <i class="fas fa-search"></i>
            </div>
            <div class="search-header-text">
                <h6>Búsqueda Rápida</h6>
                <p>Encuentra registros por ID o cualquier campo</p>
            </div>
        </div>
        <form id="form-busqueda-texto">
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
                       placeholder="Escribe tu búsqueda aquí... (Ej: 'id:123' o '#123' para ID exacto)"
                       data-url="{{ url()->current() }}"
                       title="Use 'id:' o '#' seguido del número para buscar solo por ID">
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
            // Manejar el submit del formulario de búsqueda de texto
            $('#form-busqueda-texto').on('submit', function(e) {
                e.preventDefault();
                var buscar = $('.buscar').val();
                var searchMode = $('.search-mode').val();
                var url = '{{ url()->current() }}';

                // Mantener los filtros actuales
                var params = new URLSearchParams(window.location.search);
                if (buscar) {
                    params.set('buscar', buscar);
                } else {
                    params.delete('buscar');
                }
                if (searchMode) {
                    params.set('search_mode', searchMode);
                }

                window.location.href = url + '?' + params.toString();
            });
        });
    </script>
@endpush
