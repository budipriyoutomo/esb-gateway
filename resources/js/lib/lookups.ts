import { show as lookupRoute } from '@/routes/lookups';

/** Mirrors App\Domain\Canonical\LookupKind. */
export type LookupKind =
    | 'branches'
    | 'suppliers'
    | 'locations'
    | 'coa-accounts'
    | 'cash-bank-accounts'
    | 'statuses'
    | 'transfer-products'
    | 'metrics'
    | 'category-types'
    | 'taxes'
    | 'product-categories'
    | 'product-sub-categories'
    | 'units'
    | 'currencies'
    | 'product-details'
    | 'purposes';

/** Mirrors App\Domain\Canonical\LookupOption. */
export type LookupOption = {
    value: string;
    label: string;
    code: string | null;
    meta: Record<string, string | number | null>;
};

export type LookupPage = { options: LookupOption[]; hasMore: boolean };

export type LookupParams = {
    search?: string;
    page: number;
    branchId?: number | null;
};

const GENERIC_ERROR = 'Could not load the list. Try again.';

/**
 * One page of a picker's options from GET /lookups/{kind} ([ESB-019]).
 * Rejects with a message fit to show: the gateway's own (rate limit,
 * validation, normalized ESB error), never a raw upstream one.
 */
export async function fetchLookup(
    kind: LookupKind,
    params: LookupParams,
    signal?: AbortSignal,
): Promise<LookupPage> {
    const query: Record<string, string | number> = {};

    if (params.search) {
        query.search = params.search;
    }

    query.page = params.page;

    if (params.branchId !== undefined && params.branchId !== null) {
        query.branchId = params.branchId;
    }

    const response = await fetch(lookupRoute.url(kind, { query }), {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        signal,
    });

    const body: unknown = await response.json().catch(() => null);

    if (!response.ok) {
        const message =
            typeof body === 'object' &&
            body !== null &&
            'message' in body &&
            typeof body.message === 'string'
                ? body.message
                : GENERIC_ERROR;

        throw new Error(message);
    }

    return body as LookupPage;
}
