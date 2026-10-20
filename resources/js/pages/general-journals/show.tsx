import { Head, router } from '@inertiajs/react';
import { JournalLinesTable } from '@/components/journal-lines-table';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import {
    index as indexRoute,
    show as showRoute,
} from '@/routes/general-journals';
import type { JournalLine } from '@/types/accounting';
import type { Pagination } from '@/types/pagination';

type Props = {
    journalHeadId: number;
    lines: JournalLine[];
    pagination: Pagination | null;
};

/**
 * One general journal's lines ([ESB-041]). The ERP's detail carries the
 * lines only; the date, transaction and reference are on the list.
 */
export default function GeneralJournalShow({
    journalHeadId,
    lines,
    pagination,
}: Props) {
    const title = `Journal ${journalHeadId}`;

    return (
        <>
            <Head title={title} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={title}
                    description="General journal lines, read live from the ERP."
                    back={{ href: indexRoute().url, label: 'General journal' }}
                />

                <JournalLinesTable lines={lines} />

                <ListPagination
                    pagination={pagination}
                    noun="lines"
                    onPageChange={(page) =>
                        router.get(
                            showRoute(journalHeadId, { query: { page } }).url,
                            {},
                            { preserveScroll: true },
                        )
                    }
                />
            </div>
        </>
    );
}
