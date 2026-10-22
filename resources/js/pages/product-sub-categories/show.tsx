import { Head } from '@inertiajs/react';
import { DetailField } from '@/components/detail-fields';
import { RecordActions } from '@/components/record-actions';
import { ReferenceDataShow } from '@/components/reference-data-show';
import { usePermissions } from '@/hooks/use-permissions';
import { formatQuantity } from '@/lib/format';
import {
    index as subCategoriesIndex,
    destroy as destroyRoute,
    deactivate as deactivateRoute,
    restore as restoreRoute,
    edit as editRoute,
} from '@/routes/product-sub-categories';
import type { ProductSubCategory } from '@/types/master-data';

export default function ProductSubCategoryShow({
    subCategory,
}: {
    subCategory: ProductSubCategory;
}) {
    const { can } = usePermissions();
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
                actions={
                    <RecordActions
                        kind="product sub-category"
                        name={subCategory.name || 'Product sub-category'}
                        active={subCategory.active}
                        canManage={
                            can('esb.invoke') &&
                            can('product-sub-categories.manage')
                        }
                        editUrl={editRoute(subCategory.id).url}
                        urls={{
                            delete: destroyRoute(subCategory.id).url,
                            deactivate: deactivateRoute(subCategory.id).url,
                            restore: restoreRoute(subCategory.id).url,
                        }}
                    />
                }
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
