import { Head } from '@inertiajs/react';
import { DetailField } from '@/components/detail-fields';
import { ReferenceDataShow } from '@/components/reference-data-show';
import { formatQuantity } from '@/lib/format';
import { index as subCategoriesIndex } from '@/routes/product-sub-categories';
import type { ProductSubCategory } from '@/types/master-data';

export default function ProductSubCategoryShow({
    subCategory,
}: {
    subCategory: ProductSubCategory;
}) {
    return (
        <>
            <Head title={subCategory.name || 'Product sub-category'} />

            <ReferenceDataShow
                title={subCategory.name || 'Product sub-category'}
                back={{
                    href: subCategoriesIndex(),
                    label: 'Product sub-categories',
                }}
                record={subCategory}
            >
                <DetailField
                    label="Dead stock after"
                    value={
                        subCategory.deadStockDays === null
                            ? null
                            : `${formatQuantity(subCategory.deadStockDays)} days without movement`
                    }
                />
                <DetailField label="Notes" value={subCategory.notes} />
            </ReferenceDataShow>
        </>
    );
}

ProductSubCategoryShow.layout = {
    breadcrumbs: [
        { title: 'Product sub-categories', href: subCategoriesIndex() },
    ],
};
