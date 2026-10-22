import { Head, Link } from '@inertiajs/react';
import { ApprovalActions } from '@/components/approval-actions';
import { AttachmentActions } from '@/components/attachment-actions';
import {
    DetailField,
    DetailFields,
    DetailSummary,
} from '@/components/detail-fields';
import { JournalLinesTable } from '@/components/journal-lines-table';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import { formatDate, formatDateTime } from '@/lib/format';
import {
    destroy as destroyRoute,
    edit as editRoute,
    index as indexRoute,
} from '@/routes/memorial-journals';
import { store as decisionRoute } from '@/routes/memorial-journals/decisions';
import { store as imagesRoute } from '@/routes/memorial-journals/images';
import type { MemorialJournal } from '@/types/accounting';
import { MEMORIAL_JOURNAL_STATUS } from '@/types/accounting';

type Props = {
    journal: MemorialJournal;
};

/** Mirrors DecideMemorialJournalRequest::REASON_MAX. */
const REASON_MAX = 500;

/** Mirrors AttachMemorialJournalImageRequest::EXTENSIONS. */
const IMAGE_TYPES = '.jpg,.jpeg,.png';

const DECIDABLE: number[] = [
    MEMORIAL_JOURNAL_STATUS.new,
    MEMORIAL_JOURNAL_STATUS.waitingForApproval,
];

function byline(name: string | null, at: string | null): string | null {
    const parts = [name, at === null ? null : formatDateTime(at)].filter(
        (part) => part !== null,
    );

    return parts.length === 0 ? null : parts.join(' · ');
}

/**
 * Edit, authorize / reject / delete ([ESB-044], [ESB-045]): offered by
 * permission and by the statuses the vendor documents. The vendor needs a
 * reason to reject only while the journal waits for approval; the ERP
 * refuses whatever else it will not allow, and says why.
 */
function MemorialJournalActions({ journal }: Props) {
    const { can } = usePermissions();
    const invoke = can('esb.invoke');
    const manage = invoke && can('memorial-journals.manage');
    const status = journal.statusId;

    return (
        <>
            {manage && (
                <Button size="sm" variant="outline" asChild>
                    <Link href={editRoute(journal.number).url}>Edit</Link>
                </Button>
            )}
            <ApprovalActions
                documentLabel="memorial journal"
                number={journal.number}
                decisionUrl={decisionRoute(journal.number).url}
                deleteUrl={destroyRoute(journal.number).url}
                canDecide={
                    invoke &&
                    can('memorial-journals.approve') &&
                    status !== null &&
                    DECIDABLE.includes(status)
                }
                canDelete={manage}
                rejectReason={{
                    max: REASON_MAX,
                    required:
                        status === MEMORIAL_JOURNAL_STATUS.waitingForApproval,
                }}
            />
        </>
    );
}

/**
 * One memorial journal ([ESB-043]) with its debit / credit lines, as the
 * ERP has them. Images are counted and added ([ESB-046]), not opened.
 */
export default function MemorialJournalShow({ journal }: Props) {
    const { can } = usePermissions();

    return (
        <>
            <Head title={journal.number} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={journal.number}
                    description="Memorial journal, read live from the ERP."
                    back={{
                        href: indexRoute().url,
                        label: 'Memorial journals',
                    }}
                    actions={
                        <>
                            <StatusBadge status={journal.statusName} />
                            <MemorialJournalActions journal={journal} />
                        </>
                    }
                />

                <DetailSummary
                    label="Memorial journal summary"
                    items={[
                        { label: 'Date', value: formatDate(journal.date) },
                        { label: 'Notes', value: journal.notes },
                        {
                            label: 'Approval flow',
                            value:
                                journal.hasApproval === null
                                    ? null
                                    : journal.hasApproval
                                      ? 'Yes'
                                      : 'No',
                        },
                    ]}
                />

                <DetailFields>
                    <DetailField
                        label="Created"
                        value={byline(journal.createdBy, journal.createdAt)}
                    />
                    <DetailField
                        label="Last edited"
                        value={byline(journal.editedBy, journal.editedAt)}
                    />
                    <DetailField
                        label="Authorized"
                        value={byline(
                            journal.authorizedBy,
                            journal.authorizedAt,
                        )}
                    />
                </DetailFields>

                <JournalLinesTable lines={journal.lines} />

                <AttachmentActions
                    name={journal.number}
                    count={journal.attachmentCount}
                    canManage={
                        can('esb.invoke') && can('memorial-journals.manage')
                    }
                    uploadUrl={imagesRoute(journal.number).url}
                    accept={IMAGE_TYPES}
                    single
                />
            </div>
        </>
    );
}
