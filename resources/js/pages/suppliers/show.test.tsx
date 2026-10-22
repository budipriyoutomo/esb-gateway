import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vite-plus/test';
import SupplierShow from '@/pages/suppliers/show';
import type { Permission } from '@/types';
import type { Supplier } from '@/types/master-data';

vi.mock('@/hooks/use-permissions', () => ({
    usePermissions: () => ({
        can: (_permission: Permission) => false,
        canAny: () => false,
    }),
}));

const supplier: Supplier = {
    provider: 'esb',
    id: 5,
    name: 'PT Sumber Pangan',
    code: 'SUP-05',
    categoryId: null,
    categoryName: 'Food',
    address: null,
    city: 'Jakarta',
    state: null,
    country: null,
    zipCode: null,
    phone1: null,
    phone2: null,
    fax: null,
    npwp: null,
    notes: null,
    creditDays: 30,
    payableCoaNo: null,
    advancePurchaseCoaNo: null,
    paymentMethodId: null,
    vatSubject: null,
    lockVat: null,
    invoiceOnReceipt: null,
    usedInTransactions: null,
    active: true,
    createdBy: null,
    createdAt: null,
    editedBy: null,
    editedAt: null,
    contacts: [
        {
            id: 1,
            name: 'Budi',
            cellPhone: null,
            email: 'budi@example.test',
            isDefault: true,
            sendsPurchaseOrderEmail: true,
        },
    ],
    bankAccounts: [
        {
            bankId: 2,
            bankName: 'BCA',
            accountName: 'PT Sumber Pangan',
            accountNumber: '1234566789',
        },
    ],
    products: [],
    attachmentCount: 0,
};

describe('SupplierShow', () => {
    it('keeps the supplier fields above the tabs', () => {
        render(<SupplierShow supplier={supplier} />);

        expect(screen.getByText('30 days')).toBeVisible();
    });

    it('puts contacts, bank accounts and product codes in tabs', () => {
        render(<SupplierShow supplier={supplier} />);

        const tabs = within(
            screen.getByRole('tablist', { name: 'Supplier sections' }),
        ).getAllByRole('tab');

        expect(tabs.map((tab) => tab.textContent)).toEqual([
            'Contacts 1',
            'Bank accounts 1',
            'Supplier product codes 0',
        ]);
        expect(
            screen.getByRole('table', { name: 'Supplier contacts' }),
        ).toBeVisible();
    });

    it('shows the whole account number', async () => {
        const user = userEvent.setup();
        render(<SupplierShow supplier={supplier} />);

        await user.click(screen.getByRole('tab', { name: 'Bank accounts 1' }));

        const panel = screen.getByRole('tabpanel');
        expect(panel).toHaveTextContent('1234566789');
        expect(panel).not.toHaveTextContent('last four digits');
    });
});
