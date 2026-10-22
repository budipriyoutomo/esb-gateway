import { flattenNavigation } from '@/lib/navigation';
import type { BreadcrumbItem, NavEntry } from '@/types';

/**
 * Words for the path segments that are pages of their own rather than a
 * document number or id.
 */
const SEGMENT_TITLES: Record<string, string> = {
    create: 'New',
    edit: 'Edit',
    import: 'Import',
    imports: 'Imports',
    summary: 'Summary',
    temp: 'Proposed prices',
};

function decodeSegment(segment: string): string {
    try {
        return decodeURIComponent(segment);
    } catch {
        return segment;
    }
}

/**
 * The breadcrumb trail of a page, read from its path and the menu
 * (TODO-modules.md UI-3): the menu link the path starts with, then one crumb
 * per further segment -- "New" / "Edit" / "Import" in words, a document
 * number as it was typed. Each crumb links to its own path.
 *
 * The longest matching menu path wins, so /product-sub-categories never
 * lands under /products. A page outside the menu gets no trail here and
 * names its own (settings, the import screen).
 */
export function deriveBreadcrumbs(
    path: string,
    navigation: NavEntry[],
): BreadcrumbItem[] {
    const clean = path.split(/[?#]/)[0]!.replace(/\/+$/, '') || '/';

    const module = flattenNavigation(navigation)
        .filter(
            (link) => clean === link.href || clean.startsWith(`${link.href}/`),
        )
        .sort((a, b) => b.href.length - a.href.length)[0];

    if (!module) {
        return [];
    }

    const trail: BreadcrumbItem[] = [
        { title: module.title, href: module.href },
    ];
    let href = module.href;

    for (const segment of clean.slice(module.href.length).split('/')) {
        if (segment === '') {
            continue;
        }

        href = `${href}/${segment}`;
        trail.push({
            title: SEGMENT_TITLES[segment] ?? decodeSegment(segment),
            href,
        });
    }

    return trail;
}
