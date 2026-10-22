import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
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
                <PageHeader
                    title={purpose.name || `#${String(purpose.id)}`}
                    description={`Account ${purpose.accountNo ?? '—'}`}
                    back={{ href: purposesIndex(), label: 'Purposes' }}
                    actions={
                        purpose.active !== null && (
                            <StatusBadge
                                status={purpose.active ? 'Active' : 'Inactive'}
                            />
                        )
                    }
                />

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
