import { Button } from '@/components/ui/button';
import { formatQuantity } from '@/lib/format';
import type { Pagination } from '@/types/pagination';

type Props = {
    pagination: Pagination | null;
    /** Plural noun for the count, e.g. "purchase orders". */
    noun: string;
    onPageChange: (page: number) => void;
};

/** Previous / next paging under every list. */
export function ListPagination({ pagination, noun, onPageChange }: Props) {
    if (pagination === null) {
        return null;
    }

    const { page, lastPage, count, hasMorePages } = pagination;

    return (
        <nav
            aria-label="Pagination"
            className="flex flex-wrap items-center justify-between gap-4"
        >
            <p className="text-muted-foreground text-sm">
                Page {formatQuantity(page)} of {formatQuantity(lastPage)} —{' '}
                {formatQuantity(count)} {noun}
            </p>
            <div className="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    disabled={page <= 1}
                    onClick={() => {
                        onPageChange(page - 1);
                    }}
                >
                    Previous
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    disabled={!hasMorePages}
                    onClick={() => {
                        onPageChange(page + 1);
                    }}
                >
                    Next
                </Button>
            </div>
        </nav>
    );
}
