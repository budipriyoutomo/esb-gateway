import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AlertError from '@/components/alert-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatAmount, formatDateTime } from '@/lib/format';
import { prepare, send } from '@/routes/closing-recaps/sales';
import { show as showSalesPush } from '@/routes/sales-pushes';

export type ClosingSales = {
    hasData: boolean;
    grandTotal: number | null;
    payments: number;
    draft: {
        id: string;
        salesNum: string;
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
    } | null;
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
 * A closing recap's sales as one ESB Push Sales transaction (ADR-032, P29),
 * from the prices, taxes and payments sync-api sent (contract revision 3,
 * TBD). Sending queues it through the sales push module.
 */
export function ClosingSalesPush({
    recapId,
    sales,
    canProcess,
    actions,
}: Props) {
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);
    const draft = sales.draft;

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
                                        'Queue these sales for the ERP? A revision replaces the sales sent before under the same sales number.',
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

            {!sales.hasData ? (
                <p className="text-muted-foreground text-sm">
                    sync-api sent no prices, taxes or payments for this recap,
                    so its sales cannot go to the ERP yet (contract revision 3).
                </p>
            ) : draft === null ? (
                <p className="text-muted-foreground text-sm">
                    Grand total {formatAmount(sales.grandTotal)} with{' '}
                    {sales.payments}{' '}
                    {sales.payments === 1 ? 'payment' : 'payments'}. Nothing
                    prepared yet.
                </p>
            ) : (
                <div
                    aria-label={`Sales ${draft.salesNum}`}
                    className="grid gap-2 rounded-xl border p-4 text-sm"
                >
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="font-medium">{draft.salesNum}</span>
                        <Badge
                            variant={
                                draft.status === 'sent'
                                    ? 'default'
                                    : 'secondary'
                            }
                        >
                            {draft.status === 'sent' ? 'Sent' : 'Prepared'}
                        </Badge>
                        {!draft.current && (
                            <Badge variant="outline">
                                Sequence {draft.sequence}
                            </Badge>
                        )}
                        <span className="text-muted-foreground text-xs">
                            {draft.menuLines}{' '}
                            {draft.menuLines === 1 ? 'menu line' : 'menu lines'}{' '}
                            · grand total {formatAmount(draft.grandTotal)}
                            {draft.sentAt
                                ? ` · sent ${formatDateTime(draft.sentAt)}`
                                : ''}
                        </span>
                    </div>

                    {draft.push && (
                        <div className="flex flex-wrap items-center gap-2 text-xs">
                            <span className="text-muted-foreground">
                                ERP push
                            </span>
                            <Badge
                                variant={
                                    PUSH_VARIANT[draft.push.status] ??
                                    'destructive'
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
            )}
        </section>
    );
}
