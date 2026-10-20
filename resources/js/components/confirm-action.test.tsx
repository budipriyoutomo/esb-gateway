import { router } from '@inertiajs/react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { ConfirmAction } from '@/components/confirm-action';

type VisitOptions = { onStart?: () => void; onFinish?: () => void };

afterEach(() => {
    vi.restoreAllMocks();
});

function setup() {
    render(
        <ConfirmAction
            label="Finish"
            title="Finish GTR-1?"
            description="Nothing more is delivered against it."
            url="/goods-transfer-requests/GTR-1/finish"
        />,
    );
}

describe('ConfirmAction', () => {
    it('confirms, then sends a PATCH to the url', async () => {
        const patch = vi
            .spyOn(router, 'patch')
            .mockImplementation(() => undefined);
        setup();

        await userEvent.click(screen.getByRole('button', { name: 'Finish' }));
        const dialog = screen.getByRole('dialog', { name: 'Finish GTR-1?' });

        expect(dialog).toHaveTextContent(
            'Nothing more is delivered against it.',
        );

        await userEvent.click(
            within(dialog).getByRole('button', { name: 'Finish' }),
        );

        expect(patch).toHaveBeenCalledTimes(1);
        expect(patch.mock.calls[0][0]).toBe(
            '/goods-transfer-requests/GTR-1/finish',
        );
    });

    it('cancelling sends nothing', async () => {
        const patch = vi.spyOn(router, 'patch');
        setup();

        await userEvent.click(screen.getByRole('button', { name: 'Finish' }));
        await userEvent.click(screen.getByRole('button', { name: 'Cancel' }));

        expect(patch).not.toHaveBeenCalled();
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('locks the controls while the write is in flight', async () => {
        vi.spyOn(router, 'patch').mockImplementation((_url, _data, options) => {
            (options as VisitOptions).onStart?.();
        });
        setup();

        await userEvent.click(screen.getByRole('button', { name: 'Finish' }));
        const dialog = screen.getByRole('dialog');
        await userEvent.click(
            within(dialog).getByRole('button', { name: 'Finish' }),
        );

        expect(
            within(dialog).getByRole('button', { name: 'Sending…' }),
        ).toBeDisabled();
        expect(
            within(dialog).getByRole('button', { name: 'Cancel' }),
        ).toBeDisabled();
    });
});
