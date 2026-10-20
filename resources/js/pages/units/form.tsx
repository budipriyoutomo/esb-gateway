import { Head, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { LookupCombobox } from '@/components/lookup-combobox';
import { RecordForm, RecordFormField } from '@/components/record-form';
import { Input } from '@/components/ui/input';
import {
    index as unitsIndex,
    show as unitsShow,
    store as storeRoute,
    update as updateRoute,
} from '@/routes/units';
import type { UnitOfMeasure } from '@/types/master-data';

type FormData = {
    name: string;
    metricId: number | null;
    taxId: number | null;
    notes: string;
};

/** Create or edit a unit of measure ([ESB-030], S3). */
export default function UnitForm({ unit }: { unit: UnitOfMeasure | null }) {
    const form = useForm<FormData>({
        name: unit?.name ?? '',
        metricId: unit?.metricId ?? null,
        taxId: unit?.taxId ?? null,
        notes: unit?.notes ?? '',
    });
    const errors = form.errors as Partial<
        Record<keyof FormData | 'form', string>
    >;
    const title = unit ? `Edit ${unit.name || 'unit'}` : 'New unit';

    function submit() {
        if (unit) {
            form.put(updateRoute(unit.id).url);
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
                    unit
                        ? {
                              href: unitsShow(unit.id),
                              label: unit.name || 'Unit',
                          }
                        : { href: unitsIndex(), label: 'Units' }
                }
                cancelHref={unit ? unitsShow(unit.id) : unitsIndex()}
                submitLabel={unit ? 'Save changes' : 'Create unit'}
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

                <div className="grid gap-2">
                    <LookupCombobox
                        id="metricId"
                        label="Metric"
                        kind="metrics"
                        value={
                            form.data.metricId === null
                                ? null
                                : String(form.data.metricId)
                        }
                        selectedLabel={unit?.metricName}
                        onChange={(option) =>
                            form.setData(
                                'metricId',
                                option ? Number(option.value) : null,
                            )
                        }
                        placeholder="Choose a metric"
                    />
                    <InputError message={errors.metricId} />
                </div>

                <div className="grid gap-2">
                    <LookupCombobox
                        id="taxId"
                        label="Tax reference (optional)"
                        kind="taxes"
                        value={
                            form.data.taxId === null
                                ? null
                                : String(form.data.taxId)
                        }
                        selectedLabel={
                            unit?.taxId ? `Tax #${unit.taxId}` : null
                        }
                        onChange={(option) =>
                            form.setData(
                                'taxId',
                                option ? Number(option.value) : null,
                            )
                        }
                        placeholder="No tax reference"
                    />
                    <InputError message={errors.taxId} />
                </div>

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

UnitForm.layout = {
    breadcrumbs: [{ title: 'Units', href: unitsIndex() }],
};
