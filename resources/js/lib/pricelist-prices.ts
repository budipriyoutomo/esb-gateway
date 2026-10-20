import {
    lastPrices as lastPricesRoute,
    purchaseHistory as purchaseHistoryRoute,
} from '@/routes/pricelists';

/** Mirrors App\Domain\Canonical\PurchasePrice. */
export type PurchasePrice = {
    invoiceNumber: string;
    invoiceDate: string | null;
    price: number | null;
    supplierId: number | null;
    supplierName: string | null;
};

const GENERIC_ERROR = 'Could not load the prices. Try again.';

async function getJson<T>(url: string, signal?: AbortSignal): Promise<T> {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        signal,
    });

    const body: unknown = await response.json().catch(() => null);

    if (!response.ok) {
        throw new Error(
            typeof body === 'object' &&
                body !== null &&
                'message' in body &&
                typeof body.message === 'string'
                ? body.message
                : GENERIC_ERROR,
        );
    }

    return body as T;
}

/**
 * The active price of each product detail for a supplier and currency on a
 * date (GET /pricelists/last-prices, [ESB-032]); null is "no active price".
 */
export async function fetchLastPrices(
    params: {
        supplierId: number;
        currencyId: number;
        priceDate: string;
        productDetailIds: number[];
    },
    signal?: AbortSignal,
): Promise<Record<number, number | null>> {
    const query = new URLSearchParams({
        supplierId: String(params.supplierId),
        currencyId: String(params.currencyId),
        priceDate: params.priceDate,
    });
    params.productDetailIds.forEach((id) =>
        query.append('productDetailIds[]', String(id)),
    );

    const body = await getJson<{ prices: Record<string, number | null> }>(
        `${lastPricesRoute().url}?${query.toString()}`,
        signal,
    );

    return Object.fromEntries(
        Object.entries(body.prices).map(([id, price]) => [Number(id), price]),
    );
}

/** Up to five recent purchase invoice prices of one product detail. */
export async function fetchPurchasePrices(
    params: {
        supplierId: number;
        currencyId: number;
        productDetailId: number;
        otherSuppliers: boolean;
    },
    signal?: AbortSignal,
): Promise<PurchasePrice[]> {
    const body = await getJson<{ invoices: PurchasePrice[] }>(
        purchaseHistoryRoute.url({
            query: {
                supplierId: params.supplierId,
                currencyId: params.currencyId,
                productDetailId: params.productDetailId,
                otherSuppliers: params.otherSuppliers ? 1 : 0,
            },
        }),
        signal,
    );

    return body.invoices;
}
