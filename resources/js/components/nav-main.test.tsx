import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Database, Package, Ruler, ScrollText, Settings2 } from 'lucide-react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { NavMain, NAV_GROUPS_STORAGE_KEY } from '@/components/nav-main';
import { SidebarProvider } from '@/components/ui/sidebar';
import { TooltipProvider } from '@/components/ui/tooltip';
import type { NavEntry } from '@/types';

const page = { url: '/dashboard' };

vi.mock('@inertiajs/react', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/react')>()),
    usePage: () => page,
    Link: ({
        href,
        children,
        ...rest
    }: {
        href: string;
        children: React.ReactNode;
    }) => (
        <a href={href} {...rest}>
            {children}
        </a>
    ),
}));

const items: NavEntry[] = [
    {
        title: 'Master data',
        icon: Database,
        items: [
            { title: 'Products', href: '/products', icon: Package },
            { title: 'Units', href: '/units', icon: Ruler },
        ],
    },
    {
        title: 'System',
        icon: Settings2,
        items: [
            {
                title: 'Integration logs',
                href: '/integration-logs',
                icon: ScrollText,
            },
        ],
    },
];

function renderNav() {
    return render(
        <TooltipProvider>
            <SidebarProvider>
                <NavMain items={items} />
            </SidebarProvider>
        </TooltipProvider>,
    );
}

function groupButton(name: string) {
    return screen.getByRole('button', { name });
}

beforeEach(() => {
    page.url = '/dashboard';
    window.localStorage.clear();
});

afterEach(() => {
    vi.restoreAllMocks();
});

describe('NavMain groups', () => {
    it('start closed, except the group holding the current page', () => {
        page.url = '/units/3';
        renderNav();

        expect(groupButton('Master data')).toHaveAttribute(
            'aria-expanded',
            'true',
        );
        expect(groupButton('System')).toHaveAttribute('aria-expanded', 'false');
    });

    it('remember which groups were opened or closed', async () => {
        const user = userEvent.setup();
        const { unmount } = renderNav();

        await user.click(groupButton('System'));
        expect(groupButton('System')).toHaveAttribute('aria-expanded', 'true');
        expect(
            JSON.parse(window.localStorage.getItem(NAV_GROUPS_STORAGE_KEY)!),
        ).toEqual({ System: true });

        unmount();
        renderNav();

        expect(groupButton('System')).toHaveAttribute('aria-expanded', 'true');
        expect(groupButton('Master data')).toHaveAttribute(
            'aria-expanded',
            'false',
        );
    });

    it('still work when storage is unavailable', async () => {
        const user = userEvent.setup();
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
            throw new Error('blocked');
        });
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
            throw new Error('blocked');
        });

        renderNav();
        await user.click(groupButton('System'));

        expect(groupButton('System')).toHaveAttribute('aria-expanded', 'true');
    });

    it('open when the person navigates into a group they had closed', async () => {
        page.url = '/units';
        window.localStorage.setItem(
            NAV_GROUPS_STORAGE_KEY,
            JSON.stringify({ 'Master data': false }),
        );

        renderNav();

        expect(groupButton('Master data')).toHaveAttribute(
            'aria-expanded',
            'true',
        );
    });
});
