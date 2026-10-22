import { Breadcrumbs } from '@/components/breadcrumbs';
import { CommandPalette } from '@/components/command-palette';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { deriveBreadcrumbs } from '@/lib/breadcrumbs';
import { mainNavigation } from '@/lib/navigation';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { currentUrl } = useCurrentUrl();

    // Pages inside the menu get their trail from the URL, so every module
    // reads the same way (TODO-modules.md UI-3). A page outside the menu
    // (settings, the import screen) still names its own.
    const derived = deriveBreadcrumbs(currentUrl, mainNavigation);

    return (
        <header className="border-sidebar-border/50 flex h-16 shrink-0 items-center gap-2 border-b px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex min-w-0 items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs
                    breadcrumbs={derived.length > 0 ? derived : breadcrumbs}
                />
            </div>
            <div className="ml-auto">
                <CommandPalette />
            </div>
        </header>
    );
}
