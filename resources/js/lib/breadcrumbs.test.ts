import { describe, expect, it } from 'vite-plus/test';
import { deriveBreadcrumbs } from '@/lib/breadcrumbs';
import { mainNavigation, visibleNavigation } from '@/lib/navigation';

const navigation = visibleNavigation(mainNavigation, () => true);

function trail(path: string) {
    return deriveBreadcrumbs(path, navigation).map((crumb) => [
        crumb.title,
        crumb.href,
    ]);
}

describe('deriveBreadcrumbs', () => {
    it('starts every trail at the module the page belongs to', () => {
        expect(trail('/disbursements')).toEqual([
            ['Disbursements', '/disbursements'],
        ]);
        expect(trail('/dashboard')).toEqual([['Dashboard', '/dashboard']]);
    });

    it('adds the document number on a detail page', () => {
        expect(trail('/memorial-journals/MJ202603260001')).toEqual([
            ['Memorial journals', '/memorial-journals'],
            ['MJ202603260001', '/memorial-journals/MJ202603260001'],
        ]);
    });

    it('names create, edit and import pages in words', () => {
        expect(trail('/item-journals/create')).toEqual([
            ['Item journals', '/item-journals'],
            ['New', '/item-journals/create'],
        ]);
        expect(trail('/disbursements/DB1/edit')).toEqual([
            ['Disbursements', '/disbursements'],
            ['DB1', '/disbursements/DB1'],
            ['Edit', '/disbursements/DB1/edit'],
        ]);
        expect(trail('/purchase-requests/import')).toEqual([
            ['Purchase requests', '/purchase-requests'],
            ['Import', '/purchase-requests/import'],
        ]);
    });

    it('names the gateway sub-pages that have no menu entry of their own', () => {
        expect(trail('/pricelists/temp/PL202603010001')).toEqual([
            ['Pricelist', '/pricelists'],
            ['Proposed prices', '/pricelists/temp'],
            ['PL202603010001', '/pricelists/temp/PL202603010001'],
        ]);
        expect(trail('/general-ledger/summary')).toEqual([
            ['General ledger', '/general-ledger'],
            ['Summary', '/general-ledger/summary'],
        ]);
        expect(trail('/document-templates/imports/4')).toEqual([
            ['Document templates', '/document-templates'],
            ['Imports', '/document-templates/imports'],
            ['4', '/document-templates/imports/4'],
        ]);
    });

    it('matches the longest menu path, not a shorter one that shares a prefix', () => {
        expect(trail('/product-sub-categories/12')[0]).toEqual([
            'Product sub-categories',
            '/product-sub-categories',
        ]);
    });

    it('shows a number the way it was typed, without percent escapes', () => {
        expect(trail('/purchase-orders/PO%2D1')[1]?.[0]).toBe('PO-1');
    });

    it('gives nothing for a page outside the menu, so the page names its own', () => {
        expect(trail('/settings/profile')).toEqual([]);
        expect(trail('/imports/units')).toEqual([]);
    });
});
