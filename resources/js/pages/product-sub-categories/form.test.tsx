import { router } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import SubCategoryForm from '@/pages/product-sub-categories/form';
import type { ProductSubCategory } from '@/types/master-data';

afterEach(() => {
    vi.restoreAllMocks();
});

const subCategory: ProductSubCategory = {
    provider: 'esb',
    id: 7,
    name: 'Beras',
    deadStockDays: 90,
    notes: null,
    active: true,
    createdBy: null,
    createdAt: null,
    editedBy: null,
    editedAt: null,
};

describe('Sub-category form', () => {
    it('creates a sub-category with a dead stock threshold in days', async () => {
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        render(<SubCategoryForm subCategory={null} />);

        await userEvent.type(screen.getByLabelText('Name'), 'Gula');
        await userEvent.type(
            screen.getByLabelText('Dead stock after (days)'),
            '30',
        );
        await userEvent.click(
            screen.getByRole('button', { name: 'Create sub-category' }),
        );

        const [url, options] = visit.mock.calls[0];
        expect(url).toEqual(expect.stringMatching(/\/product-sub-categories$/));
        expect(options).toMatchObject({
            method: 'post',
            data: { name: 'Gula', deadStockDays: 30, notes: '' },
        });
    });

    it('edits a sub-category', async () => {
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        render(<SubCategoryForm subCategory={subCategory} />);

        expect(screen.getByLabelText('Dead stock after (days)')).toHaveValue(
            90,
        );
        await userEvent.click(
            screen.getByRole('button', { name: 'Save changes' }),
        );

        const [url, options] = visit.mock.calls[0];
        expect(url).toEqual(
            expect.stringMatching(/\/product-sub-categories\/7$/),
        );
        expect(options).toMatchObject({ method: 'put' });
    });
});
