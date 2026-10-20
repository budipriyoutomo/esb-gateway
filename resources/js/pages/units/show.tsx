import { Head } from '@inertiajs/react';
import { DetailField } from '@/components/detail-fields';
import { RecordActions } from '@/components/record-actions';
import { ReferenceDataShow } from '@/components/reference-data-show';
import { usePermissions } from '@/hooks/use-permissions';
import {
    index as unitsIndex,
    destroy as destroyRoute,
    restore as restoreRoute,
    edit as editRoute,
} from '@/routes/units';
import type { UnitOfMeasure } from '@/types/master-data';

export default function UnitShow({ unit }: { unit: UnitOfMeasure }) {
    const { can } = usePermissions();
    return (
        <>
            <Head title={unit.name || 'Unit'} />

            <ReferenceDataShow
                title={unit.name || 'Unit'}
                back={{ href: unitsIndex(), label: 'Units' }}
                record={unit}
                actions={
                    <RecordActions
                        kind="unit"
                        name={unit.name || 'Unit'}
                        active={unit.active}
                        canManage={can('esb.invoke') && can('units.manage')}
                        editUrl={editRoute(unit.id).url}
                        urls={{
                            delete: destroyRoute(unit.id).url,
                            restore: restoreRoute(unit.id).url,
                        }}
                    />
                }
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
