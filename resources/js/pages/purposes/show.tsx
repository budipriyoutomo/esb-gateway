import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { index as purposesIndex } from '@/routes/purposes';
import type { Purpose } from '@/types/purpose';

function Field({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-muted-foreground text-xs">{label}</dt>
            <dd className="text-sm">{value}</dd>
        </div>
    );
}

function joinPresent(parts: (string | null)[]): string {
    return parts.filter((part) => part !== null).join(', ') || '—';
}

export default function PurposeShow({ purpose }: { purpose: Purpose }) {
    return (
        <>
            <Head title={`Purpose ${purpose.name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex flex-col gap-2">
                        <Link
                            href={purposesIndex()}
                            className="text-muted-foreground inline-flex items-center gap-1 text-sm hover:underline"
                        >
                            <ArrowLeft className="size-4" /> Purposes
                        </Link>
                        <Heading
                            title={purpose.name || `#${String(purpose.id)}`}
                            description={`Account ${purpose.accountNo ?? '—'}`}
                        />
                    </div>
                    {purpose.active !== null && (
                        <Badge
                            variant={purpose.active ? 'default' : 'secondary'}
                        >
                            {purpose.active ? 'Active' : 'Inactive'}
                        </Badge>
                    )}
                </div>

                <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                    <h2 className="mb-3 text-sm font-medium">General</h2>
                    <dl className="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <Field
                            label="Account no."
                            value={purpose.accountNo ?? '—'}
                        />
                        <Field
                            label="Transaction type ids"
                            value={
                                purpose.transactionTypeIds.length === 0
                                    ? '—'
                                    : purpose.transactionTypeIds.join(', ')
                            }
                        />
                        <Field
                            label="Created"
                            value={joinPresent([
                                purpose.createdBy,
                                purpose.createdAt,
                            ])}
                        />
                        <Field
                            label="Last edited"
                            value={joinPresent([
                                purpose.editedBy,
                                purpose.editedAt,
                            ])}
                        />
                    </dl>
                </section>
            </div>
        </>
    );
}

PurposeShow.layout = {
    breadcrumbs: [
        {
            title: 'Purposes',
            href: purposesIndex(),
        },
    ],
};
