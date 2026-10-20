import { router } from '@inertiajs/react';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import PricelistForm from '@/pages/pricelists/form';
import type { TempPricelist } from '@/types/master-data';

const prices = vi.hoisted(() => ({
    fetchLastPrices: vi.fn(),
    fetchPurchasePrices: vi.fn(),
}));

vi.mock('@/lib/pricelist-prices', () => prices);

beforeEach(() => {
    prices.fetchLastPrices.mockReset().mockResolvedValue({});
    prices.fetchPurchasePrices.mockReset().mockResolvedValue([]);
});

afterEach(() => {
    vi.restoreAllMocks();
});

const proposal: TempPricelist = {
    provider: 'esb',
    number: 'TPL202603200001',
    supplierId: 10,
    supplierName: 'CV Maju Jaya',
    currencyId: 1,
    currencyName: 'IDR',
    priceDate: '2026-04-01',
    expireDate: '2026-06-30',
    statusId: 38,
    statusName: 'Waiting For Approval',
    branches: { all: false, names: ['Jakarta Pusat'], ids: [1] },
    lines: [
        {
            productDetailId: 501,
            productCode: 'BB-001',
            productName: 'Beras Premium',
            unitName: 'KG',
            price: 15000,
            lastPrice: 14500,
        },
    ],
    createdBy: null,
    createdAt: null,
    editedBy: null,
    editedAt: null,
    rejectedBy: null,
    rejectedAt: null,
};

describe('Pricelist form', () => {
    it('starts a proposal for every branch with one empty line', async () => {
        render(<PricelistForm pricelist={null} />);

        expect(
            screen.getByRole('heading', { name: 'New pricelist' }),
        ).toBeVisible();
        expect(screen.getByLabelText('All branches')).toBeChecked();

        const lines = screen.getByRole('table', { name: 'Prices' });
        expect(within(lines).getAllByRole('row')).toHaveLength(2);

        await userEvent.click(
            screen.getByRole('button', { name: 'Add product' }),
        );
        expect(within(lines).getAllByRole('row')).toHaveLength(3);

        await userEvent.click(
            within(lines).getAllByRole('button', { name: /Remove line/ })[1],
        );
        expect(within(lines).getAllByRole('row')).toHaveLength(2);
    });

    it('sends back the proposal as it stands, with its branch ids', async () => {
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        render(<PricelistForm pricelist={proposal} />);

        expect(
            screen.getByRole('heading', { name: 'Edit TPL202603200001' }),
        ).toBeVisible();
        expect(screen.getByLabelText('All branches')).not.toBeChecked();
        expect(screen.getByText('Jakarta Pusat')).toBeVisible();

        await userEvent.clear(screen.getByLabelText('Price, line 1'));
        await userEvent.type(screen.getByLabelText('Price, line 1'), '15500');
        await userEvent.click(
            screen.getByRole('button', { name: 'Save changes' }),
        );

        const [url, options] = visit.mock.calls[0];
        expect(url).toEqual(
            expect.stringMatching(/\/pricelists\/temp\/TPL202603200001$/),
        );
        expect(options).toMatchObject({
            method: 'put',
            data: {
                supplierId: 10,
                currencyId: 1,
                priceDate: '2026-04-01',
                expireDate: '2026-06-30',
                branchIds: [1],
                lines: [{ productDetailId: 501, price: 15500 }],
            },
        });
    });

    it('sends no branches when it applies to every branch', async () => {
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        render(<PricelistForm pricelist={proposal} />);

        await userEvent.click(screen.getByLabelText('All branches'));
        await userEvent.click(
            screen.getByRole('button', { name: 'Save changes' }),
        );

        expect(visit.mock.calls[0][1]).toMatchObject({
            data: { branchIds: [] },
        });
    });

    it('shows the active price of each product for the supplier and date', async () => {
        prices.fetchLastPrices.mockResolvedValue({ 501: 14500 });
        render(<PricelistForm pricelist={proposal} />);

        await waitFor(() =>
            expect(
                screen.getByLabelText('Active price, line 1'),
            ).toHaveTextContent('14.500'),
        );
        expect(prices.fetchLastPrices).toHaveBeenCalledWith(
            {
                supplierId: 10,
                currencyId: 1,
                priceDate: '2026-04-01',
                productDetailIds: [501],
            },
            expect.anything(),
        );
    });

    it('shows recent purchase invoice prices of a product', async () => {
        prices.fetchLastPrices.mockResolvedValue({});
        prices.fetchPurchasePrices.mockResolvedValue([
            {
                invoiceNumber: 'PI-0001',
                invoiceDate: '2026-03-10',
                price: 14800,
                supplierId: 10,
                supplierName: 'CV Maju Jaya',
            },
        ]);
        render(<PricelistForm pricelist={proposal} />);

        await userEvent.click(
            screen.getByRole('button', { name: 'Purchase history, line 1' }),
        );

        const dialog = await screen.findByRole('dialog');
        expect(await within(dialog).findByText('PI-0001')).toBeVisible();
        expect(prices.fetchPurchasePrices).toHaveBeenCalledWith(
            {
                supplierId: 10,
                currencyId: 1,
                productDetailId: 501,
                otherSuppliers: false,
            },
            expect.anything(),
        );
    });
});
