import { Head } from '@inertiajs/react';
import { DetailField } from '@/components/detail-fields';
import { ReferenceDataShow } from '@/components/reference-data-show';
import { index as unitsIndex } from '@/routes/units';
import type { UnitOfMeasure } from '@/types/master-data';

export default function UnitShow({ unit }: { unit: UnitOfMeasure }) {
    return (
        <>
            <Head title={unit.name || 'Unit'} />

            <ReferenceDataShow
                title={unit.name || 'Unit'}
                back={{ href: unitsIndex(), label: 'Units' }}
                record={unit}
            >
                <DetailField label="Metric" value={unit.metricName} />
                <DetailField label="Notes" value={unit.notes} />
                <DetailField
                    label="Tax reference id"
                    value={unit.taxId === null ? null : String(unit.taxId)}
                />
            </ReferenceDataShow>
        </>
    );
}

UnitShow.layout = {
    breadcrumbs: [{ title: 'Units', href: unitsIndex() }],
};
