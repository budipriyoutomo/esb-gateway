import { Head, Link, router } from '@inertiajs/react';
import { FileUp, Plus } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
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

const ANY = 'any';

export default function DocumentTemplatesIndex({
    templates,
    pagination,
    filters,
    types,
}: Props) {
    const { can } = usePermissions();
    const canManage = can('esb.invoke') && can('document-templates.manage');
    const [name, setName] = useState(filters.requestTemplateName ?? '');
    const [type, setType] = useState(
        filters.requestTemplateTypeID === undefined
            ? ANY
            : String(filters.requestTemplateTypeID),
    );
    const [status, setStatus] = useState(
        filters.flagActive === undefined ? ANY : String(filters.flagActive),
    );

    function search(page = 1, next: { type?: string; status?: string } = {}) {
        const nextType = next.type ?? type;
        const nextStatus = next.status ?? status;
        const query: Record<string, string | number> = { page };

        if (name !== '') {
            query.requestTemplateName = name;
        }

        if (nextType !== ANY) {
            query.requestTemplateTypeID = nextType;
        }

        if (nextStatus !== ANY) {
            query.flagActive = nextStatus;
        }

        router.get(documentTemplatesIndex().url, query, {
            preserveState: true,
            replace: true,
        });
    }

    return (
        <>
            <Head title="Document templates" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title="Document templates"
                        description="Product sets that pre-fill purchase requests, usage, and item journals, from ESB Core."
                    />
                    {canManage && (
                        <div className="flex gap-2">
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
                        </div>
                    )}
                </div>

                <form
                    className="flex flex-wrap items-end gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        search();
                    }}
                >
                    <Input
                        value={name}
                        onChange={(event) => {
                            setName(event.target.value);
                        }}
                        placeholder="Template name"
                        aria-label="Template name"
                        className="w-56"
                    />
                    <Select
                        value={type}
                        onValueChange={(value) => {
                            setType(value);
                            search(1, { type: value });
                        }}
                    >
                        <SelectTrigger className="w-48" aria-label="Type">
                            <SelectValue placeholder="Any type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ANY}>Any type</SelectItem>
                            {types.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={String(option.value)}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={status}
                        onValueChange={(value) => {
                            setStatus(value);
                            search(1, { status: value });
                        }}
                    >
                        <SelectTrigger className="w-36" aria-label="Status">
                            <SelectValue placeholder="Any status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ANY}>Any status</SelectItem>
                            <SelectItem value="1">Active</SelectItem>
                            <SelectItem value="0">Inactive</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-2">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Branches</TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {templates.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={4}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        No document templates matched.
                                    </TableCell>
                                </TableRow>
                            )}

                            {templates.map((template, index) => (
                                <TableRow
                                    key={`${String(template.id)}-${String(index)}`}
                                >
                                    <TableCell className="font-medium">
                                        {template.id !== null ? (
                                            <Link
                                                href={documentTemplatesShow(
                                                    template.id,
                                                )}
                                                className="hover:underline"
                                            >
                                                {template.name ||
                                                    `#${String(template.id)}`}
                                            </Link>
                                        ) : (
                                            template.name || '—'
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex flex-wrap gap-1">
                                            {template.typeNames.length === 0
                                                ? '—'
                                                : template.typeNames.map(
                                                      (typeName) => (
                                                          <Badge
                                                              key={typeName}
                                                              variant="outline"
                                                          >
                                                              {typeName}
                                                          </Badge>
                                                      ),
                                                  )}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-sm">
                                        {template.branchNames.length === 0
                                            ? '—'
                                            : template.branchNames.join(', ')}
                                    </TableCell>
                                    <TableCell>
                                        {template.active !== null && (
                                            <Badge
                                                variant={
                                                    template.active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {template.active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {pagination && (
                    <div className="flex items-center justify-between gap-4">
                        <p className="text-muted-foreground text-sm">
                            Page {pagination.page} of {pagination.lastPage} —{' '}
                            {pagination.count} templates
                        </p>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={pagination.page <= 1}
                                onClick={() => {
                                    search(pagination.page - 1);
                                }}
                            >
                                Previous
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={!pagination.hasMorePages}
                                onClick={() => {
                                    search(pagination.page + 1);
                                }}
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

DocumentTemplatesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Document templates',
            href: documentTemplatesIndex(),
        },
    ],
};
