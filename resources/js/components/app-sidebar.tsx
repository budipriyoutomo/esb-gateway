import { Link } from '@inertiajs/react';
import {
    BookOpen,
    Boxes,
    ChefHat,
    ClipboardCheck,
    ClipboardList,
    ArrowLeftRight,
    PackageCheck,
    PackageOpen,
    NotebookPen,
    Warehouse,
    Database,
    FileText,
    FolderGit2,
    Goal,
    LayoutGrid,
    ListTree,
    Package,
    Ruler,
    Tag,
    Tags,
    Truck,
    Factory,
    Landmark,
    BadgeDollarSign,
    Banknote,
    BookOpenText,
    Calculator,
    NotebookText,
    Scale,
    HandCoins,
    PlugZap,
    ReceiptText,
    ScrollText,
    Send,
    ShoppingCart,
    Store,
    Users,
    UtensilsCrossed,
    Wallet,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/hooks/use-permissions';
import { dashboard } from '@/routes';
import { index as billsOfMaterialIndex } from '@/routes/bills-of-material';
import { index as coaAccountsIndex } from '@/routes/coa-accounts';
import { index as documentTemplatesIndex } from '@/routes/document-templates';
import { index as endpointsIndex } from '@/routes/endpoints';
import { index as goodsDeliveriesIndex } from '@/routes/goods-deliveries';
import { index as goodsReceiptsIndex } from '@/routes/goods-receipts';
import { index as goodsTransferRequestsIndex } from '@/routes/goods-transfer-requests';
import { index as esbConnectionsIndex } from '@/routes/esb-connections';
import { index as integrationLogsIndex } from '@/routes/integration-logs';
import { index as itemJournalsIndex } from '@/routes/item-journals';
import { index as masterMenusIndex } from '@/routes/master-menus';
import { index as menusIndex } from '@/routes/menus';
import { index as pricelistsIndex } from '@/routes/pricelists';
import { index as productCategoriesIndex } from '@/routes/product-categories';
import { index as productSubCategoriesIndex } from '@/routes/product-sub-categories';
import { index as productsIndex } from '@/routes/products';
import { index as purchaseInvoicesIndex } from '@/routes/purchase-invoices';
import { index as purchaseOrdersIndex } from '@/routes/purchase-orders';
import { index as purchaseRequestsIndex } from '@/routes/purchase-requests';
import { index as purposesIndex } from '@/routes/purposes';
import { index as salesPushesIndex } from '@/routes/sales-pushes';
import { index as simplePurchasesIndex } from '@/routes/simple-purchases';
import { index as simpleTransfersIndex } from '@/routes/simple-transfers';
import { index as disbursementsIndex } from '@/routes/disbursements';
import { index as generalJournalsIndex } from '@/routes/general-journals';
import { index as generalLedgerIndex } from '@/routes/general-ledger';
import { index as memorialJournalsIndex } from '@/routes/memorial-journals';
import { index as supplierCategoriesIndex } from '@/routes/supplier-categories';
import { index as suppliersIndex } from '@/routes/suppliers';
import { index as unitsIndex } from '@/routes/units';
import { index as usersIndex } from '@/routes/users';
import type { NavEntry, NavGroup, NavItem, Permission } from '@/types';

type GatedNavItem = NavItem & { permission: Permission };
type GatedNavGroup = Omit<NavGroup, 'items'> & { items: GatedNavItem[] };
type GatedNavEntry = GatedNavItem | GatedNavGroup;

const mainNavItems: GatedNavEntry[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
        permission: 'dashboard.view',
    },
    {
        title: 'Master data',
        icon: Database,
        items: [
            {
                title: 'Products',
                href: productsIndex(),
                icon: Package,
                permission: 'esb.invoke',
            },
            {
                title: 'Units',
                href: unitsIndex(),
                icon: Ruler,
                permission: 'esb.invoke',
            },
            {
                title: 'Product categories',
                href: productCategoriesIndex(),
                icon: Tags,
                permission: 'esb.invoke',
            },
            {
                title: 'Product sub-categories',
                href: productSubCategoriesIndex(),
                icon: Tag,
                permission: 'esb.invoke',
            },
            {
                title: 'Suppliers',
                href: suppliersIndex(),
                icon: Factory,
                permission: 'esb.invoke',
            },
            {
                title: 'Supplier categories',
                href: supplierCategoriesIndex(),
                icon: Truck,
                permission: 'esb.invoke',
            },
            {
                title: 'Menus',
                href: menusIndex(),
                icon: UtensilsCrossed,
                permission: 'esb.invoke',
            },
            {
                title: 'Master menu',
                href: masterMenusIndex(),
                icon: ChefHat,
                permission: 'esb.invoke',
            },
            {
                title: 'Bill of material',
                href: billsOfMaterialIndex(),
                icon: Boxes,
                permission: 'esb.invoke',
            },
            {
                title: 'Pricelist',
                href: pricelistsIndex(),
                icon: BadgeDollarSign,
                permission: 'esb.invoke',
            },
            {
                title: 'Chart of accounts',
                href: coaAccountsIndex(),
                icon: Landmark,
                permission: 'esb.invoke',
            },
            {
                title: 'Purposes',
                href: purposesIndex(),
                icon: Goal,
                permission: 'esb.invoke',
            },
            {
                title: 'Document templates',
                href: documentTemplatesIndex(),
                icon: FileText,
                permission: 'esb.invoke',
            },
        ],
    },
    {
        title: 'Purchase',
        icon: ShoppingCart,
        items: [
            {
                title: 'Purchase requests',
                href: purchaseRequestsIndex(),
                icon: ClipboardList,
                permission: 'esb.invoke',
            },
            {
                title: 'Purchase orders',
                href: purchaseOrdersIndex(),
                icon: ClipboardCheck,
                permission: 'esb.invoke',
            },
            {
                title: 'Purchase invoices',
                href: purchaseInvoicesIndex(),
                icon: ReceiptText,
                permission: 'esb.invoke',
            },
            {
                title: 'Simple purchases',
                href: simplePurchasesIndex(),
                icon: Wallet,
                permission: 'esb.invoke',
            },
        ],
    },
    {
        title: 'Inventory',
        icon: Warehouse,
        items: [
            {
                title: 'Goods transfer requests',
                href: goodsTransferRequestsIndex(),
                icon: ArrowLeftRight,
                permission: 'esb.invoke',
            },
            {
                title: 'Goods deliveries',
                href: goodsDeliveriesIndex(),
                icon: Truck,
                permission: 'esb.invoke',
            },
            {
                title: 'Goods receipts',
                href: goodsReceiptsIndex(),
                icon: PackageCheck,
                permission: 'esb.invoke',
            },
            {
                title: 'Simple transfers',
                href: simpleTransfersIndex(),
                icon: PackageOpen,
                permission: 'esb.invoke',
            },
            {
                title: 'Item journals',
                href: itemJournalsIndex(),
                icon: NotebookPen,
                permission: 'esb.invoke',
            },
        ],
    },
    {
        title: 'Finance',
        icon: Banknote,
        items: [
            {
                title: 'Disbursements',
                href: disbursementsIndex(),
                icon: HandCoins,
                permission: 'esb.invoke',
            },
        ],
    },
    {
        title: 'Accounting',
        icon: Calculator,
        items: [
            {
                title: 'General journal',
                href: generalJournalsIndex(),
                icon: BookOpenText,
                permission: 'esb.invoke',
            },
            {
                title: 'General ledger',
                href: generalLedgerIndex(),
                icon: Scale,
                permission: 'esb.invoke',
            },
            {
                title: 'Memorial journals',
                href: memorialJournalsIndex(),
                icon: NotebookText,
                permission: 'esb.invoke',
            },
        ],
    },
    {
        title: 'Sales',
        icon: Store,
        items: [
            {
                title: 'Sales pushes',
                href: salesPushesIndex(),
                icon: Send,
                permission: 'sales-pushes.view',
            },
        ],
    },
    {
        title: 'Integration logs',
        href: integrationLogsIndex(),
        icon: ScrollText,
        permission: 'integration-logs.view',
    },
    {
        title: 'Endpoint catalog',
        href: endpointsIndex(),
        icon: ListTree,
        permission: 'endpoints.view',
    },
    {
        title: 'ESB connections',
        href: esbConnectionsIndex(),
        icon: PlugZap,
        permission: 'esb-connections.view',
    },
    {
        title: 'Users',
        href: usersIndex(),
        icon: Users,
        permission: 'users.view',
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { can } = usePermissions();

    // Hiding a link the user cannot follow is a convenience only. The route
    // itself is authorized server-side, so a hand-typed URL still gets a 403.
    // A group shows only the children the user may open, and disappears
    // entirely when none are left.
    const visibleNavItems = mainNavItems.flatMap((entry): NavEntry[] => {
        if (!('items' in entry)) {
            return can(entry.permission) ? [entry] : [];
        }

        const items = entry.items.filter((item) => can(item.permission));

        return items.length > 0 ? [{ ...entry, items }] : [];
    });

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={visibleNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
