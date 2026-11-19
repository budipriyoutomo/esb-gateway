import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ClosingMappingNav } from '@/components/closing-mapping-nav';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import InputError from '@/components/input-error';
import { LookupCombobox } from '@/components/lookup-combobox';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { destroy, update } from '@/routes/closing-mappings/payments';
import { index as closingRecapsIndex } from '@/routes/closing-recaps';
import type { ClosingPaymentMapping } from '@/types/closing';

type PaymentRow = {
    paymentMethod: string;
    recaps: number;
    mapping: ClosingPaymentMapping | null;
};

type Props = {
    canManage: boolean;
    payments: PaymentRow[];
};

const accountLabel = (mapping: ClosingPaymentMapping) =>
    mapping.coaName ? `${mapping.coaNo} · ${mapping.coaName}` : mapping.coaNo;

/**
 * The ERP account and merchant discount rate of each POS payment method
 * (ADR-032, P30). sync-api sends only the method's name; Push Sales needs
 * the account.
 */
export default function ClosingPaymentMappings({ canManage, payments }: Props) {
    const [editing, setEditing] = useState<PaymentRow | null>(null);

    const columns: DataTableColumn<PaymentRow>[] = [
        {
            key: 'method',
            header: 'Payment method',
            className: 'font-medium',
            cell: (row) => row.paymentMethod,
        },
        {
            key: 'recaps',
            header: 'Recaps',
            align: 'right',
            cell: (row) => row.recaps,
        },
        {
            key: 'account',
            header: 'ERP account',
            cell: (row) =>
                row.mapping ? (
                    accountLabel(row.mapping)
                ) : (
                    <Badge variant="destructive">Not mapped</Badge>
                ),
        },
        {
            key: 'rate',
            header: 'Merchant discount rate',
            align: 'right',
            cell: (row) =>
                row.mapping ? `${row.mapping.merchantDiscountRate}%` : '—',
        },
    ];

    if (canManage) {
        columns.push({
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            label: 'Actions',
            align: 'right',
            cell: (row) => (
                <div className="flex justify-end gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        aria-label={`${row.mapping ? 'Edit' : 'Map'} ${row.paymentMethod}`}
                        onClick={() => {
                            setEditing(row);
                        }}
                    >
                        {row.mapping ? 'Edit' : 'Map'}
                    </Button>
                    {row.mapping && (
                        <Button
                            size="sm"
                            variant="ghost"
                            aria-label={`Remove the mapping of ${row.paymentMethod}`}
                            onClick={() => {
                                if (
                                    window.confirm(
                                        `Remove the mapping of ${row.paymentMethod}? Its sales cannot be prepared until it is mapped again.`,
                                    )
                                ) {
                                    router.delete(
                                        destroy(row.paymentMethod).url,
                                        { preserveScroll: true },
                                    );
                                }
                            }}
                        >
                            Remove
                        </Button>
                    )}
                </div>
            ),
        });
    }

    return (
        <>
            <Head title="Closing mappings · Payment methods" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Closing mappings"
                    description="The ERP account and merchant discount rate for each payment method the POS names. Push Sales records every payment with them."
                    back={{
                        href: closingRecapsIndex(),
                        label: 'Closing recaps',
                    }}
                />

                <ClosingMappingNav current="payments" />

                <DataTable
                    caption="Payment mappings"
                    columns={columns}
                    rows={payments}
                    rowKey={(row) => row.paymentMethod}
                    empty="No payment methods yet. They appear once sync-api sends payments."
                />
            </div>

            {editing && (
                <PaymentDialog
                    row={editing}
                    onClose={() => {
                        setEditing(null);
                    }}
                />
            )}
        </>
    );
}

function PaymentDialog({
    row,
    onClose,
}: {
    row: PaymentRow;
    onClose: () => void;
}) {
    const form = useForm({
        coaNo: row.mapping?.coaNo ?? '',
        coaName: row.mapping?.coaName ?? '',
        merchantDiscountRate: row.mapping?.merchantDiscountRate ?? '0',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.put(update(row.paymentMethod).url, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    }

    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open) onClose();
            }}
        >
            <DialogContent>
                <form onSubmit={submit} noValidate>
                    <DialogHeader>
                        <DialogTitle>
                            Map payment method {row.paymentMethod}
                        </DialogTitle>
                        <DialogDescription>
                            The account each payment of this method is booked to
                            in Push Sales.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <LookupCombobox
                                id="payment-account"
                                label="ERP account"
                                kind="coa-accounts"
                                value={
                                    form.data.coaNo === ''
                                        ? null
                                        : form.data.coaNo
                                }
                                selectedLabel={
                                    form.data.coaNo === ''
                                        ? null
                                        : form.data.coaName
                                          ? `${form.data.coaNo} · ${form.data.coaName}`
                                          : form.data.coaNo
                                }
                                onChange={(option) => {
                                    form.setData((data) => ({
                                        ...data,
                                        coaNo: option?.value ?? '',
                                        coaName: option?.label ?? '',
                                    }));
                                }}
                                placeholder="Search accounts"
                            />
                            <InputError
                                message={
                                    form.errors.coaNo ?? form.errors.coaName
                                }
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="payment-rate">
                                Merchant discount rate (%)
                            </Label>
                            <Input
                                id="payment-rate"
                                inputMode="decimal"
                                value={form.data.merchantDiscountRate}
                                onChange={(event) => {
                                    form.setData(
                                        'merchantDiscountRate',
                                        event.target.value,
                                    );
                                }}
                            />
                            <InputError
                                message={form.errors.merchantDiscountRate}
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Save
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
