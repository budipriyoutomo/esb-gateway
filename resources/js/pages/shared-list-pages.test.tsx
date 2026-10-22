import { render, screen, within } from '@testing-library/react';
import { describe, expect, it, vi } from 'vite-plus/test';
import BillsOfMaterialIndex from '@/pages/bills-of-material/index';
import DocumentTemplateImportsIndex from '@/pages/document-template-imports/index';
import DocumentTemplatesIndex from '@/pages/document-templates/index';
import EndpointsIndex from '@/pages/endpoints/index';
import IntegrationLogsIndex from '@/pages/integration-logs/index';
import MenusIndex from '@/pages/menus/index';
import ProductsIndex from '@/pages/products/index';
import PurposesIndex from '@/pages/purposes/index';
import SalesPushesIndex from '@/pages/sales-pushes/index';
import PurchaseInvoicesIndex from '@/pages/purchase-invoices/index';
import PurchaseRequestsIndex from '@/pages/purchase-requests/index';
import SimplePurchasesIndex from '@/pages/simple-purchases/index';
import UsersIndex from '@/pages/users/index';
import type { Permission } from '@/types';
import type { MasterMenu } from '@/types/master-menu';
import type { Pagination } from '@/types/pagination';

// TODO-modules.md UI-6: the older list pages, rebuilt on the shared
// components (PageHeader, FilterBar, DataTable, ListPagination,
// StatusBadge) like the purchase order list. Behaviour is unchanged; these
// tests pin that each page now uses them.

vi.mock('@/hooks/use-permissions', () => ({
    usePermissions: () => ({
        can: (_permission: Permission) => false,
        canAny: () => false,
    }),
}));

const pagination: Pagination = {
    page: 1,
    limit: 20,
    count: 45,
    lastPage: 3,
    hasMorePages: true,
};

type Expectation = {
    title: string;
    caption: string;
    /** A link in the first row to the detail page. */
    link?: { name: string; href: string };
    /** A status shown through StatusBadge. */
    status?: string;
    /** Whether the page has a FilterBar. */
    search?: boolean;
    /** The pagination noun; omitted for an unpaged list. */
    noun?: string;
};

function expectSharedListPage({
    title,
    caption,
    link,
    status,
    search = true,
    noun,
}: Expectation) {
    expect(
        screen.getByRole('heading', { level: 1, name: title }),
    ).toBeVisible();

    if (search) {
        expect(screen.getByRole('search')).toBeInTheDocument();
    }

    const table = screen.getByRole('table', { name: caption });
    const cells = within(table).getAllByRole('cell');
    // DataTable labels its cells for the narrow-screen cards.
    expect(cells[0]).toHaveAttribute('data-label');

    if (link) {
        expect(
            within(table).getByRole('link', { name: link.name }),
        ).toHaveAttribute('href', link.href);
    }

    if (status) {
        expect(
            within(table).getByText(status).closest('[data-tone]'),
        ).not.toBeNull();
    }

    if (noun) {
        expect(
            within(
                screen.getByRole('navigation', { name: 'Pagination' }),
            ).getByText(new RegExp(`45 ${noun}`)),
        ).toBeVisible();
    }
}

