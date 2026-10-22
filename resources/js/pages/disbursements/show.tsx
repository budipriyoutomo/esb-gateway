import { Head, Link } from '@inertiajs/react';
import { ApprovalActions } from '@/components/approval-actions';
import { AttachmentActions } from '@/components/attachment-actions';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import {
    DetailField,
    DetailFields,
    DetailSummary,
} from '@/components/detail-fields';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import {
    formatAmount,
    formatDate,
    formatDateTime,
    formatQuantity,
} from '@/lib/format';
import {
    destroy as destroyRoute,
    edit as editRoute,
    index as indexRoute,
} from '@/routes/disbursements';
import { store as attachmentsRoute } from '@/routes/disbursements/attachments';
import { store as decisionRoute } from '@/routes/disbursements/decisions';
import type { Disbursement, DisbursementLine } from '@/types/disbursements';
import { DISBURSEMENT_STATUS, PAYMENT } from '@/types/disbursements';

type Props = {
    disbursement: Disbursement;
};

/** Mirrors DecideDisbursementRequest::REASON_MAX. */
const REASON_MAX = 500;

/** Mirrors AttachDisbursementFilesRequest::EXTENSIONS. */
const ATTACHMENT_TYPES = '.pdf,.bmp,.gif,.jpg,.jpeg,.png';

const DECIDABLE: number[] = [
    DISBURSEMENT_STATUS.new,
    DISBURSEMENT_STATUS.waitingForApproval,
];

const DELETABLE: number[] = [
    DISBURSEMENT_STATUS.new,
    DISBURSEMENT_STATUS.rejected,
    DISBURSEMENT_STATUS.authorized,
    DISBURSEMENT_STATUS.waitingForApproval,
];

function byline(name: string | null, at: string | null): string | null {
    const parts = [name, at === null ? null : formatDateTime(at)].filter(
        (part) => part !== null,
    );

    return parts.length === 0 ? null : parts.join(' · ');
}

const lineColumns: DataTableColumn<DisbursementLine>[] = [
    {
        key: 'account',
        header: 'Account',
        cell: (line) => (
            <>
                <span className="font-mono text-xs">{line.coaNo ?? '—'}</span>
                {line.coaDescription && (
                    <span className="text-muted-foreground block text-xs">
                        {line.coaDescription}
                    </span>
                )}
            </>
        ),
    },
    {
        key: 'description',
        header: 'Description',
        cell: (line) => line.description ?? '—',
    },
    {
        key: 'branch',
        header: 'Branch',
        className: 'text-muted-foreground',
        cell: (line) => line.branchName ?? '—',
    },
    {
        key: 'amount',
        header: 'Amount',
        align: 'right',
        cell: (line) => formatAmount(line.amount),
    },
];

/**
 * Edit, authorize / reject / delete ([ESB-038], [ESB-039]): offered by
 * permission and by the statuses the vendor documents. Whether an edit is
 * allowed is the ERP's to say when it is opened.
 */
function DisbursementActions({ disbursement }: Props) {
    const { can } = usePermissions();
    const invoke = can('esb.invoke');
    const manage = invoke && can('disbursements.manage');
    const status = disbursement.statusId;

    return (
        <>
            {manage && (
                <Button size="sm" variant="outline" asChild>
                    <Link href={editRoute(disbursement.number).url}>Edit</Link>
                </Button>
            )}
            <ApprovalActions
                documentLabel="disbursement"
                number={disbursement.number}
                decisionUrl={decisionRoute(disbursement.number).url}
                deleteUrl={destroyRoute(disbursement.number).url}
                canDecide={
                    invoke &&
                    can('disbursements.approve') &&
                    status !== null &&
                    DECIDABLE.includes(status)
                }
                canDelete={
                    manage && status !== null && DELETABLE.includes(status)
                }
                rejectReason={{ max: REASON_MAX, required: false }}
            />
        </>
    );
}

/**
 * One disbursement ([ESB-037]): who is paid, how, the account lines, and the
 * payments and settlements against it. Amounts and totals are the ERP's.
 */
