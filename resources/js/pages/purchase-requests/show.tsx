import { Head, Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { ApprovalActions } from '@/components/approval-actions';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { usePermissions } from '@/hooks/use-permissions';
import {
    destroy as destroyPurchaseRequest,
    edit as editPurchaseRequest,
    index as purchaseRequestsIndex,
} from '@/routes/purchase-requests';
import { store as storeDecision } from '@/routes/purchase-requests/decisions';
import { formatDate, formatDateTime, formatQuantity } from '@/lib/format';

type PurchaseRequestLine = {
    productId: number | null;
    productDetailId: number | null;
    productCode: string;
    productName: string;
    uomName: string | null;
    qty: number;
    notes: string | null;
    stock: number | null;
    suggestionQty: number | null;
    minQty: number | null;
    maxQty: number | null;
    requestProcessId: number | null;
};

type PurchaseRequest = {
    provider: string;
    number: string;
    date: string | null;
    requiredDate: string | null;
    branchName: string | null;
    costCenterName: string | null;
    projectName: string | null;
    isTemplate: boolean;
    statusName: string | null;
    notes: string | null;
    createdByName: string | null;
    createdAt: string | null;
    editedByName: string | null;
    editedAt: string | null;
    authorizedByName: string | null;
    authorizedAt: string | null;
    lines: PurchaseRequestLine[];
    purchaseNumbers: string[];
    transferNumbers: string[];
};

type Props = {
    purchaseRequest: PurchaseRequest;
};

function byline(name: string | null, at: string | null): string {
    if (name === null && at === null) {
        return '—';
    }

    return [name, at === null ? null : formatDateTime(at)]
        .filter((part) => part !== null)
        .join(' · ');
}

function Field({ label, value }: { label: string; value: string | null }) {
    return (
        <div className="grid gap-0.5">
            <dt className="text-muted-foreground text-xs">{label}</dt>
            <dd className="text-sm">{value ?? '—'}</dd>
        </div>
    );
}

export default function PurchaseRequestShow({ purchaseRequest: pr }: Props) {
    const { can } = usePermissions();
    const canManage = can('esb.invoke') && can('purchase-requests.manage');

    return (
        <>
            <Head title={pr.number} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={pr.number}
                    description="Read live from the ERP."
                    back={{
                        href: purchaseRequestsIndex(),
                        label: 'Purchase requests',
                    }}
                    actions={
                        <>
                            {pr.isTemplate && (
                                <Badge variant="outline">Template</Badge>
                            )}
                            <StatusBadge status={pr.statusName} />
                            {/* Offered on every request: which statuses accept a
                                change is not documented, so the ERP is left to
                                refuse one it does not accept. */}
                            {canManage && (
                                <Button size="sm" variant="outline" asChild>
                                    <Link href={editPurchaseRequest(pr.number)}>
                                        <Pencil className="size-4" />
                                        Edit
                                    </Link>
                                </Button>
                            )}
                            {/* The ESB takes no reason for a rejection. */}
                            <ApprovalActions
                                documentLabel="purchase request"
                                number={pr.number}
                                decisionUrl={storeDecision(pr.number).url}
                                deleteUrl={
                                    destroyPurchaseRequest(pr.number).url
                                }
                                canDecide={
                                    can('esb.invoke') &&
                                    can('purchase-requests.approve')
                                }
                                canDelete={canManage}
                            />
                        </>
                    }
                />

                <dl className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Field label="Request date" value={formatDate(pr.date)} />
                    <Field
                        label="Required date"
                        value={formatDate(pr.requiredDate)}
                    />
                    <Field label="Branch" value={pr.branchName} />
                    <Field label="Cost center" value={pr.costCenterName} />
                    <Field label="Project" value={pr.projectName} />
                    <Field
                        label="Created"
                        value={byline(pr.createdByName, pr.createdAt)}
                    />
                    <Field
                        label="Last edited"
                        value={byline(pr.editedByName, pr.editedAt)}
                    />
                    <Field
                        label="Authorized"
                        value={byline(pr.authorizedByName, pr.authorizedAt)}
                    />
                </dl>

                {pr.notes && (
                    <div className="grid gap-0.5">
                        <p className="text-muted-foreground text-xs">Notes</p>
                        <p className="text-sm whitespace-pre-line">
                            {pr.notes}
                        </p>
                    </div>
                )}

                <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border p-2">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Code</TableHead>
                                <TableHead>Product</TableHead>
                                <TableHead className="text-right">
                                    Qty
                                </TableHead>
                                <TableHead>UoM</TableHead>
                                <TableHead className="text-right">
                                    Stock
                                </TableHead>
                                <TableHead className="text-right">
                                    Suggested
                                </TableHead>
                                <TableHead>Notes</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {pr.lines.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        This purchase request has no lines.
                                    </TableCell>
                                </TableRow>
                            )}

                            {pr.lines.map((line, index) => (
                                // A product can appear on more than one line,
                                // so its id is not a unique key on its own.
                                <TableRow
                                    key={`${line.productDetailId ?? 'x'}-${index}`}
                                >
                                    <TableCell className="font-mono text-xs">
                                        {line.productCode || '—'}
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        {line.productName || '—'}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatQuantity(line.qty)}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {line.uomName ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground text-right tabular-nums">
                                        {formatQuantity(line.stock)}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground text-right tabular-nums">
                                        {formatQuantity(line.suggestionQty)}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {line.notes ?? '—'}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {(pr.purchaseNumbers.length > 0 ||
                    pr.transferNumbers.length > 0) && (
                    <dl className="grid gap-4 sm:grid-cols-2">
                        <Field
                            label="Purchase documents"
                            value={
                                pr.purchaseNumbers.length > 0
                                    ? pr.purchaseNumbers.join(', ')
                                    : null
                            }
                        />
                        <Field
                            label="Transfer documents"
                            value={
                                pr.transferNumbers.length > 0
                                    ? pr.transferNumbers.join(', ')
                                    : null
                            }
                        />
                    </dl>
                )}
            </div>
        </>
    );
}
