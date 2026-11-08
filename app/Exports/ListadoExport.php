<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * One Excel export for every listing.
 *
 * The other classes in this namespace each rebuild their module's query by
 * hand, which is why only a third of the listings had an export at all. This
 * one receives rows that the controller has already fetched and formatted
 * through App\Support\TableExport, so a new listing needs no class of its own.
 */
class ListadoExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly array $headings,
        private readonly array $rows,
    ) {
    }

    public function array(): array
    {
        return array_map(
            fn (array $row) => array_map([$this, 'neutralise'], $row),
            $this->rows
        );
    }

    /**
     * A spreadsheet treats a cell starting with = + - @ or a control character
     * as a formula. Client names and observations in this system are typed by
     * people and also come from an external registry, so a value like
     * `=HYPERLINK(...)` would run the moment someone opened the file. Prefixing
     * it with an apostrophe makes the spreadsheet show it literally.
     *
     * Negative amounts are left alone: "-1.500.000" is a number, not a
     * formula, and quoting it would turn a column of figures into text.
     */
    private function neutralise($value)
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        if (!preg_match('/^[=+\-@\t\r]/', $value)) {
            return $value;
        }

        // -1.500.000 / +12,5 / -3 are values, not formulas.
        if (preg_match('/^[+\-]?[\d.,\s]+$/', $value)) {
            return $value;
        }

        return "'" . $value;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function styles(Worksheet $sheet): array
    {
        // The header row is frozen and bold: these listings run to thousands
        // of rows and scrolling without it loses the column meanings.
        $sheet->freezePane('A2');

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '18181B'],
                ],
            ],
        ];
    }
}
