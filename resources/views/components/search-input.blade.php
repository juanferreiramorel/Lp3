<style>
    .buscar-input {
        border-left: 0 !important;
        border-right: 0 !important;
        padding: 0.55rem 1rem;
        font-size: 0.95rem;
    }

    .buscar-input:focus {
        box-shadow: 0 0 0 0.15rem rgba(0,123,255,.15);
        border-color: #80bdff;
    }

    .input-group-custom {
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        border-radius: 0.35rem;
    }
</style>

<div class="clearfix mb-3">
    <form id="form-busqueda-texto">
        <div class="input-group input-group-search-component">
            <div class="input-group-prepend">
                <select class="form-control btn-secondary search-mode search-mode-select-component" name="search_mode">
                    <option value="all" {{ request()->get('search_mode') == 'all' || !request()->get('search_mode') ? 'selected' : '' }}>
                        <i class="fas fa-th-list"></i> Todos los campos
                    </option>
                    <option value="id" {{ request()->get('search_mode') == 'id' ? 'selected' : '' }}>
                        <i class="fas fa-hashtag"></i> Solo ID
                    </option>
                </select>
            </div>
            <input type="text"
                   class="form-control buscar buscar-input-component"
                   name="buscar"
                   value="{{ request()->get('buscar', null) }}"
                   placeholder="Buscar... (Tip: 'id:123' o '#123' para ID exacto)"
                   data-url="{{ url()->current() }}"
                   title="Use 'id:' o '#' seguido del número para buscar solo por ID">
            <div class="input-group-append">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-search mr-1"></i> Buscar
                </button>
            </div>
        </div>
    </form>
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
