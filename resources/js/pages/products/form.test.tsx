import { router } from '@inertiajs/react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import ProductForm from '@/pages/products/form';
import type { ProductDetail, ProductUnit } from '@/types/master-data';

afterEach(() => {
    vi.restoreAllMocks();
});

const unit = (overrides: Partial<ProductUnit>): ProductUnit => ({
    productDetailId: 501,
    uomId: 4,
    uomName: 'KG',
    qty: 1,
    isBase: true,
    purchasable: true,
    saleable: false,
    stocked: true,
    transferable: true,
    sku: 'BRS-KG',
    basePrice: 15000,
    weight: 1,
    menuName: null,
    menuId: null,
    cubication: null,
    active: true,
    ...overrides,
});

const product: ProductDetail = {
    provider: 'esb',
    id: 50,
    name: 'Beras Premium',
    code: 'BB-001',
    categoryId: 3,
    categoryName: 'Bahan Baku',
    subCategoryId: 7,
    subCategoryName: 'Beras',
    bomId: null,
    bomName: null,
    purchasable: true,
    saleable: false,
    requestable: true,
    vat: true,
    luxury: false,
    receiptTolerance: 2.5,
    coretaxCode: null,
    coretaxCodeId: null,
    customFields: {},
    notes: null,
    active: true,
    units: [
        unit({}),
        unit({
            productDetailId: 502,
            uomId: 9,
            uomName: 'SAK',
            qty: 25,
            isBase: false,
            sku: null,
        }),
    ],
    createdBy: null,
    createdAt: null,
    editedBy: null,
    editedAt: null,
};

describe('Product form', () => {
    it('starts a new product with one base unit', () => {
        render(<ProductForm product={null} />);

        expect(
            screen.getByRole('heading', { name: 'New product' }),
        ).toBeVisible();
        const units = screen.getByRole('table', { name: 'Units' });
        expect(within(units).getAllByRole('row')).toHaveLength(2);
        expect(screen.getByLabelText('Base unit, line 1')).toBeChecked();
    });

    it('sends back each unit with its id, and one base unit', async () => {
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        render(<ProductForm product={product} />);

        expect(screen.getByLabelText('Category')).toHaveValue('Bahan Baku');
        expect(screen.getByLabelText('Unit, line 2')).toHaveValue('SAK');

        // Choosing another base unit clears the first.
        await userEvent.click(screen.getByLabelText('Base unit, line 2'));
        expect(screen.getByLabelText('Base unit, line 1')).not.toBeChecked();

        await userEvent.click(
            screen.getByRole('button', { name: 'Save changes' }),
        );

        const [url, options] = visit.mock.calls[0];
        expect(url).toEqual(expect.stringMatching(/\/products\/50$/));
        expect(options).toMatchObject({
            method: 'put',
            data: {
                name: 'Beras Premium',
                categoryId: 3,
                subCategoryId: 7,
                receiptTolerance: 2.5,
                units: [
                    { productDetailId: 501, uomId: 4, isBase: false },
                    { productDetailId: 502, uomId: 9, qty: 25, isBase: true },
                ],
            },
        });
    });

    it('adds and removes units', async () => {
        render(<ProductForm product={null} />);
        const units = screen.getByRole('table', { name: 'Units' });

        await userEvent.click(screen.getByRole('button', { name: 'Add unit' }));
        expect(within(units).getAllByRole('row')).toHaveLength(3);
        expect(screen.getByLabelText('Base unit, line 2')).not.toBeChecked();

        await userEvent.click(
            screen.getByRole('button', { name: 'Remove line 2' }),
        );
        expect(within(units).getAllByRole('row')).toHaveLength(2);
    });
});
