import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useId } from 'react';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { usePermissions } from '@/hooks/use-permissions';
import { formatDateTime } from '@/lib/format';
import { create as disbursementsCreate } from '@/routes/disbursements';
import { index as esbConnectionsIndex } from '@/routes/esb-connections';
import { create as goodsTransferRequestsCreate } from '@/routes/goods-transfer-requests';
import { index as integrationLogsIndex } from '@/routes/integration-logs';
import { create as memorialJournalsCreate } from '@/routes/memorial-journals';
import { create as purchaseRequestsCreate } from '@/routes/purchase-requests';
import type { Permission } from '@/types';

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
    /** How the family's most recent call in the window ended. */
    lastOutcome: string | null;
    lastCallAt: string | null;
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

type PendingApproval = {
    key: string;
    title: string;
    /** Null when the ERP would not give the count. */
    count: number | null;
    href: string;
};

type Props = {
    metrics: Metrics;
    byOutcome: Record<string, number>;
    byFamily: Record<string, FamilyHealth>;
    recentFailures: Failure[];
    esb: { environment: string; authMechanism: string };
    queue: { pending: number | null };
    /** Deferred: undefined until the ERP counts arrive. */
    pendingApprovals?: PendingApproval[];
};

/**
 * The approve permissions whose modules the server counts (PendingApprovals):
 * the documented "Waiting For Approval" status filter exists only for these.
 */
const COUNTED_APPROVALS: Permission[] = [
    'disbursements.approve',
    'memorial-journals.approve',
    'item-journals.approve',
    'pricelists.approve',
];

const SHORTCUTS: { title: string; href: string; permission: Permission }[] = [
    {
        title: 'New purchase request',
        href: purchaseRequestsCreate.url(),
        permission: 'purchase-requests.manage',
    },
    {
        title: 'New goods transfer request',
        href: goodsTransferRequestsCreate.url(),
        permission: 'goods-transfer-requests.manage',
    },
    {
        title: 'New disbursement',
        href: disbursementsCreate.url(),
        permission: 'disbursements.manage',
    },
    {
        title: 'New memorial journal',
        href: memorialJournalsCreate.url(),
        permission: 'memorial-journals.manage',
    },
    {
        title: 'Integration logs',
        href: integrationLogsIndex.url(),
        permission: 'integration-logs.view',
    },
    {
        title: 'ESB connections',
        href: esbConnectionsIndex.url(),
        permission: 'esb-connections.view',
    },
];

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

function Panel({
    title,
    description,
    action,
    busy,
    className,
    children,
}: {
    title: string;
    description?: string;
    action?: ReactNode;
    busy?: boolean;
    className?: string;
    children: ReactNode;
}) {
    const headingId = useId();

    return (
        <section
            aria-labelledby={headingId}
            aria-busy={busy}
            className={`border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4 ${className ?? ''}`}
        >
            <div className="flex items-center justify-between gap-4">
                <h2 id={headingId} className="text-sm font-semibold">
                    {title}
                </h2>
                {action}
            </div>
            {description && (
                <p className="text-muted-foreground mt-1 text-xs">
                    {description}
                </p>
            )}
            {children}
        </section>
    );
}

function lastCallText(health: FamilyHealth): string {
    if (health.lastOutcome === null) {
        return 'No calls';
    }

    return health.lastOutcome === 'success'
        ? 'Last call succeeded'
        : `Last call failed (${health.lastOutcome})`;
}

export default function Dashboard({
    metrics,
    byOutcome,
    byFamily,
    recentFailures,
    esb,
    queue,
    pendingApprovals,
}: Props) {
    const { can, canAny } = usePermissions();
    const families = Object.entries(byFamily);
    const outcomes = Object.entries(byOutcome).filter(
        ([outcome]) => outcome !== 'success',
    );
    const shortcuts = SHORTCUTS.filter((shortcut) => can(shortcut.permission));
    const showApprovals =
        canAny(...COUNTED_APPROVALS) &&
        (pendingApprovals === undefined || pendingApprovals.length > 0);

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
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

                {(showApprovals || shortcuts.length > 0) && (
                    <div className="grid gap-4 lg:grid-cols-2">
                        {showApprovals && (
                            <Panel
                                title="Waiting for approval"
                                description="Documents the ERP holds as Waiting For Approval, in the modules you approve."
                                busy={pendingApprovals === undefined}
                            >
                                {pendingApprovals === undefined ? (
                                    <div className="mt-4 space-y-2">
                                        <Skeleton className="h-9 w-full" />
                                        <Skeleton className="h-9 w-full" />
                                    </div>
                                ) : (
                                    <ul className="mt-4 space-y-1">
                                        {pendingApprovals.map((module) => (
                                            <li key={module.key}>
                                                <Link
                                                    href={module.href}
                                                    className="hover:bg-accent focus-visible:ring-ring flex items-center justify-between gap-4 rounded-md px-2 py-2 text-sm outline-none focus-visible:ring-2"
                                                >
                                                    <span>{module.title}</span>
                                                    {module.count === null ? (
                                                        <span className="text-muted-foreground text-xs">
                                                            Unknown
                                                        </span>
                                                    ) : (
                                                        <Badge
                                                            variant={
                                                                module.count > 0
                                                                    ? 'default'
                                                                    : 'secondary'
                                                            }
                                                            className="tabular-nums"
                                                        >
                                                            {module.count}
                                                        </Badge>
                                                    )}
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </Panel>
                        )}

                        {shortcuts.length > 0 && (
                            <Panel title="Shortcuts">
                                <ul className="mt-4 flex flex-wrap gap-2">
                                    {shortcuts.map((shortcut) => (
                                        <li key={shortcut.href}>
                                            <Link
                                                href={shortcut.href}
                                                className="hover:bg-accent focus-visible:ring-ring inline-flex min-h-9 items-center rounded-md border px-3 text-sm outline-none focus-visible:ring-2"
                                            >
                                                {shortcut.title}
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </Panel>
                        )}
                    </div>
                )}

                <div className="grid gap-4 lg:grid-cols-2">
                    <Panel
                        title="ESB connection status"
                        description="Per base URL family: the families are different hosts and can fail independently."
                        action={
                            can('esb-connections.view') ? (
                                <Link
                                    href={esbConnectionsIndex()}
                                    className="text-xs underline underline-offset-4"
                                >
                                    Connections
                                </Link>
                            ) : undefined
                        }
                    >
                        {families.length === 0 ? (
                            <p className="text-muted-foreground mt-4 text-sm">
                                No calls recorded in this window.
                            </p>
                        ) : (
                            <ul className="mt-4 space-y-3">
                                {families.map(([family, health]) => (
                                    <li
                                        key={family}
                                        aria-label={family}
                                        className="text-sm"
                                    >
                                        <div className="flex items-center justify-between gap-4">
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
                                        </div>
                                        <p
                                            className={`mt-1 text-xs ${
                                                health.lastOutcome !== null &&
                                                health.lastOutcome !== 'success'
                                                    ? 'text-destructive'
                                                    : 'text-muted-foreground'
                                            }`}
                                        >
                                            {lastCallText(health)}
                                            {health.lastCallAt &&
                                                ` · ${formatDateTime(health.lastCallAt)}`}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Panel>

                    <Panel title="Failures by kind">
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
                    </Panel>
                </div>

                <Panel
                    title="Recent failures"
                    action={
                        can('integration-logs.view') ? (
                            <Link
                                href={integrationLogsIndex()}
                                className="text-xs underline underline-offset-4"
                            >
                                All integration logs
                            </Link>
                        ) : undefined
                    }
                >
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
                </Panel>
            </div>
        </>
    );
}
