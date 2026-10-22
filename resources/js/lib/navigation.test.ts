import { describe, expect, it } from 'vite-plus/test';
import {
    documentTargets,
    flattenNavigation,
    mainNavigation,
    visibleNavigation,
} from '@/lib/navigation';
import { toUrl } from '@/lib/utils';
import type { NavEntry, NavGroup, Permission } from '@/types';

const allowAll = () => true;

function groupTitles(entries: NavEntry[]): string[] {
    return entries.map((entry) => entry.title);
}

function group(entries: NavEntry[], title: string): NavGroup {
    const found = entries.find((entry) => entry.title === title);

    if (!found || !('items' in found)) {
        throw new Error(`No group ${title}`);
    }

    return found;
}

describe('mainNavigation', () => {
    it('groups the modules in the agreed order, with the gateway pages under System', () => {
        expect(
            groupTitles(visibleNavigation(mainNavigation, allowAll)),
        ).toEqual([
            'Dashboard',
            'Master data',
            'Purchase',
            'Inventory',
            'Finance',
            'Accounting',
            'POS',
            'Sales',
            'System',
        ]);

        expect(
            group(
                visibleNavigation(mainNavigation, allowAll),
                'System',
            ).items.map((item) => item.title),
        ).toEqual([
            'Integration logs',
            'Endpoint catalog',
            'ESB connections',
            'Users',
        ]);
    });

    it('gives every group an icon', () => {
        for (const entry of mainNavigation) {
            expect(entry.icon, entry.title).toBeTruthy();
        }
    });
});

describe('visibleNavigation', () => {
    it('keeps only what the person may open and drops groups left empty', () => {
        const granted: Permission[] = ['dashboard.view', 'users.view'];

        const visible = visibleNavigation(mainNavigation, (permission) =>
            granted.includes(permission),
        );

        expect(groupTitles(visible)).toEqual(['Dashboard', 'System']);
        expect(
            group(visible, 'System').items.map((item) => item.title),
        ).toEqual(['Users']);
    });
});

describe('flattenNavigation', () => {
    it('lists every link once with the group it sits in', () => {
        const links = flattenNavigation(
            visibleNavigation(mainNavigation, allowAll),
        );

        expect(links[0]).toEqual({
            title: 'Dashboard',
            group: null,
            href: '/dashboard',
        });
        expect(links).toContainEqual({
            title: 'Disbursements',
            group: 'Finance',
            href: '/disbursements',
        });
        expect(new Set(links.map((link) => link.href)).size).toBe(links.length);
    });
});

describe('documentTargets', () => {
    it('opens a document by its number on the module show page', () => {
        const disbursements = documentTargets.find(
            (target) => target.title === 'Disbursements',
        );

        expect(disbursements?.permission).toBe('esb.invoke');
        expect(toUrl(disbursements!.href('DB202603200001'))).toBe(
            '/disbursements/DB202603200001',
        );
    });

    it('covers the documents that are opened by number', () => {
        expect(documentTargets.map((target) => target.title)).toEqual([
            'Purchase requests',
            'Purchase orders',
            'Purchase invoices',
            'Simple purchases',
            'Goods transfer requests',
            'Goods deliveries',
            'Goods receipts',
            'Simple transfers',
            'Item journals',
            'Disbursements',
            'Memorial journals',
            'Proposed prices',
        ]);
    });
});
