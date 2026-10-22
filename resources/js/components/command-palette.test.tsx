import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { CommandPalette } from '@/components/command-palette';
import type { Permission } from '@/types';

const granted: { permissions: Permission[] } = { permissions: [] };
const visit = vi.fn();

vi.mock('@/hooks/use-permissions', () => ({
    usePermissions: () => ({
        can: (permission: Permission) =>
            granted.permissions.includes(permission),
    }),
}));

vi.mock('@inertiajs/react', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/react')>()),
    router: { visit: (...args: unknown[]) => visit(...args) },
}));

beforeEach(() => {
    granted.permissions = ['dashboard.view', 'esb.invoke', 'users.view'];
    visit.mockReset();
});

async function openPalette() {
    const user = userEvent.setup();
    render(<CommandPalette />);
    await user.keyboard('{Control>}k{/Control}');

    return user;
}

describe('CommandPalette', () => {
    it('opens with Ctrl+K and focuses the search', async () => {
        await openPalette();

        const dialog = screen.getByRole('dialog', { name: 'Go to' });
        const input = within(dialog).getByRole('combobox', {
            name: 'Search pages or type a document number',
        });

        expect(input).toHaveFocus();
    });

    it('opens from the header button too', async () => {
        const user = userEvent.setup();
        render(<CommandPalette />);

        await user.click(screen.getByRole('button', { name: /search/i }));

        expect(screen.getByRole('dialog', { name: 'Go to' })).toBeVisible();
    });

    it('lists only the pages the person may open, filtered as they type', async () => {
        const user = await openPalette();

        expect(screen.getByRole('option', { name: /Users/ })).toBeVisible();
        expect(
            screen.queryByRole('option', { name: /Integration logs/ }),
        ).not.toBeInTheDocument();

        await user.keyboard('disb');

        const options = screen.getAllByRole('option');
        expect(options).toHaveLength(1);
        expect(options[0]).toHaveTextContent('Disbursements');
        expect(options[0]).toHaveTextContent('Finance');
    });

    it('moves with the arrow keys and opens the active page with Enter', async () => {
        const user = await openPalette();
        await user.keyboard('journal');

        const input = screen.getByRole('combobox');
        const [first, second] = screen.getAllByRole('option');

        expect(first).toHaveAttribute('aria-selected', 'true');
        expect(input).toHaveAttribute('aria-activedescendant', first?.id);

        await user.keyboard('{ArrowDown}');
        expect(second).toHaveAttribute('aria-selected', 'true');
        expect(input).toHaveAttribute('aria-activedescendant', second?.id);

        await user.keyboard('{Enter}');

        expect(visit).toHaveBeenCalledWith('/general-journals');
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('offers to open a document number in each module that reads by number', async () => {
        const user = await openPalette();
        await user.keyboard('DB202603200001');

        const option = screen.getByRole('option', {
            name: /Open DB202603200001 in Disbursements/,
        });
        await user.click(option);

        expect(visit).toHaveBeenCalledWith('/disbursements/DB202603200001');
    });

    it('does not offer a document for text that cannot be a number', async () => {
        const user = await openPalette();
        await user.keyboard('purchase order');

        expect(
            screen.queryByRole('option', { name: /^Open / }),
        ).not.toBeInTheDocument();

        await user.clear(screen.getByRole('combobox'));
        await user.keyboard('../users');

        expect(
            screen.queryByRole('option', { name: /^Open / }),
        ).not.toBeInTheDocument();
    });

    it('offers no documents without esb.invoke', async () => {
        granted.permissions = ['dashboard.view'];
        const user = await openPalette();
        await user.keyboard('DB202603200001');

        expect(screen.queryAllByRole('option')).toHaveLength(0);
        expect(screen.getByText('Nothing matches.')).toBeVisible();
    });
});
