import { Link } from '@inertiajs/react';
import { BookOpen, FolderGit2, LayoutGrid, Package, Users } from 'lucide-react';
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
import { index as productsIndex } from '@/routes/products';
import { index as usersIndex } from '@/routes/users';
import type { NavItem, Permission } from '@/types';

type GatedNavItem = NavItem & { permission: Permission };

const mainNavItems: GatedNavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
        permission: 'dashboard.view',
    },
    {
        title: 'Products',
        href: productsIndex(),
        icon: Package,
        permission: 'esb.invoke',
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
    const visibleNavItems = mainNavItems.filter((item) => can(item.permission));

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
