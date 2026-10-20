import { Head, useForm } from '@inertiajs/react';
import { RecordForm, RecordFormField } from '@/components/record-form';
import { Input } from '@/components/ui/input';
import {
    index as subCategoriesIndex,
    show as subCategoriesShow,
    store as storeRoute,
    update as updateRoute,
} from '@/routes/product-sub-categories';
import type { ProductSubCategory } from '@/types/master-data';

type FormData = {
    name: string;
    deadStockDays: number | null;
    notes: string;
};

/**
 * Create or edit a product sub-category ([ESB-030], S3). Active / inactive
 * is changed from the detail page, not here.
 */
export default function SubCategoryForm({
    subCategory,
}: {
    subCategory: ProductSubCategory | null;
}) {
    const form = useForm<FormData>({
        name: subCategory?.name ?? '',
        deadStockDays: subCategory?.deadStockDays ?? null,
        notes: subCategory?.notes ?? '',
    });
    const errors = form.errors as Partial<
        Record<keyof FormData | 'form', string>
    >;
    const title = subCategory
        ? `Edit ${subCategory.name || 'sub-category'}`
        : 'New sub-category';

    function submit() {
        if (subCategory) {
            form.put(updateRoute(subCategory.id).url);
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
                    subCategory
                        ? {
                              href: subCategoriesShow(subCategory.id),
                              label: subCategory.name || 'Sub-category',
                          }
                        : {
                              href: subCategoriesIndex(),
                              label: 'Sub-categories',
                          }
                }
                cancelHref={
                    subCategory
                        ? subCategoriesShow(subCategory.id)
                        : subCategoriesIndex()
                }
                submitLabel={
                    subCategory ? 'Save changes' : 'Create sub-category'
                }
                onSubmit={submit}
                processing={form.processing}
                isDirty={form.isDirty}
                formError={errors.form}
            >
                <RecordFormField id="name" label="Name" error={errors.name}>
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

                <RecordFormField
                    id="deadStockDays"
                    label="Dead stock after (days)"
                    hint="A product with no movement for this many days counts as dead stock."
                    error={errors.deadStockDays}
                >
                    <Input
                        id="deadStockDays"
                        type="number"
                        inputMode="numeric"
                        min={1}
                        required
                        className="max-w-40"
                        value={form.data.deadStockDays ?? ''}
                        onChange={(event) =>
                            form.setData(
                                'deadStockDays',
                                event.target.value === ''
                                    ? null
                                    : Number(event.target.value),
                            )
                        }
                    />
                </RecordFormField>

                <RecordFormField id="notes" label="Notes" error={errors.notes}>
                    <Input
                        id="notes"
                        value={form.data.notes}
                        maxLength={100}
                        onChange={(event) =>
                            form.setData('notes', event.target.value)
                        }
                    />
                </RecordFormField>
            </RecordForm>
        </>
    );
}

SubCategoryForm.layout = {
    breadcrumbs: [{ title: 'Sub-categories', href: subCategoriesIndex() }],
};
