import { Head, Link } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';
import { ApprovalActions } from '@/components/approval-actions';
import { AttachmentActions } from '@/components/attachment-actions';
import { ConfirmAction } from '@/components/confirm-action';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import {
    DetailField,
    DetailFields,
    DetailSummary,
} from '@/components/detail-fields';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import {
    formatAmount,
    formatDate,
    formatDateTime,
    formatQuantity,
} from '@/lib/format';
import {
    attachmentsUrl,
    completionUrl,
    decisionUrl,
    destroyUrl,
    editUrl,
    indexUrl,
    isEditableKind,
    partyLabel,
    sideLabels,
    TRANSFER_REQUEST_STATUS,
} from '@/lib/inventory';
import type {
    InventoryDocument,
    InventoryKind,
    InventoryLine,
    InventoryParty,
} from '@/types/inventory';

type Props = {
    kind: InventoryKind;
    document: InventoryDocument;
};

/** Mirrors DecideInventoryDocumentRequest::REASON_MAX. */
const REASON_MAX = 500;

type QuantityKey =
    | 'requestedQty'
    | 'qty'
    | 'receivedQty'
    | 'outstandingQty'
    | 'availableQty'
    | 'stockQty'
    | 'pendingQty';

const QUANTITIES: { key: QuantityKey; header: string }[] = [
    { key: 'requestedQty', header: 'Requested' },
    { key: 'qty', header: 'Qty' },
    { key: 'receivedQty', header: 'Received' },
    { key: 'outstandingQty', header: 'Outstanding' },
    { key: 'availableQty', header: 'Available' },
    { key: 'stockQty', header: 'Stock' },
    { key: 'pendingQty', header: 'Pending' },
];

function byline(name: string | null, at: string | null): string | null {
    const parts = [name, at === null ? null : formatDateTime(at)].filter(
        (part) => part !== null,
    );

    return parts.length === 0 ? null : parts.join(' · ');
}

/**
 * Only the columns the document has: a quantity no line carries is not a
 * column (each kind reads different ones, ADR-026).
 */
function lineColumns(lines: InventoryLine[]): DataTableColumn<InventoryLine>[] {
    const has = (pick: (line: InventoryLine) => unknown) =>
        lines.some((line) => pick(line) !== null);

    return [
        ...(has((line) => line.productCode)
            ? [
                  {
                      key: 'code',
                      header: 'Code',
                      className: 'font-mono text-xs',
                      cell: (line: InventoryLine) => line.productCode ?? '—',
                  },
              ]
            : []),
        {
            key: 'product',
            header: 'Product',
            cell: (line) => (
                <>
                    <div className="font-medium">{line.productName ?? '—'}</div>
                    {line.notes && (
                        <div className="text-muted-foreground text-xs">
                            {line.notes}
                        </div>
                    )}
                </>
            ),
        },
        ...QUANTITIES.filter(({ key }) => has((line) => line[key])).map(
            ({ key, header }) => ({
                key,
                header,
                align: 'right' as const,
                cell: (line: InventoryLine) => formatQuantity(line[key]),
            }),
        ),
        {
            key: 'unit',
            header: 'Unit',
            className: 'text-muted-foreground',
            cell: (line) => line.unit ?? '—',
        },
        ...(has((line) => line.cost)
            ? [
                  {
                      key: 'cost',
                      header: 'Cost',
                      align: 'right' as const,
                      cell: (line: InventoryLine) => formatAmount(line.cost),
                  },
              ]
            : []),
        ...(has((line) => line.purpose)
            ? [
                  {
                      key: 'purpose',
                      header: 'Purpose',
                      cell: (line: InventoryLine) => line.purpose ?? '—',
                  },
              ]
            : []),
    ];
}

/**
 * Authorize / reject / delete ([ESB-034]), and a transfer request's finish /
 * reopen. Offered by permission; for transfer requests also by the statuses
 * the vendor documents, and delete is hidden when the ERP says it cannot be
 * deleted. Anything else the ERP refuses is reported in the toast.
 */
