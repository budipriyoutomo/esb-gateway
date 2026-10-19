import { router } from '@inertiajs/react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';

type Row = { number: string; supplier: string; total: string };

const columns: DataTableColumn<Row>[] = [
    {
        key: 'number',
        header: 'Number',
        cell: (row) => <a href={`/po/${row.number}`}>{row.number}</a>,
    },
    { key: 'supplier', header: 'Supplier', cell: (row) => row.supplier },
    { key: 'total', header: 'Total', cell: (row) => row.total, align: 'right' },
];

const rows: Row[] = [
    { number: 'PO-1', supplier: 'Acme', total: '1.500,00' },
    { number: 'PO-2', supplier: 'Globex', total: '20,00' },
];

function renderTable(props: Partial<Parameters<typeof DataTable<Row>>[0]>) {
    return render(
        <DataTable<Row>
            caption="Purchase orders"
            columns={columns}
            rows={rows}
            rowKey={(row) => row.number}
            empty="No purchase orders matched this filter."
            {...props}
        />,
    );
}

afterEach(() => {
    vi.restoreAllMocks();
});

describe('DataTable', () => {
    it('renders a captioned table with a header and a row per item', () => {
        renderTable({});

        const table = screen.getByRole('table', { name: 'Purchase orders' });
        const headers = within(table).getAllByRole('columnheader');

        expect(headers.map((th) => th.textContent)).toEqual([
            'Number',
            'Supplier',
            'Total',
        ]);
        // header row + two data rows
        expect(within(table).getAllByRole('row')).toHaveLength(3);
        expect(
            within(table).getByRole('cell', { name: 'Globex' }),
        ).toBeVisible();
    });

    it('right-aligns numeric columns, header and cells alike', () => {
        renderTable({});

        expect(
            screen.getByRole('columnheader', { name: 'Total' }),
        ).toHaveAttribute('data-align', 'right');
        expect(screen.getByRole('cell', { name: '1.500,00' })).toHaveAttribute(
            'data-align',
            'right',
        );
    });

    it('explains an empty result across the full width', () => {
        renderTable({ rows: [] });

        const cell = screen.getByRole('cell', {
            name: 'No purchase orders matched this filter.',
        });

        expect(cell).toHaveAttribute('colspan', '3');
    });

    it('shows skeleton rows and marks the table busy while loading', () => {
        renderTable({ loading: true });

        const table = screen.getByRole('table', { name: 'Purchase orders' });

        expect(table).toHaveAttribute('aria-busy', 'true');
        expect(screen.queryByText('Acme')).not.toBeInTheDocument();
        expect(within(table).getAllByRole('row').length).toBeGreaterThan(1);
    });

    it('shows a friendly error instead of rows', () => {
        renderTable({ error: 'The ERP did not respond. Try again shortly.' });

        expect(screen.getByRole('alert')).toHaveTextContent(
            'The ERP did not respond. Try again shortly.',
        );
        expect(screen.queryByText('Acme')).not.toBeInTheDocument();
    });

    it('opens the detail page when a row is clicked', async () => {
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        renderTable({ rowHref: (row) => `/po/${row.number}` });

        await userEvent.click(screen.getByRole('cell', { name: 'Globex' }));

        expect(visit).toHaveBeenCalledExactlyOnceWith('/po/PO-2');
    });

    it('leaves a click on a link inside the row to the link', async () => {
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        renderTable({ rowHref: (row) => `/po/${row.number}` });

        const link = screen.getByRole('link', { name: 'PO-1' });
        link.addEventListener('click', (event) => {
            event.preventDefault();
        });
        await userEvent.click(link);

        expect(visit).not.toHaveBeenCalled();
    });

    it('does not navigate rows without a detail page', async () => {
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        renderTable({
            rowHref: (row) =>
                row.number === 'PO-1' ? null : `/po/${row.number}`,
        });

        await userEvent.click(screen.getByRole('cell', { name: 'Acme' }));

        expect(visit).not.toHaveBeenCalled();
    });
});
