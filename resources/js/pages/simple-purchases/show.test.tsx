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
import SimplePurchaseShow from '@/pages/simple-purchases/show';
import type { Permission } from '@/types';
import type { SimplePurchase } from '@/types/simple-purchase';

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

const simplePurchase: SimplePurchase = {
    provider: 'esb',
    number: 'SP202603260001',
    supplierInvoiceNumber: null,
    date: '2026-03-26',
    dueDate: null,
    purchaseTypeName: null,
    branchId: 3,
    branchName: 'Outlet Senopati',
    locationName: null,
    supplierId: 9,
    supplierName: 'Acme',
    supplierPhone: null,
    contactName: null,
    paymentMethodName: null,
    currencyId: 1,
    currencyName: 'Rupiah',
    currencySign: 'Rp',
    rate: 1,
    costCenterName: null,
    projectName: null,
    costTotal: null,
    total: 1500,
    statusId: 1,
    statusName: 'New',
    notes: null,
    createdByName: null,
    createdAt: null,
    editedByName: null,
    editedAt: null,
    authorizedByName: null,
    authorizedAt: null,
    lines: [],
    costs: [],
    payments: [],
};

function renderPage() {
    render(<SimplePurchaseShow simplePurchase={simplePurchase} />);
}

describe('Simple purchase detail', () => {
    it('offers no actions without the permissions', () => {
        granted.permissions = ['esb.invoke'];
        renderPage();

        expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });

    it('offers delete to a manager, not a decision', () => {
        granted.permissions = ['esb.invoke', 'simple-purchases.manage'];
        renderPage();

        expect(screen.getByRole('button', { name: 'Delete' })).toBeVisible();
        expect(
            screen.queryByRole('button', { name: 'Authorize' }),
        ).not.toBeInTheDocument();
    });

    it('requires a reason to reject, and sends it', async () => {
        granted.permissions = ['esb.invoke', 'simple-purchases.approve'];
        const post = vi.spyOn(router, 'post').mockImplementation(() => {});
        renderPage();

        await userEvent.click(screen.getByRole('button', { name: 'Reject' }));

        const dialog = screen.getByRole('dialog');
        expect(
            within(dialog).getByText(
                /The simple purchase is rejected in the ERP straight away, with the reason below\./,
            ),
        ).toBeVisible();

        const send = within(dialog).getByRole('button', { name: 'Reject' });
        expect(send).toBeDisabled();

        const reason = within(dialog).getByLabelText('Reason');
        expect(reason).toHaveAttribute('maxLength', '500');
        await userEvent.type(reason, 'Wrong supplier');
        await userEvent.click(send);

        expect(post).toHaveBeenCalledExactlyOnceWith(
            '/simple-purchases/SP202603260001/decisions',
            { decision: 'reject', reason: 'Wrong supplier' },
            expect.anything(),
        );
    });
});
