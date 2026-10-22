import { router } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import MemorialJournalForm from '@/pages/memorial-journals/form';
import type { JournalLine, MemorialJournal } from '@/types/accounting';

afterEach(() => {
    vi.restoreAllMocks();
});

function line(overrides: Partial<JournalLine> = {}): JournalLine {
    return {
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
        ...overrides,
    };
}

const journal: MemorialJournal = {
    provider: 'esb',
    number: 'MJ202603260001',
    date: '2026-03-26',
    notes: 'Koreksi biaya sewa',
    hasApproval: true,
    statusId: 1,
    statusName: 'New',
    lines: [
        line(),
        line({
            id: 72,
            coaNo: '2101',
            coaDescription: 'Hutang Usaha',
            debit: 0,
            credit: 500000,
            notes: null,
        }),
    ],
    attachmentCount: 0,
    createdBy: null,
    createdAt: null,
    editedBy: null,
    editedAt: null,
    authorizedBy: null,
    authorizedAt: null,
};

function submitted(visit: { mock: { calls: unknown[][] } }) {
    const [url, options] = visit.mock.calls[0] as [
        string,
        { method: string; data: unknown },
    ];

    return { url, method: options.method, data: options.data };
}

describe('Memorial journal form', () => {
    it('starts a new journal with two lines', () => {
        render(<MemorialJournalForm journal={null} />);

        expect(
            screen.getByRole('heading', { name: 'New memorial journal' }),
        ).toBeVisible();
        expect(screen.getByLabelText('Debit, line 1')).toBeVisible();
        expect(screen.getByLabelText('Debit, line 2')).toBeVisible();
        // The vendor needs at least two lines.
        expect(
            screen.getByRole('button', { name: 'Remove line 1' }),
        ).toBeDisabled();
    });

    it('sends an edited journal in the request’s names, each line on one side', async () => {
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        render(<MemorialJournalForm journal={journal} />);

        expect(
            screen.getByRole('heading', { name: 'Edit MJ202603260001' }),
        ).toBeVisible();
        expect(screen.getByLabelText('Credit, line 1')).toHaveValue(null);

        // A credit on the first line takes it off the debit side.
        await userEvent.type(screen.getByLabelText('Credit, line 1'), '250');
        expect(screen.getByLabelText('Debit, line 1')).toHaveValue(null);

        await userEvent.click(
            screen.getByRole('button', { name: 'Save changes' }),
        );

        const sent = submitted(visit);
        expect(sent.url).toMatch(/\/memorial-journals\/MJ202603260001$/);
        expect(sent.method).toBe('put');
        expect(sent.data).toEqual({
            date: '2026-03-26',
            notes: 'Koreksi biaya sewa',
            lines: [
                {
                    id: 71,
                    branchId: 3,
                    coaNo: '6101',
                    currencyId: 1,
                    rate: 1,
                    debit: null,
                    credit: 250,
                    notes: 'Koreksi',
                },
                {
                    id: 72,
                    branchId: 3,
                    coaNo: '2101',
                    currencyId: 1,
                    rate: 1,
                    debit: null,
                    credit: 500000,
                    notes: '',
                },
            ],
        });
    });

    it('shows the totals and says when they differ, without blocking the save', async () => {
        render(<MemorialJournalForm journal={journal} />);

        expect(screen.getByText('Debit and credit balance.')).toBeVisible();

        await userEvent.clear(screen.getByLabelText('Debit, line 1'));
        await userEvent.type(screen.getByLabelText('Debit, line 1'), '400000');

        expect(
            screen.getByText(/Debit and credit differ by 100\.000,00/),
        ).toBeVisible();
        expect(
            screen.getByRole('button', { name: 'Save changes' }),
        ).toBeEnabled();
    });

    it('adds a line on the previous line’s branch and currency', async () => {
        render(<MemorialJournalForm journal={journal} />);

        await userEvent.click(screen.getByRole('button', { name: 'Add line' }));

        expect(screen.getByLabelText('Debit, line 3')).toBeVisible();
        expect(
            screen.getByRole('button', { name: 'Remove line 3' }),
        ).toBeEnabled();
    });
});
