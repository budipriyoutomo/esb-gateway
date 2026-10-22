import { router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import {
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';

export type DataTableColumn<T> = {
    key: string;
    header: ReactNode;
    cell: (row: T) => ReactNode;
    /**
     * The column's name on a narrow screen, where each row is a card and
     * each cell shows its column's name. Defaults to `header` when that is
     * plain text.
     */
    label?: string;
    /** Numbers are right-aligned (header and cells) with tabular figures. */
    align?: 'left' | 'right';
    className?: string;
};

type Props<T> = {
    /** Accessible name of the table (visually hidden). */
    caption: string;
    columns: DataTableColumn<T>[];
    rows: T[];
    rowKey: (row: T, index: number) => string;
    /** What to say, and what to do next, when there are no rows. */
    empty: ReactNode;
    loading?: boolean;
    /** A message fit for the user (never a raw ESB message). */
    error?: string | null;
    /**
     * Makes a row open its detail page on click. Keyboard users use the link
     * in the row, which every row with a detail page must also have.
     */
    rowHref?: (row: T) => string | null;
};

const SKELETON_ROWS = 5;

// Below 640px each row is a card: the header row is visually hidden (still
// read by screen readers) and each cell shows its column's name from
// data-label. Explicit roles keep the table semantics that display: block
// drops in some browsers (TODO-modules.md UI-5).
const CARD_ROW = 'max-sm:block max-sm:px-3 max-sm:py-2';
const CARD_CELL =
    'max-sm:flex max-sm:items-start max-sm:justify-between max-sm:gap-4 max-sm:px-0 max-sm:py-1 max-sm:text-right max-sm:whitespace-normal max-sm:before:shrink-0 max-sm:before:text-left max-sm:before:font-medium max-sm:before:text-muted-foreground max-sm:before:content-[attr(data-label)]';
const CARD_FULL_WIDTH_CELL = 'max-sm:block max-sm:whitespace-normal';

/**
 * The list table used by every index page: sticky header, right-aligned
 * numbers, clickable rows, and explicit loading, empty and error states.
 */
export function DataTable<T>({
    caption,
    columns,
    rows,
    rowKey,
    empty,
    loading = false,
    error = null,
    rowHref,
}: Props<T>) {
    const align = (column: DataTableColumn<T>) => column.align ?? 'left';
    const label = (column: DataTableColumn<T>) =>
        column.label ??
        (typeof column.header === 'string' ? column.header : undefined);

    function body() {
        if (error) {
            return (
                <TableRow role="row" className={CARD_ROW}>
                    <TableCell
                        role="cell"
                        colSpan={columns.length}
                        className={cn('py-8 text-center', CARD_FULL_WIDTH_CELL)}
                    >
                        <p role="alert" className="text-destructive-foreground">
                            {error}
                        </p>
                    </TableCell>
                </TableRow>
            );
        }

        if (loading) {
            return Array.from({ length: SKELETON_ROWS }, (_, index) => (
                <TableRow
                    key={`skeleton-${index}`}
                    role="row"
                    className={CARD_ROW}
                >
                    {columns.map((column) => (
                        <TableCell
                            key={column.key}
                            role="cell"
                            className={CARD_FULL_WIDTH_CELL}
                        >
                            <Skeleton className="h-4 w-full max-w-32" />
                        </TableCell>
                    ))}
                </TableRow>
            ));
        }

        if (rows.length === 0) {
            return (
                <TableRow role="row" className={CARD_ROW}>
                    <TableCell
                        role="cell"
                        colSpan={columns.length}
                        className={cn(
                            'text-muted-foreground py-8 text-center whitespace-normal',
                            CARD_FULL_WIDTH_CELL,
                        )}
                    >
                        {empty}
                    </TableCell>
                </TableRow>
            );
        }

        return rows.map((row, index) => {
            const href = rowHref?.(row) ?? null;

            return (
                <TableRow
                    key={rowKey(row, index)}
                    role="row"
                    className={cn(CARD_ROW, href !== null && 'cursor-pointer')}
                    onClick={
                        href === null
                            ? undefined
                            : (event) => {
                                  // A link or button inside the row handles
                                  // its own click.
                                  if (
                                      (event.target as HTMLElement).closest(
                                          'a, button',
                                      )
                                  ) {
                                      return;
                                  }

                                  router.visit(href);
                              }
                    }
                >
                    {columns.map((column) => (
                        <TableCell
                            key={column.key}
                            role="cell"
                            data-align={align(column)}
                            data-label={label(column)}
                            className={cn(
                                align(column) === 'right' &&
                                    'text-right tabular-nums',
                                CARD_CELL,
                                column.className,
                            )}
                        >
                            {column.cell(row)}
                        </TableCell>
                    ))}
                </TableRow>
            );
        });
    }

    return (
        <div className="border-sidebar-border/70 dark:border-sidebar-border max-h-[70vh] overflow-auto rounded-xl border">
            <table
                role="table"
                aria-busy={loading || undefined}
                className="w-full caption-bottom text-sm max-sm:block"
            >
                <caption className="sr-only">{caption}</caption>
                <TableHeader
                    role="rowgroup"
                    className="bg-background sticky top-0 z-10 max-sm:sr-only"
                >
                    <TableRow role="row">
                        {columns.map((column) => (
                            <TableHead
                                key={column.key}
                                role="columnheader"
                                data-align={align(column)}
                                className={cn(
                                    align(column) === 'right' && 'text-right',
                                )}
                            >
                                {column.header}
                            </TableHead>
                        ))}
                    </TableRow>
                </TableHeader>
                <TableBody role="rowgroup" className="max-sm:block">
                    {body()}
                </TableBody>
            </table>
        </div>
    );
}
