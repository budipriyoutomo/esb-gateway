import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vite-plus/test';
import MasterMenuShow from '@/pages/master-menus/show';
import type { MasterMenu } from '@/types/master-menu';

function menu(overrides: Partial<MasterMenu> = {}): MasterMenu {
    return {
        provider: 'esb',
        id: 1,
        code: 'PS01',
        name: 'Paket Separate',
        shortName: null,
        alternativeName: null,
        categoryName: 'Food',
        bomName: 'BOM Paket',
        active: true,
        taxLabel: 'PB1',
        otherTax: null,
        openPrice: null,
        printZeroValue: null,
        zeroValueText: null,
        salesAccount: null,
        cogsAccount: null,
        discountAccount: null,
        description: null,
        notes: null,
        buttonColor: null,
        separatePrintPackage: null,
        separateTaxCalculation: null,
        templates: [
            {
                templateId: 18,
                templateName: 'RX Rest',
                active: true,
                price: 20000,
            },
            { templateId: 307, templateName: 'Coba', active: false, price: 0 },
        ],
        packageGroups: [],
        extras: [
            {
                extraId: 2649,
                name: 'Beef Burger',
                active: true,
                minQty: 0,
                maxQty: 10,
                price: 1000,
            },
        ],
        icons: [],
        tags: [],
        relatedMenus: [],
        ...overrides,
    };
}

describe('MasterMenuShow', () => {
    it('keeps the general fields above the tabs', () => {
        render(<MasterMenuShow menu={menu()} />);

        expect(
            screen.getByRole('heading', { level: 2, name: 'General' }),
        ).toBeVisible();
        expect(screen.getByText('BOM Paket')).toBeVisible();
    });

    it('puts prices, package and extras in tabs with their counts', () => {
        render(<MasterMenuShow menu={menu()} />);

        const tabs = within(
            screen.getByRole('tablist', { name: 'Menu sections' }),
        ).getAllByRole('tab');

        expect(tabs.map((tab) => tab.textContent)).toEqual([
            'Prices 2',
            'Package 0',
            'Extras 1',
        ]);
        expect(screen.getByRole('tabpanel')).toHaveTextContent('RX Rest');
    });

    it('shows the extras on their tab', async () => {
        const user = userEvent.setup();
        render(<MasterMenuShow menu={menu()} />);

        await user.click(screen.getByRole('tab', { name: 'Extras 1' }));

        expect(screen.getByRole('tabpanel')).toHaveTextContent('Beef Burger');
    });

    it('adds a related menus tab only when there are some', () => {
        render(
            <MasterMenuShow
                menu={menu({
                    relatedMenus: [{ menuId: 9, name: 'Es Teh', code: 'ET01' }],
                })}
            />,
        );

        expect(
            screen.getByRole('tab', { name: 'Related menus 1' }),
        ).toBeVisible();
    });
});
