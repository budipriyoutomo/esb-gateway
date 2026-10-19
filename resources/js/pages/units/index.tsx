import { Head, Link } from '@inertiajs/react';
import { ReferenceDataIndex } from '@/components/reference-data-index';
import type { ReferenceDataFilters } from '@/components/reference-data-index';
import { index as unitsIndex, show as unitsShow } from '@/routes/units';
import type { UnitOfMeasureSummary } from '@/types/master-data';
import type { Pagination } from '@/types/pagination';

type Props = {
    units: UnitOfMeasureSummary[];
    pagination: Pagination | null;
    filters: ReferenceDataFilters;
};

export default function UnitsIndex({ units, pagination, filters }: Props) {
    return (
        <>
            <Head title="Units" />

            <ReferenceDataIndex
                id="units"
                title="Units"
                description="Units of measure, read live from the ERP."
                url={unitsIndex().url}
                filters={filters}
                pagination={pagination}
                rows={units}
                columns={[
                    {
                        key: 'name',
                        header: 'Unit',
                        className: 'font-medium',
                        cell: (unit) =>
                            unit.id === null ? (
                                unit.name || '—'
                            ) : (
                                <Link
                                    href={unitsShow(unit.id)}
                                    className="underline-offset-4 hover:underline"
                                >
                                    {unit.name || '—'}
                                </Link>
                            ),
                    },
                    {
                        key: 'metric',
                        header: 'Metric',
                        cell: (unit) => unit.metricName ?? '—',
                    },
                    {
                        key: 'tax',
                        header: 'Tax reference',
                        className: 'text-muted-foreground',
                        cell: (unit) =>
                            [unit.taxCode, unit.taxName]
                                .filter((part) => part !== null)
                                .join(' — ') || '—',
                    },
                    {
                        key: 'notes',
                        header: 'Notes',
                        className: 'text-muted-foreground',
                        cell: (unit) => unit.notes ?? '—',
                    },
                ]}
                detailUrl={(id) => unitsShow(id).url}
                noun="units"
                searchPlaceholder="Unit or metric name"
            />
        </>
    );
}

UnitsIndex.layout = {
    breadcrumbs: [{ title: 'Units', href: unitsIndex() }],
};
