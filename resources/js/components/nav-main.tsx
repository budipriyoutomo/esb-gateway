import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { toUrl } from '@/lib/utils';
import type { NavEntry, NavGroup, NavItem } from '@/types';

/** localStorage key holding which sidebar groups were left open. */
export const NAV_GROUPS_STORAGE_KEY = 'nav-groups';

function readGroupStates(): Record<string, boolean> {
    try {
        const stored = window.localStorage.getItem(NAV_GROUPS_STORAGE_KEY);
        const parsed: unknown = stored ? JSON.parse(stored) : {};

        return parsed && typeof parsed === 'object'
            ? (parsed as Record<string, boolean>)
            : {};
    } catch {
        return {};
    }
}

function rememberGroupState(title: string, open: boolean): void {
    try {
        window.localStorage.setItem(
            NAV_GROUPS_STORAGE_KEY,
            JSON.stringify({ ...readGroupStates(), [title]: open }),
        );
    } catch {
        // Private window or blocked storage: the group still opens and
        // closes, it just starts closed again next time.
    }
}

function isNavGroup(entry: NavEntry): entry is NavGroup {
    return 'items' in entry;
}

export function NavMain({ items }: { items: NavEntry[] }) {
    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Platform</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((entry) =>
                    isNavGroup(entry) ? (
                        <NavMainGroup key={entry.title} group={entry} />
                    ) : (
                        <NavMainItem key={entry.title} item={entry} />
                    ),
                )}
            </SidebarMenu>
        </SidebarGroup>
    );
}

function NavMainItem({ item }: { item: NavItem }) {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <SidebarMenuItem>
            <SidebarMenuButton
                asChild
                isActive={isCurrentUrl(item.href)}
                tooltip={{ children: item.title }}
            >
                <Link href={item.href} prefetch>
                    {item.icon && <item.icon />}
                    <span>{item.title}</span>
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    );
}

function NavMainGroup({ group }: { group: NavGroup }) {
    const { currentUrl, isCurrentUrl } = useCurrentUrl();
    // A detail or edit page belongs to its module's group too.
    const hasActiveChild = group.items.some((item) => {
        const href = toUrl(item.href);

        return currentUrl === href || currentUrl.startsWith(`${href}/`);
    });
    const [open, setOpen] = useState(
        () => hasActiveChild || readGroupStates()[group.title] === true,
    );

    // Open the group whenever the current page lives inside it, so the
    // active link is never hidden behind a collapsed header -- even when the
    // person closed the group earlier.
    useEffect(() => {
        if (hasActiveChild) {
            setOpen(true);
        }
    }, [hasActiveChild]);

    function toggle(next: boolean) {
        setOpen(next);
        rememberGroupState(group.title, next);
    }

    return (
        <Collapsible
            asChild
            open={open}
            onOpenChange={toggle}
            className="group/collapsible"
        >
            <SidebarMenuItem>
                <CollapsibleTrigger asChild>
                    <SidebarMenuButton
                        isActive={hasActiveChild}
                        tooltip={{ children: group.title }}
                    >
                        {group.icon && <group.icon />}
                        <span>{group.title}</span>
                        <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                    </SidebarMenuButton>
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <SidebarMenuSub>
                        {group.items.map((item) => (
                            <SidebarMenuSubItem key={item.title}>
                                <SidebarMenuSubButton
                                    asChild
                                    isActive={isCurrentUrl(item.href)}
                                >
                                    <Link href={item.href} prefetch>
                                        {item.icon && <item.icon />}
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        ))}
                    </SidebarMenuSub>
                </CollapsibleContent>
            </SidebarMenuItem>
        </Collapsible>
    );
}
