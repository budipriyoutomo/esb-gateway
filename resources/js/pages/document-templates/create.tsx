import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import AlertError from '@/components/alert-error';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import {
    create as createDocumentTemplate,
    index as documentTemplatesIndex,
    store as storeDocumentTemplate,
} from '@/routes/document-templates';
import type {
    DocumentTemplateTypeOption,
    ProductDetailOption,
} from '@/types/document-template';
import type { PurposeSummary } from '@/types/purpose';

type Branch = {
    id: number;
    name: string;
    code: string | null;
};

type Props = {
    types: DocumentTemplateTypeOption[];
    branches: Branch[];
    purposes: PurposeSummary[];
    products: ProductDetailOption[];
    productSearch: string | null;
};

type FormLine = {
    productDetailId: number;
    productCode: string | null;
    productName: string;
    uomName: string | null;
    name: string;
    purposeId: string;
};

type FormData = {
    name: string;
    type: string;
    active: boolean;
    branchIds: number[];
    lines: FormLine[];
};

const NO_PURPOSE = 'none';

export default function DocumentTemplateCreate({
    types,
    branches,
    purposes,
    products,
    productSearch,
}: Props) {
    const [search, setSearch] = useState(productSearch ?? '');

    const form = useForm<FormData>({
        name: '',
        type: '',
        active: true,
        branchIds: [],
        lines: [],
    });

    const errors = form.errors as Record<string, string | undefined>;
    const added = new Set(form.data.lines.map((line) => line.productDetailId));
    const branchError =
        errors.branchIds ??
        Object.entries(errors).find(([key]) =>
            key.startsWith('branchIds.'),
        )?.[1];

    function searchProducts(event: React.FormEvent | React.MouseEvent) {
        event.preventDefault();

        router.get(
            createDocumentTemplate().url,
            search.trim() === '' ? {} : { productSearch: search.trim() },
            {
                only: ['products', 'productSearch'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }

    function toggleBranch(id: number, checked: boolean) {
        form.setData(
            'branchIds',
            checked
                ? [...form.data.branchIds, id]
                : form.data.branchIds.filter((branchId) => branchId !== id),
        );
    }

    function addProduct(product: ProductDetailOption) {
        if (added.has(product.productDetailId)) {
            return;
        }

        form.setData('lines', [
            ...form.data.lines,
            {
                productDetailId: product.productDetailId,
                productCode: product.productCode,
                productName: product.productName,
                uomName: product.uomName,
                name: product.productName,
                purposeId: NO_PURPOSE,
            },
        ]);
    }

    function updateLine(index: number, patch: Partial<FormLine>) {
        form.setData(
            'lines',
            form.data.lines.map((line, at) =>
                at === index ? { ...line, ...patch } : line,
            ),
        );
    }

    function removeLine(index: number) {
        form.setData(
            'lines',
            form.data.lines.filter((_, at) => at !== index),
        );
    }

    function submit(event: React.FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            name: data.name.trim(),
            type: data.type === '' ? null : Number(data.type),
            active: data.active,
            branchIds: data.branchIds,
            lines: data.lines.map((line) => ({
                productDetailId: line.productDetailId,
                name: line.name.trim(),
                purposeId:
                    line.purposeId === NO_PURPOSE
                        ? null
                        : Number(line.purposeId),
            })),
        }));

        // Not idempotent: the button stays disabled while the request runs,
        // and the server serializes creates per person too.
        form.post(storeDocumentTemplate().url, { preserveScroll: true });
    }

    return (
        <>
            <Head title="New document template" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <Button variant="ghost" size="sm" asChild className="-ml-2">
                        <Link href={documentTemplatesIndex()}>
                            <ArrowLeft className="size-4" />
                            Document templates
                        </Link>
                    </Button>
                </div>

                <Heading
                    title="New document template"
                    description="Name the template, choose the branches that may use it, and add the products it pre-fills."
                />

                {errors.form && (
                    <AlertError
                        title="The ERP refused this template."
                        errors={[errors.form]}
                    />
                )}

                <section className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div className="grid gap-1.5">
                        <Label htmlFor="dt-name">Name</Label>
                        <Input
                            id="dt-name"
                            value={form.data.name}
                            onChange={(event) => {
                                form.setData('name', event.target.value);
                            }}
                            maxLength={255}
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="dt-type">Type</Label>
                        <Select
                            value={form.data.type}
                            onValueChange={(value) => {
                                form.setData('type', value);
                            }}
                        >
                            <SelectTrigger id="dt-type">
                                <SelectValue placeholder="Choose a type" />
                            </SelectTrigger>
                            <SelectContent>
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
                        <InputError message={errors.type} />
                    </div>

                    <div className="flex items-center gap-2 pt-6">
                        <Checkbox
                            id="dt-active"
                            checked={form.data.active}
                            onCheckedChange={(checked) => {
                                form.setData('active', checked === true);
                            }}
                        />
                        <Label htmlFor="dt-active">Active</Label>
                        <InputError message={errors.active} />
                    </div>
                </section>

                <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                    <h2 className="mb-3 text-sm font-medium">
                        Branches ({form.data.branchIds.length} chosen)
                    </h2>
                    {branches.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            The ERP returned no active branches.
                        </p>
                    ) : (
                        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
                            {branches.map((branch) => (
                                <label
                                    key={branch.id}
                                    className="flex items-center gap-2 text-sm"
                                >
                                    <Checkbox
                                        checked={form.data.branchIds.includes(
                                            branch.id,
                                        )}
                                        onCheckedChange={(checked) => {
                                            toggleBranch(
                                                branch.id,
                                                checked === true,
                                            );
                                        }}
                                    />
                                    {branch.name}
                                    {branch.code && (
                                        <span className="text-muted-foreground font-mono text-xs">
                                            {branch.code}
                                        </span>
                                    )}
                                </label>
                            ))}
                        </div>
                    )}
                    <InputError message={branchError} className="mt-2" />
                </section>

                <section className="border-sidebar-border/70 dark:border-sidebar-border flex flex-col gap-3 rounded-xl border p-4">
                    <h2 className="text-sm font-medium">Find products</h2>
                    <div className="flex flex-wrap gap-2">
                        <Input
                            value={search}
                            onChange={(event) => {
                                setSearch(event.target.value);
                            }}
                            onKeyDown={(event) => {
                                if (event.key === 'Enter') {
                                    searchProducts(event);
                                }
                            }}
                            placeholder="Product name"
                            aria-label="Product name"
                            maxLength={100}
                            className="w-64"
                        />
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={searchProducts}
                        >
                            Search
                        </Button>
                    </div>
                    <InputError message={errors.productSearch} />

                    {productSearch !== null && (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Product</TableHead>
                                    <TableHead>Unit</TableHead>
                                    <TableHead className="w-24" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {products.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="text-muted-foreground py-6 text-center"
                                        >
                                            No products matched “{productSearch}
                                            ”.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {products.map((product) => (
                                    <TableRow key={product.productDetailId}>
                                        <TableCell className="font-mono text-xs">
                                            {product.productCode ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {product.productName || '—'}
                                        </TableCell>
                                        <TableCell>
                                            {product.uomName ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="outline"
                                                disabled={added.has(
                                                    product.productDetailId,
                                                )}
                                                onClick={() => {
                                                    addProduct(product);
                                                }}
                                            >
                                                {added.has(
                                                    product.productDetailId,
                                                ) ? (
                                                    'Added'
                                                ) : (
                                                    <>
                                                        <Plus className="size-4" />
                                                        Add
                                                    </>
                                                )}
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </section>

                <form
                    onSubmit={submit}
                    className="border-sidebar-border/70 dark:border-sidebar-border flex flex-col gap-3 rounded-xl border p-4"
                >
                    <h2 className="text-sm font-medium">
                        Products on this template ({form.data.lines.length})
                    </h2>
                    <InputError message={errors.lines} />

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Product</TableHead>
                                <TableHead>Unit</TableHead>
                                <TableHead>Line name</TableHead>
                                <TableHead>Purpose</TableHead>
                                <TableHead className="w-12" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {form.data.lines.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="text-muted-foreground py-6 text-center"
                                    >
                                        Search for a product above and add it.
                                    </TableCell>
                                </TableRow>
                            )}
                            {form.data.lines.map((line, index) => (
                                <TableRow key={line.productDetailId}>
                                    <TableCell>
                                        {line.productName || '—'}
                                        {line.productCode && (
                                            <span className="text-muted-foreground block font-mono text-xs">
                                                {line.productCode}
                                            </span>
                                        )}
                                        <InputError
                                            message={
                                                errors[
                                                    `lines.${String(index)}.productDetailId`
                                                ]
                                            }
                                        />
                                    </TableCell>
                                    <TableCell>{line.uomName ?? '—'}</TableCell>
                                    <TableCell>
                                        <Input
                                            value={line.name}
                                            onChange={(event) => {
                                                updateLine(index, {
                                                    name: event.target.value,
                                                });
                                            }}
                                            aria-label={`Line name for ${line.productName}`}
                                            maxLength={255}
                                        />
                                        <InputError
                                            message={
                                                errors[
                                                    `lines.${String(index)}.name`
                                                ]
                                            }
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <Select
                                            value={line.purposeId}
                                            onValueChange={(value) => {
                                                updateLine(index, {
                                                    purposeId: value,
                                                });
                                            }}
                                        >
                                            <SelectTrigger
                                                className="w-48"
                                                aria-label={`Purpose for ${line.productName}`}
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value={NO_PURPOSE}>
                                                    No purpose
                                                </SelectItem>
                                                {purposes
                                                    .filter(
                                                        (purpose) =>
                                                            purpose.id !== null,
                                                    )
                                                    .map((purpose) => (
                                                        <SelectItem
                                                            key={purpose.id}
                                                            value={String(
                                                                purpose.id,
                                                            )}
                                                        >
                                                            {purpose.name}
                                                        </SelectItem>
                                                    ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={
                                                errors[
                                                    `lines.${String(index)}.purposeId`
                                                ]
                                            }
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            aria-label={`Remove ${line.productName}`}
                                            onClick={() => {
                                                removeLine(index);
                                            }}
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    <div className="flex justify-end gap-2">
                        <Button variant="outline" asChild>
                            <Link href={documentTemplatesIndex()}>Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Creating…' : 'Create template'}
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

DocumentTemplateCreate.layout = {
    breadcrumbs: [
        {
            title: 'Document templates',
            href: documentTemplatesIndex(),
        },
        {
            title: 'New',
            href: createDocumentTemplate(),
        },
    ],
};
