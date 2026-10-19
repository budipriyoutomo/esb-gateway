/**
 * Page metadata every list page receives, mirroring
 * App\Domain\Canonical\Pagination::jsonSerialize() (locked by PaginationTest).
 */
export type Pagination = {
    page: number;
    limit: number;
    count: number;
    lastPage: number;
    hasMorePages: boolean;
};

/**
 * Paging part of a list's filters. Master-data lists extend it with their
 * own fields, e.g. `PagingFilters & { menuName?: string }`.
 */
export type PagingFilters = {
    page?: number;
    limit?: number;
};

/**
 * Search and document-date part shared by transactional document lists
 * (purchase request/order/invoice, simple purchase). Each page adds its own
 * fields and index signature.
 */
export type DocumentFilters = {
    search?: string;
    dateFrom?: string;
    dateTo?: string;
};
