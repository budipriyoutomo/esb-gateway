import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import type { SalesPushStatus } from '@/pages/sales-pushes/index';
import { statusVariant } from '@/pages/sales-pushes/index';
import {
    index as salesPushesIndex,
    retry as retrySalesPush,
} from '@/routes/sales-pushes';

type SalesPush = {
    id: string;
    salesNum: string;
    branchCode: string;
    salesDate: string | null;
    client: string | null;
    status: SalesPushStatus;
    attempts: number;
    pushedAt: string | null;
    createdAt: string | null;
    updatedAt: string | null;
    lastAttemptedAt: string | null;
    failure: string | null;
    reference: string | null;
    errors: { field: string | null; message: string }[];
    esbStatus: string | null;
    esbSyncDate: string | null;
    canRetry: boolean;
    summary: {
        billNum: string | null;
        salesDateIn: string | null;
        salesDateOut: string | null;
        visitPurposeName: string | null;
        statusId: string | null;
        grandTotal: number | null;
        paymentTotal: number | null;
        menuLines: number;
        payments: number;
    };
};

function dateTime(value: string | null): string {
    return value ? new Date(value).toLocaleString() : '—';
}

function amount(value: number | null): string {
    return value === null ? '—' : value.toLocaleString();
}

function Field({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-muted-foreground text-xs">{label}</dt>
            <dd className="text-sm">{value}</dd>
        </div>
    );
}

export default function SalesPushShow({ push }: { push: SalesPush }) {
    const { can } = usePermissions();
    const [processing, setProcessing] = useState(false);

    function retry() {
        router.post(
            retrySalesPush(push.id).url,
            {},
            {
                preserveScroll: true,
                onStart: () => {
                    setProcessing(true);
                },
                onFinish: () => {
                    setProcessing(false);
                },
            },
        );
    }

    return (
        <>
            <Head title={`Sales ${push.salesNum}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex flex-col gap-2">
                        <Link
                            href={salesPushesIndex()}
                            className="text-muted-foreground inline-flex items-center gap-1 text-sm hover:underline"
                        >
                            <ArrowLeft className="size-4" /> Sales pushes
                        </Link>
                        <Heading
                            title={`Sales ${push.salesNum}`}
                            description={`Branch ${push.branchCode} — from ${push.client ?? 'an unknown client'}`}
                        />
                    </div>

                    <div className="flex items-center gap-2">
                        <Badge variant={statusVariant(push.status)}>
                            {push.status}
                        </Badge>
                        {push.canRetry && can('sales-pushes.retry') && (
                            <Button
                                size="sm"
                                disabled={processing}
                                onClick={retry}
                            >
                                Send again
                            </Button>
                        )}
                    </div>
                </div>

                {push.errors.length > 0 && (
                    <div className="border-destructive/50 rounded-xl border p-4">
                        <h2 className="mb-2 text-sm font-medium">
                            The ERP refused this transaction
                        </h2>
                        <ul className="list-disc space-y-1 pl-5 text-sm">
                            {push.errors.map((error, index) => (
                                <li key={index}>
                                    {error.field && (
                                        <span className="font-mono text-xs">
                                            {error.field}:{' '}
                                        </span>
                                    )}
                                    {error.message}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                <div className="border-sidebar-border/70 dark:border-sidebar-border grid gap-6 rounded-xl border p-4 md:grid-cols-2">
                    <section>
                        <h2 className="mb-3 text-sm font-medium">
                            Transaction
                        </h2>
                        <dl className="grid grid-cols-2 gap-3">
                            <Field
                                label="Bill number"
                                value={push.summary.billNum ?? '—'}
                            />
                            <Field
                                label="Sales date"
                                value={push.salesDate ?? '—'}
                            />
                            <Field
                                label="In"
                                value={push.summary.salesDateIn ?? '—'}
                            />
                            <Field
                                label="Out"
                                value={push.summary.salesDateOut ?? '—'}
                            />
                            <Field
                                label="Visit purpose"
                                value={push.summary.visitPurposeName ?? '—'}
                            />
                            <Field
                                label="POS status ID"
                                value={push.summary.statusId ?? '—'}
                            />
                            <Field
                                label="Grand total"
                                value={amount(push.summary.grandTotal)}
                            />
                            <Field
                                label="Payment total"
                                value={amount(push.summary.paymentTotal)}
                            />
                            <Field
                                label="Menu lines"
                                value={String(push.summary.menuLines)}
                            />
                            <Field
                                label="Payments"
                                value={String(push.summary.payments)}
                            />
                        </dl>
                    </section>

                    <section>
                        <h2 className="mb-3 text-sm font-medium">Delivery</h2>
                        <dl className="grid grid-cols-2 gap-3">
                            <Field
                                label="Received"
                                value={dateTime(push.createdAt)}
                            />
                            <Field
                                label="Attempts"
                                value={String(push.attempts)}
                            />
                            <Field
                                label="Last attempt"
                                value={dateTime(push.lastAttemptedAt)}
                            />
                            <Field
                                label="Accepted by the ERP"
                                value={dateTime(push.pushedAt)}
                            />
                            <Field
                                label="ERP sync date"
                                value={push.esbSyncDate ?? '—'}
                            />
                            <Field
                                label="ERP result status"
                                value={push.esbStatus ?? '—'}
                            />
                            <Field
                                label="Last failure"
                                value={push.failure ?? '—'}
                            />
                            <Field
                                label="Reference"
                                value={push.reference ?? '—'}
                            />
                        </dl>
                    </section>
                </div>
            </div>
        </>
    );
}

SalesPushShow.layout = {
    breadcrumbs: [
        {
            title: 'Sales pushes',
            href: salesPushesIndex(),
        },
    ],
};
