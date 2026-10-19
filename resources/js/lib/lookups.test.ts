import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { fetchLookup } from '@/lib/lookups';

afterEach(() => {
    vi.unstubAllGlobals();
});

function stubFetch(status: number, body: unknown) {
    const fetch = vi.fn(() =>
        Promise.resolve(
            new Response(JSON.stringify(body), {
                status,
                headers: { 'Content-Type': 'application/json' },
            }),
        ),
    );
    vi.stubGlobal('fetch', fetch);

    return fetch;
}

describe('fetchLookup', () => {
    it('asks the gateway for one page of a lookup, as JSON', async () => {
        const fetch = stubFetch(200, {
            options: [{ value: '1', label: 'Jakarta', code: 'JKT', meta: {} }],
            hasMore: false,
        });

        const page = await fetchLookup('locations', {
            search: 'gud',
            page: 2,
            branchId: 1,
        });

        expect(page.options[0].label).toBe('Jakarta');

        const [url, init] = fetch.mock.calls[0] as unknown as [
            string,
            RequestInit,
        ];
        expect(url).toBe('/lookups/locations?search=gud&page=2&branchId=1');
        expect(new Headers(init.headers).get('Accept')).toBe(
            'application/json',
        );
    });

    it('leaves out empty parameters', async () => {
        const fetch = stubFetch(200, { options: [], hasMore: false });

        await fetchLookup('branches', { search: '', page: 1 });

        expect((fetch.mock.calls[0] as unknown as [string])[0]).toBe(
            '/lookups/branches?page=1',
        );
    });

    it('fails with the gateway’s own message', async () => {
        stubFetch(429, {
            message:
                'Too many ERP requests from this account. Wait a minute and try again.',
        });

        await expect(fetchLookup('branches', { page: 1 })).rejects.toThrow(
            'Too many ERP requests from this account. Wait a minute and try again.',
        );
    });

    it('fails with a generic message when there is none', async () => {
        stubFetch(500, 'not json');

        await expect(fetchLookup('branches', { page: 1 })).rejects.toThrow(
            'Could not load the list. Try again.',
        );
    });
});
