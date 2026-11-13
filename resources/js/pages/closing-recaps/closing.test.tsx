import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vite-plus/test';
import ClosingItemMappings from '@/pages/closing-mappings/items';
import ClosingOutletMappings from '@/pages/closing-mappings/outlets';
import ClosingPurposeMappings from '@/pages/closing-mappings/purposes';
import ClosingRecapsIndex from '@/pages/closing-recaps/index';
import type { ClosingJournal } from '@/components/closing-item-journals';
import ClosingRecapShow from '@/pages/closing-recaps/show';

// ADR-032: closing recaps held in the gateway, and their ERP mappings.

function paginator<T>(data: T[]) {
    return {
        data,
        current_page: 1,
        last_page: 1,
        per_page: 25,
        total: data.length,
        from: data.length ? 1 : null,
        to: data.length || null,
        links: [],
        first_page_url: '',
        last_page_url: '',
        next_page_url: null,
        prev_page_url: null,
        path: '',
    };
}

const recap = {
    id: '01JCLOSING',
    outletCode: 'STTSM',
    productionDate: '2026-10-12',
    sequence: 2,
    items: [
        {
            source: 'closing' as const,
            key: '0b6f2c1e-8a2d-4c3b-9e7f-1a2b3c4d5e6f',
            code: 'SU-001',
            name: 'Sushi Roll Salmon',
            colorplateSold: '10',
            posQty: '3',
            totalSold: '13',
            waste: '1',
            adjustment: '-1',
            compensation: '0',
            esbMenu: null,
            bom: null,
        },
        {
            source: 'pos_only' as const,
            key: 'DRK-OCHA-01',
            code: 'DRK-OCHA-01',
            name: 'Ocha Dingin',
            colorplateSold: '0',
            posQty: '2',
            totalSold: '2',
            waste: '0',
            adjustment: '0',
            compensation: '0',
            esbMenu: 'OCHA · Ocha Dingin',
            bom: null,
        },
    ],
    status: 'received' as const,
    lastReceivedAt: '2026-10-13T03:00:05+00:00',
    ready: false,
    missing: [
        {
            type: 'outlet' as const,
            key: 'STTSM',
            label: 'STTSM',
            message: 'Outlet STTSM has no ERP branch and location.',
        },
        {
            type: 'purpose' as const,
            key: 'waste',
            label: 'Waste',
            message: 'Waste has no ERP purpose.',
        },
    ],
    outlet: null,
    submissions: [
        {
            id: '01JSUB2',
            sequence: 2,
            messageId: '6f1c2a7e-3b4d-4e8f-9a10-2b3c4d5e6f70',
            sentAt: '2026-10-13T03:00:00+00:00',
            receivedAt: '2026-10-13T03:00:05+00:00',
            client: 'sync-api',
            current: true,
        },
    ],
};

describe('Closing recap list', () => {
    it('shows whether each recap has its mappings', () => {
        render(
            <ClosingRecapsIndex
                recaps={paginator([
                    {
                        id: 'A',
                        outletCode: 'STTSM',
                        productionDate: '2026-10-12',
                        sequence: 2,
                        items: 2,
                        status: 'received',
                        lastReceivedAt: null,
                        ready: false,
                        missing: 3,
                    },
                    {
                        id: 'B',
                        outletCode: 'RXT',
                        productionDate: '2026-10-11',
                        sequence: 1,
                        items: 5,
                        status: 'received',
                        lastReceivedAt: null,
                        ready: true,
                        missing: 0,
                    },
                ])}
                filters={{}}
                statuses={['received']}
            />,
        );

        const table = screen.getByRole('table', { name: 'Closing recaps' });
        expect(within(table).getByText('3 mappings missing')).toBeVisible();
        expect(within(table).getByText('Ready')).toBeVisible();
        expect(
            within(table).getAllByRole('link')[0].getAttribute('href'),
        ).toContain('/closing-recaps/A');
    });
});

