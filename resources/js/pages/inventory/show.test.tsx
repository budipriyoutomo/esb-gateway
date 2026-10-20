import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import InventoryShow from '@/pages/inventory/show';
import type { Permission } from '@/types';
import type { InventoryDocument, InventoryKind } from '@/types/inventory';

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

const receipts: InventoryKind = {
    value: 'goods-receipts',
    label: 'Goods receipt',
    pluralLabel: 'Goods receipts',
    statuses: [],
    filtersByBranch: true,
    searchable: false,
    rejectTakesReason: false,
    editable: false,
    attachments: null,
};

const transferRequests: InventoryKind = {
    ...receipts,
    value: 'goods-transfer-requests',
    label: 'Goods transfer request',
    pluralLabel: 'Goods transfer requests',
    searchable: true,
    editable: true,
    attachments: {
        uploadReplaces: false,
        removable: true,
        extensions: ['jpg', 'jpeg', 'png'],
    },
};

const deliveries: InventoryKind = {
    ...receipts,
    value: 'goods-deliveries',
    label: 'Goods delivery',
    pluralLabel: 'Goods deliveries',
    attachments: {
        uploadReplaces: true,
        removable: true,
        extensions: ['pdf', 'bmp', 'gif', 'jpg', 'jpeg', 'png'],
    },
};

const itemJournals: InventoryKind = {
    ...receipts,
    value: 'item-journals',
    label: 'Item journal',
    pluralLabel: 'Item journals',
    rejectTakesReason: true,
};

const empty = {
    branchId: null,
    branchName: null,
    locationId: null,
    locationName: null,
    name: null,
    address: null,
    phone: null,
};

const receipt: InventoryDocument = {
    provider: 'esb',
    kind: 'goods-receipts',
    number: 'GR202603220001',
    date: '2026-03-22',
    statusId: 5,
    statusName: 'Received',
    from: { ...empty, name: 'CV Maju Jaya' },
    to: {
        ...empty,
        branchId: 3,
        branchName: 'Outlet Senopati',
        locationName: 'Gudang Outlet',
    },
    transactionType: 'Purchase Order',
    reference: 'PO202603200001',
    referenceDate: '2026-03-20',
    notes: 'Partial',
    costCenter: null,
    project: null,
    lines: [
        {
            productDetailId: 501,
            productCode: 'BB-001',
            productName: 'Beras Premium',
            unit: 'KG',
            qty: 20,
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
        },
    ],
    linkedDocuments: [
        {
            type: 'Purchase invoice',
            number: 'PI202603230001',
            date: '2026-03-23',
            statusName: 'New',
        },
    ],
    attachmentCount: 1,
    stockUsed: true,
    createdBy: 'sari',
    createdAt: '2026-03-22 12:00:00',
    editedBy: null,
    editedAt: null,
    authorizedBy: 'budi',
    authorizedAt: '2026-03-22 13:00:00',
    canEdit: null,
    canDelete: null,
    carryOver: {
        purchaseRequestNumbers: [],
        costCenterId: null,
        projectId: null,
        requestTemplateId: null,
        hasAssets: false,
    },
};

const transferRequest = (
    statusId: number,
    overrides: Partial<InventoryDocument> = {},
): InventoryDocument => ({
    ...receipt,
    kind: 'goods-transfer-requests',
    number: 'GTR202603200001',
    statusId,
    stockUsed: null,
    ...overrides,
});

