import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index as menusIndex } from '@/routes/master-menus';
import type { MasterMenu } from '@/types/master-menu';
import { formatAmount, formatFlag } from '@/types/master-menu';

function Field({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-muted-foreground text-xs">{label}</dt>
            <dd className="text-sm">{value}</dd>
        </div>
    );
}

function Section({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
            <h2 className="mb-3 text-sm font-medium">{title}</h2>
            {children}
        </section>
    );
}

function ActiveBadge({ active }: { active: boolean | null }) {
    if (active === null) {
        return null;
    }

    return (
        <Badge variant={active ? 'default' : 'secondary'}>
            {active ? 'Active' : 'Inactive'}
        </Badge>
    );
}

function Empty({ children }: { children: string }) {
    return <p className="text-muted-foreground text-sm">{children}</p>;
}

export default function MasterMenuShow({ menu }: { menu: MasterMenu }) {
    return (
        <>
            <Head title={`Menu ${menu.code}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex flex-col gap-2">
                        <Link
                            href={menusIndex()}
                            className="text-muted-foreground inline-flex items-center gap-1 text-sm hover:underline"
                        >
                            <ArrowLeft className="size-4" /> Master menu
                        </Link>
                        <Heading
                            title={menu.name}
                            description={`${menu.code}${menu.categoryName ? ` — ${menu.categoryName}` : ''}`}
                        />
                    </div>
                    <div className="flex items-center gap-2">
                        {menu.buttonColor && (
                            <span
                                className="size-5 rounded border"
                                style={{ backgroundColor: menu.buttonColor }}
                                title={`POS button colour ${menu.buttonColor}`}
                            />
                        )}
                        <ActiveBadge active={menu.active} />
                    </div>
                </div>

                <Section title="General">
                    <dl className="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <Field
                            label="Short name"
                            value={menu.shortName ?? '—'}
                        />
                        <Field
                            label="Alternative name"
                            value={menu.alternativeName ?? '—'}
                        />
                        <Field label="BOM" value={menu.bomName ?? '—'} />
                        <Field label="Tax" value={menu.taxLabel ?? '—'} />
                        <Field
                            label="Service charge"
                            value={formatFlag(menu.otherTax)}
                        />
                        <Field
                            label="Open price"
                            value={formatFlag(menu.openPrice)}
                        />
                        <Field
                            label="Print zero value"
                            value={formatFlag(menu.printZeroValue)}
                        />
                        <Field
                            label="Zero value text"
                            value={menu.zeroValueText ?? '—'}
                        />
                        <Field
                            label="Sales account"
                            value={menu.salesAccount ?? '—'}
                        />
                        <Field
                            label="COGS account"
                            value={menu.cogsAccount ?? '—'}
                        />
                        <Field
                            label="Discount account"
                            value={menu.discountAccount ?? '—'}
                        />
                        <Field label="Notes" value={menu.notes ?? '—'} />
                    </dl>
                    {menu.description && (
                        <p className="mt-3 text-sm">{menu.description}</p>
                    )}
                    {(menu.icons.length > 0 || menu.tags.length > 0) && (
                        <div className="mt-3 flex flex-wrap gap-1">
                            {menu.icons.map((icon) => (
                                <Badge key={`icon-${icon}`} variant="outline">
                                    {icon}
                                </Badge>
                            ))}
                            {menu.tags.map((tag) => (
                                <Badge key={`tag-${tag}`} variant="secondary">
                                    #{tag}
                                </Badge>
                            ))}
                        </div>
                    )}
                </Section>

                <Section title="Prices per menu template">
                    {menu.templates.length === 0 ? (
                        <Empty>Not on any menu template.</Empty>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Template</TableHead>
                                    <TableHead className="text-right">
                                        Price
                                    </TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {menu.templates.map((template) => (
                                    <TableRow key={template.templateId}>
                                        <TableCell>
                                            {template.templateName ??
                                                `#${String(template.templateId)}`}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatAmount(template.price)}
                                        </TableCell>
                                        <TableCell>
                                            <ActiveBadge
                                                active={template.active}
                                            />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </Section>

                <Section title="Package">
                    <dl className="mb-3 grid grid-cols-2 gap-3 md:grid-cols-4">
                        <Field
                            label="Print components separately"
                            value={formatFlag(menu.separatePrintPackage)}
                        />
                        <Field
                            label="Separate tax calculation"
                            value={formatFlag(menu.separateTaxCalculation)}
                        />
                    </dl>
                    {menu.packageGroups.length === 0 ? (
                        <Empty>Not a package menu.</Empty>
                    ) : (
                        <div className="flex flex-col gap-4">
                            {menu.packageGroups.map((group, index) => (
                                <div
                                    key={`${String(group.groupId)}-${String(index)}`}
                                >
                                    <div className="mb-1 flex items-center gap-2 text-sm">
                                        <span className="font-medium">
                                            {group.name}
                                        </span>
                                        <span className="text-muted-foreground text-xs">
                                            pick {formatAmount(group.minQty)}–
                                            {formatAmount(group.maxQty)}
                                        </span>
                                        <ActiveBadge active={group.active} />
                                    </div>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Code</TableHead>
                                                <TableHead>Menu</TableHead>
                                                <TableHead className="text-right">
                                                    Additional price
                                                </TableHead>
                                                <TableHead>Default</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {group.items.map((item) => (
                                                <TableRow key={item.menuId}>
                                                    <TableCell className="font-mono text-xs">
                                                        {item.code ?? '—'}
                                                    </TableCell>
                                                    <TableCell>
                                                        {item.name}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {formatAmount(
                                                            item.additionalPrice,
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {formatFlag(
                                                            item.defaultItem,
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            ))}
                        </div>
                    )}
                </Section>

                <Section title="Extras">
                    {menu.extras.length === 0 ? (
                        <Empty>No extras.</Empty>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Extra</TableHead>
                                    <TableHead className="text-right">
                                        Price
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Qty
                                    </TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {menu.extras.map((extra, index) => (
                                    <TableRow
                                        key={`${String(extra.extraId)}-${String(index)}`}
                                    >
                                        <TableCell>{extra.name}</TableCell>
                                        <TableCell className="text-right">
                                            {formatAmount(extra.price)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatAmount(extra.minQty)}–
                                            {formatAmount(extra.maxQty)}
                                        </TableCell>
                                        <TableCell>
                                            <ActiveBadge
                                                active={extra.active}
                                            />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </Section>

                {menu.relatedMenus.length > 0 && (
                    <Section title="Related menus">
                        <ul className="text-sm">
                            {menu.relatedMenus.map((related) => (
                                <li key={related.menuId}>
                                    <span className="font-mono text-xs">
                                        {related.code ?? '—'}
                                    </span>{' '}
                                    {related.name ??
                                        `#${String(related.menuId)}`}
                                </li>
                            ))}
                        </ul>
                    </Section>
                )}
            </div>
        </>
    );
}

MasterMenuShow.layout = {
    breadcrumbs: [
        {
            title: 'Master menu',
            href: menusIndex(),
        },
    ],
};
