import { Head } from '@inertiajs/react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { FilterBar } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { SelectFilterField } from '@/components/select-filter-field';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFilterForm } from '@/hooks/use-filter-form';
import { formatDateTime } from '@/lib/format';
import { fromPaginator } from '@/lib/pagination';
import type { LaravelPaginator } from '@/lib/pagination';
import { index as integrationLogsIndex } from '@/routes/integration-logs';

type LogRow = {
    id: number;
    correlationId: string;
    operation: string;
    family: string;
    environment: string;
    method: string;
    path: string;
    outcome: string;
    esbCode: string | null;
    message: string | null;
    upstreamStatus: number | null;
    durationMs: number | null;
    retryCount: number;
    user: string | null;
    createdAt: string | null;
};

type Props = {
    logs: LaravelPaginator<LogRow>;
    filters: Record<string, string>;
    outcomes: string[];
    families: string[];
};

/** The gateway's own outcome names, so they keep their own colours. */
function outcomeVariant(
    outcome: string,
): 'default' | 'secondary' | 'destructive' {
    if (outcome === 'success') return 'default';
    if (outcome === 'validation' || outcome === 'business_rejection') {
        return 'secondary';
    }

    return 'destructive';
}

const columns: DataTableColumn<LogRow>[] = [
    {
        key: 'when',
        header: 'When',
        className: 'text-muted-foreground text-xs',
        cell: (log) => formatDateTime(log.createdAt),
    },
    {
        key: 'operation',
        header: 'Operation',
        className: 'font-medium',
        cell: (log) => (
            <span>
                {log.operation}
                <span className="text-muted-foreground ml-2 font-mono text-xs">
                    {log.method} {log.path}
                </span>
            </span>
        ),
    },
    {
        key: 'outcome',
        header: 'Outcome',
        cell: (log) => (
            <span>
                <Badge variant={outcomeVariant(log.outcome)}>
                    {log.outcome}
                </Badge>
                {log.message && (
                    <span className="text-muted-foreground mt-1 block max-w-md text-xs whitespace-normal">
                        {log.message}
                    </span>
                )}
            </span>
        ),
    },
    {
        key: 'code',
        header: 'Code',
        className: 'font-mono text-xs',
        cell: (log) => log.esbCode ?? '—',
    },
    {
        key: 'duration',
        header: 'Duration',
        className: 'text-muted-foreground text-xs',
        cell: (log) =>
            `${log.durationMs === null ? '—' : `${String(log.durationMs)} ms`}${
                log.retryCount > 0 ? ` (${String(log.retryCount)} retries)` : ''
            }`,
    },
    {
        key: 'by',
        header: 'By',
        className: 'text-muted-foreground text-xs',
        cell: (log) => log.user ?? 'System',
    },
    {
        key: 'correlationId',
        header: 'Correlation ID',
        className: 'font-mono text-xs',
        cell: (log) => log.correlationId,
    },
];

export default function IntegrationLogsIndex({
    logs,
    filters,
    outcomes,
    families,
}: Props) {
    const form = useFilterForm(integrationLogsIndex().url, filters, [
        { key: 'correlationId', chip: (value) => `Reference ${value}` },
        { key: 'operation', chip: (value) => `Operation ${value}` },
        { key: 'outcome', chip: (value) => `Outcome ${value}` },
        { key: 'family', chip: (value) => `Family ${value}` },
    ]);

    // The selects apply at once, as they did before.
    function applyNow(key: string, value: string) {
        form.set(key, value);
        form.apply({ ...form.values, [key]: value });
    }

    return (
        <>
            <Head title="Integration logs" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Integration logs"
                    description="Every call the gateway made to the ERP service. Search by the reference shown on an error page."
                />

                <FilterBar
                    id="logs"
                    search={{
                        label: 'Correlation ID',
                        value: form.values.correlationId!,
                        onChange: (value) => form.set('correlationId', value),
                        placeholder: 'The reference on an error page',
                    }}
                    {...form.bar}
                    defaultAdvancedOpen={Boolean(
                        filters.operation || filters.outcome || filters.family,
                    )}
                >
                    <div className="grid w-full gap-1.5 sm:w-48">
                        <Label htmlFor="logs-operation">Operation</Label>
                        <Input
                            id="logs-operation"
                            value={form.values.operation}
                            onChange={(event) => {
                                form.set('operation', event.target.value);
                            }}
                        />
                    </div>
                    <SelectFilterField
                        id="logs-outcome"
                        label="Outcome"
                        anyLabel="Any outcome"
                        options={outcomes.map((outcome) => ({
                            value: outcome,
                            label: outcome,
                        }))}
                        value={form.values.outcome!}
                        onChange={(value) => {
                            applyNow('outcome', value);
                        }}
                        className="w-48"
                    />
                    <SelectFilterField
                        id="logs-family"
                        label="Family"
                        anyLabel="Any family"
                        options={families.map((family) => ({
                            value: family,
                            label: family,
                        }))}
                        value={form.values.family!}
                        onChange={(value) => {
                            applyNow('family', value);
                        }}
                    />
                </FilterBar>

                <DataTable
                    caption="ERP calls"
                    columns={columns}
                    rows={logs.data}
                    rowKey={(log) => String(log.id)}
                    empty="No calls matched these filters."
                />

                <ListPagination
                    pagination={fromPaginator(logs)}
                    noun="calls"
                    onPageChange={form.goToPage}
                />
            </div>
        </>
    );
}
