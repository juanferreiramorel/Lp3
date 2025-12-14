<style>
    .search-container {
        margin-bottom: 24px;
    }

    .search-card {
        background: linear-gradient(135deg, #343a40 0%, #23272b 100%);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 10px 30px rgba(52, 58, 64, 0.3);
        border: none;
        position: relative;
        overflow: hidden;
    }

    /* Efecto decorativo de fondo */
    .search-card::before {
        content: '';
        position: absolute;
        top: -100px;
        right: -80px;
        width: 280px;
        height: 280px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 50%;
        pointer-events: none;
    }

    .search-card::after {
        content: '';
        position: absolute;
        bottom: -70px;
        left: -50px;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.06);
        border-radius: 50%;
        pointer-events: none;
    }

    .search-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 20px;
        position: relative;
        z-index: 1;
    }

    .search-icon-wrapper {
        width: 48px;
        height: 48px;
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 1.2rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .search-icon-wrapper:hover {
        transform: scale(1.1) rotate(5deg);
        background: rgba(255, 255, 255, 0.35);
    }

    .search-header-text h6 {
        margin: 0;
        font-weight: 700;
        color: #ffffff;
        font-size: 1.1rem;
        letter-spacing: -0.02em;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .search-header-text p {
        margin: 2px 0 0 0;
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.85);
        font-weight: 400;
    }

    .input-group-search-modern {
        position: relative;
        display: flex;
        border-radius: 12px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1;
    }

    .input-group-search-modern:focus-within {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2), 0 0 0 3px rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
    }

    .search-mode-select {
        border: none;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        color: #495057;
        font-weight: 600;
        font-size: 0.875rem;
        padding: 14px 16px;
        border-right: 2px solid #e0e7ff;
        cursor: pointer;
        outline: none;
        min-width: 160px;
        transition: all 0.2s ease;
    }

    .search-mode-select:hover {
        background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    }

    .search-mode-select:focus {
        outline: none;
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        color: #4338ca;
    }

    .buscar-input-modern {
        flex: 1;
        border: none;
        padding: 14px 20px;
        font-size: 0.975rem;
        color: #1e293b;
        background: #ffffff;
        outline: none;
        transition: all 0.2s ease;
    }

    .buscar-input-modern::placeholder {
        color: #94a3b8;
        font-weight: 400;
    }

    .buscar-input-modern:focus {
        outline: none;
        background: #fafbfc;
    }

    .btn-buscar-modern {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: #ffffff;
        border: none;
        padding: 14px 28px;
        font-weight: 600;
        font-size: 0.925rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        white-space: nowrap;
        box-shadow: inset 0 0 0 0 rgba(255, 255, 255, 0.2);
    }

    .btn-buscar-modern:hover {
        background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
        transform: translateX(-2px);
        box-shadow: inset 0 0 0 100px rgba(255, 255, 255, 0.1);
    }

    .btn-buscar-modern:active {
        transform: translateX(-2px) scale(0.98);
    }

    .btn-buscar-modern i {
        font-size: 1rem;
        transition: transform 0.3s ease;
    }

    .btn-buscar-modern:hover i {
        transform: scale(1.15);
    }

    /* Animación de entrada */
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .search-card {
        animation: slideInDown 0.5s ease-out;
    }

    @media (max-width: 768px) {
        .search-card {
            padding: 20px 16px;
            border-radius: 12px;
        }

        .search-icon-wrapper {
            width: 42px;
            height: 42px;
            font-size: 1.1rem;
        }

        .search-header-text h6 {
            font-size: 1rem;
        }

        .search-header-text p {
            font-size: 0.8rem;
        }

        .search-mode-select {
            min-width: 130px;
            font-size: 0.85rem;
            padding: 12px 14px;
        }

        .buscar-input-modern {
            font-size: 0.9rem;
            padding: 12px 16px;
        }

        .btn-buscar-modern {
            padding: 12px 22px;
            font-size: 0.9rem;
        }

        .btn-buscar-modern span {
            display: none;
        }
    }

    @media (max-width: 576px) {
        .search-header {
            margin-bottom: 16px;
        }

        .input-group-search-modern {
            border-radius: 10px;
        }

        .search-mode-select {
            min-width: 110px;
            font-size: 0.8rem;
            padding: 11px 12px;
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
        <!-- Changing ID to CLASS to avoid collisions with duplicates -->
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
        // Manejar el submit del formulario de búsqueda de texto (using classes)
        $('.form-busqueda-texto').on('submit', function(e) {
            e.preventDefault();
            // Find inputs strictly within THIS form instance
            var buscar = $(this).find('.buscar').val();
            var searchMode = $(this).find('.search-mode').val();
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