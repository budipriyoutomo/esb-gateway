import { Head, useForm } from '@inertiajs/react';
import { RecordForm, RecordFormField } from '@/components/record-form';
import { Input } from '@/components/ui/input';
import {
    index as categoriesIndex,
    show as categoriesShow,
    store as storeRoute,
    update as updateRoute,
} from '@/routes/supplier-categories';
import type { SupplierCategory } from '@/types/master-data';

type FormData = { name: string };

/** Create or rename a supplier category ([ESB-030], S3). */
export default function SupplierCategoryForm({
    category,
}: {
    category: SupplierCategory | null;
}) {
    const form = useForm<FormData>({ name: category?.name ?? '' });
    const errors = form.errors as Partial<
        Record<keyof FormData | 'form', string>
    >;
    const title = category
        ? `Edit ${category.name || 'supplier category'}`
        : 'New supplier category';

    function submit() {
        if (category) {
            form.put(updateRoute(category.id).url);
        } else {
            form.post(storeRoute().url);
        }
    }

    return (
        <>
            <Head title={title} />

            <RecordForm
                title={title}
                back={
                    category
                        ? {
                              href: categoriesShow(category.id),
                              label: category.name || 'Supplier category',
                          }
                        : {
                              href: categoriesIndex(),
                              label: 'Supplier categories',
                          }
                }
                cancelHref={
                    category ? categoriesShow(category.id) : categoriesIndex()
                }
                submitLabel={
                    category ? 'Save changes' : 'Create supplier category'
                }
                onSubmit={submit}
                processing={form.processing}
                isDirty={form.isDirty}
                formError={errors.form}
            >
                <RecordFormField
                    id="name"
                    label="Name"
                    hint="Letters, digits and plain punctuation only; the ERP refuses accented characters."
                    error={errors.name}
                >
                    <Input
                        id="name"
                        value={form.data.name}
                        maxLength={50}
                        required
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                    />
                </RecordFormField>
            </RecordForm>
        </>
    );
}

SupplierCategoryForm.layout = {
    breadcrumbs: [{ title: 'Supplier categories', href: categoriesIndex() }],
};
