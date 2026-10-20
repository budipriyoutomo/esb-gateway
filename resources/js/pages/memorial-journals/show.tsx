import { Head } from '@inertiajs/react';
import {
    DetailField,
    DetailFields,
    DetailSummary,
} from '@/components/detail-fields';
import { JournalLinesTable } from '@/components/journal-lines-table';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { formatDate, formatDateTime } from '@/lib/format';
import { index as indexRoute } from '@/routes/memorial-journals';
import type { MemorialJournal } from '@/types/accounting';

type Props = {
    journal: MemorialJournal;
};

function byline(name: string | null, at: string | null): string | null {
    const parts = [name, at === null ? null : formatDateTime(at)].filter(
        (part) => part !== null,
    );

    return parts.length === 0 ? null : parts.join(' · ');
}

/**
 * One memorial journal ([ESB-043]) with its debit / credit lines, as the
 * ERP has them. Attachments are counted, not opened.
 */
export default function MemorialJournalShow({ journal }: Props) {
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
                    actions={<StatusBadge status={journal.statusName} />}
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
                        label="Attachments"
                        value={
                            journal.attachmentCount === 0
                                ? 'None'
                                : String(journal.attachmentCount)
                        }
                    />
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
            </div>
        </>
    );
}
