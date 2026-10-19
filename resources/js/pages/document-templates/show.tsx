import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index as documentTemplatesIndex } from '@/routes/document-templates';
import type { DocumentTemplate } from '@/types/document-template';
import { formatAmount } from '@/lib/format';

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

function yesNo(value: boolean | null): string {
    if (value === null) {
        return '—';
    }

    return value ? 'Yes' : 'No';
}

function idList(ids: number[]): string {
    return ids.length === 0 ? '—' : ids.join(', ');
}

export default function DocumentTemplateShow({
    template,
}: {
    template: DocumentTemplate;
}) {
    return (
        <>
            <Head title={`Document template ${template.name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex flex-col gap-2">
                        <Link
                            href={documentTemplatesIndex()}
                            className="text-muted-foreground inline-flex items-center gap-1 text-sm hover:underline"
                        >
                            <ArrowLeft className="size-4" /> Document templates
                        </Link>
                        <Heading
                            title={template.name || `#${String(template.id)}`}
                            description={template.typeName ?? 'Unknown type'}
                        />
                    </div>
                    {template.active !== null && (
                        <Badge
                            variant={template.active ? 'default' : 'secondary'}
                        >
                            {template.active ? 'Active' : 'Inactive'}
                        </Badge>
                    )}
                </div>

                <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                    <h2 className="mb-3 text-sm font-medium">General</h2>
                    <dl className="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <Field label="Type" value={template.typeName ?? '—'} />
                        <Field
                            label="Auto transfer"
                            value={yesNo(template.autoTransfer)}
                        />
                        <Field
                            label="Allow zero pricelist"
                            value={yesNo(template.allowZeroPricelist)}
                        />
                        <Field
                            label="Last synced"
                            value={template.syncedAt ?? '—'}
                        />
                        <Field
                            label="Branch ids"
                            value={idList(template.branchIds)}
                        />
                        <Field
                            label="Cost center ids"
                            value={idList(template.costCenterIds)}
                        />
                        <Field
                            label="Created"
                            value={joinPresent([
                                template.createdBy,
                                template.createdAt,
                            ])}
                        />
                        <Field
                            label="Last edited"
                            value={joinPresent([
                                template.editedBy,
                                template.editedAt,
                            ])}
                        />
                    </dl>
                </section>

                <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-2">
                    <h2 className="px-2 pt-2 pb-1 text-sm font-medium">
                        Products ({template.lines.length})
                    </h2>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Code</TableHead>
                                <TableHead>Product</TableHead>
                                <TableHead>Unit</TableHead>
                                <TableHead>Category</TableHead>
                                <TableHead>Purpose id</TableHead>
                                <TableHead className="text-right">
                                    Last cost
                                </TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {template.lines.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        This template has no products.
                                    </TableCell>
                                </TableRow>
                            )}

                            {template.lines.map((line, index) => (
                                <TableRow
                                    key={`${String(line.lineId)}-${String(index)}`}
                                >
                                    <TableCell className="font-mono text-xs">
                                        {line.productCode ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        {line.productName || '—'}
                                        {line.name &&
                                            line.name !== line.productName && (
                                                <span className="text-muted-foreground block text-xs">
                                                    {line.name}
                                                </span>
                                            )}
                                    </TableCell>
                                    <TableCell>{line.uomName ?? '—'}</TableCell>
                                    <TableCell>
                                        {line.categoryName ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        {line.purposeId ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatAmount(line.lastCost)}
                                    </TableCell>
                                    <TableCell>
                                        {line.active !== null && (
                                            <Badge
                                                variant={
                                                    line.active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {line.active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </section>
            </div>
        </>
    );
}

DocumentTemplateShow.layout = {
    breadcrumbs: [
        {
            title: 'Document templates',
            href: documentTemplatesIndex(),
        },
    ],
};