export default function DisbursementShow({ disbursement }: Props) {
    const { can } = usePermissions();
    const credit = disbursement.paymentId === PAYMENT.credit;
    const currency = disbursement.currencySign ?? disbursement.currencyName;
    const bank = [
        disbursement.bankName,
        disbursement.bankAccountName,
        disbursement.bankAccountNumber,
    ].filter((part) => part !== null);

    return (
        <>
            <Head title={disbursement.number} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={disbursement.number}
                    description="Disbursement, read live from the ERP."
                    back={{ href: indexRoute().url, label: 'Disbursements' }}
                    actions={
                        <>
                            <StatusBadge status={disbursement.statusName} />
                            <DisbursementActions disbursement={disbursement} />
                        </>
                    }
                />

                <DetailSummary
                    label="Disbursement summary"
                    items={[
                        { label: 'Date', value: formatDate(disbursement.date) },
                        { label: 'Branch', value: disbursement.branchName },
                        {
                            label: 'Payment',
                            value: disbursement.paymentName,
                        },
                        {
                            label: 'Total',
                            value: [
                                currency,
                                formatAmount(disbursement.grandTotal),
                            ]
                                .filter((part) => part !== null)
                                .join(' '),
                        },
                    ]}
                />

                <DetailFields>
                    <DetailField
                        label="Supplier"
                        value={disbursement.supplierName}
                    />
                    {!credit && (
                        <DetailField
                            label="Cash / bank account"
                            value={disbursement.cashBankCoaNo}
                        />
                    )}
                    {credit && (
                        <>
                            <DetailField
                                label="Reference"
                                value={disbursement.reference}
                            />
                            <DetailField
                                label="Credit term"
                                value={
                                    disbursement.creditTerm === null
                                        ? null
                                        : `${disbursement.creditTerm} days`
                                }
                            />
                        </>
                    )}
                    <DetailField
                        label="Invoice due"
                        value={
                            disbursement.invoiceDueDate === null
                                ? null
                                : formatDate(disbursement.invoiceDueDate)
                        }
                    />
                    <DetailField
                        label="Currency"
                        value={
                            disbursement.currencyName === null
                                ? null
                                : `${disbursement.currencyName}${
                                      disbursement.rate === null ||
                                      disbursement.rate === 1
                                          ? ''
                                          : ` · rate ${formatQuantity(disbursement.rate)}`
                                  }`
                        }
                    />
                    {bank.length > 0 && (
                        <DetailField
                            label="Bank account"
                            value={bank.join(' · ')}
                        />
                    )}
                    {disbursement.costCenter && (
                        <DetailField
                            label="Cost center"
                            value={disbursement.costCenter}
                        />
                    )}
                    {disbursement.project && (
                        <DetailField
                            label="Project"
                            value={disbursement.project}
                        />
                    )}
                    {disbursement.releasePaymentCode && (
                        <DetailField
                            label="Payment release"
                            value={disbursement.releasePaymentCode}
                        />
                    )}
                    <DetailField label="Notes" value={disbursement.notes} />
                    <DetailField
                        label="Created"
                        value={byline(
                            disbursement.createdBy,
                            disbursement.createdAt,
                        )}
                    />
                    <DetailField
                        label="Last edited"
                        value={byline(
                            disbursement.editedBy,
                            disbursement.editedAt,
                        )}
                    />
                    <DetailField
                        label="Authorized"
                        value={byline(
                            disbursement.authorizedBy,
                            disbursement.authorizedAt,
                        )}
                    />
                </DetailFields>

                <DataTable
                    caption="Account lines"
                    columns={lineColumns}
                    rows={disbursement.lines}
                    rowKey={(line, index) => `${line.coaNo ?? 'line'}-${index}`}
                    empty="This disbursement has no lines."
                />

                <AttachmentActions
                    name={disbursement.number}
                    count={disbursement.attachmentCount}
                    canManage={can('esb.invoke') && can('disbursements.manage')}
                    uploadUrl={attachmentsRoute(disbursement.number).url}
                    accept={ATTACHMENT_TYPES}
                    uploadBlocked={
                        disbursement.attachmentCount > 0
                            ? 'The ERP would replace the current attachments with new files. Add files in the ERP.'
                            : null
                    }
                />

                {disbursement.linkedDocuments.length > 0 && (
                    <section className="grid gap-2">
                        <h2 className="text-sm font-medium">
                            Payments and settlements
                        </h2>
                        <ul className="grid gap-1 text-sm">
                            {disbursement.linkedDocuments.map((linked) => (
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
                                    <span className="tabular-nums">
                                        {formatAmount(linked.total)}
                                    </span>
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
