import type { Pagination } from '@/types/pagination';

/** The fields of a Laravel LengthAwarePaginator the gateway's own lists send. */
export type LaravelPaginator<T> = {
    data: T[];
    total: number;
    per_page?: number;
    current_page: number;
    last_page: number;
};

/**
 * A Laravel paginator (the gateway's own tables: integration logs, sales
 * pushes, endpoints) as the Pagination shape ListPagination reads, so every
 * list pages the same way.
 */
export function fromPaginator<T>(paginator: LaravelPaginator<T>): Pagination {
    return {
        page: paginator.current_page,
        limit: paginator.per_page ?? paginator.data.length,
        count: paginator.total,
        lastPage: paginator.last_page,
        hasMorePages: paginator.current_page < paginator.last_page,
    };
}
