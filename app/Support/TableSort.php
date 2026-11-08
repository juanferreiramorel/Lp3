<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Resolves the column a listing is ordered by, from the request.
 *
 * The column map is the single source of truth, shared with the
 * <x-data-table> component: the controller uses it to build a safe ORDER BY,
 * the view uses the same array to draw the header. Adding a sortable column is
 * therefore one line in one place.
 *
 *   $columns = [
 *       'id'      => ['label' => '#',      'sort' => 'r.id_recibo'],
 *       'cliente' => ['label' => 'Cliente','sort' => "c.clie_nombre"],
 *       'acciones'=> ['label' => 'Acciones'],            // no 'sort' => not sortable
 *   ];
 *
 *   $sort = TableSort::resolve($columns, 'id');
 *   ... " ORDER BY {$sort->orderBy()} "
 *
 * The SQL fragment can only ever come from the map, never from the request:
 * the request supplies a key, and a key that is not in the map is discarded.
 * Nothing the user types reaches the query.
 */
class TableSort
{
    public const ASC = 'asc';
    public const DESC = 'desc';

    private function __construct(
        public readonly string $key,
        public readonly string $direction,
        private readonly array $columns,
        private readonly string $expression,
        /** True when the user picked this order, false when it is the default. */
        public readonly bool $explicit = false,
    ) {
    }

    /**
     * @param string|null $defaultExpression  SQL for a listing whose natural
     *        order is not any of the displayed columns (e.g. stock ordered by
     *        p.id_producto while the grid shows s.id_stock). When given and the
     *        user has not picked a column, no header is marked as active —
     *        which is the truth, since none of them is doing the ordering.
     */
    public static function resolve(
        array $columns,
        string $defaultKey,
        string $defaultDirection = self::DESC,
        ?Request $request = null,
        ?string $defaultExpression = null
    ): self {
        $request = $request ?: request();

        $key = (string) $request->query('sort', '');
        $direction = strtolower((string) $request->query('dir', ''));
        $explicit = self::isSortable($columns, $key);

        if (!$explicit) {
            if ($defaultExpression !== null) {
                $direction = in_array($direction, [self::ASC, self::DESC], true)
                    ? $direction
                    : $defaultDirection;

                return new self('', $direction, $columns, $defaultExpression, false);
            }

            $key = $defaultKey;
            $direction = $direction !== '' ? $direction : $defaultDirection;
        }

        if (!in_array($direction, [self::ASC, self::DESC], true)) {
            $direction = $defaultDirection;
        }

        // Fall back to the first sortable column if the default is not one.
        if (!self::isSortable($columns, $key)) {
            foreach ($columns as $candidate => $definition) {
                if (!empty($definition['sort'])) {
                    $key = (string) $candidate;
                    break;
                }
            }
        }

        $expression = $columns[$key]['sort'] ?? '';

        return new self($key, $direction, $columns, (string) $expression, $explicit);
    }

    public static function isSortable(array $columns, string $key): bool
    {
        return $key !== '' && !empty($columns[$key]['sort']);
    }

    /**
     * A ready-to-interpolate ORDER BY body, e.g. "r.fecha_recibo DESC".
     * Returns an empty string when nothing is sortable, so callers can keep
     * their own ordering.
     */
    public function orderBy(): string
    {
        if ($this->expression === '') {
            return '';
        }

        $direction = $this->direction === self::ASC ? 'ASC' : 'DESC';

        // Rows the sort cannot separate must still come out in a stable order,
        // otherwise paging through them silently repeats and drops records.
        $tiebreaker = $this->columns[$this->key]['tiebreaker'] ?? null;

        return $tiebreaker
            ? "{$this->expression} {$direction}, {$tiebreaker} {$direction}"
            : "{$this->expression} {$direction}";
    }

    /**
     * The query parameters a header link should carry, as a three-state cycle:
     *
     *   unsorted  ->  first direction
     *   first     ->  opposite direction
     *   opposite  ->  cleared (empty array), back to the listing's own order
     *
     * The column the listing falls back to has no third state, because clearing
     * it would land on itself: it just flips between its two directions, so no
     * click is ever a no-op.
     */
    public function nextStateFor(string $key): array
    {
        $first = $this->columns[$key]['first'] ?? self::ASC;
        $second = $first === self::ASC ? self::DESC : self::ASC;

        if (!$this->isSortedBy($key)) {
            return ['sort' => $key, 'dir' => $first];
        }

        if ($this->direction === $first) {
            return ['sort' => $key, 'dir' => $second];
        }

        return $this->explicit ? [] : ['sort' => $key, 'dir' => $first];
    }

    /** True when clicking this header clears the sort instead of flipping it. */
    public function nextClearsFor(string $key): bool
    {
        return $this->nextStateFor($key) === [];
    }

    /** 'none' | 'asc' | 'desc' — what the header icon should show right now. */
    public function stateFor(string $key): string
    {
        return $this->isSortedBy($key) ? $this->direction : 'none';
    }

    public function isSortedBy(string $key): bool
    {
        return $key === $this->key && $this->expression !== '';
    }

    /** Value for the th's aria-sort attribute. */
    public function ariaSort(string $key): string
    {
        if (!$this->isSortedBy($key)) {
            return 'none';
        }

        return $this->direction === self::ASC ? 'ascending' : 'descending';
    }
}
