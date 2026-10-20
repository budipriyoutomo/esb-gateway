import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { fetchLastPrices, fetchPurchasePrices } from '@/lib/pricelist-prices';

afterEach(() => {
    vi.unstubAllGlobals();
});

function stubFetch(body: unknown, ok = true) {
    const fetchMock = vi.fn((_url: string, _init?: RequestInit) =>
        Promise.resolve({
            ok,
            json: () => Promise.resolve(body),
        } as Response),
    );
    vi.stubGlobal('fetch', fetchMock);

    return fetchMock;
}

describe('pricelist prices', () => {
    it('asks for the active price of every product on the lines', async () => {
        const fetchMock = stubFetch({ prices: { '501': 14500, '502': null } });

        const prices = await fetchLastPrices({
            supplierId: 10,
            currencyId: 1,
            priceDate: '2026-04-01',
            productDetailIds: [501, 502],
        });

        expect(prices).toEqual({ 501: 14500, 502: null });
        const url = new URL(String(fetchMock.mock.calls[0][0]), 'http://x');
        expect(url.pathname).toBe('/pricelists/last-prices');
        expect(url.searchParams.getAll('productDetailIds[]')).toEqual([
            '501',
            '502',
        ]);
        expect(url.searchParams.get('priceDate')).toBe('2026-04-01');
    });

    it('rejects with the gateway message, never a raw one', async () => {
        stubFetch({ message: 'Too many requests.' }, false);

        await expect(
            fetchPurchasePrices({
                supplierId: 10,
                currencyId: 1,
                productDetailId: 501,
                otherSuppliers: false,
            }),
        ).rejects.toThrow('Too many requests.');
    });
});
