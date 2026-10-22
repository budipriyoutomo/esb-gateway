<?php

namespace App\Exports;

/**
 * A list query moved to another page and page size, everything else kept.
 *
 * Every list query is a readonly value whose constructor promotes each
 * property, `page` and `limit` among them; this rebuilds one from its own
 * values.
 */
final class PagedQuery
{
    /**
     * @template TQuery of object
     *
     * @param  TQuery  $query
     * @return TQuery
     */
    public static function at(object $query, int $page, int $limit = CsvExport::PAGE_SIZE): object
    {
        return new ($query::class)(...[...get_object_vars($query), 'page' => $page, 'limit' => $limit]);
    }
}
