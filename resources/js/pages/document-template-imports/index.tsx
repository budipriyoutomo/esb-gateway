import { Head, Link, useForm } from '@inertiajs/react';
import { Download, Upload } from 'lucide-react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    show as importsShow,
    store as importsStore,
    template as importsTemplate,
} from '@/routes/document-template-imports';
import { index as documentTemplatesIndex } from '@/routes/document-templates';
import type {
    ImportEntryStatus,
    ImportSummary,
} from '@/types/document-template-import';
import {
    ENTRY_STATUS_LABELS,
    ENTRY_STATUS_VARIANTS,
} from '@/types/document-template-import';
import { formatDateTime } from '@/lib/format';

const SHOWN: ImportEntryStatus[] = [
    'synced',
    'ready',
    'pending',
    'blocked',
    'invalid',
    'failed',
    'unconfirmed',
];

const columns: DataTableColumn<ImportSummary>[] = [
    {
        key: 'file',
        header: 'File',
        className: 'font-medium',
        cell: (item) => (
            <span>
                <Link
                    href={importsShow(item.id)}
                    className="underline-offset-4 hover:underline"
                >
                    {item.filename}
                </Link>
                <span className="text-muted-foreground block text-xs">
                    #{item.id}
                </span>
            </span>
        ),
    },
    {
        key: 'uploaded',
        header: 'Uploaded',
        cell: (item) => (
            <span>
                {formatDateTime(item.createdAt)}
                <span className="text-muted-foreground block text-xs">
                    {item.uploadedBy ?? '—'}
                </span>
            </span>
        ),
    },
    {
        key: 'templates',
        header: 'Templates',
        cell: (item) =>
            item.templates === 0 ? (
                '—'
            ) : (
                <span className="flex flex-wrap gap-1">
                    {SHOWN.filter(
                        (status) => (item.counts[status] ?? 0) > 0,
                    ).map((status) => (
                        <Badge
                            key={status}
                            variant={ENTRY_STATUS_VARIANTS[status]}
                        >
                            {item.counts[status]} {ENTRY_STATUS_LABELS[status]}
                        </Badge>
                    ))}
                </span>
            ),
    },
    {
        key: 'status',
        header: 'Status',
        cell: (item) => (item.status === 'idle' ? '—' : `${item.status}…`),
    },
];

export default function DocumentTemplateImportsIndex({
    imports,
}: {
    imports: ImportSummary[];
}) {
    const form = useForm<{ file: File | null }>({ file: null });

    function submit(event: React.FormEvent) {
        event.preventDefault();
        form.post(importsStore().url, { forceFormData: true });
    }

    return (
        <>
            <Head title="Import document templates" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Import document templates"
                    description="Upload a spreadsheet. It is stored here first; validate it against the ERP, then sync the templates that are ready."
                    back={{
                        href: documentTemplatesIndex(),
                        label: 'Document templates',
                    }}
                />

                <form
                    onSubmit={submit}
                    className="border-sidebar-border/70 dark:border-sidebar-border flex flex-col gap-2 rounded-xl border p-4"
                >
                    <div className="flex flex-wrap items-start justify-between gap-2">
                        <p className="text-muted-foreground max-w-2xl text-sm">
                            Columns: Document Template Name, Document Template
                            Type, Branch Aktif, Kategori, Product Name, Product
                            Code, Unit. One row per product; rows with the same
                            name form one template. Kategori becomes the line
                            name. The template includes instructions and the
                            ERP&apos;s current branch codes.
                        </p>
                        {/* A file download, not an Inertia visit. */}
                        <Button variant="outline" size="sm" asChild>
                            <a href={importsTemplate().url}>
                                <Download className="size-4" />
                                Download template
                            </a>
                        </Button>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Input
                            type="file"
                            accept=".xlsx"
                            aria-label="Spreadsheet"
                            className="w-80"
                            onChange={(event) => {
                                form.setData(
                                    'file',
                                    event.target.files?.[0] ?? null,
                                );
                            }}
                        />
                        <Button
                            type="submit"
                            disabled={
                                form.processing || form.data.file === null
                            }
                        >
                            <Upload className="size-4" />
                            {form.processing ? 'Reading…' : 'Upload'}
                        </Button>
                    </div>
                    <InputError message={form.errors.file} />
                </form>

                <DataTable
                    caption="Uploaded spreadsheets"
                    columns={columns}
                    rows={imports}
                    rowKey={(item) => String(item.id)}
                    rowHref={(item) => importsShow(item.id).url}
                    empty="Nothing imported yet."
                />
            </div>
        </>
    );
}
