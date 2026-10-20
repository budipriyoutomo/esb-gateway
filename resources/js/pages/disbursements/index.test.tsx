import { router } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import DisbursementIndex from '@/pages/disbursements/index';
import type { Permission } from '@/types';
import type { DisbursementSummary } from '@/types/disbursements';

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

const statuses = [
    { id: 1, name: 'New' },
    { id: 38, name: 'Waiting For Approval' },
    { id: 3, name: 'Authorized' },
    { id: 2, name: 'Rejected' },
];

const row: DisbursementSummary = {
    provider: 'esb',
    number: 'DB202603250001',
    date: '2026-03-25',
    branchId: 3,
    branchName: 'Outlet Senopati',
    supplierId: 14,
    supplierName: 'PT Properti Jaya',
    paymentMethod: 'Credit',
    reference: 'INV-RUKO-03',
    notes: null,
    grandTotal: 15000000.5,
    invoiceDueDate: '2026-04-24',
    overdue: true,
    hasApproval: true,
    statusId: 1,
    statusName: 'New',
};

describe('Disbursement list', () => {
    it('shows each disbursement with its ERP total and overdue flag', () => {
        render(
            <DisbursementIndex
                disbursements={[row]}
                pagination={null}
                filters={{}}
                statuses={statuses}
            />,
        );

        expect(
            screen.getByRole('link', { name: 'DB202603250001' }),
        ).toBeVisible();
        expect(screen.getByText('PT Properti Jaya')).toBeVisible();
        expect(screen.getByText('Overdue')).toBeVisible();
        expect(screen.getByText('15.000.000,50')).toBeVisible();
    });

    it('offers new and import only to whoever manages disbursements', () => {
        const { rerender } = render(
            <DisbursementIndex
                disbursements={[]}
                pagination={null}
                filters={{}}
                statuses={statuses}
            />,
        );

        expect(
            screen.queryByRole('link', { name: 'New disbursement' }),
        ).not.toBeInTheDocument();

        granted.permissions = ['esb.invoke', 'disbursements.manage'];
        rerender(
            <DisbursementIndex
                disbursements={[]}
                pagination={null}
                filters={{}}
                statuses={statuses}
            />,
        );

        expect(
            screen.getByRole('link', { name: 'New disbursement' }),
        ).toBeVisible();
        expect(screen.getByRole('link', { name: 'Import' })).toBeVisible();
    });

    it('sends the overdue filter as 1 and shows it as a chip', async () => {
        const get = vi.spyOn(router, 'get').mockImplementation(() => {});
        render(
            <DisbursementIndex
                disbursements={[]}
                pagination={null}
                filters={{ overdue: true }}
                statuses={statuses}
            />,
        );

        expect(screen.getByText('Overdue')).toBeVisible();

        await userEvent.type(
            screen.getByPlaceholderText('Disbursement number'),
            'DB1{Enter}',
        );

        expect(get).toHaveBeenCalled();
        const [, query] = get.mock.calls[0] as [
            string,
            Record<string, unknown>,
        ];
        expect(query).toMatchObject({ number: 'DB1', overdue: 1 });
    });
});
