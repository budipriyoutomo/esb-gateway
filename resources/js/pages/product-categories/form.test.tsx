import { router } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import ProductCategoryForm from '@/pages/product-categories/form';
import type { ProductCategory } from '@/types/master-data';

afterEach(() => {
    vi.restoreAllMocks();
});

const category: ProductCategory = {
    provider: 'esb',
    id: 3,
    name: 'Bahan Baku',
    code: 'BB',
    typeId: 1,
    typeName: 'Inventory',
    notes: 'Raw materials',
    active: true,
    coaNo: '1301',
    cogsCoaNo: '5101',
    accrualBasisCoaNo: null,
    opnameCoaNo: '5901',
    productSalesCoaNo: null,
    depreciationCoaNo: null,
    depreciationExpenseCoaNo: null,
    depreciationMonths: null,
    fiscalDepreciationMonths: null,
    depreciationTypeId: null,
    fiscalDepreciationTypeId: null,
    assetTax: false,
    createdBy: null,
    createdAt: null,
    editedBy: null,
    editedAt: null,
};

describe('Product category form', () => {
    it('starts a new category empty', () => {
        render(<ProductCategoryForm category={null} />);

        expect(
            screen.getByRole('heading', { name: 'New category' }),
        ).toBeVisible();
        expect(screen.getByLabelText('Name')).toHaveValue('');
        expect(screen.getByLabelText('Asset tax')).not.toBeChecked();
    });

    it('sends back every account the category had, changed or not', async () => {
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        render(<ProductCategoryForm category={category} />);

        expect(screen.getByLabelText('Type')).toHaveValue('Inventory');
        expect(screen.getByLabelText('Inventory account')).toHaveValue('1301');

        await userEvent.clear(screen.getByLabelText('Code'));
        await userEvent.type(screen.getByLabelText('Code'), 'BBK');
        await userEvent.click(
            screen.getByRole('button', { name: 'Save changes' }),
        );

        const [url, options] = visit.mock.calls[0];
        expect(url).toEqual(expect.stringMatching(/\/product-categories\/3$/));
        expect(options).toMatchObject({
            method: 'put',
            data: {
                name: 'Bahan Baku',
                code: 'BBK',
                typeId: 1,
                coaNo: '1301',
                cogsCoaNo: '5101',
                opnameCoaNo: '5901',
                accrualBasisCoaNo: null,
                depreciationMonths: null,
                assetTax: false,
            },
        });
    });
});
