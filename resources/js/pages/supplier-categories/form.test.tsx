import { router } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import SupplierCategoryForm from '@/pages/supplier-categories/form';

afterEach(() => {
    vi.restoreAllMocks();
});

describe('Supplier category form', () => {
    it('creates a supplier category', async () => {
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        render(<SupplierCategoryForm category={null} />);

        await userEvent.type(screen.getByLabelText('Name'), 'Bahan Pokok');
        await userEvent.click(
            screen.getByRole('button', { name: 'Create supplier category' }),
        );

        const [url, options] = visit.mock.calls[0];
        expect(url).toEqual(expect.stringMatching(/\/supplier-categories$/));
        expect(options).toMatchObject({
            method: 'post',
            data: { name: 'Bahan Pokok' },
        });
    });

    it('renames a supplier category', async () => {
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        render(
            <SupplierCategoryForm
                category={{
                    provider: 'esb',
                    id: 5,
                    name: 'Bahan Pokok',
                    active: true,
                    createdBy: null,
                    createdAt: null,
                    editedBy: null,
                    editedAt: null,
                }}
            />,
        );

        await userEvent.clear(screen.getByLabelText('Name'));
        await userEvent.type(screen.getByLabelText('Name'), 'Sembako');
        await userEvent.click(
            screen.getByRole('button', { name: 'Save changes' }),
        );

        const [url, options] = visit.mock.calls[0];
        expect(url).toEqual(expect.stringMatching(/\/supplier-categories\/5$/));
        expect(options).toMatchObject({
            method: 'put',
            data: { name: 'Sembako' },
        });
    });
});
