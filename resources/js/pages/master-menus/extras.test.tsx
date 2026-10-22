import { render, screen, within } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import MasterMenuExtras from '@/pages/master-menus/extras';
import type { Pagination } from '@/types/pagination';

const pagination: Pagination = {
    page: 1,
    limit: 20,
    count: 45,
    lastPage: 3,
    hasMorePages: true,
};

const extra = {
    menuCode: 'PS01',
    menuName: 'Paket Separate',
    extraId: 2649,
    name: 'Beef Burger',
    active: true,
    minQty: 0,
    maxQty: 10,
    price: 1000,
};

describe('MasterMenuExtras', () => {
    it('lists each extra with a link to its menu', () => {
        render(
            <MasterMenuExtras
                extras={[extra]}
                pagination={pagination}
                filters={{}}
            />,
        );

        expect(
            screen.getByRole('heading', { level: 1, name: 'Menu extras' }),
        ).toBeVisible();

        const table = screen.getByRole('table', { name: 'Menu extras' });

        expect(
            within(table).getByRole('link', { name: 'PS01' }),
        ).toHaveAttribute('href', '/master-menus/PS01');
        expect(within(table).getByText('Beef Burger')).toBeVisible();
        expect(within(table).getByText('Paket Separate')).toBeVisible();
        expect(
            within(table).getByText('Active').closest('[data-tone]'),
        ).not.toBeNull();
    });

    it('says the list covers one page of menus, not every extra', () => {
        render(
            <MasterMenuExtras
                extras={[extra]}
                pagination={pagination}
                filters={{}}
            />,
        );

        expect(
            within(
                screen.getByRole('navigation', { name: 'Pagination' }),
            ).getByText(/45 menus/),
        ).toBeVisible();
    });

    it('explains an empty page', () => {
        render(<MasterMenuExtras extras={[]} pagination={null} filters={{}} />);

        expect(
            screen.getByText('No extras on this page of menus.'),
        ).toBeVisible();
    });
});
