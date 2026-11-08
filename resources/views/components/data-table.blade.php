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
@endphp

<div class="table-responsive">
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
