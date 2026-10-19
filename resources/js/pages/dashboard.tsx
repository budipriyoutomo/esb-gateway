import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { index as integrationLogsIndex } from '@/routes/integration-logs';
import { dashboard } from '@/routes';
import { formatDateTime } from '@/lib/format';

type Metrics = {
    windowHours: number;
    total: number;
    successful: number;
    failed: number;
    successRate: number | null;
    averageDurationMs: number | null;
};

type FamilyHealth = {
    total: number;
    failed: number;
    successRate: number | null;
};

type Failure = {
    id: number;
    correlationId: string;
    operation: string;
    family: string;
    outcome: string;
    esbCode: string | null;
    message: string | null;
    createdAt: string | null;
};

type Props = {
    metrics: Metrics;
    byOutcome: Record<string, number>;
    byFamily: Record<string, FamilyHealth>;
    recentFailures: Failure[];
    esb: { environment: string; authMechanism: string };
    queue: { pending: number | null };
};

function Stat({
    label,
    value,
    hint,
}: {
    label: string;
    value: string;
    hint?: string;
}) {
    return (
        <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
            <p className="text-muted-foreground text-xs tracking-wide uppercase">
                {label}
            </p>
            <p className="mt-2 text-2xl font-semibold">{value}</p>
            {hint && (
                <p className="text-muted-foreground mt-1 text-xs">{hint}</p>
            )}
        </div>
    );
}

export default function Dashboard({
    metrics,
    byOutcome,
    byFamily,
    recentFailures,
    esb,
    queue,
}: Props) {
    const families = Object.entries(byFamily);
    const outcomes = Object.entries(byOutcome).filter(
        ([outcome]) => outcome !== 'success',
    );

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Integration activity"
                    description={`ERP calls in the last ${String(metrics.windowHours)} hours. Environment: ${esb.environment} (${esb.authMechanism}).`}
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Stat label="Calls" value={String(metrics.total)} />
                    <Stat
                        label="Success rate"
                        // Null means nothing was measured. Rendering 0% would
                        // read as an outage on a quiet morning.
                        value={
                            metrics.successRate === null
                                ? 'No data'
                                : `${String(metrics.successRate)}%`
                        }
                        hint={
                            metrics.total === 0
                                ? 'No calls in this window'
                                : `${String(metrics.failed)} failed`
                        }
                    />
                    <Stat
                        label="Average duration"
                        value={
                            metrics.averageDurationMs === null
                                ? 'No data'
                                : `${String(metrics.averageDurationMs)} ms`
                        }
                    />
                    <Stat
                        label="Queued jobs"
                        value={
                            queue.pending === null
                                ? 'Unavailable'
                                : String(queue.pending)
                        }
                        hint={
                            queue.pending === null
                                ? 'Queue backend unreachable'
                                : undefined
                        }
                    />
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                        <h2 className="text-sm font-semibold">
                            Availability by base URL family
                        </h2>
                        <p className="text-muted-foreground mt-1 text-xs">
                            The families are different hosts and can fail
                            independently.
                        </p>

                        {families.length === 0 ? (
                            <p className="text-muted-foreground mt-4 text-sm">
                                No calls recorded in this window.
                            </p>
                        ) : (
                            <ul className="mt-4 space-y-2">
                                {families.map(([family, health]) => (
                                    <li
                                        key={family}
                                        className="flex items-center justify-between gap-4 text-sm"
                                    >
                                        <span className="font-mono text-xs">
                                            {family}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {health.total} calls
                                        </span>
                                        <Badge
                                            variant={
                                                health.failed === 0
                                                    ? 'default'
                                                    : 'destructive'
                                            }
                                        >
                                            {health.successRate === null
                                                ? 'No data'
                                                : `${String(health.successRate)}%`}
                                        </Badge>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                        <h2 className="text-sm font-semibold">
                            Failures by kind
                        </h2>

                        {outcomes.length === 0 ? (
                            <p className="text-muted-foreground mt-4 text-sm">
                                No failures in this window.
                            </p>
                        ) : (
                            <ul className="mt-4 space-y-2">
                                {outcomes.map(([outcome, count]) => (
                                    <li
                                        key={outcome}
                                        className="flex items-center justify-between gap-4 text-sm"
                                    >
                                        <span>{outcome}</span>
                                        <span className="text-muted-foreground">
                                            {count}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>

                <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                    <div className="flex items-center justify-between gap-4">
                        <h2 className="text-sm font-semibold">
                            Recent failures
                        </h2>
                        <Link
                            href={integrationLogsIndex()}
                            className="text-xs underline underline-offset-4"
                        >
                            All integration logs
                        </Link>
                    </div>

                    {recentFailures.length === 0 ? (
                        <p className="text-muted-foreground mt-4 text-sm">
                            Nothing has failed in this window.
                        </p>
                    ) : (
                        <ul className="mt-4 space-y-3">
                            {recentFailures.map((failure) => (
                                <li key={failure.id} className="text-sm">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Badge variant="destructive">
                                            {failure.outcome}
                                        </Badge>
                                        <span className="font-medium">
                                            {failure.operation}
                                        </span>
                                        {failure.esbCode && (
                                            <span className="text-muted-foreground font-mono text-xs">
                                                {failure.esbCode}
                                            </span>
                                        )}
                                        <span className="text-muted-foreground text-xs">
                                            {failure.createdAt
                                                ? formatDateTime(
                                                      failure.createdAt,
                                                  )
                                                : ''}
                                        </span>
                                    </div>
                                    {failure.message && (
                                        <p className="text-muted-foreground mt-1 text-xs">
                                            {failure.message}
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
