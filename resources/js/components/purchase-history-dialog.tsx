import { History } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatAmount, formatDate } from '@/lib/format';
import { fetchPurchasePrices } from '@/lib/pricelist-prices';
import type { PurchasePrice } from '@/lib/pricelist-prices';

type Props = {
    /** e.g. "Purchase history, line 1". */
    label: string;
    product: string | null;
    supplierId: number | null;
    currencyId: number | null;
    productDetailId: number | null;
};

/**
 * Recent purchase invoice prices of one product ([ESB-032]): this
 * supplier's, or other suppliers'. Loaded when opened.
 */
export function PurchaseHistoryDialog({
    label,
    product,
    supplierId,
    currencyId,
    productDetailId,
}: Props) {
    const [open, setOpen] = useState(false);
    const [otherSuppliers, setOtherSuppliers] = useState(false);
    const [invoices, setInvoices] = useState<PurchasePrice[] | null>(null);
    const [error, setError] = useState<string | null>(null);
    const ready =
        supplierId !== null && currencyId !== null && productDetailId !== null;

    useEffect(() => {
        if (!open || !ready) {
            return;
        }

        const controller = new AbortController();
        setInvoices(null);
        setError(null);

        fetchPurchasePrices(
            { supplierId, currencyId, productDetailId, otherSuppliers },
            controller.signal,
        )
            .then(setInvoices)
            .catch((failure: unknown) => {
                if (!controller.signal.aborted) {
                    setError(
                        failure instanceof Error
                            ? failure.message
                            : 'Could not load the prices. Try again.',
                    );
                }
            });

        return () => controller.abort();
    }, [open, ready, supplierId, currencyId, productDetailId, otherSuppliers]);

    return (
        <>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                aria-label={label}
                title={
                    ready
                        ? label
                        : 'Choose a supplier, currency and product first'
                }
                disabled={!ready}
                onClick={() => setOpen(true)}
            >
                <History className="size-4" aria-hidden />
            </Button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Recent purchase prices</DialogTitle>
                        <DialogDescription>
                            {product ?? 'This product'} — up to five recent
                            purchase invoices, from the ERP.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="other-suppliers"
                            checked={otherSuppliers}
                            onCheckedChange={(checked) =>
                                setOtherSuppliers(checked === true)
                            }
                        />
                        <Label htmlFor="other-suppliers">
                            Other suppliers instead
                        </Label>
                    </div>

                    {error && (
                        <p role="alert" className="text-destructive text-sm">
                            {error}
                        </p>
                    )}
                    {invoices === null && error === null && (
                        <p className="text-muted-foreground text-sm">
                            Loading…
                        </p>
                    )}
                    {invoices !== null && invoices.length === 0 && (
                        <p className="text-muted-foreground text-sm">
                            No recent purchase invoices.
                        </p>
                    )}
                    {invoices !== null && invoices.length > 0 && (
                        <Table aria-label="Recent purchase prices">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Invoice</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Supplier</TableHead>
                                    <TableHead className="text-right">
                                        Price
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {invoices.map((invoice) => (
                                    <TableRow key={invoice.invoiceNumber}>
                                        <TableCell className="font-mono text-xs">
                                            {invoice.invoiceNumber}
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(invoice.invoiceDate)}
                                        </TableCell>
                                        <TableCell>
                                            {invoice.supplierName ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {formatAmount(invoice.price)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
