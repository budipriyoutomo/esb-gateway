import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vite-plus/test';
import { ListPagination } from '@/components/list-pagination';
import type { Pagination } from '@/types/pagination';

const middle: Pagination = {
    page: 2,
    limit: 20,
    count: 92,
    lastPage: 5,
    hasMorePages: true,
};

describe('ListPagination', () => {
    it('summarises the position in id-ID numbers', () => {
        render(
            <ListPagination
                pagination={{ ...middle, count: 1234, lastPage: 62 }}
                noun="purchase orders"
                onPageChange={() => {}}
            />,
        );

        expect(
            screen.getByText('Page 2 of 62 — 1.234 purchase orders'),
        ).toBeInTheDocument();
    });

    it('moves to the previous and next page', async () => {
        const onPageChange = vi.fn();
        render(
            <ListPagination
                pagination={middle}
                noun="purchase orders"
                onPageChange={onPageChange}
            />,
        );

        await userEvent.click(screen.getByRole('button', { name: 'Previous' }));
        await userEvent.click(screen.getByRole('button', { name: 'Next' }));

        expect(onPageChange.mock.calls).toEqual([[1], [3]]);
    });

    it('disables Previous on the first page and Next on the last', () => {
        const { rerender } = render(
            <ListPagination
                pagination={{ ...middle, page: 1 }}
                noun="rows"
                onPageChange={() => {}}
            />,
        );

        expect(screen.getByRole('button', { name: 'Previous' })).toBeDisabled();
        expect(screen.getByRole('button', { name: 'Next' })).toBeEnabled();

        rerender(
            <ListPagination
                pagination={{ ...middle, page: 5, hasMorePages: false }}
                noun="rows"
                onPageChange={() => {}}
            />,
        );

        expect(screen.getByRole('button', { name: 'Previous' })).toBeEnabled();
        expect(screen.getByRole('button', { name: 'Next' })).toBeDisabled();
    });

    it('is a labelled navigation landmark', () => {
        render(
            <ListPagination
                pagination={middle}
                noun="rows"
                onPageChange={() => {}}
            />,
        );

        expect(
            screen.getByRole('navigation', { name: 'Pagination' }),
        ).toBeInTheDocument();
    });

    it('renders nothing without pagination', () => {
        const { container } = render(
            <ListPagination
                pagination={null}
                noun="rows"
                onPageChange={() => {}}
            />,
        );

        expect(container).toBeEmptyDOMElement();
    });
});
