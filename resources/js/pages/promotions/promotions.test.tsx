import { router } from '@inertiajs/react';
import { fireEvent, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import * as lookups from '@/lib/lookups';
import PromotionForm from '@/pages/promotions/form';
import PromotionIndex from '@/pages/promotions/index';
import type { Permission } from '@/types';
import type { Promotion, PromotionTypeOption } from '@/types/pos';

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

const promotion: Promotion = {
    provider: 'esb',
    id: 52,
    code: 'PROMOESO50K',
    typeName: 'PROMO DISCOUNT (Rp) ESO',
    notes: 'PROMOESO50K',
    discount: 50000,
    minSubtotal: 0,
    startAt: '2023-06-01 00:00:00',
    endAt: '2025-12-31 00:00:00',
    shownOnEso: true,
    branches: [{ id: 2, code: 'OUT1', name: 'OUTLET Tangerang 1' }],
    selfOrderPaymentMethods: ['OVO (Xendit)'],
    paymentMethods: ['DANA'],
    scope: { menuCategories: 0, menuCategoryDetails: 0, menus: 0 },
};

const types: PromotionTypeOption[] = [
    {
        id: 1,
        name: 'Discount (%)',
        fields: [
            'discount',
            'authorizationNeeded',
            'minSalesPrice',
            'maxSalesPrice',
            'applyTo',
            'employeeGroups',
            'includePackageContent',
            'includeMenuExtra',
            'times',
        ],
    },
    {
        id: 6,
        name: 'Discount (Rp) ESO',
        fields: [
            'discount',
            'minSalesPrice',
            'showOnEso',
            'maxUsage',
            'maxUsageTotal',
            'visitPurposeIds',
            'selfOrderPaymentMethodCodes',
            'bankIdentificationNumbers',
        ],
    },
];

const applyTo = [
    'All Transaction',
    'Member & Staff',
    'Staff Only',
    'Member Only',
];

describe('Promotion list', () => {
    it('shows each promotion with its branches and period', () => {
        render(
            <PromotionIndex
                promotions={[promotion]}
                pagination={null}
                filters={{}}
            />,
        );

        expect(screen.getAllByText('PROMOESO50K').length).toBeGreaterThan(0);
        expect(screen.getByText('OUTLET Tangerang 1')).toBeVisible();
        expect(screen.getByText('All menus')).toBeVisible();
    });

    it('offers a new promotion only to whoever manages them', () => {
        granted.permissions = ['esb.invoke', 'promotions.manage'];
        render(
            <PromotionIndex promotions={[]} pagination={null} filters={{}} />,
        );

        expect(
            screen.getByRole('link', { name: 'New promotion' }),
        ).toBeVisible();
    });
});

describe('Promotion form', () => {
    it('shows only the chosen type’s fields', async () => {
        render(
            <PromotionForm
                types={types}
                applyTo={applyTo}
                voucherSources={['ESB', 'Giftee']}
            />,
        );

        expect(screen.getByLabelText('Discount (%)')).toBeVisible();
        expect(screen.getByLabelText('Include menu extra')).toBeVisible();
        expect(
            screen.queryByLabelText('Show on ESB Order'),
        ).not.toBeInTheDocument();

        await userEvent.selectOptions(screen.getByLabelText('Type'), '6');

        expect(screen.getByLabelText('Discount (Rp)')).toBeVisible();
        expect(screen.getByLabelText('Show on ESB Order')).toBeVisible();
        expect(
            screen.queryByLabelText('Include menu extra'),
        ).not.toBeInTheDocument();
    });

    it('sends the common fields and the type’s own, nothing else', async () => {
        vi.spyOn(lookups, 'fetchLookup').mockResolvedValue({
            options: [
                {
                    value: '2',
                    label: 'OUTLET Tangerang 1',
                    code: 'OUT1',
                    meta: {},
                },
            ],
            hasMore: false,
        });
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        render(
            <PromotionForm
                types={types}
                applyTo={applyTo}
                voucherSources={['ESB', 'Giftee']}
            />,
        );

        await userEvent.selectOptions(screen.getByLabelText('Type'), '6');
        await userEvent.click(
            screen.getByRole('combobox', { name: 'Branches' }),
        );
        await userEvent.click(
            await screen.findByRole('option', { name: /OUTLET Tangerang 1/ }),
        );
        await userEvent.type(screen.getByLabelText('Notes'), 'Promo');
        await userEvent.type(screen.getByLabelText('Promotion code'), 'PR1');
        await userEvent.click(screen.getByLabelText('Monday'));
        fireEvent.change(screen.getByLabelText('Starts'), {
            target: { value: '2026-11-01T00:00' },
        });
        fireEvent.change(screen.getByLabelText('Ends'), {
            target: { value: '2026-11-30T23:59' },
        });
        await userEvent.type(screen.getByLabelText('Discount (Rp)'), '10000');
        await userEvent.type(screen.getByLabelText('Minimum subtotal'), '0');
        await userEvent.type(
            screen.getByLabelText('Max uses per customer'),
            '1',
        );
        await userEvent.type(screen.getByLabelText('Max uses in total'), '11');
        await userEvent.type(
            screen.getByLabelText('ESB Order payment method codes'),
            'ovo, qrisesb',
        );
        await userEvent.click(
            screen.getByRole('button', { name: 'Create promotion' }),
        );

        const [url, options] = visit.mock.calls[0] as [
            string,
            { method: string; data: unknown },
        ];
        expect(url).toMatch(/\/promotions$/);
        expect(options.method).toBe('post');
        expect(options.data).toEqual({
            type: 6,
            masterCode: '',
            branchCodes: ['OUT1'],
            notes: 'Promo',
            promotionCode: 'PR1',
            description: '',
            days: [1],
            startAt: '2026-11-01 00:00:00',
            endAt: '2026-11-30 23:59:00',
            allCategories: true,
            applyDiscountTo: null,
            menuCategoryIds: [],
            menuCategoryDetailIds: [],
            menuIds: [],
            usedForLoyalty: null,
            paymentMethodName: '',
            discount: 10000,
            minSalesPrice: 0,
            showOnEso: false,
            maxUsage: 1,
            maxUsageTotal: 11,
            visitPurposeIds: [],
            selfOrderPaymentMethodCodes: ['ovo', 'qrisesb'],
            bankIdentificationNumbers: [],
        });
    });
});