function DocumentActions({ kind, document }: Props) {
    const { can } = usePermissions();
    const invoke = can('esb.invoke');
    const approve = invoke && can(`${kind.value}.approve`);
    const manage = invoke && can(`${kind.value}.manage`);
    const transfer = kind.value === 'goods-transfer-requests';
    const status = document.statusId;
    const label = kind.label.toLowerCase();

    const mayFinish =
        transfer &&
        manage &&
        (status === TRANSFER_REQUEST_STATUS.authorized ||
            status === TRANSFER_REQUEST_STATUS.delivering);
    const mayReopen =
        transfer && manage && status === TRANSFER_REQUEST_STATUS.finished;
    // The ERP's own "no", and assets (the form has none), rule out an edit.
    const editHref =
        manage &&
        isEditableKind(kind.value) &&
        document.canEdit !== false &&
        !document.carryOver.hasAssets
            ? editUrl(kind.value, document.number)
            : null;

    return (
        <>
            {editHref !== null && (
                <Button size="sm" variant="outline" asChild>
                    <Link href={editHref}>Edit</Link>
                </Button>
            )}
            <ApprovalActions
                documentLabel={label}
                number={document.number}
                decisionUrl={decisionUrl(kind.value, document.number)}
                deleteUrl={destroyUrl(kind.value, document.number)}
                canDecide={
                    approve &&
                    (!transfer || status === TRANSFER_REQUEST_STATUS.new)
                }
                canDelete={manage && document.canDelete !== false}
                rejectReason={
                    kind.rejectTakesReason
                        ? { max: REASON_MAX, required: false }
                        : undefined
                }
            />
            {mayFinish && (
                <ConfirmAction
                    label="Finish"
                    title={`Finish ${document.number}?`}
                    description="The request is closed in the ERP straight away: nothing more is delivered against it. It can be reopened later."
                    url={completionUrl(document.number, 'finish')}
                />
            )}
            {mayReopen && (
                <ConfirmAction
                    label="Reopen"
                    title={`Reopen ${document.number}?`}
                    description="The ERP puts the request back to the status it had before it was finished."
                    url={completionUrl(document.number, 'reopen')}
                />
            )}
        </>
    );
}

/**
 * The document's attachments ([ESB-036]): counted, never opened from the
 * gateway. Where the ERP's upload keeps only what it is sent back, files
 * are attached only while there are none.
 */
function Attachments({ kind, document }: Props) {
    const { can } = usePermissions();
    const attachments = kind.attachments;
    const url = attachmentsUrl(kind.value, document.number);

    if (attachments === null || url === null) {
        return null;
    }

    const blocked =
        attachments.uploadReplaces && document.attachmentCount > 0
            ? attachments.removable
                ? 'The ERP would replace the current attachments with new files. Remove the current attachments first, then attach every file again.'
                : 'The ERP would replace the current attachments with new files. Add files in the ERP.'
            : null;

    return (
        <AttachmentActions
            name={document.number}
            count={document.attachmentCount}
            canManage={can('esb.invoke') && can(`${kind.value}.manage`)}
            uploadUrl={url}
            deleteUrl={attachments.removable ? url : undefined}
            accept={attachments.extensions
                .map((extension) => `.${extension}`)
                .join(',')}
            uploadBlocked={blocked}
        />
    );
}

function Party({ party }: { party: InventoryParty | null }) {
    const label = partyLabel(party);

    if (party === null || label === null) {
        return null;
    }

    const contact = [party.address, party.phone].filter(
        (part) => part !== null,
    );

    return (
        <>
            <span>{label}</span>
            {party.name !== null &&
                (party.branchName !== null || party.locationName !== null) && (
                    <span className="text-muted-foreground block text-xs">
                        {[party.branchName, party.locationName]
                            .filter((part) => part !== null)
                            .join(' · ')}
                    </span>
                )}
            {contact.length > 0 && (
                <span className="text-muted-foreground block text-xs">
                    {contact.join(' · ')}
                </span>
            )}
        </>
    );
}

