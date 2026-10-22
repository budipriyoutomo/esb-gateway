import { Head, Link } from '@inertiajs/react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { DetailField, DetailFields } from '@/components/detail-fields';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import { formatQuantity } from '@/lib/format';
import {
    edit as editRoute,
    index as indexRoute,
} from '@/routes/menu-categories';
import type { MenuCategory, MenuCategoryDetail } from '@/types/pos';

type Props = {
    category: MenuCategory;
    /** The list page the category was found on. */
    page: number;
};

/** A POS button colour; the mapper lets only hex codes through. */
function Swatch({ color }: { color: string | null }) {
    if (color === null) {
        return <span className="text-muted-foreground">Default</span>;
    }

    return (
        <span className="inline-flex items-center gap-2">
            <span
                className="size-4 rounded border"
                style={{ backgroundColor: color }}
                aria-hidden
            />
            <span className="font-mono text-xs">{color}</span>
        </span>
    );
}

const detailColumns: DataTableColumn<MenuCategoryDetail>[] = [
    {
        key: 'name',
        header: 'Name',
        className: 'font-medium',
        cell: (detail) => detail.name,
    },
    {
        key: 'code',
        header: 'Code',
        className: 'font-mono text-xs',
        cell: (detail) => detail.code ?? '—',
    },
    {
        key: 'max',
        header: 'Max order',
        align: 'right',
        cell: (detail) =>
            detail.maxOrderQty === null
                ? '—'
                : formatQuantity(detail.maxOrderQty),
    },
    {
        key: 'color',
        header: 'Button colour',
        cell: (detail) => <Swatch color={detail.buttonColor} />,
    },
    {
        key: 'status',
        header: 'Status',
        cell: (detail) => <StatusBadge status={detail.statusName} />,
    },
];

/**
 * One POS menu category ([ESB-047]) with its category details. The
 * accounts are the ERP's account descriptions.
 */
export default function MenuCategoryShow({ category, page }: Props) {
    const { can } = usePermissions();
    const manage = can('esb.invoke') && can('menu-categories.manage');

    return (
        <>
            <Head title={category.name} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={category.name}
                    description="Menu category, read live from ESB OMS."
                    back={{
                        href: indexRoute({ query: { page } }).url,
                        label: 'Menu categories',
                    }}
                    actions={
                        <>
                            <StatusBadge status={category.statusName} />
                            {manage && (
                                <Button size="sm" variant="outline" asChild>
                                    <Link
                                        href={
                                            editRoute(category.id, {
                                                query: { page },
                                            }).url
                                        }
                                    >
                                        Edit
                                    </Link>
                                </Button>
                            )}
                        </>
                    }
                />

                <DetailFields>
                    <DetailField label="Code" value={category.code} />
                    <DetailField
                        label="Sales account"
                        value={category.salesAccount}
                    />
                    <DetailField
                        label="COGS account"
                        value={category.cogsAccount}
                    />
                    <DetailField
                        label="Discount account"
                        value={category.discountAccount}
                    />
                    <DetailField
                        label="Description"
                        value={category.description}
                    />
                    <DetailField
                        label="Button colour"
                        value={<Swatch color={category.buttonColor} />}
                    />
                </DetailFields>

                <DataTable
                    caption="Category details"
                    columns={detailColumns}
                    rows={category.details}
                    rowKey={(detail) => String(detail.id)}
                    empty="This category has no details."
                />
            </div>
        </>
    );
}
