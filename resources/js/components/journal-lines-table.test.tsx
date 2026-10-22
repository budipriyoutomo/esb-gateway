import { render, screen, within } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { JournalLinesTable } from '@/components/journal-lines-table';
import type { JournalLine } from '@/types/accounting';

function line(overrides: Partial<JournalLine>): JournalLine {
    return {
        id: 1,
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
        notes: null,
        ...overrides,
    };
}

describe('JournalLinesTable', () => {
    it('shows each side as the ERP sent it, with no totals', () => {
        render(
            <JournalLinesTable
                lines={[
                    line({ costCenter: 'Operasional' }),
                    line({
                        id: 2,
                        coaNo: '2101',
                        coaDescription: 'Hutang Usaha',
                        debit: 0,
                        credit: 500000,
                    }),
                ]}
            />,
        );

        const table = screen.getByRole('table', { name: 'Journal lines' });
        const rows = within(table).getAllByRole('row');

        expect(rows).toHaveLength(3);
        expect(within(rows[1]).getByText('500.000,00')).toBeVisible();
        expect(within(rows[1]).getByText('Operasional')).toBeVisible();
        expect(within(rows[2]).getByText('Hutang Usaha')).toBeVisible();
        expect(
            within(table).queryByText('1.000.000,00'),
        ).not.toBeInTheDocument();
    });

    it('says so when there are no lines', () => {
        render(<JournalLinesTable lines={[]} />);

        expect(screen.getByText('This journal has no lines.')).toBeVisible();
    });
});