describe('Closing recap detail', () => {
    it('lists every missing mapping with a link to where it is fixed', () => {
        render(<ClosingRecapShow recap={recap} />);

        const gaps = screen.getByRole('region', {
            name: 'Before this recap can be prepared',
        });
        const items = within(gaps).getAllByRole('listitem');

        expect(items).toHaveLength(2);
        expect(items[0]).toHaveTextContent(
            'Outlet STTSM has no ERP branch and location.',
        );
        expect(
            within(items[0]).getByRole('link', { name: 'Map it' }),
        ).toHaveAttribute('href', '/closing-mappings/outlets');
        expect(
            within(items[1]).getByRole('link', { name: 'Map it' }),
        ).toHaveAttribute('href', '/closing-mappings/purposes');
    });

    it('shows quantities, and that a POS product has no bill of material', () => {
        render(<ClosingRecapShow recap={recap} />);

        const table = screen.getByRole('table', {
            name: 'Closing recap items',
        });
        const [, salmon, ocha] = within(table).getAllByRole('row');

        expect(salmon).toHaveTextContent('Sushi Roll Salmon');
        expect(salmon).toHaveTextContent('-1');
        expect(ocha).toHaveTextContent('OCHA · Ocha Dingin');
        expect(ocha).toHaveTextContent('Not used');
    });

    it('hides the gap list once the recap is ready', () => {
        render(
            <ClosingRecapShow recap={{ ...recap, ready: true, missing: [] }} />,
        );

        expect(
            screen.queryByRole('region', {
                name: 'Before this recap can be prepared',
            }),
        ).not.toBeInTheDocument();
        expect(screen.getByText('Ready')).toBeVisible();
    });
});

describe('Closing mappings', () => {
    const outletRows = [
        {
            outletCode: 'RXT',
            recaps: 1,
            lastProductionDate: '2026-10-12',
            mapping: {
                branchId: 3,
                branchCode: 'RXT',
                branchName: 'Roxy',
                locationId: 9,
                locationName: 'Dapur Roxy',
            },
        },
        {
            outletCode: 'STTSM',
            recaps: 2,
            lastProductionDate: '2026-10-12',
            mapping: null,
        },
    ];

    it('offers no edit controls without the manage permission', () => {
        render(
            <ClosingOutletMappings canManage={false} outlets={outletRows} />,
        );

        expect(screen.getByText('Not mapped')).toBeVisible();
        expect(
            screen.queryByRole('button', { name: /Map|Edit/ }),
        ).not.toBeInTheDocument();
    });

    it('opens the outlet dialog with a branch and a branch-bound location picker', async () => {
        const user = userEvent.setup();
        render(<ClosingOutletMappings canManage outlets={outletRows} />);

        await user.click(screen.getByRole('button', { name: 'Map STTSM' }));

        const dialog = screen.getByRole('dialog', {
            name: 'Map outlet STTSM',
        });
        expect(
            within(dialog).getByRole('combobox', { name: 'ERP branch' }),
        ).toBeEnabled();
        expect(
            within(dialog).getByRole('combobox', { name: 'Stock location' }),
        ).toBeDisabled();
    });

    it('offers a bill of material only for closing menus', async () => {
        const user = userEvent.setup();
        render(
            <ClosingItemMappings
                canManage
                filters={{}}
                items={paginator([
                    {
                        source: 'closing',
                        key: '0b6f2c1e-8a2d-4c3b-9e7f-1a2b3c4d5e6f',
                        code: 'SU-001',
                        name: 'Sushi Roll Salmon',
                        recaps: 1,
                        needsBom: true,
                        mapping: null,
                    },
                    {
                        source: 'pos_only',
                        key: 'DRK-OCHA-01',
                        code: 'DRK-OCHA-01',
                        name: 'Ocha Dingin',
                        recaps: 1,
                        needsBom: false,
                        mapping: null,
                    },
                ])}
            />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Map Sushi Roll Salmon' }),
        );
        let dialog = screen.getByRole('dialog');
        expect(
            within(dialog).getByRole('combobox', { name: 'Bill of material' }),
        ).toBeVisible();
        await user.click(
            within(dialog).getByRole('button', { name: 'Cancel' }),
        );

        await user.click(
            screen.getByRole('button', { name: 'Map Ocha Dingin' }),
        );
        dialog = screen.getByRole('dialog');
        expect(
            within(dialog).getByRole('combobox', { name: 'ERP menu' }),
        ).toBeVisible();
        expect(
            within(dialog).queryByRole('combobox', {
                name: 'Bill of material',
            }),
        ).not.toBeInTheDocument();
    });

    it('lists the three closing quantities with their direction', () => {
        render(
            <ClosingPurposeMappings
                canManage={false}
                purposes={[
                    {
                        kind: 'waste',
                        label: 'Waste',
                        mapping: {
                            purposeId: 7,
                            purposeName: 'Waste Dapur',
                            direction: -1,
                        },
                    },
                    { kind: 'adjustment', label: 'Adjustment', mapping: null },
                    {
                        kind: 'compensation',
                        label: 'Compensation',
                        mapping: null,
                    },
                ]}
            />,
        );

        const table = screen.getByRole('table', { name: 'Purpose mappings' });
        expect(within(table).getAllByRole('row')).toHaveLength(4);
        expect(table).toHaveTextContent('Waste Dapur');
        expect(table).toHaveTextContent('Stock out (quantity × −1)');
        expect(within(table).getAllByText('Not mapped')).toHaveLength(2);
    });
});

