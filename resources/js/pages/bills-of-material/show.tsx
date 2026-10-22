import { Head } from '@inertiajs/react';
import { DetailTabs } from '@/components/detail-tabs';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index as bomsIndex } from '@/routes/bills-of-material';
import type { BillOfMaterial } from '@/types/bill-of-material';
import { formatAmount, formatQuantity } from '@/lib/format';

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

function Empty({ children }: { children: string }) {
    return <p className="text-muted-foreground text-sm">{children}</p>;
}

function percent(value: number | null): string {
    return value === null ? '—' : `${formatQuantity(value)}%`;
}

export default function BillOfMaterialShow({ bom }: { bom: BillOfMaterial }) {
    const product = [bom.productCode, bom.productName]
        .filter((part) => part !== null)
        .join(' — ');

    return (
        <>
            <Head title={`BOM ${bom.code ?? bom.name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={bom.name || `#${String(bom.id)}`}
                    description={`${bom.code ?? '—'}${bom.typeName ? ` — ${bom.typeName}` : ''}`}
                    back={{ href: bomsIndex(), label: 'Bill of material' }}
                    actions={
                        bom.active !== null && (
                            <StatusBadge
                                status={bom.active ? 'Active' : 'Inactive'}
                            />
                        )
                    }
                />

                <Section title="General">
                    <dl className="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <Field label="Product" value={product || '—'} />
                        <Field label="Unit" value={bom.uomName ?? '—'} />
                        <Field label="Access" value={bom.accessType ?? '—'} />
                        <Field
                            label="Material cost"
                            value={formatAmount(bom.materialCostTotal)}
                        />
                        <Field
                            label="Other cost"
                            value={formatAmount(bom.otherCostTotal)}
                        />
                        <Field
                            label="Weight factor total"
                            value={formatQuantity(bom.weightFactorTotal)}
                        />
                        <Field
                            label="Created"
                            value={
                                [bom.createdBy, bom.createdAt]
                                    .filter((part) => part !== null)
                                    .join(', ') || '—'
                            }
                        />
                        <Field
                            label="Last edited"
                            value={
                                [bom.editedBy, bom.editedAt]
                                    .filter((part) => part !== null)
                                    .join(', ') || '—'
                            }
                        />
                    </dl>
                    {bom.notes && <p className="mt-3 text-sm">{bom.notes}</p>}
                </Section>

                <DetailTabs
                    label="Bill of material sections"
                    tabs={[
                        {
                            value: 'materials',
                            label: 'Materials',
                            count: bom.lines.length,
                            content: (
                                <>
                                    {bom.lines.length === 0 ? (
                                        <Empty>No materials.</Empty>
                                    ) : (
                                        <div className="overflow-x-auto">
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>
                                                            Code
                                                        </TableHead>
                                                        <TableHead>
                                                            Material
                                                        </TableHead>
                                                        <TableHead className="text-right">
                                                            Qty
                                                        </TableHead>
                                                        <TableHead>
                                                            Unit
                                                        </TableHead>
                                                        <TableHead className="text-right">
                                                            Last cost
                                                        </TableHead>
                                                        <TableHead className="text-right">
                                                            Tolerance
                                                        </TableHead>
                                                        <TableHead className="text-right">
                                                            Yield
                                                        </TableHead>
                                                        <TableHead>
                                                            Substitutes
                                                        </TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {bom.lines.map(
                                                        (line, index) => (
                                                            <TableRow
                                                                key={`${String(line.lineId)}-${String(index)}`}
                                                            >
                                                                <TableCell className="font-mono text-xs">
                                                                    {line.productCode ??
                                                                        '—'}
                                                                </TableCell>
                                                                <TableCell>
                                                                    {
                                                                        line.productName
                                                                    }
                                                                    {line.printGroup && (
                                                                        <span className="text-muted-foreground block text-xs">
                                                                            {
                                                                                line.printGroup
                                                                            }
                                                                        </span>
                                                                    )}
                                                                </TableCell>
                                                                <TableCell className="text-right">
                                                                    {formatQuantity(
                                                                        line.qty,
                                                                    )}
                                                                </TableCell>
                                                                <TableCell>
                                                                    {line.uomName ??
                                                                        '—'}
                                                                </TableCell>
                                                                <TableCell className="text-right">
                                                                    {formatAmount(
                                                                        line.lastCost,
                                                                    )}
                                                                </TableCell>
                                                                <TableCell className="text-right">
                                                                    {percent(
                                                                        line.tolerancePercent,
                                                                    )}
                                                                </TableCell>
                                                                <TableCell className="text-right">
                                                                    {percent(
                                                                        line.yieldPercent,
                                                                    )}
                                                                </TableCell>
                                                                <TableCell className="text-xs">
                                                                    {line
                                                                        .substitutes
                                                                        .length ===
                                                                        0 &&
                                                                    line
                                                                        .manufacturingSubstitutes
                                                                        .length ===
                                                                        0
                                                                        ? '—'
                                                                        : [
                                                                              ...line.substitutes.map(
                                                                                  (
                                                                                      substitute,
                                                                                  ) =>
                                                                                      substitute.productName ??
                                                                                      substitute.productCode ??
                                                                                      `#${String(substitute.productDetailId)}`,
                                                                              ),
                                                                              ...line.manufacturingSubstitutes.map(
                                                                                  (
                                                                                      name,
                                                                                  ) =>
                                                                                      `${name} (manufacturing)`,
                                                                              ),
                                                                          ].join(
                                                                              ', ',
                                                                          )}
                                                                </TableCell>
                                                            </TableRow>
                                                        ),
                                                    )}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    )}
                                </>
                            ),
                        },
                        {
                            value: 'costs',
                            label: 'Other costs',
                            count: bom.costs.length,
                            content: (
                                <>
                                    {bom.costs.length === 0 ? (
                                        <Empty>No other costs.</Empty>
                                    ) : (
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>
                                                        Account
                                                    </TableHead>
                                                    <TableHead>
                                                        Description
                                                    </TableHead>
                                                    <TableHead className="text-right">
                                                        Total
                                                    </TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {bom.costs.map(
                                                    (cost, index) => (
                                                        <TableRow key={index}>
                                                            <TableCell className="font-mono text-xs">
                                                                {cost.accountNo ??
                                                                    '—'}
                                                            </TableCell>
                                                            <TableCell>
                                                                {cost.description ??
                                                                    '—'}
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                {formatAmount(
                                                                    cost.total,
                                                                )}
                                                            </TableCell>
                                                        </TableRow>
                                                    ),
                                                )}
                                            </TableBody>
                                        </Table>
                                    )}
                                </>
                            ),
                        },
                        ...(bom.replacements.length > 0
                            ? [
                                  {
                                      value: 'replacements',
                                      label: 'Replacements per menu',
                                      count: bom.replacements.length,
                                      content: (
                                          <>
                                              <Table>
                                                  <TableHeader>
                                                      <TableRow>
                                                          <TableHead>
                                                              Menu
                                                          </TableHead>
                                                          <TableHead>
                                                              Material
                                                          </TableHead>
                                                          <TableHead>
                                                              Replaced by
                                                          </TableHead>
                                                          <TableHead className="text-right">
                                                              Qty
                                                          </TableHead>
                                                          <TableHead>
                                                              Unit
                                                          </TableHead>
                                                      </TableRow>
                                                  </TableHeader>
                                                  <TableBody>
                                                      {bom.replacements.map(
                                                          (
                                                              replacement,
                                                              index,
                                                          ) => (
                                                              <TableRow
                                                                  key={index}
                                                              >
                                                                  <TableCell>
                                                                      {replacement.menuName ??
                                                                          replacement.menuCode ??
                                                                          '—'}
                                                                  </TableCell>
                                                                  <TableCell>
                                                                      {replacement.productName ??
                                                                          replacement.productCode ??
                                                                          '—'}
                                                                  </TableCell>
                                                                  <TableCell>
                                                                      {replacement.replacementProductName ??
                                                                          replacement.replacementProductCode ??
                                                                          '—'}
                                                                  </TableCell>
                                                                  <TableCell className="text-right">
                                                                      {formatQuantity(
                                                                          replacement.qty,
                                                                      )}
                                                                  </TableCell>
                                                                  <TableCell>
                                                                      {replacement.uomName ??
                                                                          '—'}
                                                                  </TableCell>
                                                              </TableRow>
                                                          ),
                                                      )}
                                                  </TableBody>
                                              </Table>
                                          </>
                                      ),
                                  },
                              ]
                            : []),
                    ]}
                />
            </div>
        </>
    );
}
