import { router } from '@inertiajs/react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { RecordActions } from '@/components/record-actions';

type VisitOptions = {
    onStart?: () => void;
    onFinish?: () => void;
};

afterEach(() => {
    vi.restoreAllMocks();
});

function setup(props: Partial<ComponentProps<typeof RecordActions>> = {}) {
    render(
        <RecordActions
            kind="product category"
            name="Bahan Baku"
            active={true}
            canManage
            urls={{
                delete: '/product-categories/3',
                deactivate: '/product-categories/3/deactivate',
                restore: '/product-categories/3/restore',
            }}
            {...props}
        />,
    );
}

describe('RecordActions', () => {
    it('offers deactivate and delete on an active record', () => {
        setup();

        expect(
            screen.getByRole('button', { name: 'Deactivate' }),
        ).toBeVisible();
        expect(screen.getByRole('button', { name: 'Delete' })).toBeVisible();
        expect(
            screen.queryByRole('button', { name: 'Restore' }),
        ).not.toBeInTheDocument();
    });

    it('offers restore and delete on an inactive record', () => {
        setup({ active: false });

        expect(screen.getByRole('button', { name: 'Restore' })).toBeVisible();
        expect(
            screen.queryByRole('button', { name: 'Deactivate' }),
        ).not.toBeInTheDocument();
    });

    it('offers only what the module supports', () => {
        setup({
            active: false,
            urls: { delete: '/units/4', restore: '/units/4/restore' },
        });

        expect(screen.getByRole('button', { name: 'Restore' })).toBeVisible();

        setup({
            active: true,
            urls: { delete: '/units/4', restore: '/units/4/restore' },
        });

        expect(
            screen.queryByRole('button', { name: 'Deactivate' }),
        ).not.toBeInTheDocument();
    });

    it('offers every supported action when the status is unknown', () => {
        setup({ active: null });

        expect(screen.getAllByRole('button')).toHaveLength(3);
    });

    it('renders nothing without the manage permission', () => {
        setup({ canManage: false, editUrl: '/product-categories/3/edit' });

        expect(screen.queryByRole('button')).not.toBeInTheDocument();
        expect(screen.queryByRole('link')).not.toBeInTheDocument();
    });

    it('links to the edit form when the module has one', () => {
        setup({ editUrl: '/product-categories/3/edit' });

        expect(screen.getByRole('link', { name: 'Edit' })).toHaveAttribute(
            'href',
            '/product-categories/3/edit',
        );
    });

    it('confirms, then deactivates with the record name for the message', async () => {
        const patch = vi
            .spyOn(router, 'patch')
            .mockImplementation((_url, _data, options) => {
                (options as VisitOptions).onStart?.();
                (options as VisitOptions).onFinish?.();
            });
        setup();

        await userEvent.click(
            screen.getByRole('button', { name: 'Deactivate' }),
        );
        const dialog = screen.getByRole('dialog', {
            name: 'Deactivate Bahan Baku?',
        });

        expect(dialog).toHaveTextContent('can be restored later');

        await userEvent.click(
            within(dialog).getByRole('button', { name: 'Deactivate' }),
        );

        expect(patch).toHaveBeenCalledOnce();
        expect(patch.mock.calls[0][0]).toBe('/product-categories/3/deactivate');
        expect(patch.mock.calls[0][1]).toEqual({ name: 'Bahan Baku' });
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('confirms, then deletes', async () => {
        const destroy = vi.spyOn(router, 'delete').mockImplementation(() => {});
        setup();

        await userEvent.click(screen.getByRole('button', { name: 'Delete' }));
        const dialog = screen.getByRole('dialog', {
            name: 'Delete Bahan Baku?',
        });

        expect(dialog).toHaveTextContent('cannot be undone from the gateway');

        await userEvent.click(
            within(dialog).getByRole('button', { name: 'Delete' }),
        );

        expect(destroy.mock.calls[0][0]).toBe('/product-categories/3');
        expect(destroy.mock.calls[0][1]).toMatchObject({
            data: { name: 'Bahan Baku' },
        });
    });

    it('locks the dialog while the write is in flight', async () => {
        vi.spyOn(router, 'patch').mockImplementation((_url, _data, options) => {
            (options as VisitOptions).onStart?.();
        });
        setup({ active: false });

        await userEvent.click(screen.getByRole('button', { name: 'Restore' }));
        const dialog = screen.getByRole('dialog');
        await userEvent.click(
            within(dialog).getByRole('button', { name: 'Restore' }),
        );

        expect(
            within(dialog).getByRole('button', { name: 'Sending…' }),
        ).toBeDisabled();

        await userEvent.keyboard('{Escape}');

        expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('cancelling sends nothing', async () => {
        const patch = vi.spyOn(router, 'patch').mockImplementation(() => {});
        setup();

        await userEvent.click(
            screen.getByRole('button', { name: 'Deactivate' }),
        );
        await userEvent.click(screen.getByRole('button', { name: 'Cancel' }));

        expect(patch).not.toHaveBeenCalled();
    });

    it('adds a module-specific warning to a confirmation', async () => {
        setup({
            kind: 'account',
            name: '1101 Kas',
            urls: { deactivate: '/coa-accounts/1101/deactivate' },
            warnings: {
                deactivate: 'Its active sub-accounts are deactivated too.',
            },
        });

        await userEvent.click(
            screen.getByRole('button', { name: 'Deactivate' }),
        );

        expect(screen.getByRole('dialog')).toHaveTextContent(
            'Its active sub-accounts are deactivated too.',
        );
    });
});
