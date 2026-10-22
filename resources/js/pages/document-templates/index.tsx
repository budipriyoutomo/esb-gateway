import { Head, Link } from '@inertiajs/react';
import { FileUp, Plus } from 'lucide-react';
import { ActiveStatusField } from '@/components/active-status-field';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { FilterBar } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { SelectFilterField } from '@/components/select-filter-field';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFilterForm } from '@/hooks/use-filter-form';
import { usePermissions } from '@/hooks/use-permissions';
import { index as importsIndex } from '@/routes/document-template-imports';
import {
    create as documentTemplatesCreate,
    index as documentTemplatesIndex,
    show as documentTemplatesShow,
} from '@/routes/document-templates';
import type {
    DocumentTemplateSummary,
    DocumentTemplateTypeOption,
} from '@/types/document-template';
import type { Pagination, PagingFilters } from '@/types/pagination';

type Filters = PagingFilters & {
    requestTemplateName?: string;
    requestTemplateTypeID?: number;
    flagActive?: number;
};

type Props = {
    templates: DocumentTemplateSummary[];
    pagination: Pagination | null;
    filters: Filters;
    types: DocumentTemplateTypeOption[];
};

const columns: DataTableColumn<DocumentTemplateSummary>[] = [
    {
        key: 'name',
        header: 'Name',
        className: 'font-medium',
        cell: (template) =>
            template.id !== null ? (
                <Link
                    href={documentTemplatesShow(template.id)}
                    className="underline-offset-4 hover:underline"
                >
                    {template.name || `#${String(template.id)}`}
                </Link>
            ) : (
                template.name || '—'
            ),
    },
    {
        key: 'type',
        header: 'Type',
        cell: (template) =>
            template.typeNames.length === 0 ? (
                '—'
            ) : (
                <span className="flex flex-wrap gap-1">
                    {template.typeNames.map((typeName) => (
                        <Badge key={typeName} variant="outline">
                            {typeName}
                        </Badge>
                    ))}
                </span>
            ),
    },
    {
        key: 'branches',
        header: 'Branches',
        className: 'whitespace-normal',
        cell: (template) =>
            template.branchNames.length === 0
                ? '—'
                : template.branchNames.join(', '),
    },
    {
        key: 'status',
        header: 'Status',
        cell: (template) => (
            <StatusBadge
                status={
                    template.active === null
                        ? null
                        : template.active
                          ? 'Active'
                          : 'Inactive'
                }
            />
        ),
    },
];

export default function DocumentTemplatesIndex({
    templates,
    pagination,
    filters,
    types,
}: Props) {
    const { can } = usePermissions();
    const canManage = can('esb.invoke') && can('document-templates.manage');
    const form = useFilterForm(documentTemplatesIndex().url, filters, [
        { key: 'requestTemplateName', chip: (value) => `“${value}”` },
        {
            key: 'requestTemplateTypeID',
            chip: (value) =>
                types.find((option) => String(option.value) === value)?.label ??
                `Type ${value}`,
        },
        {
            key: 'flagActive',
            chip: (value) => (value === '1' ? 'Active' : 'Inactive'),
        },
    ]);

    // The selects apply at once, as they did before.
    function applyNow(key: string, value: string) {
        form.set(key, value);
        form.apply({ ...form.values, [key]: value });
    }

    return (
        <>
            <Head title="Document templates" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Document templates"
                    description="Product sets that pre-fill purchase requests, usage, and item journals, from ESB Core."
                    actions={
                        canManage && (
                            <>
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={importsIndex()}>
                                        <FileUp className="size-4" />
                                        Import
                                    </Link>
                                </Button>
                                <Button size="sm" asChild>
                                    <Link href={documentTemplatesCreate()}>
                                        <Plus className="size-4" />
                                        New template
                                    </Link>
                                </Button>
                            </>
                        )
                    }
                />

                <FilterBar
                    id="templates"
                    search={{
                        label: 'Template name',
                        value: form.values.requestTemplateName!,
                        onChange: (value) =>
                            form.set('requestTemplateName', value),
                        placeholder: 'Template name',
                    }}
                    {...form.bar}
                    defaultAdvancedOpen={
                        filters.requestTemplateTypeID !== undefined ||
                        filters.flagActive !== undefined
                    }
                >
                    <SelectFilterField
                        id="templates-type"
                        label="Type"
                        anyLabel="Any type"
                        options={types.map((option) => ({
                            value: String(option.value),
                            label: option.label,
                        }))}
                        value={form.values.requestTemplateTypeID!}
                        onChange={(value) => {
                            applyNow('requestTemplateTypeID', value);
                        }}
                        className="w-48"
                    />
                    <ActiveStatusField
                        id="templates-status"
                        value={form.values.flagActive!}
                        onChange={(value) => {
                            applyNow('flagActive', value);
                        }}
                    />
                </FilterBar>

                <DataTable
                    caption="Document templates"
                    columns={columns}
                    rows={templates}
                    rowKey={(template, index) =>
                        `${String(template.id)}-${String(index)}`
                    }
                    rowHref={(template) =>
                        template.id !== null
                            ? documentTemplatesShow(template.id).url
                            : null
                    }
                    empty="No document templates matched."
                />

                <ListPagination
                    pagination={pagination}
                    noun="templates"
                    onPageChange={form.goToPage}
                />
            </div>
        </>
    );
}
