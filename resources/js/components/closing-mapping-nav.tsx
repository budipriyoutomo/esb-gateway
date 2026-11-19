import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { items, outlets, payments, purposes } from '@/routes/closing-mappings';

const LINKS = [
    { key: 'outlets', label: 'Outlets', href: outlets().url },
    { key: 'items', label: 'Menus & products', href: items().url },
    { key: 'purposes', label: 'Purposes', href: purposes().url },
    { key: 'payments', label: 'Payment methods', href: payments().url },
] as const;

/**
 * Moves between the closing mapping pages (ADR-032).
 */
export function ClosingMappingNav({
    current,
}: {
    current: (typeof LINKS)[number]['key'];
}) {
    return (
        <nav aria-label="Closing mappings" className="flex flex-wrap gap-1">
            {LINKS.map((link) => (
                <Link
                    key={link.key}
                    href={link.href}
                    aria-current={link.key === current ? 'page' : undefined}
                    className={cn(
                        'rounded-md px-3 py-1.5 text-sm',
                        link.key === current
                            ? 'bg-muted font-medium'
                            : 'text-muted-foreground hover:bg-muted/50',
                    )}
                >
                    {link.label}
                </Link>
            ))}
        </nav>
    );
}
