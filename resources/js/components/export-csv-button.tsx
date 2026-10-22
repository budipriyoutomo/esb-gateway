import { Download } from 'lucide-react';
import { Button } from '@/components/ui/button';

type QueryValue = string | number | boolean | number[] | null | undefined;

/**
 * The list's filters for its export (ADR-031): every filter the list
 * applied, without its page and page size -- the export reads every page.
 */
export function exportQuery(
    filters: Record<string, QueryValue>,
): Record<string, string | number | boolean | number[]> {
    return Object.fromEntries(
        Object.entries(filters).filter(
            (entry): entry is [string, string | number | boolean | number[]] =>
                entry[0] !== 'page' &&
                entry[0] !== 'limit' &&
                entry[1] !== undefined &&
                entry[1] !== null &&
                entry[1] !== '',
        ),
    );
}

/**
 * Downloads the list as CSV, built by the gateway (ADR-031). A plain link:
 * the response is a file, not an Inertia page. A refused export (too many
 * rows) comes back to the list with a toast.
 */
export function ExportCsvButton({ href }: { href: string }) {
    return (
        <Button size="sm" variant="outline" asChild>
            <a href={href} download>
                <Download className="size-4" />
                Export CSV
            </a>
        </Button>
    );
}
