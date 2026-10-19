import { Head } from '@inertiajs/react';
import { ReferenceDataShow } from '@/components/reference-data-show';
import { index as categoriesIndex } from '@/routes/supplier-categories';
import type { SupplierCategory } from '@/types/master-data';

export default function SupplierCategoryShow({
    category,
}: {
    category: SupplierCategory;
}) {
    return (
        <>
            <Head title={category.name || 'Supplier category'} />

            <ReferenceDataShow
                title={category.name || 'Supplier category'}
                back={{ href: categoriesIndex(), label: 'Supplier categories' }}
                record={category}
            >
                {null}
            </ReferenceDataShow>
        </>
    );
}

SupplierCategoryShow.layout = {
    breadcrumbs: [{ title: 'Supplier categories', href: categoriesIndex() }],
};
