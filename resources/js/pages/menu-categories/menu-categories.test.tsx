import { router } from '@inertiajs/react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import MenuCategoryForm from '@/pages/menu-categories/form';
import MenuCategoryIndex from '@/pages/menu-categories/index';
import MenuCategoryShow from '@/pages/menu-categories/show';
import type { Permission } from '@/types';
import type { MenuCategory } from '@/types/pos';

const granted: { permissions: Permission[] } = { permissions: [] };

vi.mock('@/hooks/use-permissions', () => ({
    usePermissions: () => ({
        can: (permission: Permission) =>
            granted.permissions.includes(permission),
    }),
}));

beforeEach(() => {
    granted.permissions = [];
});

afterEach(() => {
    vi.restoreAllMocks();
});

const category: MenuCategory = {
    provider: 'esb',
    id: 16,
    name: 'Food-Int',
    code: 'FINT',
    salesAccount: 'Sales - Food',
    cogsAccount: 'COGS - Food',
    discountAccount: 'Food Discount',
    description: 'Makanan Cita Rasa Indonesia',
    notes: null,
    statusName: 'Active',
    buttonColor: null,
    details: [
        {
            id: 34,
            name: 'Main Course-Int',
            code: null,
            description: null,
            maxOrderQty: 1,
            statusName: 'Active',
            buttonColor: '#a4ff70',
        },
    ],
};

const pagination = {
    page: 2,
    limit: 10,
    count: 16,
    lastPage: 2,
    hasMorePages: false,
};

describe('Menu category list', () => {
    it('links each category with the page it was listed on', () => {
        render(
            <MenuCategoryIndex
                categories={[category]}
                pagination={pagination}
                filters={{ page: 2 }}
            />,
        );

        const link = screen.getByRole('link', { name: 'Food-Int' });
        expect(link).toHaveAttribute(
            'href',
            expect.stringMatching(/\/menu-categories\/16\?page=2$/),
        );
        expect(screen.getByText('Main Course-Int')).toBeVisible();
    });

    it('offers a new category only to whoever manages them', () => {
        const { rerender } = render(
            <MenuCategoryIndex
                categories={[]}
                pagination={null}
                filters={{}}
            />,
        );

        expect(
            screen.queryByRole('link', { name: 'New menu category' }),
        ).not.toBeInTheDocument();

        granted.permissions = ['esb.invoke', 'menu-categories.manage'];
        rerender(
            <MenuCategoryIndex
                categories={[]}
                pagination={null}
                filters={{}}
            />,
        );

        expect(
            screen.getByRole('link', { name: 'New menu category' }),
        ).toBeVisible();
    });
});

describe('Menu category detail', () => {
    it('shows the accounts and the details, and edit to managers', () => {
        granted.permissions = ['esb.invoke', 'menu-categories.manage'];
        render(<MenuCategoryShow category={category} page={2} />);

        expect(screen.getByRole('heading', { name: 'Food-Int' })).toBeVisible();
        expect(screen.getByText('Sales - Food')).toBeVisible();
        const details = screen.getByRole('table', { name: 'Category details' });
        expect(within(details).getByText('Main Course-Int')).toBeVisible();
        expect(screen.getByRole('link', { name: 'Edit' })).toHaveAttribute(
            'href',
            expect.stringMatching(/\/menu-categories\/16\/edit\?page=2$/),
        );
    });
});

describe('Menu category form', () => {
    it('starts a new category with one detail', () => {
        render(<MenuCategoryForm category={null} page={null} />);

        expect(
            screen.getByRole('heading', { name: 'New menu category' }),
        ).toBeVisible();
        expect(screen.getByLabelText('Detail name, row 1')).toBeVisible();
    });

    it('keeps every current detail and sends the page it was found on', async () => {
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        render(<MenuCategoryForm category={category} page={2} />);

        // A current detail is removed in the ERP, not here.
        expect(
            screen.getByRole('button', { name: 'Remove detail 1' }),
        ).toBeDisabled();

        await userEvent.click(
            screen.getByRole('button', { name: 'Add detail' }),
        );
        await userEvent.type(
            screen.getByLabelText('Detail name, row 2'),
            'Dessert-Int',
        );
        expect(
            screen.getByRole('button', { name: 'Remove detail 2' }),
        ).toBeEnabled();

        await userEvent.click(
            screen.getByRole('button', { name: 'Save changes' }),
        );

        const [url, options] = visit.mock.calls[0] as [
            string,
            { method: string; data: unknown },
        ];
        expect(url).toMatch(/\/menu-categories\/16$/);
        expect(options.method).toBe('put');
        expect(options.data).toEqual({
            page: 2,
            name: 'Food-Int',
            code: 'FINT',
            salesAccount: 'Sales - Food',
            cogsAccount: 'COGS - Food',
            discountAccount: 'Food Discount',
            description: 'Makanan Cita Rasa Indonesia',
            buttonColor: '',
            details: [
                {
                    id: 34,
                    name: 'Main Course-Int',
                    code: '',
                    description: '',
                    maxOrderQty: 1,
                    buttonColor: '#a4ff70',
                },
                {
                    id: null,
                    name: 'Dessert-Int',
                    code: '',
                    description: '',
                    maxOrderQty: null,
                    buttonColor: '',
                },
            ],
        });
    });
});
