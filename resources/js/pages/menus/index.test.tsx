import { router } from '@inertiajs/react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import MenusIndex from '@/pages/menus/index';
import type { MasterMenu } from '@/types/master-menu';
import type { Menu } from '@/types/menu';
import type { Pagination } from '@/types/pagination';

afterEach(() => {
    vi.restoreAllMocks();
});

const pagination: Pagination = {
    page: 1,
    limit: 20,
    count: 1,
    lastPage: 1,
    hasMorePages: false,
};

const coreMenu: Menu = {
    provider: 'esb',
    id: 5,
    code: 'MN-01',
    name: 'Nasi Goreng',
    shortName: 'Nasgor',
    categoryId: null,
    categoryName: 'Makanan',
    categoryDetailId: null,
    categoryDetailName: null,
    notes: null,
};

const omsMenu = {
    provider: 'esb',
    id: 7,
    code: 'NG-01',
    name: 'Nasi Goreng Spesial',
    categoryName: 'Food',
    taxLabel: 'PB1',
    active: true,
    templates: [],
} as unknown as MasterMenu;

describe('Menus (one list, two sources — ADR-030)', () => {
    it('shows the source tabs with Core current by default', () => {
        render(
            <MenusIndex
                source="core"
                menus={[coreMenu]}
                pagination={pagination}
                filters={{}}
            />,
        );

        const tabs = screen.getByRole('navigation', { name: 'Menu source' });
        expect(
            within(tabs).getByRole('link', { name: 'ESB Core' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            within(tabs).getByRole('link', { name: 'ESB OMS' }),
        ).toHaveAttribute('href', '/menus?source=oms');

        const table = screen.getByRole('table', { name: 'ESB Core menus' });
        expect(within(table).getByText('Nasgor')).toBeVisible();
        expect(screen.getByRole('searchbox')).toHaveAccessibleName('Menu name');
    });

    it('lists OMS menus with a link to their detail', () => {
        render(
            <MenusIndex
                source="oms"
                menus={[omsMenu]}
                pagination={pagination}
                filters={{ source: 'oms', page: 1 }}
            />,
        );

        const tabs = screen.getByRole('navigation', { name: 'Menu source' });
        expect(
            within(tabs).getByRole('link', { name: 'ESB OMS' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            within(tabs).getByRole('link', { name: 'ESB Core' }),
        ).toHaveAttribute('href', '/menus');

        const table = screen.getByRole('table', { name: 'ESB OMS menus' });
        expect(
            within(table).getByRole('link', { name: 'NG-01' }),
        ).toHaveAttribute('href', '/master-menus/NG-01');
        // OMS filters by code only.
        expect(screen.getByRole('searchbox')).toHaveAccessibleName('Menu code');
    });

    it('keeps the OMS tab when filtering it', async () => {
        const get = vi.spyOn(router, 'get').mockImplementation(() => {});
        render(
            <MenusIndex
                source="oms"
                menus={[omsMenu]}
                pagination={pagination}
                filters={{ source: 'oms', page: 1 }}
            />,
        );

        await userEvent.type(screen.getByRole('searchbox'), 'NG{Enter}');

        // The tab is in the URL too, so a reset stays on it.
        expect(get).toHaveBeenCalledExactlyOnceWith(
            '/menus?source=oms',
            { source: 'oms', menuCode: 'NG' },
            { preserveState: true, replace: true },
        );
    });
});
