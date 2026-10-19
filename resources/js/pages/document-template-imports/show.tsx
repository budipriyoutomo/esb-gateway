import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CloudUpload, ListChecks } from 'lucide-react';
import { useEffect } from 'react';
import AlertError from '@/components/alert-error';
import Heading from '@/components/heading';
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
import {
    index as importsIndex,
    sync as importsSync,
    validate as importsValidate,
} from '@/routes/document-template-imports';
import { show as documentTemplatesShow } from '@/routes/document-templates';
import type {
    DocumentTemplateImport,
    ImportEntry,
    ImportEntryStatus,
} from '@/types/document-template-import';
import {
    ENTRY_STATUS_LABELS,
    ENTRY_STATUS_VARIANTS,
} from '@/types/document-template-import';
import { formatDateTime } from '@/lib/format';

type Props = {
    import: DocumentTemplateImport;
    busy: boolean;
    counts: Record<ImportEntryStatus, number>;
    entries: ImportEntry[];
};

const POLL_MS = 3000;

const ORDER: ImportEntryStatus[] = [
    'synced',
    'ready',
    'pending',
    'syncing',
    'blocked',
    'invalid',
    'failed',
    'unconfirmed',
];

function EntryRow({ entry }: { entry: ImportEntry }) {
    return (
        <TableRow className="align-top">
            <TableCell className="text-muted-foreground tabular-nums">
                {entry.position}
            </TableCell>
            <TableCell>
                <details>
                    <summary className="cursor-pointer font-medium">
                        {entry.name}
                        <span className="text-muted-foreground ml-2 text-xs font-normal">
                            {entry.lines.length} product(s)
                        </span>
                    </summary>
                    <table className="mt-2 w-full text-xs">
                        <thead className="text-muted-foreground">
                            <tr>
                                <th className="pr-2 text-left font-normal">
                                    Row
                                </th>
                                <th className="pr-2 text-left font-normal">
                                    Code
                                </th>
                                <th className="pr-2 text-left font-normal">
                                    Product
                                </th>
                                <th className="pr-2 text-left font-normal">
                                    Unit
                                </th>
                                <th className="pr-2 text-left font-normal">
                                    Line name
                                </th>
                                <th className="text-left font-normal">
                                    ERP id
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {entry.lines.map((line) => (
                                <tr key={line.rowNumber}>
                                    <td className="pr-2 tabular-nums">
                                        {line.rowNumber}
                                    </td>
                                    <td className="pr-2 font-mono">
                                        {line.productCode || '—'}
                                    </td>
                                    <td className="pr-2">
                                        {line.productName || '—'}
                                    </td>
                                    <td className="pr-2">{line.unit || '—'}</td>
                                    <td className="pr-2">
                                        {line.category || '—'}
                                    </td>
                                    <td className="tabular-nums">
                                        {line.productDetailId ?? '—'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </details>
            </TableCell>
            <TableCell className="text-sm">{entry.typeName ?? '—'}</TableCell>
            <TableCell className="max-w-48 text-xs">
                {entry.branchCodes.length === 0
                    ? '—'
                    : entry.branchCodes.join(', ')}
            </TableCell>
            <TableCell>
                <Badge variant={ENTRY_STATUS_VARIANTS[entry.status]}>
                    {ENTRY_STATUS_LABELS[entry.status]}
                </Badge>
                {entry.esbTemplateId !== null && (
                    <Link
                        href={documentTemplatesShow(entry.esbTemplateId)}
                        className="mt-1 block text-xs hover:underline"
                    >
                        ERP #{entry.esbTemplateId}
                    </Link>
                )}
            </TableCell>
            <TableCell className="max-w-md text-xs">
                {entry.problems.length === 0 ? (
                    '—'
                ) : (
                    <ul className="text-destructive list-inside list-disc">
                        {entry.problems.map((problem) => (
                            <li key={problem}>{problem}</li>
                        ))}
                    </ul>
                )}
            </TableCell>
        </TableRow>
    );
}

export default function DocumentTemplateImportShow({
    import: current,
    busy,
    counts,
    entries,
}: Props) {
    // While a validate or sync job holds the import, refresh its state.
    useEffect(() => {
        if (!busy) {
            return;
        }

        const timer = window.setInterval(() => {
            router.reload({ only: ['import', 'busy', 'counts', 'entries'] });
        }, POLL_MS);

        return () => {
            window.clearInterval(timer);
        };
    }, [busy]);

    const checkable =
        counts.pending +
        counts.ready +
        counts.blocked +
        counts.failed +
        counts.unconfirmed;

    function post(url: string) {
        router.post(url, {}, { preserveScroll: true });
    }

    return (
        <>
            <Head title={`Import ${current.filename}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <Button variant="ghost" size="sm" asChild className="-ml-2">
                        <Link href={importsIndex()}>
                            <ArrowLeft className="size-4" />
                            Imports
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={current.filename}
                        description={`Import #${String(current.id)} · uploaded ${formatDateTime(current.createdAt)} by ${current.uploadedBy ?? '—'}`}
                    />
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            disabled={busy || checkable === 0}
                            onClick={() => {
                                post(importsValidate(current.id).url);
                            }}
                        >
                            <ListChecks className="size-4" />
                            {current.status === 'validating'
                                ? 'Validating…'
                                : 'Validate against ERP'}
                        </Button>
                        <Button
                            disabled={busy || counts.ready === 0}
                            onClick={() => {
                                post(importsSync(current.id).url);
                            }}
                        >
                            <CloudUpload className="size-4" />
                            {current.status === 'syncing'
                                ? 'Syncing…'
                                : `Sync ${String(counts.ready)} ready to ERP`}
                        </Button>
                    </div>
                </div>

                {current.lastError && (
                    <AlertError
                        title="The last run stopped early."
                        errors={[current.lastError]}
                    />
                )}

                {current.problems.length > 0 && (
                    <AlertError
                        title="Rows that belong to no template were skipped."
                        errors={current.problems}
                    />
                )}

                <div className="flex flex-wrap gap-2">
                    {ORDER.filter((status) => counts[status] > 0).map(
                        (status) => (
                            <Badge
                                key={status}
                                variant={ENTRY_STATUS_VARIANTS[status]}
                            >
                                {counts[status]} {ENTRY_STATUS_LABELS[status]}
                            </Badge>
                        ),
                    )}
                    <span className="text-muted-foreground text-xs">
                        Validated {formatDateTime(current.validatedAt)} · Synced{' '}
                        {formatDateTime(current.syncedAt)}
                    </span>
                </div>

                <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-2">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-10">#</TableHead>
                                <TableHead>Template</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Branches</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Why</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {entries.map((entry) => (
                                <EntryRow key={entry.id} entry={entry} />
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}

DocumentTemplateImportShow.layout = {
    breadcrumbs: [{ title: 'Document template imports', href: importsIndex() }],
};
