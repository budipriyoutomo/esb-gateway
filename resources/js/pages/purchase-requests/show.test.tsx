import { router } from '@inertiajs/react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import PurchaseRequestShow from '@/pages/purchase-requests/show';
import type { Permission } from '@/types';

const granted: { permissions: Permission[] } = { permissions: [] };

vi.mock('@/hooks/use-permissions', () => ({
    usePermissions: () => ({
        can: (permission: Permission) =>
            granted.permissions.includes(permission),
    }),
}));

beforeEach(() => {
    granted.permissions = [];
});

afterEach(() => {
    vi.restoreAllMocks();
});

const purchaseRequest = {
    provider: 'esb',
    number: 'PR202603260005',
    date: '2026-03-26',
    requiredDate: '2026-03-30',
    branchName: 'Outlet Senopati',
    costCenterName: null,
    projectName: null,
    isTemplate: false,
    statusName: 'New',
    notes: null,
    createdByName: 'Andi Pratama',
    createdAt: '2026-03-26 08:00:00',
    editedByName: null,
    editedAt: null,
    authorizedByName: null,
    authorizedAt: null,
    lines: [],
    purchaseNumbers: [],
    transferNumbers: [],
};

function renderPage() {
    render(<PurchaseRequestShow purchaseRequest={purchaseRequest} />);
}

describe('Purchase request detail', () => {
    it('offers no actions without the permissions', () => {
        granted.permissions = ['esb.invoke'];
        renderPage();

        expect(screen.queryByRole('button')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Edit' }),
        ).not.toBeInTheDocument();
    });

    it('offers authorize and reject to an approver, not edit or delete', () => {
        granted.permissions = ['esb.invoke', 'purchase-requests.approve'];
        renderPage();

        expect(screen.getByRole('button', { name: 'Authorize' })).toBeVisible();
        expect(screen.getByRole('button', { name: 'Reject' })).toBeVisible();
        expect(
            screen.queryByRole('button', { name: 'Delete' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Edit' }),
        ).not.toBeInTheDocument();
    });

    it('offers edit and delete to a manager, not a decision', () => {
        granted.permissions = ['esb.invoke', 'purchase-requests.manage'];
        renderPage();

        expect(screen.getByRole('link', { name: 'Edit' })).toBeVisible();
        expect(screen.getByRole('button', { name: 'Delete' })).toBeVisible();
        expect(
            screen.queryByRole('button', { name: 'Authorize' }),
        ).not.toBeInTheDocument();
    });

    it('rejects without asking for a reason (the ESB takes none)', async () => {
        granted.permissions = ['esb.invoke', 'purchase-requests.approve'];
        const post = vi.spyOn(router, 'post').mockImplementation(() => {});
        renderPage();

        await userEvent.click(screen.getByRole('button', { name: 'Reject' }));

        const dialog = screen.getByRole('dialog');
        expect(
            within(dialog).getByText(
                /The purchase request is rejected in the ERP straight away\./,
            ),
        ).toBeVisible();
        expect(within(dialog).queryByRole('textbox')).not.toBeInTheDocument();

        await userEvent.click(
            within(dialog).getByRole('button', { name: 'Reject' }),
        );

        expect(post).toHaveBeenCalledExactlyOnceWith(
            '/purchase-requests/PR202603260005/decisions',
            { decision: 'reject' },
            expect.anything(),
        );
    });

    it('deletes after confirmation', async () => {
        granted.permissions = ['esb.invoke', 'purchase-requests.manage'];
        const destroy = vi.spyOn(router, 'delete').mockImplementation(() => {});
        renderPage();

        await userEvent.click(screen.getByRole('button', { name: 'Delete' }));
        await userEvent.click(
            within(screen.getByRole('dialog')).getByRole('button', {
                name: 'Delete',
            }),
        );

        expect(destroy).toHaveBeenCalledExactlyOnceWith(
            '/purchase-requests/PR202603260005',
            expect.anything(),
        );
    });
});
