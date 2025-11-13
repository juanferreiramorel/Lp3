<!-- Nombre del rol -->
<div class="form-group col-sm-12">
    {!! Form::label('name', 'Rol:') !!}
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255, 'placeholder' => 'Ej: Supervisor de ventas']) !!}
</div>

@php
    $selectedPermissions = collect(old('permiso_id', $rolePermissionIds ?? []))
        ->map(function ($value) {
            return (int) $value;
        })
        ->filter()
        ->unique()
        ->values()
        ->all();

    $selectedPermissionLookup = array_flip($selectedPermissions);
    $totalPermissions = $permisos->count();

    $groupedPermissions = collect($permisos)
        ->sortBy('name')
        ->groupBy(function ($permiso) {
            $parts = preg_split('/[.\s]/', $permiso->name, 2);
            $key = strtolower($parts[0] ?? $permiso->name);
            return $key ?: 'otros';
        })
        ->sortKeys();

    $slugify = function ($value) {
        $value = preg_replace('/[^A-Za-z0-9]+/', '-', (string) $value);
        $value = trim($value, '-');
        return $value !== '' ? strtolower($value) : 'permisos';
    };

    $formatGroupLabel = function ($value) {
        $value = str_replace(['-', '_'], ' ', (string) $value);
        return ucwords($value ?: 'Otros');
    };
@endphp

<div class="col-12">
    <div class="card shadow-sm permission-manager" id="permission-manager">
        <div class="card-body">
            <div class="row">
                <div class="col-xl-4 mb-4 mb-xl-0 permission-tools pr-xl-4">
                    <h3 class="h5 font-weight-bold mb-1">Permisos</h3>
                    <p class="text-muted small mb-4">Busca, filtra y marca los permisos que necesita este rol.</p>

                    <div class="d-flex flex-wrap align-items-center mb-3">
                        <span class="badge badge-success mr-2 mb-2">
                            Seleccionados:
                            <span data-selected-count>{{ count($selectedPermissions) }}</span>
                        </span>
                        <span class="badge badge-light mb-2">Total: {{ $totalPermissions }}</span>
                    </div>

                    <label for="permission-search" class="small text-uppercase text-muted font-weight-bold mb-1">
                        Buscar permiso
                    </label>
                    <div class="input-group input-group-sm mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="search"
                               id="permission-search"
                               class="form-control"
                               placeholder="Nombre, módulo o acción"
                               aria-label="Buscar permisos">
                    </div>

                    <button type="button" class="btn btn-outline-primary btn-block mb-2" data-action="select-all">
                        <i class="fas fa-check-double mr-1"></i> Marcar todos
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-block mb-4" data-action="clear-all">
                        <i class="fas fa-eraser mr-1"></i> Desmarcar Todos
                    </button>

                    <div class="small text-muted">
                        Consejo: usa la búsqueda para filtrar un módulo y emplea los botones de cada grupo para asignar permisos de forma masiva.
                    </div>
                </div>
                <div class="col-xl-8">
                    <div id="permission-group-wrapper">
                        @foreach ($groupedPermissions as $group => $groupPermissions)
                            @php
                                $groupSlug = $slugify($group);
                                $groupId = 'permission-group-' . $groupSlug . '-' . $loop->index;
                            @endphp
                            <div class="card permission-group card-outline card-light mb-3" data-group="{{ $groupSlug }}">
                                <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                                    <div class="mb-2 mb-md-0">
                                        <button class="btn btn-link text-left p-0 font-weight-bold permission-group-toggle"
                                                type="button"
                                                data-toggle="collapse"
                                                data-target="#{{ $groupId }}"
                                                aria-expanded="true"
                                                aria-controls="{{ $groupId }}">
                                            <i class="fas fa-layer-group text-primary mr-2"></i>
                                            {{ $formatGroupLabel($group) }}
                                        </button>
                                        <span class="badge badge-light ml-md-2">{{ $groupPermissions->count() }} permisos</span>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button"
                                                class="btn btn-outline-primary"
                                                data-group-action="select"
                                                data-group-id="{{ $groupId }}">
                                            Seleccionar
                                        </button>
                                        <button type="button"
                                                class="btn btn-outline-secondary"
                                                data-group-action="clear"
                                                data-group-id="{{ $groupId }}">
                                            Desmarcar
                                        </button>
                                    </div>
                                </div>
                                <div id="{{ $groupId }}" class="collapse show">
                                    <div class="card-body pt-0">
                                        <div class="row">
                                            @foreach ($groupPermissions as $permiso)
                                                @php
                                                    $isChecked = isset($selectedPermissionLookup[$permiso->id]);
                                                    $permissionName = strtolower($permiso->name);
                                                @endphp
                                                <div class="col-sm-6 col-lg-4 permission-item mb-2"
                                                     data-permission="{{ $permissionName }}"
                                                     data-permission-group="{{ $groupSlug }}">
                                                    <div class="custom-control custom-checkbox">
                                                        <input class="custom-control-input permission-checkbox"
                                                               type="checkbox"
                                                               id="permiso-{{ $permiso->id }}"
                                                               name="permiso_id[]"
                                                               value="{{ $permiso->id }}"
                                                               {{ $isChecked ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="permiso-{{ $permiso->id }}">
                                                            {{ $permiso->name }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #permission-manager .permission-group .card-header {
        background-color: #fdfdfd;
    }

    #permission-manager .permission-group-toggle {
        text-decoration: none;
    }

    #permission-manager .permission-group-toggle:hover {
        text-decoration: none;
    }

    @media (min-width: 1200px) {
        #permission-manager .permission-tools {
            border-right: 1px solid #e9ecef;
        }
    }
