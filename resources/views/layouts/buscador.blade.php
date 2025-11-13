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


<form id="form-busqueda">
    <div class="input-group mb-3 input-group-custom">
        <div class="input-group-prepend">
            <select class="form-control btn-secondary search-mode" name="search_mode">
                <option value="all" {{ request()->get('search_mode') == 'all' || !request()->get('search_mode') ? 'selected' : '' }}>
                    <i class="fas fa-th-list"></i> Todos los campos
                </option>
                <option value="id" {{ request()->get('search_mode') == 'id' ? 'selected' : '' }}>
                    <i class="fas fa-hashtag"></i> Solo ID
                </option>
            </select>
        </div>
        <input type="text"
               class="form-control buscar buscar-input"
               name="buscar"
               value="{{ request()->get('buscar', null) }}"
               placeholder="Buscar... (Tip: 'id:123' o '#123' para ID exacto)"
               data-url="{{ $url }}"
               title="Use 'id:' o '#' seguido del número para buscar solo por ID. Ejemplo: id:123"
               aria-describedby="button-addon2">
        <div class="input-group-append">
            <button class="btn btn-primary"
                    type="submit"
                    id="button-addon2">
                <i class="fas fa-search mr-1"></i> Buscar
            </button>
        </div>
    </div>
</form>