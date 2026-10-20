import { router } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { MoveActions } from '@/components/move-actions';

afterEach(() => {
    vi.restoreAllMocks();
});

describe('MoveActions', () => {
    it('moves the record up or down with its name for the message', async () => {
        const patch = vi.spyOn(router, 'patch').mockImplementation(() => {});
        render(
            <MoveActions name="Kas" url="/coa-accounts/1101/move" canManage />,
        );

        await userEvent.click(screen.getByRole('button', { name: 'Move up' }));

        expect(patch).toHaveBeenCalledWith(
            '/coa-accounts/1101/move',
            { direction: 'up', name: 'Kas' },
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('renders nothing without the manage permission', () => {
        render(
            <MoveActions
                name="Kas"
                url="/coa-accounts/1101/move"
                canManage={false}
            />,
        );

        expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });
});