describe('older list pages on the shared components', () => {
    it('purchase requests', () => {
        render(
            <PurchaseRequestsIndex
                purchaseRequests={[
                    {
                        provider: 'esb',
                        number: 'PR202603200001',
                        date: '2026-03-20',
                        requiredDate: '2026-03-25',
                        createdAt: null,
                        branchId: 1,
                        branchName: 'Outlet Senopati',
                        templateId: null,
                        templateName: null,
                        statusId: 1,
                        statusName: 'New',
                        notes: null,
                    },
                ]}
                pagination={pagination}
                filters={{}}
            />,
        );

        expectSharedListPage({
            title: 'Purchase requests',
            caption: 'Purchase requests',
            link: {
                name: 'PR202603200001',
                href: '/purchase-requests/PR202603200001',
            },
            status: 'New',
            noun: 'purchase requests',
        });
    });

    it('purchase invoices', () => {
        render(
            <PurchaseInvoicesIndex
                purchaseInvoices={[
                    {
                        provider: 'esb',
                        number: 'PI202603200001',
                        supplierInvoiceNumber: 'INV-9',
                        date: '2026-03-20',
                        dueDate: '2026-04-20',
                        branchId: 1,
                        branchName: 'Outlet Senopati',
                        supplierId: 4,
                        supplierName: 'PT Sumber Pangan',
                        currencyId: 1,
                        currencySign: 'Rp',
                        total: 1500000,
                        statusId: 3,
                        statusName: 'Authorized',
                        createdByName: null,
                        notes: null,
                    },
                ]}
                pagination={pagination}
                filters={{}}
            />,
        );

        expectSharedListPage({
            title: 'Purchase invoices',
            caption: 'Purchase invoices',
            link: {
                name: 'PI202603200001',
                href: '/purchase-invoices/PI202603200001',
            },
            status: 'Authorized',
            noun: 'purchase invoices',
        });
    });

    it('simple purchases', () => {
        render(
            <SimplePurchasesIndex
                simplePurchases={[
                    {
                        provider: 'esb',
                        number: 'SP202603200001',
                        supplierInvoiceNumber: null,
                        date: '2026-03-20',
                        branchId: 1,
                        branchName: 'Outlet Senopati',
                        supplierId: 4,
                        supplierName: 'PT Sumber Pangan',
                        currencySign: 'Rp',
                        total: 250000,
                        statusId: 1,
                        statusName: 'New',
                        createdByName: null,
                        notes: null,
                    },
                ]}
                pagination={pagination}
                filters={{}}
            />,
        );

        expectSharedListPage({
            title: 'Simple purchases',
            caption: 'Simple purchases',
            link: {
                name: 'SP202603200001',
                href: '/simple-purchases/SP202603200001',
            },
            status: 'New',
            noun: 'simple purchases',
        });
    });

    it('products', () => {
        render(
            <ProductsIndex
                products={[
                    {
                        provider: 'esb',
                        id: 12,
                        name: 'Beras Pandan Wangi',
                        code: 'BRS-01',
                        categoryName: 'Bahan Baku',
                        subCategoryName: null,
                        categoryTypeName: null,
                        bomName: null,
                        isActive: true,
                    },
                ]}
                pagination={pagination}
                filters={{}}
            />,
        );

        expectSharedListPage({
            title: 'Products',
            caption: 'Products',
            link: { name: 'Beras Pandan Wangi', href: '/products/12' },
            status: 'Active',
            noun: 'products',
        });
    });

    it('menus', () => {
        render(
            <MenusIndex
                source="core"
                menus={[
                    {
                        provider: 'esb',
                        id: 5,
                        code: 'MN-01',
                        name: 'Nasi Goreng',
                        shortName: null,
                        categoryId: null,
                        categoryName: 'Makanan',
                        categoryDetailId: null,
                        categoryDetailName: null,
                        notes: null,
                    },
                ]}
                pagination={pagination}
                filters={{}}
            />,
        );

        expectSharedListPage({
            title: 'Menus',
            caption: 'ESB Core menus',
            noun: 'menus',
        });
    });

    it('menus, OMS tab (was master menus)', () => {
        render(
            <MenusIndex
                source="oms"
                menus={[
                    {
                        provider: 'esb',
                        id: 7,
                        code: 'NG-01',
                        name: 'Nasi Goreng',
                        categoryName: 'Makanan',
                        taxLabel: 'PB1',
                        active: true,
                        templates: [],
                    } as unknown as MasterMenu,
                ]}
                pagination={pagination}
                filters={{}}
            />,
        );

        expectSharedListPage({
            title: 'Menus',
            caption: 'ESB OMS menus',
            link: { name: 'NG-01', href: '/master-menus/NG-01' },
            status: 'Active',
            noun: 'menus',
        });
    });

    it('bills of material', () => {
        render(
            <BillsOfMaterialIndex
                boms={[
                    {
                        provider: 'esb',
                        id: 3,
                        code: 'BOM-1',
                        name: 'Nasi Goreng',
                        typeId: 1,
                        typeName: 'Menu',
                        productName: null,
                        uomName: 'Porsi',
                        notes: null,
                        active: false,
                    },
                ]}
                pagination={pagination}
                filters={{}}
            />,
        );

        expectSharedListPage({
            title: 'Bill of material',
            caption: 'Bills of material',
            link: { name: 'Nasi Goreng', href: '/bills-of-material/3' },
            status: 'Inactive',
            noun: 'bills of material',
        });
    });

    it('purposes', () => {
        render(
            <PurposesIndex
                purposes={[
                    {
                        provider: 'esb',
                        id: 9,
                        name: 'Biaya Operasional',
                        accountNo: '6101',
                        accountName: 'Beban',
                        appliesTo: ['Item journal'],
                        active: true,
                    },
                ]}
                pagination={pagination}
                filters={{}}
            />,
        );

        expectSharedListPage({
            title: 'Purposes',
            caption: 'Purposes',
            link: { name: 'Biaya Operasional', href: '/purposes/9' },
            status: 'Active',
            noun: 'purposes',
        });
    });

    it('document templates', () => {
        render(
            <DocumentTemplatesIndex
                templates={[
                    {
                        provider: 'esb',
                        id: 4,
                        name: 'Belanja Harian',
                        typeNames: ['Purchase request'],
                        branchNames: ['Outlet Senopati'],
                        active: true,
                    },
                ]}
                types={[{ value: 1, label: 'Purchase request' }]}
                pagination={pagination}
                filters={{}}
            />,
        );

        expectSharedListPage({
            title: 'Document templates',
            caption: 'Document templates',
            link: { name: 'Belanja Harian', href: '/document-templates/4' },
            status: 'Active',
            noun: 'templates',
        });
    });

    it('document template imports', () => {
        render(
            <DocumentTemplateImportsIndex
                imports={[
                    {
                        id: 2,
                        filename: 'templates.xlsx',
                        status: 'idle',
                        uploadedBy: 'Andi',
                        createdAt: '2026-03-20 08:00:00',
                        templates: 1,
                        counts: { ready: 1 },
                    },
                ]}
            />,
        );

        expectSharedListPage({
            title: 'Import document templates',
            caption: 'Uploaded spreadsheets',
            link: {
                name: 'templates.xlsx',
                href: '/document-templates/imports/2',
            },
            search: false,
        });
        expect(
            screen.getByRole('link', { name: 'Document templates' }),
        ).toHaveAttribute('href', '/document-templates');
    });

    it('sales pushes', () => {
        render(
            <SalesPushesIndex
                pushes={{
                    data: [
                        {
                            id: 'a1',
                            salesNum: 'S-0001',
                            branchCode: 'SNP',
                            salesDate: '2026-03-20',
                            client: 'pos-1',
                            status: 'succeeded',
                            attempts: 1,
                            pushedAt: null,
                            createdAt: '2026-03-20 08:00:00',
                        },
                    ],
                    total: 45,
                    current_page: 1,
                    last_page: 3,
                }}
                filters={{}}
                statuses={['queued', 'succeeded']}
            />,
        );

        expectSharedListPage({
            title: 'Sales pushes',
            caption: 'Sales pushes',
            link: { name: 'S-0001', href: '/sales-pushes/a1' },
            noun: 'pushes',
        });
    });

    it('integration logs', () => {
        render(
            <IntegrationLogsIndex
                logs={{
                    data: [
                        {
                            id: 1,
                            correlationId: 'abc-123',
                            operation: 'product.list',
                            family: 'core',
                            environment: 'staging_int',
                            method: 'GET',
                            path: '/product/list',
                            outcome: 'success',
                            esbCode: 'EC03100000',
                            message: null,
                            upstreamStatus: 200,
                            durationMs: 120,
                            retryCount: 0,
                            user: null,
                            createdAt: '2026-03-20 08:00:00',
                        },
                    ],
                    total: 45,
                    current_page: 1,
                    last_page: 3,
                }}
                filters={{}}
                outcomes={['success', 'timeout']}
                families={['core']}
            />,
        );

        expectSharedListPage({
            title: 'Integration logs',
            caption: 'ERP calls',
            noun: 'calls',
        });
    });

    it('endpoint catalog', () => {
        render(
            <EndpointsIndex
                endpoints={{
                    data: [
                        {
                            id: 1,
                            code: 'core.product.list',
                            product: 'core',
                            group: 'product',
                            name: 'Product list',
                            version: '1',
                            method: 'GET',
                            path: '/product/list',
                            family: 'core',
                            isOperation: true,
                            docsUrl: 'https://docs.example/product-list',
                        },
                    ],
                    total: 45,
                    current_page: 1,
                    last_page: 3,
                }}
                filters={{}}
                groups={['product']}
                methods={['GET']}
                families={['core']}
            />,
        );

        expectSharedListPage({
            title: 'Endpoint catalog',
            caption: 'ERP operations',
            link: {
                name: 'Vendor docs',
                href: 'https://docs.example/product-list',
            },
            noun: 'operations',
        });
    });

    it('users, with a named role picker', () => {
        render(
            <UsersIndex
                users={[
                    {
                        id: 3,
                        name: 'Andi Pratama',
                        email: 'andi@example.test',
                        role: 'Viewer',
                        canAssignRole: true,
                    },
                ]}
                roles={['Viewer', 'Super Admin']}
            />,
        );

        expectSharedListPage({
            title: 'Users',
            caption: 'Users',
            search: false,
        });
        expect(
            screen.getByRole('combobox', { name: 'Role for Andi Pratama' }),
        ).toHaveTextContent('Viewer');
    });
});
