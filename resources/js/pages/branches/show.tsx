import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { index as branchesIndex } from '@/routes/branches';
import type { BranchDetail } from '@/types/branch';

function Field({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-muted-foreground text-xs">{label}</dt>
            <dd className="text-sm whitespace-pre-line">{value}</dd>
        </div>
    );
}

function Section({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
            <h2 className="mb-3 text-sm font-medium">{title}</h2>
            <dl className="grid grid-cols-2 gap-3 md:grid-cols-4">
                {children}
            </dl>
        </section>
    );
}

const text = (value: string | number | null) =>
    value === null ? '—' : String(value);

function joinPresent(parts: (string | null)[]): string {
    return parts.filter((part) => part !== null).join(', ') || '—';
}

function tax(name: string | null, value: number | null): string {
    if (name === null && value === null) return '—';
    return [name, value === null ? null : `${String(value)}%`]
        .filter((part) => part !== null)
        .join(' · ');
}

export default function BranchShow({ branch }: { branch: BranchDetail }) {
    return (
        <>
            <Head title={`Branch ${branch.name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={branch.name || `#${String(branch.id)}`}
                    description={`Code ${branch.code ?? '—'}`}
                    back={{ href: branchesIndex(), label: 'Branches' }}
                    actions={
                        branch.active !== null && (
                            <StatusBadge
                                status={branch.active ? 'Active' : 'Inactive'}
                            />
                        )
                    }
                />

                <Section title="General">
                    <Field label="Code" value={text(branch.code)} />
                    <Field
                        label="External code"
                        value={text(branch.externalCode)}
                    />
                    <Field
                        label="Name on ESB Order"
                        value={text(branch.esbOrderName)}
                    />
                    <Field
                        label="Branch type id"
                        value={text(branch.branchTypeId)}
                    />
                    <Field label="Brand id" value={text(branch.brandId)} />
                    <Field label="Open date" value={text(branch.openDate)} />
                    <Field label="Close date" value={text(branch.closeDate)} />
                    <Field
                        label="Timezone id"
                        value={text(branch.timezoneId)}
                    />
                </Section>

                <Section title="Contact & location">
                    <Field label="Address" value={text(branch.address)} />
                    <Field label="Phone" value={text(branch.phone)} />
                    <Field
                        label="Postal code"
                        value={text(branch.postalCode)}
                    />
                    <Field
                        label="Coordinates"
                        value={
                            branch.latitude && branch.longitude
                                ? `${branch.latitude}, ${branch.longitude}`
                                : '—'
                        }
                    />
                </Section>

                <Section title="Tax">
                    <Field
                        label="Tax"
                        value={tax(branch.taxName, branch.taxValue)}
                    />
                    <Field
                        label="Additional tax"
                        value={tax(
                            branch.additionalTaxName,
                            branch.additionalTaxValue,
                        )}
                    />
                    <Field label="VAT" value={text(branch.vatName)} />
                </Section>

                <Section title="POS">
                    <Field
                        label="POS version"
                        value={text(branch.posVersion)}
                    />
                    <Field
                        label="Cashier licences"
                        value={text(branch.licences.cashier)}
                    />
                    <Field
                        label="Order licences"
                        value={text(branch.licences.order)}
                    />
                    <Field
                        label="Tab licences"
                        value={text(branch.licences.tab)}
                    />
                    <Field
                        label="Server-only licences"
                        value={text(branch.licences.serverOnly)}
                    />
                    <Field
                        label="Receipt header"
                        value={text(branch.printingHeader)}
                    />
                    <Field
                        label="Receipt footer"
                        value={text(branch.printingFooter)}
                    />
                </Section>

                <Section title="History">
                    <Field
                        label="Created"
                        value={joinPresent([
                            branch.createdBy,
                            branch.createdAt,
                        ])}
                    />
                    <Field
                        label="Last edited"
                        value={joinPresent([branch.editedBy, branch.editedAt])}
                    />
                </Section>
            </div>
        </>
    );
}
