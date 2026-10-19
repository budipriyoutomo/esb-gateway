import { Head } from '@inertiajs/react';
import { DetailField, DetailFields } from '@/components/detail-fields';
import { ReferenceDataShow } from '@/components/reference-data-show';
import { formatQuantity } from '@/lib/format';
import { index as categoriesIndex } from '@/routes/product-categories';
import type { ProductCategory } from '@/types/master-data';

function months(value: number | null): string | null {
    return value === null ? null : `${formatQuantity(value)} months`;
}

function flag(value: boolean | null): string | null {
    if (value === null) {
        return null;
    }

    return value ? 'Yes' : 'No';
}

export default function ProductCategoryShow({
    category,
}: {
    category: ProductCategory;
}) {
    // Depreciation applies to asset categories only.
    const depreciated =
        category.depreciationCoaNo !== null ||
        category.depreciationMonths !== null ||
        category.fiscalDepreciationMonths !== null;

    return (
        <>
            <Head title={category.name || 'Product category'} />

            <ReferenceDataShow
                title={category.name || 'Product category'}
                back={{ href: categoriesIndex(), label: 'Product categories' }}
                record={category}
                extra={
                    <>
                        <section className="grid gap-3">
                            <h2 className="text-base font-medium">Accounts</h2>
                            <DetailFields>
                                <DetailField
                                    label="Inventory"
                                    value={category.coaNo}
                                />
                                <DetailField
                                    label="COGS"
                                    value={category.cogsCoaNo}
                                />
                                <DetailField
                                    label="Stock opname"
                                    value={category.opnameCoaNo}
                                />
                                <DetailField
                                    label="Product sales"
                                    value={category.productSalesCoaNo}
                                />
                                <DetailField
                                    label="Accrual basis"
                                    value={category.accrualBasisCoaNo}
                                />
                            </DetailFields>
                        </section>

                        {depreciated && (
                            <section className="grid gap-3">
                                <h2 className="text-base font-medium">
                                    Depreciation
                                </h2>
                                <DetailFields>
                                    <DetailField
                                        label="Accumulated depreciation"
                                        value={category.depreciationCoaNo}
                                    />
                                    <DetailField
                                        label="Depreciation expense"
                                        value={
                                            category.depreciationExpenseCoaNo
                                        }
                                    />
                                    <DetailField
                                        label="Length"
                                        value={months(
                                            category.depreciationMonths,
                                        )}
                                    />
                                    <DetailField
                                        label="Fiscal length"
                                        value={months(
                                            category.fiscalDepreciationMonths,
                                        )}
                                    />
                                    <DetailField
                                        label="Method id"
                                        value={
                                            category.depreciationTypeId === null
                                                ? null
                                                : String(
                                                      category.depreciationTypeId,
                                                  )
                                        }
                                    />
                                    <DetailField
                                        label="Fiscal method id"
                                        value={
                                            category.fiscalDepreciationTypeId ===
                                            null
                                                ? null
                                                : String(
                                                      category.fiscalDepreciationTypeId,
                                                  )
                                        }
                                    />
                                    <DetailField
                                        label="Asset tax"
                                        value={flag(category.assetTax)}
                                    />
                                </DetailFields>
                            </section>
                        )}
                    </>
                }
            >
                <DetailField label="Code" value={category.code} />
                <DetailField
                    label="Type"
                    value={
                        category.typeName ??
                        (category.typeId === null
                            ? null
                            : `Type ${category.typeId}`)
                    }
                />
                <DetailField label="Notes" value={category.notes} />
            </ReferenceDataShow>
        </>
    );
}

ProductCategoryShow.layout = {
    breadcrumbs: [{ title: 'Product categories', href: categoriesIndex() }],
};
