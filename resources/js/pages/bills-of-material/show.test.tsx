import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vite-plus/test';
import BillOfMaterialShow from '@/pages/bills-of-material/show';
import type { BillOfMaterial } from '@/types/bill-of-material';

function bom(overrides: Partial<BillOfMaterial> = {}): BillOfMaterial {
    return {
        provider: 'esb',
        id: 12,
        code: 'BOM-01',
        name: 'Nasi Goreng',
        typeId: null,
        typeName: 'Menu',
        accessType: 'All',
        productId: null,
        productCode: 'NG01',
        productName: 'Nasi Goreng',
        uomName: 'Portion',
        notes: null,
        active: true,
        materialCostTotal: 12000,
        otherCostTotal: 500,
        weightFactorTotal: null,
        createdBy: null,
        createdAt: null,
        editedBy: null,
        editedAt: null,
        lines: [
            {
                lineId: 1,
                productDetailId: 7,
                productCode: 'RC01',
                productName: 'Rice',
                uomName: 'Gram',
                qty: 200,
                uomQty: null,
                conversionQty: null,
                lastCost: 15,
                price: null,
                printGroup: null,
                tolerancePercent: null,
                yieldPercent: null,
                weightFactor: null,
                substitutes: [],
                manufacturingSubstitutes: [],
            },
        ],
        costs: [
            { accountNo: '6100', description: 'Gas', total: 500 },
            { accountNo: '6200', description: 'Packaging', total: 0 },
        ],
        replacements: [],
        ...overrides,
    };
}

describe('BillOfMaterialShow', () => {
    it('keeps the general fields above the tabs', () => {
        render(<BillOfMaterialShow bom={bom()} />);

        expect(
            screen.getByRole('heading', { level: 2, name: 'General' }),
        ).toBeVisible();
        expect(screen.getByText('Portion')).toBeVisible();
    });

    it('puts materials and other costs in tabs with their counts', () => {
        render(<BillOfMaterialShow bom={bom()} />);

        const tabs = within(
            screen.getByRole('tablist', { name: 'Bill of material sections' }),
        ).getAllByRole('tab');

        expect(tabs.map((tab) => tab.textContent)).toEqual([
            'Materials 1',
            'Other costs 2',
        ]);
        expect(screen.getByRole('tabpanel')).toHaveTextContent('Rice');
    });

    it('shows the other costs on their tab', async () => {
        const user = userEvent.setup();
        render(<BillOfMaterialShow bom={bom()} />);

        await user.click(screen.getByRole('tab', { name: 'Other costs 2' }));

        expect(screen.getByRole('tabpanel')).toHaveTextContent('Packaging');
    });

    it('adds a replacements tab only when there are some', () => {
        render(
            <BillOfMaterialShow
                bom={bom({
                    replacements: [
                        {
                            menuCode: 'NG01',
                            menuName: 'Nasi Goreng',
                            productCode: 'RC01',
                            productName: 'Rice',
                            replacementProductCode: 'RC02',
                            replacementProductName: 'Brown rice',
                            uomName: 'Gram',
                            qty: 200,
                        },
                    ],
                })}
            />,
        );

        expect(
            screen.getByRole('tab', { name: 'Replacements per menu 1' }),
        ).toBeVisible();
    });
});
