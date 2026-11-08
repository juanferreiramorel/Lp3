{{--
    The one listing shell.

    Every grid in the app renders through this component, so a feature added
    here reaches all of them at once. Today that is sortable headers, a
    consistent empty state and the responsive wrapper.

    Usage:

        <x-data-table :columns="$columns" :sort="$sort" :paginator="$recibos">
            @foreach($recibos as $r)
                <tr>...</tr>
            @endforeach
        </x-data-table>

    $columns is the same array the controller hands to TableSort, so a column
    is described once. Keys understood per column:
        label       visible heading
        sort        SQL expression; its presence is what makes it sortable
        align       'right' | 'center'
        width       fixed width, e.g. '140px'
        class       extra classes for both th and the header link
        hide        'sm' | 'md' | 'lg' — drop the column below that breakpoint
--}}
@props([
    'columns' => [],
    'sort' => null,
    'paginator' => null,
    'empty' => 'No hay registros para mostrar.',
    'emptyHint' => null,
    'tableClass' => 'table table-hover',
    // Set to false for a listing whose controller has no export hook yet.
    'exportable' => true,
    // Classes for the scrolling wrapper around the table only. A listing that
    // swaps to cards on a phone passes "d-none d-md-block" here instead of
    // wrapping the whole component, which would hide the export toolbar too.
    'wrapperClass' => '',
])

@php
    $hideClasses = [
        'sm' => 'd-none d-sm-table-cell',
        'md' => 'd-none d-md-table-cell',
        'lg' => 'd-none d-lg-table-cell',
    ];

    // Links keep every filter the user already applied and send them back to
    // the first page, because page 7 of the old order means nothing.
    $baseQuery = collect(request()->query())->except(['sort', 'dir', 'page'])->all();

    $isEmpty = $paginator !== null && count($paginator) === 0;

    // Columns worth offering in an export: the actions column has no value to
    // carry, and anything marked export => false opts out.
    $exportColumns = collect($columns)
        ->filter(fn ($c, $k) => ($c['export'] ?? true) !== false && !empty($c['label']) && !empty($c['field'] ?? $k))
        ->all();

    $showExport = $exportable && !empty($exportColumns) && !$isEmpty;
@endphp

@if ($showExport)
    {{-- The export carries whatever filter and order the user is looking at,
         so what comes out of the file matches what is on the screen. --}}
    <div class="data-table-toolbar">
        @if ($paginator !== null && method_exists($paginator, 'total'))
            {{-- The strip held one lonely button. Saying how many rows the file
                 will carry gives it a reason to be there. --}}
            <span class="data-table-count">
                {{ number_format($paginator->total(), 0, ',', '.') }}
                {{ $paginator->total() === 1 ? 'registro' : 'registros' }}
            </span>
        @else
            <span></span>
        @endif

        <div class="dropdown">
            <button class="btn btn-export d-inline-flex align-items-center" type="button"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-file-download mr-2" aria-hidden="true"></i>
                <span>Exportar</span>
                <i class="fas fa-caret-down ml-2" aria-hidden="true"></i>
            </button>

            <div class="dropdown-menu dropdown-menu-right export-menu" data-export-base="{{ request()->url() }}">
                <div class="export-menu__head">
                    <strong>Columnas a exportar</strong>
                    <button type="button" class="btn btn-link p-0 export-toggle-all">Marcar todas</button>
                </div>

                <div class="export-menu__cols">
                    @foreach ($exportColumns as $key => $column)
                        <label class="export-col">
                            <input type="checkbox" class="export-col-check" value="{{ $key }}" checked>
                            <span>{{ $column['label'] }}</span>
                        </label>
                    @endforeach
                </div>

                <p class="export-menu__note">
                    Se exporta el listado completo con los filtros y el orden actuales,
                    no sólo esta página.
                </p>

                <div class="export-menu__actions">
                    <button type="button" class="btn btn-default export-go" data-format="xlsx">
                        <i class="fas fa-file-excel mr-1" aria-hidden="true"></i> Excel
                    </button>
                    <button type="button" class="btn btn-default export-go" data-format="pdf">
                        <i class="fas fa-file-pdf mr-1" aria-hidden="true"></i> PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    @once
        @push('scripts')
        <script>
            // These listeners run in the CAPTURE phase on purpose.
            //
            // layouts/app.blade.php binds `$('.dropdown-menu').on('click', e =>
            // e.stopPropagation())` directly on every dropdown, to keep the user
            // menu open while it is clicked. That handler sits on an ancestor of
            // these buttons, so it kills every delegated handler inside any
            // dropdown in the app — the clicks never reached document at all.
            // Capturing happens on the way down, before that stop can run, and
            // it keeps working after the table is swapped by AJAX.
            document.addEventListener('click', function (e) {
                const alternar = e.target.closest('.export-toggle-all');
                if (alternar) {
                    const menu = alternar.closest('.export-menu');
                    const casillas = menu.querySelectorAll('.export-col-check');
                    const marcadas = menu.querySelectorAll('.export-col-check:checked').length;
                    const marcarTodas = marcadas !== casillas.length;
                    casillas.forEach(c => { c.checked = marcarTodas; });
                    alternar.textContent = marcarTodas ? 'Desmarcar todas' : 'Marcar todas';
                    return;
                }

                const boton = e.target.closest('.export-go');
                if (!boton) return;

                const menu = boton.closest('.export-menu');
                const cols = [...menu.querySelectorAll('.export-col-check:checked')].map(c => c.value);

                if (cols.length === 0) {
                    alert('Elegí al menos una columna para exportar.');
                    return;
                }

                // Start from the current query so the file matches the screen,
                // minus the page number: an export is not paged.
                const params = new URLSearchParams(window.location.search);
                params.delete('page');
                params.set('export', boton.dataset.format);
                params.set('cols', cols.join(','));

                window.location.href = menu.dataset.exportBase + '?' + params.toString();
            }, true);
        </script>
        @endpush
    @endonce
