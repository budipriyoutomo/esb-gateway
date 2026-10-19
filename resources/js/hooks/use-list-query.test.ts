import { router } from '@inertiajs/react';
import { renderHook } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { useListQuery } from '@/hooks/use-list-query';

afterEach(() => {
    vi.restoreAllMocks();
});

function spyGet() {
    return vi.spyOn(router, 'get').mockImplementation(() => {});
}

describe('useListQuery', () => {
    it('drops empty values before asking for the list', () => {
        const get = spyGet();
        const { result } = renderHook(() =>
            useListQuery('/purchase-orders', { search: 'old', page: 3 }),
        );

        result.current.apply({
            search: 'acme',
            dateFrom: '',
            dateTo: undefined,
            supplierId: 0,
        });

        // 0 is a value; only '' / null / undefined mean "no filter". The page
        // resets to the first because the result set changed.
        expect(get).toHaveBeenCalledExactlyOnceWith(
            '/purchase-orders',
            { search: 'acme', supplierId: 0 },
            { preserveState: true, replace: true },
        );
    });

    it('trims text so a space-only search is no filter', () => {
        const get = spyGet();
        const { result } = renderHook(() => useListQuery('/x', {}));

        result.current.apply({ search: '   ', number: ' PO-1 ' });

        expect(get.mock.calls[0][1]).toEqual({ number: 'PO-1' });
    });

    it('keeps the current filters when changing page', () => {
        const get = spyGet();
        const { result } = renderHook(() =>
            useListQuery('/purchase-orders', { search: 'acme', page: 1 }),
        );

        result.current.goToPage(2);

        expect(get).toHaveBeenCalledExactlyOnceWith(
            '/purchase-orders',
            { search: 'acme', page: 2 },
            { preserveState: true, preserveScroll: true },
        );
    });

    it('clears every filter on reset', () => {
        const get = spyGet();
        const { result } = renderHook(() =>
            useListQuery('/purchase-orders', { search: 'acme', page: 4 }),
        );

        result.current.reset();

        expect(get).toHaveBeenCalledExactlyOnceWith(
            '/purchase-orders',
            {},
            { preserveState: true, replace: true },
        );
    });
});
