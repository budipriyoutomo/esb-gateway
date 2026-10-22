import { Head } from '@inertiajs/react';
import { RecordActions } from '@/components/record-actions';
import { ReferenceDataShow } from '@/components/reference-data-show';
import { usePermissions } from '@/hooks/use-permissions';
import {
    index as categoriesIndex,
    destroy as destroyRoute,
    restore as restoreRoute,
    edit as editRoute,
} from '@/routes/supplier-categories';
import type { SupplierCategory } from '@/types/master-data';

export default function SupplierCategoryShow({
    category,
}: {
    category: SupplierCategory;
}) {
    const { can } = usePermissions();
    return (
        <>
            <Head title={category.name || 'Supplier category'} />

            <ReferenceDataShow
                title={category.name || 'Supplier category'}
                back={{ href: categoriesIndex(), label: 'Supplier categories' }}
                record={category}
                actions={
                    <RecordActions
                        kind="supplier category"
                        name={category.name || 'Supplier category'}
                        active={category.active}
                        canManage={
                            can('esb.invoke') &&
                            can('supplier-categories.manage')
                        }
                        editUrl={editRoute(category.id).url}
                        urls={{
                            delete: destroyRoute(category.id).url,
                            restore: restoreRoute(category.id).url,
                        }}
                    />
                }
            >
                {null}
            </ReferenceDataShow>
        </>
    );
}
