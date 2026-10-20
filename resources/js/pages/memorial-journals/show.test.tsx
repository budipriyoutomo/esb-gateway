import { render, screen, within } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import GeneralJournalIndex from '@/pages/general-journals/index';
import MemorialJournalShow from '@/pages/memorial-journals/show';
import type { MemorialJournal } from '@/types/accounting';

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
