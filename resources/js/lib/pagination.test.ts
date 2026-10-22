import { describe, expect, it } from 'vite-plus/test';
import { fromPaginator } from '@/lib/pagination';

describe('fromPaginator', () => {
    it('reads a Laravel paginator as the shared Pagination shape', () => {
        expect(
            fromPaginator({
                data: [1, 2],
                total: 45,
                per_page: 20,
                current_page: 2,
                last_page: 3,
            }),
        ).toEqual({
            page: 2,
            limit: 20,
            count: 45,
            lastPage: 3,
            hasMorePages: true,
        });
    });

    it('has no more pages on the last one, and falls back to the row count for the limit', () => {
        expect(
            fromPaginator({
                data: [1, 2, 3],
                total: 3,
                current_page: 1,
                last_page: 1,
            }),
        ).toEqual({
            page: 1,
            limit: 3,
            count: 3,
            lastPage: 1,
            hasMorePages: false,
        });
    });
});
