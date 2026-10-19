import { Head } from '@inertiajs/react';
import { DataTable } from '@/components/data-table';
import { DetailField } from '@/components/detail-fields';
import { ReferenceDataShow } from '@/components/reference-data-show';
import { Badge } from '@/components/ui/badge';
import { index as suppliersIndex } from '@/routes/suppliers';
import type { Supplier } from '@/types/master-data';

function yesNo(value: boolean | null): string | null {
    if (value === null) {
        return null;
    }

    return value ? 'Yes' : 'No';
}

export default function SupplierShow({ supplier }: { supplier: Supplier }) {
    const location = [
        supplier.city,
        supplier.state,
        supplier.zipCode,
        supplier.country,
    ]
        .filter((part) => part !== null)
        .join(', ');

    return (
        <>
            <Head title={supplier.name || 'Supplier'} />

            <ReferenceDataShow
                title={supplier.name || 'Supplier'}
                back={{ href: suppliersIndex(), label: 'Suppliers' }}
                record={supplier}
                extra={
                    <>
                        <section className="grid gap-3">
                            <h2 className="text-base font-medium">Contacts</h2>
                            <DataTable
                                caption="Supplier contacts"
                                columns={[
                                    {
                                        key: 'name',
                                        header: 'Name',
                                        cell: (contact) => (
                                            <span className="flex items-center gap-2">
                                                {contact.name || '—'}
                                                {contact.isDefault && (
                                                    <Badge variant="secondary">
                                                        Default
                                                    </Badge>
                                                )}
                                            </span>
                                        ),
                                    },
                                    {
                                        key: 'phone',
                                        header: 'Phone',
                                        cell: (contact) =>
                                            contact.cellPhone ?? '—',
                                    },
                                    {
                                        key: 'email',
                                        header: 'Email',
                                        cell: (contact) => contact.email ?? '—',
                                    },
                                    {
                                        key: 'poEmail',
                                        header: 'Receives PO email',
                                        cell: (contact) =>
                                            yesNo(
                                                contact.sendsPurchaseOrderEmail,
                                            ) ?? '—',
                                    },
                                ]}
                                rows={supplier.contacts}
                                rowKey={(contact, index) =>
                                    String(contact.id ?? `contact-${index}`)
                                }
                                empty="No contacts recorded in the ERP."
                            />
                        </section>

                        <section className="grid gap-3">
                            <h2 className="text-base font-medium">
                                Bank accounts
                            </h2>
                            <DataTable
                                caption="Supplier bank accounts"
                                columns={[
                                    {
                                        key: 'bank',
                                        header: 'Bank',
                                        cell: (account) =>
                                            account.bankName ?? '—',
                                    },
                                    {
                                        key: 'holder',
                                        header: 'Account holder',
                                        cell: (account) =>
                                            account.accountName ?? '—',
                                    },
                                    {
                                        key: 'number',
                                        header: 'Account number',
                                        className: 'font-mono text-xs',
                                        cell: (account) =>
                                            account.maskedAccountNumber ?? '—',
                                    },
                                ]}
                                rows={supplier.bankAccounts}
                                rowKey={(account, index) =>
                                    `${account.bankId ?? 'x'}-${index}`
                                }
                                empty="No bank accounts recorded in the ERP."
                            />
                            <p className="text-muted-foreground text-xs">
                                Only the last four digits are shown here; the
                                full number is in the ERP.
                            </p>
                        </section>

                        <section className="grid gap-3">
                            <h2 className="text-base font-medium">
                                Supplier product codes
                            </h2>
                            <DataTable
                                caption="Supplier product codes"
                                columns={[
                                    {
                                        key: 'code',
                                        header: 'Supplier code',
                                        className: 'font-mono text-xs',
                                        cell: (product) =>
                                            product.supplierProductCode ?? '—',
                                    },
                                    {
                                        key: 'product',
                                        header: 'Product',
                                        cell: (product) =>
                                            product.productName ?? '—',
                                    },
                                    {
                                        key: 'uom',
                                        header: 'Unit',
                                        cell: (product) =>
                                            product.uomName ?? '—',
                                    },
                                ]}
                                rows={supplier.products}
                                rowKey={(product, index) =>
                                    `${product.productDetailId ?? 'x'}-${index}`
                                }
                                empty="No supplier product codes recorded."
                            />
                        </section>
                    </>
                }
            >
                <DetailField label="Code" value={supplier.code} />
                <DetailField label="Category" value={supplier.categoryName} />
                <DetailField label="Address" value={supplier.address} />
                <DetailField
                    label="Location"
                    value={location === '' ? null : location}
                />
                <DetailField label="Phone" value={supplier.phone1} />
                <DetailField label="Phone 2" value={supplier.phone2} />
                <DetailField label="Fax" value={supplier.fax} />
                <DetailField label="NPWP" value={supplier.npwp} />
                <DetailField
                    label="Credit terms"
                    value={
                        supplier.creditDays === null
                            ? null
                            : supplier.creditDays === 0
                              ? 'Cash'
                              : `${supplier.creditDays} days`
                    }
                />
                <DetailField
                    label="Payable account"
                    value={supplier.payableCoaNo}
                />
                <DetailField
                    label="Advance purchase account"
                    value={supplier.advancePurchaseCoaNo}
                />
                <DetailField
                    label="VAT subject"
                    value={yesNo(supplier.vatSubject)}
                />
                <DetailField
                    label="VAT locked"
                    value={yesNo(supplier.lockVat)}
                />
                <DetailField
                    label="Invoice on receipt"
                    value={yesNo(supplier.invoiceOnReceipt)}
                />
                <DetailField
                    label="Used in transactions"
                    value={yesNo(supplier.usedInTransactions)}
                />
                <DetailField label="Notes" value={supplier.notes} />
            </ReferenceDataShow>
        </>
    );
}

SupplierShow.layout = {
    breadcrumbs: [{ title: 'Suppliers', href: suppliersIndex() }],
};
