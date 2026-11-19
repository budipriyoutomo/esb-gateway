import { Link, router } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';
import { useState } from 'react';
import AlertError from '@/components/alert-error';
import { ClosingGapList } from '@/components/closing-gap-list';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatAmount, formatDateTime } from '@/lib/format';
import { prepare, send } from '@/routes/closing-recaps/sales';
import { show as showSalesPush } from '@/routes/sales-pushes';
import type { ClosingGap } from '@/types/closing';

export type ClosingSalesDraft = {
    id: string;
    salesNum: string;
    visitPurposeName: string;
    sequence: number;
    current: boolean;
    status: 'prepared' | 'superseded' | 'sent';
    menuLines: number;
    grandTotal: number | null;
    sentAt: string | null;
    push: {
        id: string;
        status: string;
        errors: { field: string | null; message: string }[];
    } | null;
};

export type ClosingSales = {
    hasData: boolean;
    /** The current version's sales, one per visit purpose. */
    purposes: {
        visitPurposeName: string;
        grandTotal: number | null;
        payments: number;
    }[];
    missing: ClosingGap[];
    /** Sent before, gone from the current version, still in the ERP. */
    dropped: { visitPurposeName: string; salesNum: string }[];
    drafts: ClosingSalesDraft[];
};

type Props = {
    recapId: string;
    sales: ClosingSales;
    canProcess: boolean;
    actions: { prepareSales: boolean; sendSales: boolean };
};

const PUSH_VARIANT: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    succeeded: 'default',
    queued: 'secondary',
    superseded: 'outline',
};

/**
 * A closing recap's sales as ESB Push Sales transactions, one per visit
 * purpose (ADR-032, P29-P30), from the prices, taxes and payments sync-api
 * sent (contract revision 3, TBD). Sending queues them through the sales
 * push module.
 */
export function ClosingSalesPush({
    recapId,
    sales,
    canProcess,
    actions,
}: Props) {
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    function post(url: string) {
        router.post(
            url,
            {},
            {
                preserveScroll: true,
                onStart: () => {
                    setBusy(true);
                    setError(null);
                },
                onError: (errors) => {
                    setError(errors.sales ?? Object.values(errors)[0] ?? null);
                },
                onFinish: () => {
                    setBusy(false);
                },
            },
        );
    }

    return (
        <section aria-labelledby="closing-sales" className="grid gap-3">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <h2 id="closing-sales" className="text-sm font-medium">
                    Sales
                </h2>
                {canProcess && (
                    <div className="flex gap-2">
                        <Button
                            size="sm"
                            variant="outline"
                            disabled={busy || !actions.prepareSales}
                            onClick={() => {
                                post(prepare(recapId).url);
                            }}
                        >
                            Prepare sales
                        </Button>
                        <Button
                            size="sm"
                            disabled={busy || !actions.sendSales}
                            onClick={() => {
                                if (
                                    window.confirm(
                                        'Queue these sales for the ERP? A revision replaces the sales sent before under the same sales numbers.',
                                    )
                                ) {
                                    post(send(recapId).url);
                                }
                            }}
                        >
                            Send sales
                        </Button>
                    </div>
                )}
            </div>

            {error && <AlertError errors={[error]} />}

            {sales.dropped.length > 0 && (
                <Alert variant="destructive" role="alert">
                    <TriangleAlert />
                    <AlertTitle>Earlier sales are still in the ERP</AlertTitle>
                    <AlertDescription>
                        {sales.dropped
                            .map(
                                (row) =>
                                    `${row.visitPurposeName} (${row.salesNum})`,
                            )
                            .join(', ')}{' '}
                        went to the ERP with an earlier version but are not in
                        this one. Push Sales cannot remove them; void them in
                        the ERP.
                    </AlertDescription>
                </Alert>
            )}

            {!sales.hasData ? (
                <p className="text-muted-foreground text-sm">
                    sync-api sent no sales for this recap, so nothing goes to
                    Push Sales (contract revision 3).
                </p>
            ) : (
                <>
                    {sales.missing.length > 0 && (
                        <ClosingGapList
                            id="closing-sales-gaps"
                            title="Before the sales can be prepared"
                            gaps={sales.missing}
                        />
                    )}

                    {sales.drafts.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            {sales.purposes
                                .map(
                                    (purpose) =>
                                        `${purpose.visitPurposeName} ${formatAmount(purpose.grandTotal)} with ${purpose.payments} ${purpose.payments === 1 ? 'payment' : 'payments'}`,
                                )
                                .join('; ')}
                            . Nothing prepared yet.
                        </p>
                    ) : (
                        sales.drafts.map((draft) => (
                            <SalesDraftCard key={draft.id} draft={draft} />
                        ))
                    )}
                </>
            )}
        </section>
    );
}

function SalesDraftCard({ draft }: { draft: ClosingSalesDraft }) {
    return (
        <div
            aria-label={`Sales ${draft.salesNum}`}
            className="grid gap-2 rounded-xl border p-4 text-sm"
        >
            <div className="flex flex-wrap items-center gap-2">
                <span className="font-medium">{draft.salesNum}</span>
                <Badge variant="outline">{draft.visitPurposeName}</Badge>
                <Badge
                    variant={draft.status === 'sent' ? 'default' : 'secondary'}
                >
                    {draft.status === 'sent' ? 'Sent' : 'Prepared'}
                </Badge>
                {!draft.current && (
                    <Badge variant="outline">Sequence {draft.sequence}</Badge>
                )}
                <span className="text-muted-foreground text-xs">
                    {draft.menuLines}{' '}
                    {draft.menuLines === 1 ? 'menu line' : 'menu lines'} · grand
                    total {formatAmount(draft.grandTotal)}
                    {draft.sentAt
                        ? ` · sent ${formatDateTime(draft.sentAt)}`
                        : ''}
                </span>
            </div>

            {draft.push && (
                <div className="flex flex-wrap items-center gap-2 text-xs">
                    <span className="text-muted-foreground">ERP push</span>
                    <Badge
                        variant={
                            PUSH_VARIANT[draft.push.status] ?? 'destructive'
                        }
                    >
                        {draft.push.status}
                    </Badge>
                    <Link
                        href={showSalesPush(draft.push.id)}
                        className="underline underline-offset-4"
                    >
                        Open the sales push
                    </Link>
                </div>
            )}

            {draft.push && draft.push.errors.length > 0 && (
                <ul className="text-destructive grid gap-0.5 text-xs">
                    {draft.push.errors.map((item, index) => (
                        <li key={index}>
                            {item.field ? `${item.field}: ` : ''}
                            {item.message}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
