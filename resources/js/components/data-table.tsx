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

    function body() {
        if (error) {
            return (
                <TableRow>
                    <TableCell
                        colSpan={columns.length}
                        className="py-8 text-center"
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
                <TableRow key={`skeleton-${index}`}>
                    {columns.map((column) => (
                        <TableCell key={column.key}>
                            <Skeleton className="h-4 w-full max-w-32" />
                        </TableCell>
                    ))}
                </TableRow>
            ));
        }

        if (rows.length === 0) {
            return (
                <TableRow>
                    <TableCell
                        colSpan={columns.length}
                        className="text-muted-foreground py-8 text-center whitespace-normal"
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
                    className={cn(href !== null && 'cursor-pointer')}
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
                            data-align={align(column)}
                            className={cn(
                                align(column) === 'right' &&
                                    'text-right tabular-nums',
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
                aria-busy={loading || undefined}
                className="w-full caption-bottom text-sm"
            >
                <caption className="sr-only">{caption}</caption>
                <TableHeader className="bg-background sticky top-0 z-10">
                    <TableRow>
                        {columns.map((column) => (
                            <TableHead
                                key={column.key}
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
                <TableBody>{body()}</TableBody>
            </table>
        </div>
    );
}