@endif

<div class="table-responsive {{ $wrapperClass }}">
    <table {{ $attributes->merge(['class' => $tableClass]) }}>
        <thead>
            <tr>
                @foreach ($columns as $key => $column)
                    @php
                        $sortable = $sort !== null && \App\Support\TableSort::isSortable($columns, (string) $key);
                        $isSorted = $sortable && $sort->isSortedBy((string) $key);
                        $align = $column['align'] ?? null;

                        $thClasses = collect([
                            $column['class'] ?? null,
                            $align === 'right' ? 'text-right' : ($align === 'center' ? 'text-center' : null),
                            isset($column['hide']) ? ($hideClasses[$column['hide']] ?? null) : null,
                            $sortable ? 'is-sortable' : null,
                            $isSorted ? 'is-sorted' : null,
                        ])->filter()->implode(' ');
                    @endphp

                    <th scope="col"
                        @if ($thClasses) class="{{ $thClasses }}" @endif
                        @if (!empty($column['width'])) style="width: {{ $column['width'] }}" @endif
                        @if ($sortable) aria-sort="{{ $sort->ariaSort((string) $key) }}" @endif>
                        @if ($sortable)
                            @php
                                $label = $column['label'] ?? $key;
                                $state = $sort->stateFor((string) $key);
                                $next = $sort->nextStateFor((string) $key);

                                // Font Awesome 5 is what this app loads, so these
                                // three are the only sort glyphs available.
                                $icon = match ($state) {
                                    'asc' => 'fa-sort-up',
                                    'desc' => 'fa-sort-down',
                                    default => 'fa-sort',
                                };

                                $aria = $next === []
                                    ? "Quitar el orden por {$label}"
                                    : (($next['dir'] ?? 'asc') === 'asc'
                                        ? "Ordenar por {$label}, de menor a mayor"
                                        : "Ordenar por {$label}, de mayor a menor");

                                $query = http_build_query($baseQuery + $next);
                            @endphp
                            <a class="th-sort th-sort--{{ $state }}"
                               href="{{ request()->url() }}{{ $query ? '?' . $query : '' }}"
                               title="{{ $aria }}"
                               aria-label="{{ $aria }}">
                                @if (!empty($column['icon']))
                                    <i class="fas {{ $column['icon'] }}" aria-hidden="true"></i>
                                @endif
                                <span>{{ $label }}</span>
                                <i class="fas {{ $icon }} th-sort-icon" aria-hidden="true"></i>
                            </a>
                        @else
                            @if (!empty($column['icon']))
                                <i class="fas {{ $column['icon'] }} mr-1" aria-hidden="true"></i>
                            @endif
                            {{ $column['label'] ?? '' }}
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @if ($isEmpty)
                <tr>
                    <td colspan="{{ max(count($columns), 1) }}" class="text-center py-5">
                        <div class="table-empty">
                            <i class="far fa-folder-open mb-2" aria-hidden="true"></i>
                            <p class="mb-0">{{ $empty }}</p>
                            @if ($emptyHint)
                                <small class="text-muted">{{ $emptyHint }}</small>
                            @endif
                        </div>
                    </td>
                </tr>
            @else
                {{ $slot }}
            @endif
        </tbody>
    </table>
</div>
