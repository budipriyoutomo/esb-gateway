import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import DisbursementShow from '@/pages/disbursements/show';
import type { Permission } from '@/types';
import type { Disbursement } from '@/types/disbursements';

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

function disbursement(overrides: Partial<Disbursement> = {}): Disbursement {
    return {
        provider: 'esb',
        number: 'DB202603250001',
        date: '2026-03-25',
        statusId: 1,
        statusName: 'New',
        branchId: 3,
        branchName: 'Outlet Senopati',
        paymentId: 2,
        paymentName: 'Credit',
        cashBankCoaNo: null,
        supplierId: 14,
        supplierName: 'PT Properti Jaya',
        currencyId: 1,
        currencyName: 'Rupiah',
        currencySign: 'Rp',
        rate: 1,
        creditTerm: 30,
        invoiceDueDate: '2026-04-24',
        reference: 'INV-RUKO-03',
        notes: 'Sewa ruko Maret',
        bankName: 'BCA',
        bankAccountName: 'PT Properti Jaya',
        bankAccountNumber: '1234567890123',
        costCenter: 'Operasional',
        project: null,
        grandTotal: 15000000.5,
        releasePaymentCode: null,
        lines: [
            {
                branchId: 3,
                branchName: 'Outlet Senopati',
                coaNo: '6101',
                coaDescription: 'Beban Sewa',
                description: 'Sewa Maret',
                amount: 15000000.5,
            },
        ],
        linkedDocuments: [
            {
                type: 'Purchase payment',
                number: 'PP202603280001',
                date: '2026-03-28',
                total: 5000000,
                currencyName: 'Rupiah',
                statusName: 'Authorized',
            },
        ],
        attachmentCount: 0,
        createdBy: 'andi',
        createdAt: '2026-03-25 09:00:00',
        editedBy: null,
        editedAt: null,
        authorizedBy: null,
        authorizedAt: null,
        ...overrides,
    };
}

describe('Disbursement detail', () => {
    it('shows who is paid, how, the lines and the payments against it', () => {
        render(<DisbursementShow disbursement={disbursement()} />);

        expect(
            screen.getByRole('heading', { name: 'DB202603250001' }),
        ).toBeVisible();
        expect(screen.getAllByText('PT Properti Jaya').length).toBeGreaterThan(
            0,
        );
        expect(screen.getByText('30 days')).toBeVisible();
        expect(
            screen.getByText('BCA · PT Properti Jaya · 1234567890123'),
        ).toBeVisible();
        expect(screen.getByText('Beban Sewa')).toBeVisible();
        expect(screen.getByText('PP202603280001')).toBeVisible();
    });

    it('shows a cash disbursement’s account, not a credit term', () => {
        render(
            <DisbursementShow
                disbursement={disbursement({
                    paymentId: 1,
                    paymentName: 'Cash',
                    cashBankCoaNo: '1101',
                })}
            />,
        );

        expect(screen.getByText('1101')).toBeVisible();
        expect(screen.queryByText('30 days')).not.toBeInTheDocument();
    });

    it('offers no actions without the permissions', () => {
        granted.permissions = ['esb.invoke'];
        render(<DisbursementShow disbursement={disbursement()} />);

        expect(screen.queryByRole('button')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Edit' }),
        ).not.toBeInTheDocument();
    });

    it('offers edit, authorize, reject and delete with its permissions', async () => {
        granted.permissions = [
            'esb.invoke',
            'disbursements.approve',
            'disbursements.manage',
        ];
        render(<DisbursementShow disbursement={disbursement()} />);

        expect(screen.getByRole('link', { name: 'Edit' })).toBeVisible();

        for (const name of ['Authorize', 'Reject', 'Delete']) {
            expect(screen.getByRole('button', { name })).toBeVisible();
        }

        await userEvent.click(screen.getByRole('button', { name: 'Reject' }));
        expect(screen.getByLabelText('Reason (optional)')).toBeVisible();
    });

    it('offers no decision once authorized, but still delete', () => {
        granted.permissions = [
            'esb.invoke',
            'disbursements.approve',
            'disbursements.manage',
        ];
        render(
            <DisbursementShow
                disbursement={disbursement({
                    statusId: 3,
                    statusName: 'Authorized',
                })}
            />,
        );

        expect(
            screen.queryByRole('button', { name: 'Authorize' }),
        ).not.toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Delete' })).toBeVisible();
    });

    it('does not offer an upload that would replace the current attachments', () => {
        granted.permissions = ['esb.invoke', 'disbursements.manage'];
        render(
            <DisbursementShow
                disbursement={disbursement({ attachmentCount: 2 })}
            />,
        );

        expect(
            screen.queryByRole('button', { name: /Attach/ }),
        ).not.toBeInTheDocument();
        expect(screen.getByText(/Add files in the ERP/)).toBeVisible();
    });
});
