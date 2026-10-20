import { render, screen, within } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import GeneralLedgerIndex from '@/pages/general-ledger/index';
import GeneralLedgerSummary from '@/pages/general-ledger/summary';
import type { LedgerEntry } from '@/types/accounting';

afterEach(() => {
    vi.restoreAllMocks();
});

const entry: LedgerEntry = {
    date: '2026-03-25',
    branchId: 3,
    branchName: 'Outlet Senopati',
    coaNo: '1101',
    coaDescription: 'Kas Outlet',
    transactionType: 'Disbursement',
    reference: 'DB202603250002',
    notes: 'Listrik',
    info: 'Listrik Maret',
    debit: 0,
    credit: 250000,
    costCenter: null,
    project: null,
    createdAt: null,
};

describe('General ledger', () => {
    it('asks for the account and period before anything is read', () => {
        render(
            <GeneralLedgerIndex
                entries={null}
                pagination={null}
                filters={{}}
                error={null}
            />,
        );

        expect(
            screen.getByText(/Choose an account and a period/),
        ).toBeVisible();
        expect(
            screen.queryByRole('table', { name: 'Ledger entries' }),
        ).not.toBeInTheDocument();
    });

    it('shows the ERP’s entries with no balance column', () => {
        render(
            <GeneralLedgerIndex
                entries={[entry]}
                pagination={{
                    page: 1,
                    limit: 20,
                    count: 1,
                    lastPage: 1,
                    hasMorePages: false,
                }}
                filters={{
                    coaNo: '1101',
                    dateFrom: '2026-03-01',
                    dateTo: '2026-03-31',
                }}
                error={null}
            />,
        );

        const table = screen.getByRole('table', { name: 'Ledger entries' });
        const headers = within(table)
            .getAllByRole('columnheader')
            .map((header) => header.textContent);

        expect(headers).not.toContain('Balance');
        expect(within(table).getByText('250.000,00')).toBeVisible();
        expect(within(table).getByText('DB202603250002')).toBeVisible();
    });

    it('shows the ERP’s refusal where the filters can be changed', () => {
        render(
            <GeneralLedgerIndex
                entries={null}
                pagination={null}
                filters={{ coaNo: '9999' }}
                error="COA 9999 does not exist"
            />,
        );

        expect(screen.getByRole('alert')).toHaveTextContent(
            'COA 9999 does not exist',
        );
        expect(
            screen.getByRole('form', { name: 'Ledger filters' }),
        ).toBeVisible();
    });

    it('asks for a period before reading the summary', () => {
        render(
            <GeneralLedgerSummary
                rows={null}
                pagination={null}
                filters={{}}
                error={null}
            />,
        );

        expect(screen.getByText(/Choose a period/)).toBeVisible();
    });
});