/**
 * One inventory document ([ESB-033], ADR-026): where the goods came from and
 * went to, the reference, the lines with the quantities this kind has, and
 * linked documents. Attachments are counted only.
 */
export default function InventoryShow({ kind, document }: Props) {
    const sides = sideLabels(kind.value);
    const reference =
        document.reference === null
            ? null
            : [document.transactionType, document.reference]
                  .filter((part) => part !== null)
                  .join(' ');

    return (
        <>
            <Head title={document.number} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={document.number}
                    description={`${kind.label}, read live from the ERP.`}
                    back={{
                        href: indexUrl(kind.value),
                        label: kind.pluralLabel,
                    }}
                    actions={
                        <>
                            <StatusBadge status={document.statusName} />
                            <DocumentActions kind={kind} document={document} />
                        </>
                    }
                />

                {document.stockUsed === true && (
                    <Alert role="status">
                        <TriangleAlert aria-hidden />
                        <AlertDescription>
                            The stock on this document has been used by another
                            transaction since. The ERP may refuse to change or
                            delete it.
                        </AlertDescription>
                    </Alert>
                )}

                <DetailSummary
                    label={`${kind.label} summary`}
                    items={[
                        { label: 'Date', value: formatDate(document.date) },
                        {
                            label: sides.from,
                            value: <Party party={document.from} />,
                        },
                        ...(sides.to === null
                            ? []
                            : [
                                  {
                                      label: sides.to,
                                      value: <Party party={document.to} />,
                                  },
                              ]),
                        ...(reference === null
                            ? []
                            : [{ label: 'Reference', value: reference }]),
                    ]}
                />

                <DetailFields>
                    {document.referenceDate && (
                        <DetailField
                            label="Reference date"
                            value={formatDate(document.referenceDate)}
                        />
                    )}
                    <DetailField label="Notes" value={document.notes} />
                    {document.costCenter && (
                        <DetailField
                            label="Cost center"
                            value={document.costCenter}
                        />
                    )}
                    {document.project && (
                        <DetailField label="Project" value={document.project} />
                    )}
                    {kind.attachments === null && (
                        <DetailField
                            label="Attachments"
                            value={
                                document.attachmentCount === 0
                                    ? 'None'
                                    : String(document.attachmentCount)
                            }
                        />
                    )}
                    <DetailField
                        label="Created"
                        value={byline(document.createdBy, document.createdAt)}
                    />
                    <DetailField
                        label="Last edited"
                        value={byline(document.editedBy, document.editedAt)}
                    />
                    <DetailField
                        label="Authorized"
                        value={byline(
                            document.authorizedBy,
                            document.authorizedAt,
                        )}
                    />
                </DetailFields>

                <DataTable
                    caption="Lines"
                    columns={lineColumns(document.lines)}
                    rows={document.lines}
                    rowKey={(line, index) =>
                        `${line.productDetailId ?? 'line'}-${index}`
                    }
                    empty="This document has no lines."
                />

                <Attachments kind={kind} document={document} />

                {document.linkedDocuments.length > 0 && (
                    <section className="grid gap-2">
                        <h2 className="text-sm font-medium">
                            Linked documents
                        </h2>
                        <ul className="grid gap-1 text-sm">
                            {document.linkedDocuments.map((linked) => (
                                <li
                                    key={`${linked.type}-${linked.number}`}
                                    className="flex flex-wrap items-center gap-2"
                                >
                                    <span className="text-muted-foreground">
                                        {linked.type}
                                    </span>
                                    <span className="font-mono text-xs">
                                        {linked.number}
                                    </span>
                                    {linked.date && (
                                        <span className="text-muted-foreground">
                                            {formatDate(linked.date)}
                                        </span>
                                    )}
                                    {linked.statusName && (
                                        <StatusBadge
                                            status={linked.statusName}
                                        />
                                    )}
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </>
    );
}
