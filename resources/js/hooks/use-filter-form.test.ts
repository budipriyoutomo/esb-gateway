import { router } from '@inertiajs/react';
import { act, renderHook } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { useFilterForm } from '@/hooks/use-filter-form';

afterEach(() => {
    vi.restoreAllMocks();
});

function spyGet() {
    return vi.spyOn(router, 'get').mockImplementation(() => {});
}

const fields = [
    { key: 'search', chip: (value: string) => `“${value}”` },
    { key: 'dateFrom', chip: (value: string) => `From ${value}` },
    { key: 'dateTo', chip: (value: string) => `To ${value}` },
];

describe('useFilterForm', () => {
    it('starts each field from what the server applied', () => {
        const { result } = renderHook(() =>
            useFilterForm('/x', { search: 'acme', page: 2 }, fields),
        );

        expect(result.current.values).toEqual({
            search: 'acme',
            dateFrom: '',
            dateTo: '',
        });
    });

    it('sends every field on submit, dropping the empty ones', () => {
        const get = spyGet();
        const { result } = renderHook(() => useFilterForm('/x', {}, fields));

        act(() => {
            result.current.set('search', 'acme');
            result.current.set('dateFrom', '2026-03-01');
        });
        result.current.bar.onSubmit();

        expect(get).toHaveBeenCalledExactlyOnceWith(
            '/x',
            { search: 'acme', dateFrom: '2026-03-01' },
            { preserveState: true, replace: true },
        );
    });

    it('shows a chip per applied filter, not per typed value', () => {
        const { result } = renderHook(() =>
            useFilterForm(
                '/x',
                { search: 'acme', dateTo: '2026-03-31', page: 2 },
                fields,
            ),
        );

        act(() => {
            result.current.set('dateFrom', '2026-03-01');
        });

        expect(result.current.bar.active).toEqual([
            { key: 'search', label: '“acme”' },
            { key: 'dateTo', label: 'To 2026-03-31' },
        ]);
    });

    it('removes one filter from the field and the query, back to page 1', () => {
        const get = spyGet();
        const { result } = renderHook(() =>
            useFilterForm(
                '/x',
                { search: 'acme', dateTo: '2026-03-31', page: 2 },
                fields,
            ),
        );

        act(() => {
            result.current.bar.onRemove('search');
        });

        expect(result.current.values.search).toBe('');
        expect(get).toHaveBeenCalledExactlyOnceWith(
            '/x',
            { dateTo: '2026-03-31' },
            { preserveState: true, replace: true },
        );
    });

    it('resets every field and the query', () => {
        const get = spyGet();
        const { result } = renderHook(() =>
            useFilterForm(
                '/x',
                { search: 'acme', dateTo: '2026-03-31' },
                fields,
            ),
        );

        act(() => {
            result.current.bar.onReset();
        });

        expect(result.current.values).toEqual({
            search: '',
            dateFrom: '',
            dateTo: '',
        });
        expect(get).toHaveBeenCalledExactlyOnceWith(
            '/x',
            {},
            { preserveState: true, replace: true },
        );
    });

    it('keeps query values that are not form fields when submitting', () => {
        const get = spyGet();
        const { result } = renderHook(() =>
            useFilterForm('/x', { kind: 'core', search: 'old' }, fields, {
                keep: ['kind'],
            }),
        );

        result.current.bar.onSubmit();

        expect(get.mock.calls[0]![1]).toEqual({ kind: 'core', search: 'old' });
    });

    it('pages with the applied filters', () => {
        const get = spyGet();
        const { result } = renderHook(() =>
            useFilterForm('/x', { search: 'acme', page: 1 }, fields),
        );

        result.current.goToPage(3);

        expect(get).toHaveBeenCalledExactlyOnceWith(
            '/x',
            { search: 'acme', page: 3 },
            { preserveState: true, preserveScroll: true },
        );
    });
});
