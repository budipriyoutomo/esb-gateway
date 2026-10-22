import {
    Boxes,
    CirclePlus,
    ClipboardCheck,
    ClipboardList,
    ArrowLeftRight,
    PackageCheck,
    PackageOpen,
    NotebookPen,
    Warehouse,
    Database,
    FileText,
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
    BadgePercent,
    LayoutList,
    MonitorSmartphone,
    Banknote,
    BookOpenText,
    Calculator,
    NotebookText,
    Scale,
    HandCoins,
    PlugZap,
    ServerCog,
    ReceiptText,
    ScrollText,
    Send,
    ShoppingCart,
    Store,
    Users,
    UtensilsCrossed,
    Wallet,
} from 'lucide-react';
import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as billsOfMaterialIndex } from '@/routes/bills-of-material';
import { index as coaAccountsIndex } from '@/routes/coa-accounts';
import { index as documentTemplatesIndex } from '@/routes/document-templates';
import { index as endpointsIndex } from '@/routes/endpoints';
import {
    index as goodsDeliveriesIndex,
    show as goodsDeliveryShow,
} from '@/routes/goods-deliveries';
import {
    index as goodsReceiptsIndex,
    show as goodsReceiptShow,
} from '@/routes/goods-receipts';
import {
    index as goodsTransferRequestsIndex,
    show as goodsTransferRequestShow,
} from '@/routes/goods-transfer-requests';
import { index as esbConnectionsIndex } from '@/routes/esb-connections';
import { index as integrationLogsIndex } from '@/routes/integration-logs';
import {
    index as itemJournalsIndex,
    show as itemJournalShow,
} from '@/routes/item-journals';
import { extras as masterMenuExtras } from '@/routes/master-menus';
import { index as menuCategoriesIndex } from '@/routes/menu-categories';
import { index as menusIndex } from '@/routes/menus';
import { index as pricelistsIndex } from '@/routes/pricelists';
import { show as tempPricelistShow } from '@/routes/pricelists/temp';
import { index as promotionsIndex } from '@/routes/promotions';
import { index as productCategoriesIndex } from '@/routes/product-categories';
import { index as productSubCategoriesIndex } from '@/routes/product-sub-categories';
import { index as productsIndex } from '@/routes/products';
import {
    index as purchaseInvoicesIndex,
    show as purchaseInvoiceShow,
} from '@/routes/purchase-invoices';
import {
    index as purchaseOrdersIndex,
    show as purchaseOrderShow,
} from '@/routes/purchase-orders';
import {
    index as purchaseRequestsIndex,
    show as purchaseRequestShow,
} from '@/routes/purchase-requests';
import { index as purposesIndex } from '@/routes/purposes';
import { index as salesPushesIndex } from '@/routes/sales-pushes';
import {
    index as simplePurchasesIndex,
    show as simplePurchaseShow,
} from '@/routes/simple-purchases';
import {
    index as simpleTransfersIndex,
    show as simpleTransferShow,
} from '@/routes/simple-transfers';
import {
    index as disbursementsIndex,
    show as disbursementShow,
} from '@/routes/disbursements';
import { index as generalJournalsIndex } from '@/routes/general-journals';
import { index as generalLedgerIndex } from '@/routes/general-ledger';
import {
    index as memorialJournalsIndex,
    show as memorialJournalShow,
} from '@/routes/memorial-journals';
import { index as supplierCategoriesIndex } from '@/routes/supplier-categories';
import { index as suppliersIndex } from '@/routes/suppliers';
import { index as unitsIndex } from '@/routes/units';
import { index as usersIndex } from '@/routes/users';
import type { NavEntry, NavGroup, NavItem, Permission } from '@/types';

/**
 * The application menu: one place for the sidebar, the command palette and
 * the breadcrumbs (TODO-modules.md UI-3).
 *
 * Every link names the permission that opens it. Hiding a link is a
 * convenience only: the route itself is authorized server-side, so a
 * hand-typed URL still gets a 403.
 */
