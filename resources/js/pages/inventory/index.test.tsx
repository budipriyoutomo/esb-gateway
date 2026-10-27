import { router } from '@inertiajs/react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import InventoryIndex from '@/pages/inventory/index';
import type { Permission } from '@/types';
import type {
    InventoryDocumentSummary,
    InventoryKind,
} from '@/types/inventory';

const granted: { permissions: Permission[] } = { permissions: [] };

vi.mock('@/hooks/use-permissions', () => ({
    usePermissions: () => ({
        can: (permission: Permission) =>
            granted.permissions.includes(permission),
    }),
}));

beforeEach(() => {
    granted.permissions = [];
});

afterEach(() => {
    vi.restoreAllMocks();
});

const deliveries: InventoryKind = {
    value: 'goods-deliveries',
    label: 'Goods delivery',
    pluralLabel: 'Goods deliveries',
    statuses: [
        { id: 1, name: 'New' },
        { id: 3, name: 'Authorized' },
        { id: 27, name: 'Released' },
    ],
    filtersByBranch: true,
    searchable: false,
    rejectTakesReason: false,
    editable: false,
    attachments: null,
};

const row: InventoryDocumentSummary = {
    provider: 'esb',
    kind: 'goods-deliveries',
    number: 'GD202603210001',
    date: '2026-03-21',
    statusId: 3,
    statusName: 'Authorized',
    from: {
        branchId: 1,
        branchName: 'Gudang Pusat',
        locationId: null,
        locationName: null,
        name: null,
        address: null,
        phone: null,
    },
    to: {
        branchId: null,
        branchName: null,
        locationId: null,
        locationName: null,
        name: 'PT Pelanggan',
        address: null,
        phone: null,
    },
    transactionType: 'Product Sales',
    reference: 'PS-1',
    notes: null,
    createdBy: null,
};

function renderPage(kind = deliveries, filters = {}, documents = [row]) {
    const get = vi.spyOn(router, 'get').mockImplementation(() => {});
    render(
        <InventoryIndex
            kind={kind}
            documents={documents}
            pagination={{
                page: 1,
                limit: 20,
                count: 1,
                lastPage: 1,
                hasMorePages: false,
            }}
            filters={filters}
        />,
    );

    return get;
}

describe('Inventory document list', () => {
    it('lists the documents of its kind with where they go from and to', () => {
        renderPage();

        expect(
            screen.getByRole('heading', { name: 'Goods deliveries' }),
        ).toBeVisible();
        const table = screen.getByRole('table', { name: 'Goods deliveries' });
        expect(
            within(table).getByRole('link', { name: 'GD202603210001' }),
        ).toHaveAttribute('href', '/goods-deliveries/GD202603210001');
        expect(within(table).getByText('Gudang Pusat')).toBeVisible();
        expect(within(table).getByText('PT Pelanggan')).toBeVisible();
        expect(within(table).getByText('21 Mar 2026')).toBeVisible();
        expect(within(table).getByText('PS-1')).toBeVisible();
    });

    it('lists a pending row by its reference, without a link to a detail', () => {
        renderPage(deliveries, {}, [
            {
                ...row,
                number: null,
                statusId: 33,
                statusName: 'Pending',
                reference: 'GTR-7',
            },
            row,
        ]);

        const table = screen.getByRole('table', { name: 'Goods deliveries' });
        const pending = within(table).getByText('GTR-7').closest('tr');
        expect(pending).not.toBeNull();
        expect(within(pending!).queryByRole('link')).toBeNull();
        expect(within(pending!).getByText('Pending')).toBeVisible();
        expect(
            within(table).getByRole('link', { name: 'GD202603210001' }),
        ).toBeVisible();
    });

    it('searches by number and filters by the kind’s own statuses', async () => {
        const get = renderPage();

        await userEvent.click(
            screen.getByRole('button', { name: 'More filters' }),
        );
        await userEvent.selectOptions(
            screen.getByLabelText('Status'),
            'Released',
        );
        await userEvent.type(screen.getByRole('searchbox'), 'GD-9{Enter}');

        expect(get).toHaveBeenCalledWith(
            '/goods-deliveries',
            { number: 'GD-9', statusId: 27 },
            expect.anything(),
        );
    });

    it('sends a free-text search where the kind has one', async () => {
        const get = renderPage({
            ...deliveries,
            value: 'goods-transfer-requests',
            pluralLabel: 'Goods transfer requests',
            searchable: true,
        });

        await userEvent.type(screen.getByRole('searchbox'), 'restock{Enter}');

        expect(get).toHaveBeenCalledWith(
            '/goods-transfer-requests',
            { search: 'restock' },
            expect.anything(),
        );
    });

    it('offers a new document only for kinds created here, with the permission', () => {
        granted.permissions = [
            'esb.invoke',
            'goods-deliveries.manage',
            'simple-transfers.manage',
        ];
        const props = {
            documents: [],
            pagination: null,
            filters: {},
        };
        const { unmount } = render(
            <InventoryIndex kind={deliveries} {...props} />,
        );

        expect(
            screen.queryByRole('link', { name: /New/ }),
        ).not.toBeInTheDocument();
        unmount();

        render(
            <InventoryIndex
                kind={{
                    ...deliveries,
                    value: 'simple-transfers',
                    label: 'Simple transfer',
                    pluralLabel: 'Simple transfers',
                    editable: true,
                }}
                {...props}
            />,
        );

        expect(
            screen.getByRole('link', { name: 'New simple transfer' }),
        ).toHaveAttribute('href', '/simple-transfers/create');
        // Simple transfers also take a spreadsheet ([ESB-036]).
        expect(screen.getByRole('link', { name: 'Import' })).toHaveAttribute(
            'href',
            '/imports/simple-transfers',
        );
    });
});