const journal: ClosingJournal = {
    id: '01JIJ1',
    kind: 'waste',
    label: 'Waste',
    sequence: 2,
    current: true,
    purposeName: 'Waste Dapur',
    direction: -1,
    status: 'prepared',
    number: null,
    notes: 'Closing STTSM 2026-10-12, sequence 2: Waste',
    lines: [
        {
            productDetailId: 801,
            productCode: 'BRS01',
            productName: 'Beras',
            uomName: 'Gram',
            qty: '-200',
        },
    ],
    breakdown: [
        {
            item: 'Sushi Roll Salmon',
            quantity: '1',
            bomId: 412,
            bomName: 'Nasi Goreng Spesial',
        },
    ],
    errors: [],
    reference: null,
    sentAt: null,
    canResolve: false,
};

describe('Closing recap item journals', () => {
    it('shows each journal with its lines and where they came from', () => {
        render(
            <ClosingRecapShow
                recap={{ ...recap, ready: true, missing: [] }}
                canProcess
                actions={{ prepare: true, send: true }}
                journals={[journal]}
            />,
        );

        const card = screen.getByRole('listitem', {
            name: 'Waste item journal, sequence 2',
        });
        expect(card).toHaveTextContent('Prepared');
        expect(card).toHaveTextContent('Waste Dapur · stock out');
        expect(
            within(card).getByRole('table', {
                name: 'Waste lines, sequence 2',
            }),
        ).toHaveTextContent('Beras · BRS01-200 Gram');
        expect(card).toHaveTextContent(
            'From Sushi Roll Salmon × 1 (Nasi Goreng Spesial)',
        );

        expect(screen.getByRole('button', { name: 'Prepare' })).toBeEnabled();
        expect(
            screen.getByRole('button', { name: 'Send to ERP' }),
        ).toBeEnabled();
    });

    it('offers no actions without the process permission', () => {
        render(<ClosingRecapShow recap={recap} journals={[journal]} />);

        expect(
            screen.queryByRole('button', { name: 'Prepare' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Send to ERP' }),
        ).not.toBeInTheDocument();
    });

    it('disables what the server says cannot be done now', () => {
        render(
            <ClosingRecapShow
                recap={recap}
                canProcess
                actions={{ prepare: false, send: false }}
                journals={[]}
            />,
        );

        expect(screen.getByRole('button', { name: 'Prepare' })).toBeDisabled();
        expect(
            screen.getByRole('button', { name: 'Send to ERP' }),
        ).toBeDisabled();
        expect(screen.getByText(/Nothing prepared yet/)).toBeVisible();
    });

    it('lets an unconfirmed journal be settled after checking the ERP', async () => {
        const user = userEvent.setup();
        render(
            <ClosingRecapShow
                recap={recap}
                canProcess
                actions={{ prepare: false, send: false }}
                journals={[
                    {
                        ...journal,
                        status: 'unconfirmed',
                        errors: [
                            {
                                field: null,
                                message: 'The ERP did not answer in time.',
                            },
                        ],
                        reference: 'b1c2-correlation',
                        canResolve: true,
                    },
                ]}
            />,
        );

        const card = screen.getByRole('listitem', {
            name: 'Waste item journal, sequence 2',
        });
        expect(card).toHaveTextContent('Check in the ERP');
        expect(card).toHaveTextContent('The ERP did not answer in time.');
        expect(card).toHaveTextContent('Reference b1c2-correlation');

        await user.click(within(card).getByRole('button', { name: 'Settle' }));

        const dialog = screen.getByRole('dialog', {
            name: 'Settle the waste item journal',
        });
        expect(
            within(dialog).getByLabelText('Item journal number in the ERP'),
        ).toBeVisible();
        expect(
            within(dialog).getByRole('button', { name: 'It was created' }),
        ).toBeVisible();
        expect(
            within(dialog).getByRole('button', {
                name: 'It was not created',
            }),
        ).toBeVisible();
    });

    it('asks whether a journal whose delete was not confirmed is gone', async () => {
        const user = userEvent.setup();
        render(
            <ClosingRecapShow
                recap={recap}
                canProcess
                journals={[
                    {
                        ...journal,
                        sequence: 1,
                        current: false,
                        status: 'delete_unconfirmed',
                        number: 'IJ202610120001',
                        canResolve: true,
                    },
                ]}
            />,
        );

        const card = screen.getByRole('listitem', {
            name: 'Waste item journal, sequence 1',
        });
        expect(card).toHaveTextContent('Sequence 1');
        await user.click(within(card).getByRole('button', { name: 'Settle' }));

        const dialog = screen.getByRole('dialog');
        expect(dialog).toHaveTextContent('IJ202610120001');
        expect(
            within(dialog).getByRole('button', { name: 'It is gone' }),
        ).toBeVisible();
        expect(
            within(dialog).getByRole('button', { name: 'It is still there' }),
        ).toBeVisible();
    });
});

describe('Closing recap sales', () => {
    const draft = {
        id: '01JSALES',
        salesNum: 'CLSTTSM20261012',
        sequence: 2,
        current: true,
        status: 'prepared' as const,
        menuLines: 2,
        grandTotal: 242650,
        sentAt: null,
        push: null,
    };

    it('says when sync-api sent no sales data', () => {
        render(
            <ClosingRecapShow
                recap={recap}
                canProcess
                actions={{ prepare: false, send: false }}
            />,
        );

        expect(
            screen.getByText(/sent no prices, taxes or payments/),
        ).toBeVisible();
        expect(
            screen.getByRole('button', { name: 'Prepare sales' }),
        ).toBeDisabled();
    });

    it('shows a prepared transaction ready to send', () => {
        render(
            <ClosingRecapShow
                recap={recap}
                canProcess
                actions={{
                    prepare: false,
                    send: false,
                    prepareSales: true,
                    sendSales: true,
                }}
                sales={{
                    hasData: true,
                    grandTotal: 242650,
                    payments: 1,
                    draft,
                }}
            />,
        );

        const card = screen.getByLabelText('Sales CLSTTSM20261012');
        expect(card).toHaveTextContent('Prepared');
        expect(card).toHaveTextContent('2 menu lines');
        expect(
            screen.getByRole('button', { name: 'Send sales' }),
        ).toBeEnabled();
    });

    it('links a sent transaction to its sales push and shows the ERP refusal', () => {
        render(
            <ClosingRecapShow
                recap={recap}
                canProcess
                actions={{
                    prepare: false,
                    send: false,
                    prepareSales: false,
                    sendSales: false,
                }}
                sales={{
                    hasData: true,
                    grandTotal: 242650,
                    payments: 1,
                    draft: {
                        ...draft,
                        status: 'sent',
                        sentAt: '2026-10-13T04:00:00+00:00',
                        push: {
                            id: '01JPUSH',
                            status: 'rejected',
                            errors: [
                                {
                                    field: 'salesHead.menu.0.menuCode',
                                    message: 'Menu not found',
                                },
                            ],
                        },
                    },
                }}
            />,
        );

        const card = screen.getByLabelText('Sales CLSTTSM20261012');
        expect(card).toHaveTextContent('Sent');
        expect(card).toHaveTextContent('rejected');
        expect(card).toHaveTextContent(
            'salesHead.menu.0.menuCode: Menu not found',
        );
        expect(
            within(card).getByRole('link', { name: 'Open the sales push' }),
        ).toHaveAttribute('href', '/sales-pushes/01JPUSH');
    });
});
