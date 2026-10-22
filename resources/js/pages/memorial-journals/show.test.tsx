import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import GeneralJournalIndex from '@/pages/general-journals/index';
import MemorialJournalIndex from '@/pages/memorial-journals/index';
import MemorialJournalShow from '@/pages/memorial-journals/show';
import type { Permission } from '@/types';
import type { MemorialJournal } from '@/types/accounting';

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

const everything: Permission[] = [
    'esb.invoke',
    'memorial-journals.approve',
    'memorial-journals.manage',
];

const journal: MemorialJournal = {
    provider: 'esb',
    number: 'MJ202603260001',
    date: '2026-03-26',
    notes: 'Koreksi biaya sewa',
    hasApproval: true,
    statusId: 1,
    statusName: 'New',
    lines: [
        {
            id: 71,
            branchId: 3,
            branchName: 'Outlet Senopati',
            coaNo: '6101',
            coaDescription: 'Beban Sewa',
            costCenter: null,
            project: null,
            currencyName: 'Rupiah',
            currencyId: 1,
            rate: 1,
            debit: 500000,
            credit: 0,
            notes: 'Koreksi',
        },
    ],
    attachmentCount: 1,
    createdBy: 'Andi Pratama',
    createdAt: '2026-03-26 08:00:00',
    editedBy: null,
    editedAt: null,
    authorizedBy: null,
    authorizedAt: null,
};

describe('Memorial journal detail', () => {
    it('shows the journal, who made it, and its lines', () => {
        render(<MemorialJournalShow journal={journal} />);

        expect(
            screen.getByRole('heading', { name: 'MJ202603260001' }),
        ).toBeVisible();
        expect(screen.getByText('Koreksi biaya sewa')).toBeVisible();
        expect(screen.getByText(/Andi Pratama/)).toBeVisible();

        const lines = screen.getByRole('table', { name: 'Journal lines' });
        expect(within(lines).getByText('Beban Sewa')).toBeVisible();
        expect(within(lines).getByText('500.000,00')).toBeVisible();
    });

    it('offers no actions without the permissions', () => {
        granted.permissions = ['esb.invoke'];
        render(<MemorialJournalShow journal={journal} />);

        expect(screen.queryByRole('button')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Edit' }),
        ).not.toBeInTheDocument();
        expect(screen.getByText('1 attachment')).toBeVisible();
    });

    it('offers edit, authorize, reject, delete and an image upload with its permissions', async () => {
        granted.permissions = everything;
        render(<MemorialJournalShow journal={journal} />);

        expect(screen.getByRole('link', { name: 'Edit' })).toBeVisible();

        for (const name of ['Authorize', 'Reject', 'Delete', 'Attach']) {
            expect(screen.getByRole('button', { name })).toBeVisible();
        }

        expect(screen.getByLabelText('Image to attach')).toHaveAttribute(
            'accept',
            '.jpg,.jpeg,.png',
        );

        // A New journal may be rejected without a reason.
        await userEvent.click(screen.getByRole('button', { name: 'Reject' }));
        expect(screen.getByLabelText('Reason (optional)')).toBeVisible();
    });

    it('asks for a reason to reject a journal waiting for approval', async () => {
        granted.permissions = everything;
        render(
            <MemorialJournalShow
                journal={{
                    ...journal,
                    statusId: 38,
                    statusName: 'Waiting For Approval',
                }}
            />,
        );

        await userEvent.click(screen.getByRole('button', { name: 'Reject' }));
        expect(screen.getByLabelText('Reason')).toBeVisible();
    });

    it('offers no decision once authorized, but still delete', () => {
        granted.permissions = everything;
        render(
            <MemorialJournalShow
                journal={{
                    ...journal,
                    statusId: 3,
                    statusName: 'Authorized',
                }}
            />,
        );

        expect(
            screen.queryByRole('button', { name: 'Authorize' }),
        ).not.toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Delete' })).toBeVisible();
    });
});

describe('Memorial journal list', () => {
    it('offers a new journal only to whoever manages them', () => {
        const props = {
            journals: [],
            pagination: null,
            filters: {},
            statuses: [{ id: 1, name: 'New' }],
        };
        const { rerender } = render(<MemorialJournalIndex {...props} />);

        expect(
            screen.queryByRole('link', { name: 'New memorial journal' }),
        ).not.toBeInTheDocument();

        granted.permissions = ['esb.invoke', 'memorial-journals.manage'];
        rerender(<MemorialJournalIndex {...props} />);

        expect(
            screen.getByRole('link', { name: 'New memorial journal' }),
        ).toBeVisible();
    });
});

describe('General journal list', () => {
    it('marks an entry the ERP does not let its users view', () => {
        render(
            <GeneralJournalIndex
                entries={[
                    {
                        provider: 'esb',
                        journalHeadId: 88123,
                        date: '2026-03-25',
                        transactionType: 'Disbursement',
                        reference: 'DB202603250001',
                        notes: null,
                        viewableInErp: false,
                    },
                ]}
                pagination={null}
                filters={{}}
            />,
        );

        expect(screen.getByText('DB202603250001')).toBeVisible();
        expect(screen.getByText('Not viewable in the ERP')).toBeVisible();
    });
});
