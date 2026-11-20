import { router } from '@inertiajs/react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import OutstandingRequests from '@/pages/purchase-requests/outstanding';
import type {
    OutstandingRequestItem,
    OutstandingRequestReport,
    OutstandingStage,
} from '@/types/pr-outstanding';

afterEach(() => {
    vi.restoreAllMocks();
});

const stages: OutstandingStage[] = [
    { value: 'not-ordered', label: 'Not ordered' },
    { value: 'not-received', label: 'Not received' },
    { value: 'partially-received', label: 'Partially received' },
    { value: 'not-as-requested', label: 'Not as requested' },
];

const unordered: OutstandingRequestItem = {
    stage: 'not-ordered',
    purchaseRequestNumbers: 'PR202603180002',
    purchaseRequestDate: '2026-03-18',
    purchaseOrderNumber: null,
    purchaseOrderDate: null,
    purchaseOrderStatus: null,
    requiredDate: '2026-03-25',
    branchName: 'Outlet Senopati',
    supplierName: 'PT Sumber Air',
    productCode: 'Air_mineral',
    productName: 'Air mineral',
    uomName: 'Bottle',
    requestedQty: null,
    orderedQty: null,
    receivedQty: null,
    outstandingQty: 20,
    ageDays: 14,
    overdueDays: 7,
};

const partial: OutstandingRequestItem = {
    ...unordered,
    stage: 'partially-received',
    purchaseRequestNumbers: 'PR202603180002,PR202603180003',
    purchaseRequestDate: null,
    purchaseOrderNumber: 'PO202603200001',
    purchaseOrderDate: '2026-03-20',
    purchaseOrderStatus: 'Receiving',
    requestedQty: 200,
    orderedQty: 200,
    receivedQty: 120,
    outstandingQty: 80,
    ageDays: 12,
    overdueDays: 0,
};

const report: OutstandingRequestReport = {
    items: [unordered, partial],
    pagination: {
        page: 1,
        limit: 50,
        count: 2,
        lastPage: 1,
        hasMorePages: false,
    },
    stageCounts: {
        'not-ordered': 1,
        'not-received': 0,
        'partially-received': 1,
        'not-as-requested': 0,
    },
    overdueCount: 1,
};

const period = { requiredDateFrom: '2026-03-01', requiredDateTo: '2026-03-31' };

describe('PR outstanding', () => {
    it('asks for a required-date period before anything is read', () => {
        render(
            <OutstandingRequests
                report={null}
                filters={{}}
                stages={stages}
                error={null}
            />,
        );

        expect(screen.getByText(/Choose a required-date period/)).toBeVisible();
        expect(screen.queryByRole('table')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: /Export CSV/ }),
        ).not.toBeInTheDocument();
    });

    it('lists each item with its stage, quantities, age and links', () => {
        render(
            <OutstandingRequests
                report={report}
                filters={period}
                stages={stages}
                error={null}
            />,
        );

        const table = screen.getByRole('table', { name: 'Outstanding items' });
        const [, first, second] = within(table).getAllByRole('row');

        expect(within(first).getByText('Not ordered')).toBeVisible();
        expect(
            within(first).getByRole('link', { name: 'PR202603180002' }),
        ).toHaveAttribute('href', '/purchase-requests/PR202603180002');
        expect(within(first).getByText('7 days overdue')).toBeVisible();

        expect(within(second).getByText('Partially received')).toBeVisible();
        expect(
            within(second).getByRole('link', { name: 'PO202603200001' }),
        ).toHaveAttribute('href', '/purchase-orders/PO202603200001');
        // Every request number of an order raised from several.
        expect(
            within(second).getByRole('link', { name: 'PR202603180003' }),
        ).toBeVisible();
        expect(within(second).getByText('80')).toBeVisible();
        expect(within(second).getByText('120')).toBeVisible();

        expect(
            screen.getByRole('link', { name: /Export CSV/ }),
        ).toHaveAttribute(
            'href',
            '/pr-outstanding/export?requiredDateFrom=2026-03-01&requiredDateTo=2026-03-31',
        );
    });

    it('shows the count of every stage and filters by one', async () => {
        const get = vi.spyOn(router, 'get').mockImplementation(() => {});

        render(
            <OutstandingRequests
                report={report}
                filters={period}
                stages={stages}
                error={null}
            />,
        );

        const tabs = screen.getByRole('navigation', { name: 'Stages' });
        expect(
            within(tabs).getByRole('button', { name: 'All 2' }),
        ).toHaveAttribute('aria-pressed', 'true');

        await userEvent.click(
            within(tabs).getByRole('button', { name: 'Not ordered 1' }),
        );

        expect(get).toHaveBeenCalledWith(
            '/pr-outstanding',
            { ...period, stage: 'not-ordered' },
            expect.anything(),
        );
    });

    it('says why the report was refused, with the filters still there', () => {
        render(
            <OutstandingRequests
                report={null}
                filters={period}
                stages={stages}
                error="This period has 140 open purchase orders; the report reads at most 100. Narrow the required dates or choose a branch."
            />,
        );

        expect(screen.getByRole('alert')).toHaveTextContent('Narrow');
        expect(
            screen.getByRole('form', { name: 'Report filters' }),
        ).toBeVisible();
    });
});
