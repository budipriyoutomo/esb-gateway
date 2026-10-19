import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

function isEmpty(value: ReactNode): boolean {
    return (
        value === null ||
        value === undefined ||
        (typeof value === 'string' && value.trim() === '')
    );
}

/** One label / value pair. A missing or blank value shows as a dash. */
export function DetailField({
    label,
    value,
}: {
    label: string;
    value: ReactNode;
}) {
    return (
        <div className="grid gap-0.5">
            <dt className="text-muted-foreground text-xs">{label}</dt>
            <dd className="text-sm">{isEmpty(value) ? '—' : value}</dd>
        </div>
    );
}

/** The grid of header fields on a detail page. */
export function DetailFields({ children }: { children: ReactNode }) {
    return (
        <dl className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {children}
        </dl>
    );
}

export type SummaryItem = {
    label: string;
    value: ReactNode;
    align?: 'left' | 'right';
};

/**
 * The card at the top of a detail page with the few facts people look for
 * first (date, status, total…).
 */
export function DetailSummary({
    label,
    items,
}: {
    /** Accessible name, e.g. "Purchase order summary". */
    label: string;
    items: SummaryItem[];
}) {
    return (
        <section
            aria-label={label}
            className="bg-card text-card-foreground rounded-xl border p-4"
        >
            <dl className="flex flex-wrap gap-x-8 gap-y-3">
                {items.map((item) => (
                    <div
                        key={item.label}
                        className={cn(
                            'grid gap-0.5',
                            item.align === 'right' &&
                                'text-right tabular-nums sm:ml-auto',
                        )}
                    >
                        <dt className="text-muted-foreground text-xs">
                            {item.label}
                        </dt>
                        <dd className="text-base font-medium">
                            {isEmpty(item.value) ? '—' : item.value}
                        </dd>
                    </div>
                ))}
            </dl>
        </section>
    );
}