export type GatedNavItem = NavItem & { permission: Permission };
export type GatedNavGroup = Omit<NavGroup, 'items'> & { items: GatedNavItem[] };
export type GatedNavEntry = GatedNavItem | GatedNavGroup;

export const mainNavigation: GatedNavEntry[] = [
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
                title: 'Menu extras',
                href: masterMenuExtras(),
                icon: CirclePlus,
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
        title: 'POS',
        icon: MonitorSmartphone,
        items: [
            {
                title: 'Menu categories',
                href: menuCategoriesIndex(),
                icon: LayoutList,
                permission: 'esb.invoke',
            },
            {
                title: 'Promotions',
                href: promotionsIndex(),
                icon: BadgePercent,
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
        title: 'System',
        icon: ServerCog,
        items: [
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
        ],
    },
];

/**
 * The menu as one person sees it: only the links they may open, and a group
 * only while it still has one.
 */
export function visibleNavigation(
    entries: GatedNavEntry[],
    can: (permission: Permission) => boolean,
): NavEntry[] {
    return entries.flatMap((entry): NavEntry[] => {
        if (!('items' in entry)) {
            return can(entry.permission) ? [entry] : [];
        }

        const items = entry.items.filter((item) => can(item.permission));

        return items.length > 0 ? [{ ...entry, items }] : [];
    });
}

export type NavLink = {
    title: string;
    /** The group the link sits in; null for a top-level link. */
    group: string | null;
    href: string;
};

/** Every link of a menu once, in menu order. */
export function flattenNavigation(entries: NavEntry[]): NavLink[] {
    return entries.flatMap((entry): NavLink[] =>
        'items' in entry
            ? entry.items.map((item) => ({
                  title: item.title,
                  group: entry.title,
                  href: toUrl(item.href),
              }))
            : [{ title: entry.title, group: null, href: toUrl(entry.href) }],
    );
}

export type DocumentTarget = {
    title: string;
    permission: Permission;
    /** The document's page; the number must already be a safe path segment. */
    href: (number: string) => string;
};

/**
 * The documents a person can open by number from the command palette, in
 * menu order. Each show route checks the number itself and answers 404 when
 * the ERP has no such document.
 */
export const documentTargets: DocumentTarget[] = [
    {
        title: 'Purchase requests',
        permission: 'esb.invoke',
        href: (number) => purchaseRequestShow.url(number),
    },
    {
        title: 'Purchase orders',
        permission: 'esb.invoke',
        href: (number) => purchaseOrderShow.url(number),
    },
    {
        title: 'Purchase invoices',
        permission: 'esb.invoke',
        href: (number) => purchaseInvoiceShow.url(number),
    },
    {
        title: 'Simple purchases',
        permission: 'esb.invoke',
        href: (number) => simplePurchaseShow.url(number),
    },
    {
        title: 'Goods transfer requests',
        permission: 'esb.invoke',
        href: (number) => goodsTransferRequestShow.url(number),
    },
    {
        title: 'Goods deliveries',
        permission: 'esb.invoke',
        href: (number) => goodsDeliveryShow.url(number),
    },
    {
        title: 'Goods receipts',
        permission: 'esb.invoke',
        href: (number) => goodsReceiptShow.url(number),
    },
    {
        title: 'Simple transfers',
        permission: 'esb.invoke',
        href: (number) => simpleTransferShow.url(number),
    },
    {
        title: 'Item journals',
        permission: 'esb.invoke',
        href: (number) => itemJournalShow.url(number),
    },
    {
        title: 'Disbursements',
        permission: 'esb.invoke',
        href: (number) => disbursementShow.url(number),
    },
    {
        title: 'Memorial journals',
        permission: 'esb.invoke',
        href: (number) => memorialJournalShow.url(number),
    },
    {
        title: 'Proposed prices',
        permission: 'esb.invoke',
        href: (number) => tempPricelistShow.url(number),
    },
];
