import { Head } from '@inertiajs/react';
import { DataTable } from '@/components/data-table';
import { DetailField } from '@/components/detail-fields';
import { RecordActions } from '@/components/record-actions';
import { ReferenceDataShow } from '@/components/reference-data-show';
import { usePermissions } from '@/hooks/use-permissions';
import { Badge } from '@/components/ui/badge';
import { formatAmount, formatQuantity } from '@/lib/format';
import {
    index as productsIndex,
    destroy as destroyRoute,
    edit as editRoute,
    restore as restoreRoute,
} from '@/routes/products';
import type { ProductDetail } from '@/types/master-data';

function yesNo(value: boolean | null): string | null {
    if (value === null) {
        return null;
    }

    return value ? 'Yes' : 'No';
}

export default function ProductShow({ product }: { product: ProductDetail }) {
    const { can } = usePermissions();
    const uses = [
        product.purchasable && 'Purchasable',
        product.requestable && 'Requestable',
        product.saleable && 'Saleable',
    ].filter((use): use is string => typeof use === 'string');

    return (
        <>
            <Head title={product.name || 'Product'} />

            <ReferenceDataShow
                title={product.name || 'Product'}
                back={{ href: productsIndex(), label: 'Products' }}
                record={product}
                actions={
                    <RecordActions
                        kind="product"
                        name={product.name || 'Product'}
                        active={product.active}
                        canManage={can('esb.invoke') && can('products.manage')}
                        editUrl={editRoute(product.id).url}
                        urls={{
                            delete: destroyRoute(product.id).url,
                            restore: restoreRoute(product.id).url,
                        }}
                    />
                }
                extra={
                    <section className="grid gap-3">
                        <h2 className="text-base font-medium">Units</h2>
                        <DataTable
                            caption="Product units"
                            columns={[
                                {
                                    key: 'uom',
                                    header: 'Unit',
                                    cell: (unit) => (
                                        <span className="flex items-center gap-2">
                                            {unit.uomName ?? '—'}
                                            {unit.isBase && (
                                                <Badge variant="secondary">
                                                    Base
                                                </Badge>
                                            )}
                                        </span>
                                    ),
                                },
                                {
                                    key: 'qty',
                                    header: 'Base units',
                                    align: 'right',
                                    cell: (unit) => formatQuantity(unit.qty),
                                },
                                {
                                    key: 'sku',
                                    header: 'SKU',
                                    className: 'font-mono text-xs',
                                    cell: (unit) => unit.sku ?? '—',
                                },
                                {
                                    key: 'price',
                                    header: 'Base price',
                                    align: 'right',
                                    cell: (unit) =>
                                        formatAmount(unit.basePrice),
                                },
                                {
                                    key: 'use',
                                    header: 'Used for',
                                    cell: (unit) => (
                                        <span className="flex flex-wrap gap-1">
                                            {unit.purchasable && (
                                                <Badge variant="outline">
                                                    Purchase
                                                </Badge>
                                            )}
                                            {unit.saleable && (
                                                <Badge variant="outline">
                                                    Sales
                                                </Badge>
                                            )}
                                            {unit.stocked && (
                                                <Badge variant="outline">
                                                    Stock
                                                </Badge>
                                            )}
                                            {unit.transferable && (
                                                <Badge variant="outline">
                                                    Transfer
                                                </Badge>
                                            )}
                                        </span>
                                    ),
                                },
                            ]}
                            rows={product.units}
                            rowKey={(unit) => String(unit.productDetailId)}
                            empty="This product has no units in the ERP."
                        />
                    </section>
                }
            >
                <DetailField label="Code" value={product.code} />
                <DetailField label="Category" value={product.categoryName} />
                <DetailField
                    label="Sub-category"
                    value={product.subCategoryName}
                />
                <DetailField label="Bill of material" value={product.bomName} />
                <DetailField
                    label="Used for"
                    value={uses.length === 0 ? null : uses.join(', ')}
                />
                <DetailField label="VAT" value={yesNo(product.vat)} />
                <DetailField
                    label="Luxury item"
                    value={yesNo(product.luxury)}
                />
                <DetailField
                    label="Receipt tolerance"
                    value={
                        product.receiptTolerance === null
                            ? null
                            : formatQuantity(product.receiptTolerance)
                    }
                />
                <DetailField label="Coretax code" value={product.coretaxCode} />
                <DetailField label="Notes" value={product.notes} />
            </ReferenceDataShow>
        </>
    );
}
