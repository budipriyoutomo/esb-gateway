import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { FilterBar } from '@/components/filter-bar';
import type { ActiveFilter } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useListQuery } from '@/hooks/use-list-query';
import { usePermissions } from '@/hooks/use-permissions';
import { formatAmount, formatDateTime } from '@/lib/format';
import {
    create as createRoute,
    index as indexRoute,
} from '@/routes/promotions';
import type { Pagination } from '@/types/pagination';
import type { Promotion } from '@/types/pos';

type Filters = {
    page?: number;
    branchCode?: string;
    promotionCode?: string;
    [key: string]: string | number | undefined;
};

type Props = {
    promotions: Promotion[];
    pagination: Pagination | null;
    filters: Filters;
};

function scope(promotion: Promotion): string {
    const parts = [
        [promotion.scope.menuCategories, 'menu categories'],
        [promotion.scope.menuCategoryDetails, 'category details'],
        [promotion.scope.menus, 'menus'],
    ]
        .filter(([count]) => (count as number) > 0)
        .map(([count, noun]) => `${count} ${noun}`);

    return parts.length === 0 ? 'All menus' : parts.join(', ');
}

const columns: DataTableColumn<Promotion>[] = [
    {
        key: 'code',
        header: 'Promotion',
        cell: (promotion) => (
            <>
                <span className="font-mono text-xs">
                    {promotion.code ?? '—'}
                </span>
                {promotion.notes && (
                    <span className="text-muted-foreground block text-xs">
                        {promotion.notes}
                    </span>
                )}
            </>
        ),
    },
    {
        key: 'type',
        header: 'Type',
        cell: (promotion) => promotion.typeName ?? '—',
    },
    {
        key: 'discount',
        header: 'Discount',
        align: 'right',
        cell: (promotion) => formatAmount(promotion.discount),
    },
    {
        key: 'min',
        header: 'Min. subtotal',
        align: 'right',
        cell: (promotion) => formatAmount(promotion.minSubtotal),
    },
    {
        key: 'period',
        header: 'Period',
        className: 'text-muted-foreground text-xs',
        cell: (promotion) =>
            [promotion.startAt, promotion.endAt]
                .map((at) => (at === null ? '—' : formatDateTime(at)))
                .join(' – '),
    },
    {
        key: 'branches',
        header: 'Branches',
        cell: (promotion) =>
            promotion.branches.length === 0
                ? '—'
                : promotion.branches.map((branch) => branch.name).join(', '),
    },
    {
        key: 'scope',
        header: 'Applies to',
        cell: scope,
    },
    {
        key: 'eso',
        header: 'ESB Order',
        cell: (promotion) =>
            promotion.shownOnEso === null ? (
                '—'
            ) : (
                <Badge variant={promotion.shownOnEso ? 'default' : 'secondary'}>
                    {promotion.shownOnEso ? 'Shown' : 'Hidden'}
                </Badge>
            ),
    },
];

/**
 * POS promotions from ESB OMS ([ESB-049]), filtered by promotion code and
 * branch code. The ESB answers the whole list; the gateway pages it.
 */
export default function PromotionIndex({
    promotions,
    pagination,
    filters,
}: Props) {
    const { can } = usePermissions();
    const manage = can('esb.invoke') && can('promotions.manage');
    const list = useListQuery(indexRoute().url, filters);
    const [code, setCode] = useState(filters.promotionCode ?? '');
    const [branchCode, setBranchCode] = useState(filters.branchCode ?? '');

    const active: ActiveFilter[] = [
        ...(filters.promotionCode
            ? [{ key: 'promotionCode', label: `Code ${filters.promotionCode}` }]
            : []),
        ...(filters.branchCode
            ? [{ key: 'branchCode', label: `Branch ${filters.branchCode}` }]
            : []),
    ];

    return (
        <>
            <Head title="Promotions" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Promotions"
                    description="POS promotions, read live from ESB OMS."
                    actions={
                        manage && (
                            <Button asChild>
                                <Link href={createRoute().url}>
                                    New promotion
                                </Link>
                            </Button>
                        )
                    }
                />

                <FilterBar
                    id="promotions"
                    search={{
                        value: code,
                        onChange: setCode,
                        placeholder: 'Promotion code',
                    }}
                    onSubmit={() =>
                        list.apply({ promotionCode: code, branchCode })
                    }
                    active={active}
                    onRemove={(key) => {
                        if (key === 'promotionCode') {
                            setCode('');
                        } else {
                            setBranchCode('');
                        }

                        list.apply({
                            ...filters,
                            page: undefined,
                            [key]: undefined,
                        });
                    }}
                    onReset={() => {
                        setCode('');
                        setBranchCode('');
                        list.reset();
                    }}
                    defaultAdvancedOpen={Boolean(filters.branchCode)}
                >
                    <div className="grid gap-1.5">
                        <Label htmlFor="promotions-branch">Branch code</Label>
                        <Input
                            id="promotions-branch"
                            maxLength={20}
                            value={branchCode}
                            onChange={(event) =>
                                setBranchCode(event.target.value)
                            }
                        />
                    </div>
                </FilterBar>

                <DataTable
                    caption="Promotions"
                    columns={columns}
                    rows={promotions}
                    rowKey={(promotion) => String(promotion.id)}
                    empty="No promotions matched this filter."
                />

                <ListPagination
                    pagination={pagination}
                    noun="promotions"
                    onPageChange={list.goToPage}
                />
            </div>
        </>
    );
}
