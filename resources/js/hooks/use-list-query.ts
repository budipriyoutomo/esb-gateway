import { router } from '@inertiajs/react';
import { useCallback, useMemo } from 'react';

export type ListQueryValue = string | number | number[] | null | undefined;
export type ListQuery = Record<string, ListQueryValue>;

/**
 * Empty values are dropped rather than sent: the ERP narrows a result set to
 * rows matching an empty filter. Text is trimmed; 0 is a real value.
 */
function compact(values: ListQuery): ListQuery {
    return Object.fromEntries(
        Object.entries(values)
            .map(([key, value]) => [
                key,
                typeof value === 'string' ? value.trim() : value,
            ])
            .filter(
                ([, value]) =>
                    value !== '' && value !== null && value !== undefined,
            ),
    );
}

/**
 * Filtering and paging for an index page, the same way on every list.
 *
 * @param url     the index route, e.g. purchaseOrdersIndex().url
 * @param filters the filters the server echoed back for this page
 */
export function useListQuery(url: string, filters: ListQuery) {
    /** New filters: back to the first page, replacing the history entry. */
    const apply = useCallback(
        (values: ListQuery) => {
            router.get(url, compact(values), {
                preserveState: true,
                replace: true,
            });
        },
        [url],
    );

    const goToPage = useCallback(
        (page: number) => {
            router.get(
                url,
                { ...compact(filters), page },
                { preserveState: true, preserveScroll: true },
            );
        },
        [url, filters],
    );

    const reset = useCallback(() => {
        router.get(url, {}, { preserveState: true, replace: true });
    }, [url]);

    return useMemo(
        () => ({ apply, goToPage, reset }),
        [apply, goToPage, reset],
    );
}
