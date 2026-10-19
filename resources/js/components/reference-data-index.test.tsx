import { router } from '@inertiajs/react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { ReferenceDataIndex } from '@/components/reference-data-index';

type Row = { id: number | null; name: string; active: boolean | null };

afterEach(() => {
    vi.restoreAllMocks();
});

const rows: Row[] = [
    { id: 4, name: 'KG', active: true },
    { id: null, name: 'Orphan', active: false },
];

function renderIndex(
    props: Partial<ComponentProps<typeof ReferenceDataIndex<Row>>> = {},
) {
    const get = vi.spyOn(router, 'get').mockImplementation(() => {});
    const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});

    render(
        <ReferenceDataIndex<Row>
            id="units"
            title="Units"
            description="Units of measure, read live from the ERP."
            url="/units"
            filters={{ page: 1, limit: 20 }}
            pagination={{
                page: 1,
                limit: 20,
                count: 2,
                lastPage: 1,
                hasMorePages: false,
            }}
            rows={rows}
            columns={[{ key: 'name', header: 'Name', cell: (row) => row.name }]}
            detailUrl={(id) => `/units/${id}`}
            noun="units"
            searchPlaceholder="Unit or metric name"
            {...props}
        />,
    );

    return { get, visit };
}

describe('ReferenceDataIndex', () => {
    it('lists the rows with an active/inactive status column', () => {
        renderIndex();

        const table = screen.getByRole('table', { name: 'Units' });

        expect(within(table).getByText('KG')).toBeVisible();
        expect(within(table).getByText('Active')).toBeVisible();
        expect(within(table).getByText('Inactive')).toBeVisible();
    });

    it('opens a row that has an id, and only those', async () => {
        const { visit } = renderIndex();

        await userEvent.click(screen.getByRole('cell', { name: 'KG' }));
        await userEvent.click(screen.getByRole('cell', { name: 'Orphan' }));

        expect(visit).toHaveBeenCalledExactlyOnceWith('/units/4');
    });

    it('sends the search and status, dropping empty ones', async () => {
        const { get } = renderIndex();

        await userEvent.type(screen.getByRole('searchbox'), 'kg');
        await userEvent.click(
            screen.getByRole('button', { name: 'More filters' }),
        );
        await userEvent.selectOptions(
            screen.getByLabelText('Status'),
            'Inactive',
        );
        await userEvent.click(screen.getByRole('button', { name: 'Apply' }));

        expect(get).toHaveBeenCalledExactlyOnceWith(
            '/units',
            { search: 'kg', flagActive: 0 },
            { preserveState: true, replace: true },
        );
    });

    it('offers an extra choice filter when the list has one', async () => {
        const { get } = renderIndex({
            extraFilter: {
                key: 'categoryTypeId',
                label: 'Type',
                options: { 1: 'Inventory', 3: 'Asset' },
            },
        });

        await userEvent.click(
            screen.getByRole('button', { name: 'More filters' }),
        );
        await userEvent.selectOptions(screen.getByLabelText('Type'), 'Asset');
        await userEvent.click(screen.getByRole('button', { name: 'Apply' }));

        expect(get.mock.calls[0][1]).toEqual({ categoryTypeId: 3 });
    });

    it('shows applied filters as chips, and clears a removed one', async () => {
        const { get } = renderIndex({
            filters: {
                page: 2,
                limit: 20,
                search: 'kg',
                flagActive: 1,
                categoryTypeId: 3,
            },
            extraFilter: {
                key: 'categoryTypeId',
                label: 'Type',
                options: { 1: 'Inventory', 3: 'Asset' },
            },
        });

        expect(
            screen.getByRole('button', { name: 'Remove filter: Active only' }),
        ).toBeVisible();
        expect(
            screen.getByRole('button', { name: 'Remove filter: Type: Asset' }),
        ).toBeVisible();

        await userEvent.click(
            screen.getByRole('button', { name: 'Remove filter: “kg”' }),
        );

        expect(get.mock.calls[0][1]).toEqual({
            limit: 20,
            flagActive: 1,
            categoryTypeId: 3,
        });
        expect(screen.getByRole('searchbox')).toHaveValue('');
    });

    it('pages through the list', async () => {
        const { get } = renderIndex({
            filters: { page: 1, limit: 20, search: 'kg' },
            pagination: {
                page: 1,
                limit: 20,
                count: 45,
                lastPage: 3,
                hasMorePages: true,
            },
        });

        await userEvent.click(screen.getByRole('button', { name: 'Next' }));

        expect(get).toHaveBeenCalledExactlyOnceWith(
            '/units',
            { page: 2, limit: 20, search: 'kg' },
            { preserveState: true, preserveScroll: true },
        );
    });
});
