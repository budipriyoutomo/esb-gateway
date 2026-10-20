import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { formatAmount } from '@/lib/format';
import type { JournalLine } from '@/types/accounting';

/** 0 on the other side of a line is "nothing", not an amount to read. */
function side(amount: number | null): string {
    return amount === null || amount === 0 ? '—' : formatAmount(amount);
}

const columns: DataTableColumn<JournalLine>[] = [
    {
        key: 'account',
        header: 'Account',
        cell: (line) => (
            <>
                <span className="font-mono text-xs">{line.coaNo ?? '—'}</span>
                {line.coaDescription && (
                    <span className="block">{line.coaDescription}</span>
                )}
                {line.notes && (
                    <span className="text-muted-foreground block text-xs">
                        {line.notes}
                    </span>
                )}
            </>
        ),
    },
    {
        key: 'branch',
        header: 'Branch',
        className: 'text-muted-foreground',
        cell: (line) => (
            <>
                {line.branchName ?? '—'}
                {(line.costCenter || line.project) && (
                    <span className="block text-xs">
                        {[line.costCenter, line.project]
                            .filter((part) => part !== null)
                            .join(' · ')}
                    </span>
                )}
            </>
        ),
    },
    {
        key: 'debit',
        header: 'Debit',
        align: 'right',
        cell: (line) => side(line.debit),
    },
    {
        key: 'credit',
        header: 'Credit',
        align: 'right',
        cell: (line) => side(line.credit),
    },
];

/**
 * A journal's debit / credit lines ([ESB-041], [ESB-043]), as the ERP has
 * them. The gateway shows no totals: balancing is the ERP's.
 */
export function JournalLinesTable({ lines }: { lines: JournalLine[] }) {
    return (
        <DataTable
            caption="Journal lines"
            columns={columns}
            rows={lines}
            rowKey={(line, index) => `${line.id ?? 'line'}-${index}`}
            empty="This journal has no lines."
        />
    );
}