</style>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const manager = document.getElementById('permission-manager');
        if (!manager) {
            return;
        }

        const checkboxes = Array.from(manager.querySelectorAll('.permission-checkbox'));
        const selectedCounter = manager.querySelector('[data-selected-count]');
        const searchInput = manager.querySelector('#permission-search');

        const updateSelectedCounter = () => {
            const totalSelected = checkboxes.filter(checkbox => checkbox.checked).length;
            if (selectedCounter) {
                selectedCounter.textContent = totalSelected;
            }
        };

        updateSelectedCounter();

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCounter);
        });

        manager.addEventListener('click', function (event) {
            const actionButton = event.target.closest('[data-action]');
            if (!actionButton) {
                return;
            }

            const action = actionButton.dataset.action;
            const shouldCheck = action === 'select-all';

            if (action === 'select-all' || action === 'clear-all') {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = shouldCheck;
                });
                updateSelectedCounter();
            }
        });

        manager.querySelectorAll('[data-group-action]').forEach(button => {
            button.addEventListener('click', function () {
                const groupId = this.dataset.groupId;
                const container = document.getElementById(groupId);
                if (!container) {
                    return;
                }

                const groupCheckboxes = Array.from(container.querySelectorAll('.permission-checkbox'));
                const shouldSelect = this.dataset.groupAction === 'select';
                groupCheckboxes.forEach(checkbox => {
                    checkbox.checked = shouldSelect;
                });
                updateSelectedCounter();
            });
        });

        if (searchInput) {
            const permissionItems = Array.from(manager.querySelectorAll('.permission-item'));
            const groups = Array.from(manager.querySelectorAll('.permission-group'));

            searchInput.addEventListener('input', function () {
                const term = this.value.trim().toLowerCase();

                permissionItems.forEach(item => {
                    const permissionName = item.dataset.permission || '';
                    const matches = permissionName.includes(term);
                    item.classList.toggle('d-none', term && !matches);
                });

                groups.forEach(group => {
                    const visibleItems = group.querySelectorAll('.permission-item:not(.d-none)');
                    group.classList.toggle('d-none', term && visibleItems.length === 0);
                });
            });
        }
    });
</script>
@endpush
