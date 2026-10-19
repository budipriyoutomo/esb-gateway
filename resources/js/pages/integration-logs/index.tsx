import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index as integrationLogsIndex } from '@/routes/integration-logs';
import { formatDateTime } from '@/lib/format';

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

type Paginated = {
    data: LogRow[];
    total: number;
    current_page: number;
    last_page: number;
};

type Props = {
    logs: Paginated;
    filters: Record<string, string>;
    outcomes: string[];
    families: string[];
};

const ANY = '__any__';

function outcomeVariant(
    outcome: string,
): 'default' | 'secondary' | 'destructive' {
    if (outcome === 'success') return 'default';
    if (outcome === 'validation' || outcome === 'business_rejection') {
        return 'secondary';
    }

    return 'destructive';
}

export default function IntegrationLogsIndex({
    logs,
    filters,
    outcomes,
    families,
}: Props) {
    const [correlationId, setCorrelationId] = useState(
        filters.correlationId ?? '',
    );
    const [operation, setOperation] = useState(filters.operation ?? '');

    function apply(overrides: Record<string, string> = {}) {
        const next: Record<string, string> = {
            ...filters,
            correlationId,
            operation,
            ...overrides,
        };

        for (const key of Object.keys(next)) {
            if (next[key] === '' || next[key] === ANY) {
                delete next[key];
            }
        }

        router.get(integrationLogsIndex().url, next, {
            preserveState: true,
            replace: true,
        });
    }

    return (
        <>
            <Head title="Integration logs" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Integration logs"
                    description="Every call the gateway made to the ERP service. Search by the reference shown on an error page."
                />

                <form
                    className="flex flex-wrap items-end gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        apply();
                    }}
                >
                    <Input
                        value={correlationId}
                        onChange={(event) => {
                            setCorrelationId(event.target.value);
                        }}
                        placeholder="Correlation ID"
                        aria-label="Correlation ID"
                        className="w-72"
                    />
                    <Input
                        value={operation}
                        onChange={(event) => {
                            setOperation(event.target.value);
                        }}
                        placeholder="Operation"
                        aria-label="Operation"
                        className="w-48"
                    />
                    <Select
                        value={filters.outcome ?? ANY}
                        onValueChange={(value) => {
                            apply({ outcome: value });
                        }}
                    >
                        <SelectTrigger className="w-48" aria-label="Outcome">
                            <SelectValue placeholder="Any outcome" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ANY}>Any outcome</SelectItem>
                            {outcomes.map((outcome) => (
                                <SelectItem key={outcome} value={outcome}>
                                    {outcome}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.family ?? ANY}
                        onValueChange={(value) => {
                            apply({ family: value });
                        }}
                    >
                        <SelectTrigger className="w-44" aria-label="Family">
                            <SelectValue placeholder="Any family" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ANY}>Any family</SelectItem>
                            {families.map((family) => (
                                <SelectItem key={family} value={family}>
                                    {family}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-2">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>When</TableHead>
                                <TableHead>Operation</TableHead>
                                <TableHead>Outcome</TableHead>
                                <TableHead>Code</TableHead>
                                <TableHead>Duration</TableHead>
                                <TableHead>By</TableHead>
                                <TableHead>Correlation ID</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {logs.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        No calls matched these filters.
                                    </TableCell>
                                </TableRow>
                            )}

                            {logs.data.map((log) => (
                                <TableRow key={log.id}>
                                    <TableCell className="text-muted-foreground text-xs">
                                        {formatDateTime(log.createdAt)}
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        {log.operation}
                                        <span className="text-muted-foreground ml-2 font-mono text-xs">
                                            {log.method} {log.path}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={outcomeVariant(
                                                log.outcome,
                                            )}
                                        >
                                            {log.outcome}
                                        </Badge>
                                        {log.message && (
                                            <p className="text-muted-foreground mt-1 max-w-md text-xs">
                                                {log.message}
                                            </p>
                                        )}
                                    </TableCell>
                                    <TableCell className="font-mono text-xs">
                                        {log.esbCode ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground text-xs">
                                        {log.durationMs === null
                                            ? '—'
                                            : `${String(log.durationMs)} ms`}
                                        {log.retryCount > 0 && (
                                            <span className="ml-1">
                                                ({log.retryCount} retries)
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground text-xs">
                                        {log.user ?? 'System'}
                                    </TableCell>
                                    <TableCell className="font-mono text-xs">
                                        {log.correlationId}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                <div className="flex items-center justify-between gap-4">
                    <p className="text-muted-foreground text-sm">
                        Page {logs.current_page} of {logs.last_page} —{' '}
                        {logs.total} calls
                    </p>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={logs.current_page <= 1}
                            onClick={() => {
                                apply({ page: String(logs.current_page - 1) });
                            }}
                        >
                            Previous
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={logs.current_page >= logs.last_page}
                            onClick={() => {
                                apply({ page: String(logs.current_page + 1) });
                            }}
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}

IntegrationLogsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Integration logs',
            href: integrationLogsIndex(),
        },
    ],
};
