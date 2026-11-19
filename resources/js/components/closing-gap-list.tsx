import { Link } from '@inertiajs/react';
import { items, outlets, payments, purposes } from '@/routes/closing-mappings';
import type { ClosingGap } from '@/types/closing';

/**
 * Where each kind of gap is fixed. A gap sync-api has to close (payments it
 * has not sent) has no page here.
 */
function gapHref(gap: ClosingGap): string | null {
    switch (gap.type) {
        case 'outlet':
            return outlets().url;
        case 'purpose':
            return purposes().url;
        case 'payment-method':
            return payments().url;
        case 'sales-payments':
            return null;
        default:
            return items({ query: { search: gap.label } }).url;
    }
}

/**
 * What a closing recap still needs (ADR-032), each with a link to the
 * mapping page that fixes it.
 */
export function ClosingGapList({
    id,
    title,
    gaps,
}: {
    id: string;
    title: string;
    gaps: ClosingGap[];
}) {
    return (
        <section
            aria-labelledby={id}
            className="grid gap-2 rounded-xl border border-dashed p-4"
        >
            <h2 id={id} className="text-sm font-medium">
                {title}
            </h2>
            <ul className="grid gap-1 text-sm">
                {gaps.map((gap) => {
                    const href = gapHref(gap);

                    return (
                        <li
                            key={`${gap.type}:${gap.key}`}
                            className="flex flex-wrap items-center gap-2"
                        >
                            <span>{gap.message}</span>
                            {href !== null && (
                                <Link
                                    href={href}
                                    className="text-xs underline underline-offset-4"
                                >
                                    Map it
                                </Link>
                            )}
                        </li>
                    );
                })}
            </ul>
        </section>
    );
}
