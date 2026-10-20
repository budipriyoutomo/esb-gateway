import { router } from '@inertiajs/react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import InventoryForm from '@/pages/inventory/form';
import type {
    InventoryDocument,
    InventoryKind,
    InventoryLine,
    InventoryParty,
} from '@/types/inventory';

afterEach(() => {
    vi.restoreAllMocks();
});

function kind(
    value: InventoryKind['value'],
    label: string,
    pluralLabel: string,
): InventoryKind {
    return {
        value,
        label,
        pluralLabel,
        statuses: [],
        filtersByBranch: true,
        searchable: false,
        rejectTakesReason: false,
        editable: true,
        attachments: null,
    };
}

const transferRequests = kind(
    'goods-transfer-requests',
    'Goods transfer request',
    'Goods transfer requests',
);
const itemJournals = kind('item-journals', 'Item journal', 'Item journals');
const simpleTransfers = kind(
    'simple-transfers',
    'Simple transfer',
    'Simple transfers',
);

function party(overrides: Partial<InventoryParty>): InventoryParty {
    return {
        branchId: null,
        branchName: null,
        locationId: null,
        locationName: null,
        name: null,
        address: null,
        phone: null,
        ...overrides,
    };
}

function line(overrides: Partial<InventoryLine>): InventoryLine {
    return {
        productDetailId: 501,
        productCode: 'BB-001',
        productName: 'Beras Premium',
        unit: 'KG',
        qty: 10,
        requestedQty: null,
        availableQty: null,
        stockQty: null,
        pendingQty: null,
        receivedQty: null,
        outstandingQty: null,
        cost: null,
        notes: null,
        purpose: null,
        purposeId: null,
        ...overrides,
    };
}

function document(overrides: Partial<InventoryDocument>): InventoryDocument {
    return {
        provider: 'esb',
        kind: 'item-journals',
        number: 'IJ202603230001',
        date: '2026-03-23',
        statusId: 1,
        statusName: 'New',
        from: null,
        to: null,
        transactionType: null,
        reference: null,
        referenceDate: null,
        notes: 'Stock opname',
        costCenter: null,
        project: null,
        lines: [],
        linkedDocuments: [],
        attachmentCount: 0,
        stockUsed: null,
        createdBy: null,
        createdAt: null,
        editedBy: null,
        editedAt: null,
        authorizedBy: null,
        authorizedAt: null,
        canEdit: null,
        canDelete: null,
        carryOver: {
            purchaseRequestNumbers: [],
            costCenterId: null,
            projectId: null,
            requestTemplateId: null,
            hasAssets: false,
        },
        ...overrides,
    };
}

function submitted(visit: { mock: { calls: unknown[][] } }) {
    const [url, options] = visit.mock.calls[0] as [
        string,
        { method: string; data: unknown },
    ];

    return { url, method: options.method, data: options.data };
}

describe('Inventory document form', () => {
    it('starts a new transfer request with one line and asks for a branch before products', async () => {
        render(<InventoryForm kind={transferRequests} document={null} />);

        expect(
            screen.getByRole('heading', {
                name: 'New goods transfer request',
            }),
        ).toBeVisible();
        expect(screen.getByLabelText('To branch')).toBeVisible();
        expect(screen.getByLabelText('Requested, line 1')).toBeVisible();

        const product = screen.getByLabelText('Product, line 1');
        expect(product).toBeDisabled();
        expect(product).toHaveAttribute('placeholder', 'Choose a branch first');

        const lines = screen.getByRole('table', { name: 'Products' });
        await userEvent.click(
            screen.getByRole('button', { name: 'Add product' }),
        );
        expect(within(lines).getAllByRole('row')).toHaveLength(3);
        await userEvent.click(
            within(lines).getAllByRole('button', { name: /Remove line/ })[1],
        );
        expect(within(lines).getAllByRole('row')).toHaveLength(2);
    });

    it('sends an edited item journal back with its purposes and costs', async () => {
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        render(
            <InventoryForm
                kind={itemJournals}
                document={document({
                    from: party({
                        branchId: 3,
                        branchName: 'Outlet Senopati',
                        locationId: 300,
                        locationName: 'Gudang Outlet',
                    }),
                    lines: [
                        line({
                            qty: -3,
                            purpose: 'Waste',
                            purposeId: 2,
                            cost: 12000,
                        }),
                    ],
                })}
            />,
        );

        expect(
            screen.getByRole('heading', { name: 'Edit IJ202603230001' }),
        ).toBeVisible();
        expect(screen.getByLabelText('Purpose, line 1')).toHaveValue('Waste');

        await userEvent.clear(screen.getByLabelText('Qty, line 1'));
        await userEvent.type(screen.getByLabelText('Qty, line 1'), '-4');
        await userEvent.click(
            screen.getByRole('button', { name: 'Save changes' }),
        );

        const sent = submitted(visit);
        expect(sent.url).toMatch(/\/item-journals\/IJ202603230001$/);
        expect(sent.method).toBe('put');
        expect(sent.data).toEqual({
            date: '2026-03-23',
            branchId: 3,
            locationId: 300,
            notes: 'Stock opname',
            lines: [
                { productDetailId: 501, qty: -4, purposeId: 2, cost: 12000 },
            ],
        });
    });

    it('creates a simple transfer between two locations', async () => {
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        render(
            <InventoryForm
                kind={simpleTransfers}
                document={document({
                    kind: 'simple-transfers',
                    number: 'ST202603240001',
                    notes: null,
                    from: party({
                        branchId: 3,
                        branchName: 'Outlet Senopati',
                        locationId: 300,
                        locationName: 'Gudang Outlet',
                    }),
                    to: party({
                        branchId: 3,
                        locationId: 301,
                        locationName: 'Dapur',
                    }),
                    lines: [line({})],
                })}
            />,
        );

        expect(screen.getByLabelText('To location')).toHaveValue('Dapur');
        expect(
            screen.queryByLabelText('Purpose, line 1'),
        ).not.toBeInTheDocument();

        await userEvent.click(
            screen.getByRole('button', { name: 'Save changes' }),
        );

        expect(submitted(visit).data).toEqual({
            date: '2026-03-23',
            branchId: 3,
            locationId: 300,
            destinationLocationId: 301,
            notes: '',
            lines: [{ productDetailId: 501, qty: 10 }],
        });
    });

    it('posts a transfer request with its requested quantities', async () => {
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        render(
            <InventoryForm
                kind={transferRequests}
                document={document({
                    kind: 'goods-transfer-requests',
                    number: 'GTR202603200001',
                    notes: 'Restock outlet',
                    from: party({
                        branchId: 1,
                        branchName: 'Gudang Pusat',
                        locationId: null,
                    }),
                    to: party({ branchId: 3, branchName: 'Outlet Senopati' }),
                    lines: [line({ qty: 20, requestedQty: 25 })],
                })}
            />,
        );

        await userEvent.click(
            screen.getByRole('button', { name: 'Save changes' }),
        );

        const sent = submitted(visit);
        expect(sent.method).toBe('put');
        expect(sent.url).toMatch(/\/goods-transfer-requests\/GTR202603200001$/);
        expect(sent.data).toEqual({
            date: '2026-03-23',
            branchId: 1,
            locationId: null,
            destinationBranchId: 3,
            notes: 'Restock outlet',
            lines: [{ productDetailId: 501, qty: 20, requestQty: 25 }],
        });
    });
});