describe('Inventory document detail', () => {
    it('shows where the goods came from and went to, and the reference', () => {
        render(<InventoryShow kind={receipts} document={receipt} />);

        expect(
            screen.getByRole('heading', { name: 'GR202603220001' }),
        ).toBeVisible();
        expect(screen.getByText('CV Maju Jaya')).toBeVisible();
        expect(
            screen.getByText('Outlet Senopati · Gudang Outlet'),
        ).toBeVisible();
        expect(screen.getByText('Purchase Order PO202603200001')).toBeVisible();
        expect(screen.getByText('PI202603230001')).toBeVisible();
    });

    it('shows only the quantity columns the document has', () => {
        render(<InventoryShow kind={receipts} document={receipt} />);

        const table = screen.getByRole('table', { name: 'Lines' });
        const headers = within(table)
            .getAllByRole('columnheader')
            .map((header) => header.textContent);

        expect(headers).toEqual(['Code', 'Product', 'Qty', 'Unit']);
        expect(within(table).getByText('20')).toBeVisible();
    });

    it('warns when the stock has been used since', () => {
        render(<InventoryShow kind={receipts} document={receipt} />);

        expect(screen.getByRole('status')).toHaveTextContent(
            'has been used by another transaction',
        );
    });

    it('offers no actions without the permissions', () => {
        granted.permissions = ['esb.invoke'];
        render(<InventoryShow kind={receipts} document={receipt} />);

        expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });

    it('offers authorize, reject and delete with the kind’s permissions', () => {
        granted.permissions = [
            'esb.invoke',
            'goods-receipts.approve',
            'goods-receipts.manage',
        ];
        render(<InventoryShow kind={receipts} document={receipt} />);

        for (const name of ['Authorize', 'Reject', 'Delete']) {
            expect(screen.getByRole('button', { name })).toBeVisible();
        }
    });

    it('does not take another kind’s permissions for this one', () => {
        granted.permissions = [
            'esb.invoke',
            'goods-deliveries.approve',
            'goods-deliveries.manage',
        ];
        render(<InventoryShow kind={receipts} document={receipt} />);

        expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });

    it('lets an item journal be rejected with an optional reason', async () => {
        granted.permissions = ['esb.invoke', 'item-journals.approve'];
        render(
            <InventoryShow
                kind={itemJournals}
                document={{ ...receipt, kind: 'item-journals' }}
            />,
        );

        await userEvent.click(screen.getByRole('button', { name: 'Reject' }));

        expect(screen.getByLabelText('Reason (optional)')).toBeVisible();
    });

    describe('goods transfer requests', () => {
        beforeEach(() => {
            granted.permissions = [
                'esb.invoke',
                'goods-transfer-requests.approve',
                'goods-transfer-requests.manage',
            ];
        });

        it('offers a decision only while the request is new', () => {
            render(
                <InventoryShow
                    kind={transferRequests}
                    document={transferRequest(1)}
                />,
            );

            expect(
                screen.getByRole('button', { name: 'Authorize' }),
            ).toBeVisible();
            expect(
                screen.queryByRole('button', { name: 'Finish' }),
            ).not.toBeInTheDocument();
        });

        it('offers finish once authorized or delivering', () => {
            for (const status of [3, 6]) {
                const { unmount } = render(
                    <InventoryShow
                        kind={transferRequests}
                        document={transferRequest(status)}
                    />,
                );

                expect(
                    screen.getByRole('button', { name: 'Finish' }),
                ).toBeVisible();
                expect(
                    screen.queryByRole('button', { name: 'Authorize' }),
                ).not.toBeInTheDocument();
                expect(
                    screen.queryByRole('button', { name: 'Reopen' }),
                ).not.toBeInTheDocument();
                unmount();
            }
        });

        it('offers reopen once finished', () => {
            render(
                <InventoryShow
                    kind={transferRequests}
                    document={transferRequest(8)}
                />,
            );

            expect(
                screen.getByRole('button', { name: 'Reopen' }),
            ).toBeVisible();
        });

        it('hides delete when the ERP says the request cannot be deleted', () => {
            render(
                <InventoryShow
                    kind={transferRequests}
                    document={transferRequest(1, { canDelete: false })}
                />,
            );

            expect(
                screen.queryByRole('button', { name: 'Delete' }),
            ).not.toBeInTheDocument();
        });
    });

    describe('editing', () => {
        beforeEach(() => {
            granted.permissions = [
                'esb.invoke',
                'goods-transfer-requests.manage',
            ];
        });

        it('links to the edit form of a kind edited here', () => {
            render(
                <InventoryShow
                    kind={transferRequests}
                    document={transferRequest(1)}
                />,
            );

            expect(screen.getByRole('link', { name: 'Edit' })).toHaveAttribute(
                'href',
                '/goods-transfer-requests/GTR202603200001/edit',
            );
        });

        it('offers no edit when the ERP says no, or the document moves assets', () => {
            const { unmount } = render(
                <InventoryShow
                    kind={transferRequests}
                    document={transferRequest(1, { canEdit: false })}
                />,
            );

            expect(
                screen.queryByRole('link', { name: 'Edit' }),
            ).not.toBeInTheDocument();
            unmount();

            render(
                <InventoryShow
                    kind={transferRequests}
                    document={transferRequest(1, {
                        carryOver: {
                            ...receipt.carryOver,
                            hasAssets: true,
                        },
                    })}
                />,
            );

            expect(
                screen.queryByRole('link', { name: 'Edit' }),
            ).not.toBeInTheDocument();
        });

        it('offers no edit for kinds the ERP creates from a reference', () => {
            granted.permissions = ['esb.invoke', 'goods-receipts.manage'];
            render(<InventoryShow kind={receipts} document={receipt} />);

            expect(
                screen.queryByRole('link', { name: 'Edit' }),
            ).not.toBeInTheDocument();
        });
    });

    describe('attachments', () => {
        it('lets a manager attach the types the kind takes', () => {
            granted.permissions = [
                'esb.invoke',
                'goods-transfer-requests.manage',
            ];
            render(
                <InventoryShow
                    kind={transferRequests}
                    document={transferRequest(1, { attachmentCount: 1 })}
                />,
            );

            expect(screen.getByText('1 attachment')).toBeVisible();
            expect(screen.getByLabelText('Files to attach')).toHaveAttribute(
                'accept',
                '.jpg,.jpeg,.png',
            );
            expect(
                screen.getByRole('button', { name: 'Remove all' }),
            ).toBeVisible();
        });

        it('asks to remove the current attachments first where an upload would replace them', () => {
            granted.permissions = ['esb.invoke', 'goods-deliveries.manage'];
            render(
                <InventoryShow
                    kind={deliveries}
                    document={{
                        ...receipt,
                        kind: 'goods-deliveries',
                        attachmentCount: 2,
                    }}
                />,
            );

            expect(
                screen.queryByLabelText('Files to attach'),
            ).not.toBeInTheDocument();
            expect(
                screen.getByText(/Remove the current attachments first/),
            ).toBeVisible();
        });

        it('only counts the attachments of a kind that takes none here', () => {
            granted.permissions = ['esb.invoke', 'goods-receipts.manage'];
            render(<InventoryShow kind={receipts} document={receipt} />);

            expect(
                screen.queryByLabelText('Files to attach'),
            ).not.toBeInTheDocument();
            expect(screen.getByText('Attachments')).toBeVisible();
        });
    });
});
