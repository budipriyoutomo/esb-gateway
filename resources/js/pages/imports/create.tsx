import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { store as storeImport } from '@/routes/imports';

type ImportKind = {
    kind: string;
    label: string;
    extensions: string[];
    /** The ERP only queues the file and imports it later. */
    queued: boolean;
    maxKilobytes: number;
    listUrl: string;
};

/** What the ERP said about one row of a refused sheet. */
type ImportError = {
    row: number | null;
    field: string | null;
    message: string;
};

/**
 * Hand the ERP a master data spreadsheet ([ESB-032], S4). The gateway does
 * not read the sheet; the layout is the ERP's own template.
 */
export default function ImportCreate({ import: kind }: { import: ImportKind }) {
    const importErrors =
        ((usePage().flash as Record<string, unknown> | undefined)
            ?.importErrors as ImportError[] | undefined) ?? [];
    const form = useForm<{ file: File | null }>({ file: null });
    const title = `Import ${kind.label}`;
    const accept = kind.extensions
        .map((extension) => `.${extension}`)
        .join(',');

    function submit(event: FormEvent) {
        event.preventDefault();

        // Importing the same sheet twice may create every row twice, so the
        // button stays disabled until the ERP has answered.
        form.post(storeImport(kind.kind).url, { forceFormData: true });
    }

    return (
        <>
            <Head title={title} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={title}
                    description="Upload an Excel sheet in the ERP's import layout. The ERP saves the rows; the gateway only passes the file on."
                    back={{ href: kind.listUrl, label: 'Back to the list' }}
                />

                <Alert>
                    <AlertTitle>Use the ERP's own template</AlertTitle>
                    <AlertDescription>
                        <p>
                            The layout is defined by the ERP and is not
                            documented to the gateway, so the file is checked
                            here only for being {accept.replaceAll(',', ' or ')}{' '}
                            of at most {Math.round(kind.maxKilobytes / 1024)}{' '}
                            MB. Download the template from the ERP if you do not
                            have it.
                        </p>
                        {kind.queued && (
                            <p>
                                The ERP imports it in the background: a sheet it
                                accepts here may still have rows it rejects
                                later, and those are not reported back to the
                                gateway.
                            </p>
                        )}
                    </AlertDescription>
                </Alert>

                <form
                    onSubmit={submit}
                    noValidate
                    className="flex flex-wrap items-end gap-3"
                >
                    <div className="grid w-full gap-1.5 sm:w-96">
                        <Label htmlFor="import-file">Excel file</Label>
                        <Input
                            id="import-file"
                            type="file"
                            accept={accept}
                            onChange={(event) => {
                                form.setData(
                                    'file',
                                    event.target.files?.[0] ?? null,
                                );
                            }}
                            required
                        />
                        <InputError message={form.errors.file} />
                    </div>
                    <Button
                        type="submit"
                        disabled={form.processing || form.data.file === null}
                    >
                        {form.processing ? 'Importing…' : 'Import'}
                    </Button>
                </form>

                {importErrors.length > 0 && (
                    <div className="grid gap-2">
                        <h2 className="text-sm font-medium">
                            The ERP refused the sheet. Fix these rows and import
                            it again:
                        </h2>
                        <div className="overflow-x-auto rounded-xl border p-2">
                            <Table aria-label="Refused rows">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-20">
                                            Row
                                        </TableHead>
                                        <TableHead className="w-48">
                                            Column
                                        </TableHead>
                                        <TableHead>Problem</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {importErrors.map((error, index) => (
                                        <TableRow key={index}>
                                            <TableCell className="tabular-nums">
                                                {error.row ?? '—'}
                                            </TableCell>
                                            <TableCell className="font-mono text-xs">
                                                {error.field ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                {error.message}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}
