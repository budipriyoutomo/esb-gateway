import { Head, useForm, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import InputError from '@/components/input-error';
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
import { index as purchaseRequestsIndex } from '@/routes/purchase-requests';
import { store as storeImport } from '@/routes/purchase-requests/import';

type Props = {
    maxKilobytes: number;
};

/** What the ERP said about one row of a refused sheet. */
type ImportError = {
    row: number | null;
    field: string | null;
    message: string;
};

export default function PurchaseRequestImport({ maxKilobytes }: Props) {
    const importErrors =
        ((usePage().flash as Record<string, unknown>).importErrors as
            | ImportError[]
            | undefined) ?? [];

    const form = useForm<{ file: File | null }>({ file: null });

    function submit(event: React.FormEvent) {
        event.preventDefault();

        // Importing the same sheet twice may create every request twice, so
        // the button stays disabled until the ERP has answered.
        form.post(storeImport().url, { forceFormData: true });
    }

    return (
        <>
            <Head title="Import purchase requests" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    back={{
                        href: purchaseRequestsIndex(),
                        label: 'Purchase requests',
                    }}
                    title="Import purchase requests"
                    description="Upload an Excel sheet in the ERP's purchase request import layout. The ERP creates the requests; the gateway only passes the file on."
                />

                <Alert>
                    <AlertTitle>Use the ERP's own template</AlertTitle>
                    <AlertDescription>
                        The sheet layout is defined by the ERP and is not
                        documented to the gateway, so the file is not checked
                        here beyond being an .xlsx or .xls file of at most{' '}
                        {Math.round(maxKilobytes / 1024)} MB. Download the
                        import template from the ERP if you do not have it.
                    </AlertDescription>
                </Alert>

                <form
                    onSubmit={submit}
                    className="flex flex-wrap items-end gap-3"
                >
                    <div className="grid w-full gap-1.5 sm:w-96">
                        <Label htmlFor="pr-import-file">Excel file</Label>
                        <Input
                            id="pr-import-file"
                            type="file"
                            accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
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
                        <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border p-2">
                            <Table>
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
