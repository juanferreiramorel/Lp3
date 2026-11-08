<?php

namespace App\Support;

use App\Exports\ListadoExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Exports a listing to Excel or PDF from the same $columns array that already
 * drives its ORDER BY and its table header.
 *
 * The existing app/Exports/*Export.php classes each re-implement their
 * module's query and filters by hand, which is why only a third of the
 * listings had an export and none of them had a PDF. Here the controller
 * hands over the rows it already knows how to fetch, and the column map says
 * what to call each field, where to read it and how to format it.
 *
 * In the controller, right after resolving $columns and $sort:
 *
 *     if ($export = TableExport::fromRequest($request, $columns)) {
 *         return $export->download(
 *             fn () => DB::select("... {$where} ORDER BY {$sort->orderBy()}"),
 *             'recibos'
 *         );
 *     }
 *
 * Per column, in addition to the keys TableSort and <x-data-table> use:
 *     field    property on the row holding the value (defaults to the key)
 *     format   'date' | 'datetime' | 'money' | 'number' (defaults to text)
 *     export   false to keep a column out of exports entirely
 */
class TableExport
{
    public const XLSX = 'xlsx';
    public const PDF = 'pdf';

    /**
     * Ceiling on how many rows one export may carry.
     *
     * It is public because the cap has to be applied by the query, not here:
     * trimming an array that the database already materialised means the whole
     * table was loaded into memory first, which is exactly what the limit
     * exists to prevent. Callers append it:
     *
     *     DB::select($seleccion . ' LIMIT ' . TableExport::MAX_ROWS)
     *     $query->limit(TableExport::MAX_ROWS)->get()
     *
     * The check below stays as a backstop for a caller that forgets.
     */
    public const MAX_ROWS = 20000;

    private function __construct(
        private readonly string $format,
        private readonly array $columns,
    ) {
    }

    /**
     * Returns null when the request is an ordinary page view.
     */
    public static function fromRequest(Request $request, array $columns): ?self
    {
        $format = strtolower((string) $request->query('export', ''));

        if (!in_array($format, [self::XLSX, self::PDF], true)) {
            return null;
        }

        return new self($format, self::selectedColumns($request, $columns));
    }

    /**
     * The columns the user left ticked, in the table's own order. An empty or
     * absent selection exports everything exportable, so a plain
     * `?export=xlsx` link still does the obvious thing.
     */
    private static function selectedColumns(Request $request, array $columns): array
    {
        $exportable = array_filter(
            $columns,
            fn ($column) => ($column['export'] ?? true) !== false && !empty($column['label'])
        );

        $asked = array_filter(explode(',', (string) $request->query('cols', '')));

        if (empty($asked)) {
            return $exportable;
        }

        $selected = array_intersect_key($exportable, array_flip($asked));

        return empty($selected) ? $exportable : $selected;
    }

    /**
     * @param callable $rows  Returns every row to export, unpaginated.
     */
    public function download(callable $rows, string $filename)
    {
        $data = $this->rowsToArrays($rows());
        $headings = array_map(fn ($c) => $c['label'], $this->columns);
        $stamp = now()->format('Y-m-d_H-i');

        if ($this->format === self::XLSX) {
            return Excel::download(
                new ListadoExport($headings, $data),
                "{$filename}_{$stamp}.xlsx"
            );
        }

        return Pdf::loadView('exports.listado', [
            'titulo' => ucfirst(str_replace('_', ' ', $filename)),
            'headings' => $headings,
            'filas' => $data,
            'generado' => now()->format('d/m/Y H:i'),
        ])
            // A listing is wider than it is tall; portrait would shrink it to
            // nothing as soon as a module has more than a few columns.
            ->setPaper('a4', 'landscape')
            ->download("{$filename}_{$stamp}.pdf");
    }

    private function rowsToArrays(iterable $rows): array
    {
        $out = [];

        foreach ($rows as $row) {
            if (count($out) >= self::MAX_ROWS) {
                break;
            }

            $line = [];

            foreach ($this->columns as $key => $column) {
                $field = $column['field'] ?? $key;
                $value = is_array($row) ? ($row[$field] ?? null) : ($row->{$field} ?? null);
                $line[] = $this->formatValue($value, $column['format'] ?? null);
            }

            $out[] = $line;
        }

        return $out;
    }

    private function formatValue($value, ?string $format): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Postgres booleans arrive as real PHP booleans and would otherwise be
        // cast to "1" and "". Not every boolean is a status: "Controla stock"
        // reads yes or no, while an `activo` flag is shown as a badge saying
        // ACTIVO. 'estado' is the one that follows the badge; a plain boolean
        // defaults to the answer that is right either way.
        if ($format === 'estado' || $format === 'bool' || is_bool($value)) {
            $on = is_bool($value)
                ? $value
                : in_array(strtolower((string) $value), ['1', 't', 'true', 'si', 'sí', 'activo'], true);

            if ($format === 'estado') {
                return $on ? 'Activo' : 'Inactivo';
            }

            return $on ? 'Sí' : 'No';
        }

        try {
            return match ($format) {
                // Guaraníes carry no decimals.
                'money' => number_format((float) $value, 0, ',', '.'),
                'number' => number_format((float) $value, 0, ',', '.'),
                'date' => \Carbon\Carbon::parse($value)->format('d/m/Y'),
                'datetime' => \Carbon\Carbon::parse($value)->format('d/m/Y H:i'),
                default => (string) $value,
            };
        } catch (\Throwable $e) {
            // A value that will not parse is still worth exporting as it is;
            // an export must never fail over one bad row.
            return (string) $value;
        }
    }
}
