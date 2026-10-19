import { router } from '@inertiajs/react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { ApprovalActions } from '@/components/approval-actions';

type VisitOptions = {
    onStart?: () => void;
    onSuccess?: () => void;
    onError?: (errors: Record<string, string>) => void;
    onFinish?: () => void;
};

afterEach(() => {
    vi.restoreAllMocks();
});

function setup(props: Partial<ComponentProps<typeof ApprovalActions>> = {}) {
    render(
        <ApprovalActions
            documentLabel="purchase order"
            number="PO-1"
            decisionUrl="/purchase-orders/PO-1/decisions"
            deleteUrl="/purchase-orders/PO-1"
            canDecide
            canDelete
            rejectReason={{ max: 500 }}
            {...props}
        />,
    );
}

/** router.post that runs the given lifecycle callbacks synchronously. */
function fakePost(...phases: ((options: VisitOptions) => void)[]) {
    return vi
        .spyOn(router, 'post')
        .mockImplementation((_url, _data, options) => {
            phases.forEach((phase) => {
                phase((options ?? {}) as VisitOptions);
            });
        });
}

const succeed = (o: VisitOptions) => {
    o.onStart?.();
    o.onSuccess?.();
    o.onFinish?.();
};

describe('ApprovalActions', () => {
    it('shows only what the person may do', () => {
        setup({ canDecide: false });

        expect(
            screen.queryByRole('button', { name: 'Authorize' }),
        ).not.toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Delete' })).toBeVisible();
    });

    it('renders nothing when the person may do nothing', () => {
        setup({ canDecide: false, canDelete: false });

        expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });

    it('offers no delete without a delete route', () => {
        setup({ deleteUrl: undefined });

        expect(
            screen.queryByRole('button', { name: 'Delete' }),
        ).not.toBeInTheDocument();
    });

    it('confirms, then authorizes without a reason', async () => {
        const post = fakePost(succeed);
        setup();

        await userEvent.click(
            screen.getByRole('button', { name: 'Authorize' }),
        );
        const dialog = screen.getByRole('dialog', {
            name: 'Authorize this purchase order?',
        });

        expect(
            within(dialog).queryByLabelText('Reason'),
        ).not.toBeInTheDocument();

        await userEvent.click(
            within(dialog).getByRole('button', { name: 'Authorize' }),
        );

        expect(post).toHaveBeenCalledOnce();
        expect(post.mock.calls[0][0]).toBe('/purchase-orders/PO-1/decisions');
        expect(post.mock.calls[0][1]).toEqual({ decision: 'authorize' });
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('requires a non-blank reason to reject when the document takes one', async () => {
        const post = fakePost(succeed);
        setup();

        await userEvent.click(screen.getByRole('button', { name: 'Reject' }));
        const dialog = screen.getByRole('dialog', {
            name: 'Reject this purchase order?',
        });
        const confirm = within(dialog).getByRole('button', { name: 'Reject' });
        const reason = within(dialog).getByLabelText('Reason');

        expect(reason).toHaveAttribute('maxlength', '500');
        expect(confirm).toBeDisabled();

        await userEvent.type(reason, '   ');
        expect(confirm).toBeDisabled();

        await userEvent.clear(reason);
        await userEvent.type(reason, 'Wrong supplier');
        await userEvent.click(confirm);

        expect(post.mock.calls[0][1]).toEqual({
            decision: 'reject',
            reason: 'Wrong supplier',
        });
    });

    it('rejects without a reason field when the document takes none', async () => {
        const post = fakePost(succeed);
        setup({ rejectReason: undefined });

        await userEvent.click(screen.getByRole('button', { name: 'Reject' }));
        const dialog = screen.getByRole('dialog');

        expect(
            within(dialog).queryByLabelText('Reason'),
        ).not.toBeInTheDocument();

        await userEvent.click(
            within(dialog).getByRole('button', { name: 'Reject' }),
        );

        expect(post.mock.calls[0][1]).toEqual({ decision: 'reject' });
    });

    it('keeps the dialog open with the server validation error', async () => {
        fakePost((o) => {
            o.onStart?.();
            o.onError?.({ reason: 'The reason may not be longer than 500.' });
            o.onFinish?.();
        });
        setup();

        await userEvent.click(screen.getByRole('button', { name: 'Reject' }));
        const dialog = screen.getByRole('dialog');
        await userEvent.type(within(dialog).getByLabelText('Reason'), 'x');
        await userEvent.click(
            within(dialog).getByRole('button', { name: 'Reject' }),
        );

        expect(screen.getByRole('dialog')).toHaveTextContent(
            'The reason may not be longer than 500.',
        );
    });

    it('locks every control while the write is in flight', async () => {
        // Started, never finished: the ERP has not answered yet.
        fakePost((o) => {
            o.onStart?.();
        });
        setup();

        await userEvent.click(
            screen.getByRole('button', { name: 'Authorize' }),
        );
        const dialog = screen.getByRole('dialog');
        await userEvent.click(
            within(dialog).getByRole('button', { name: 'Authorize' }),
        );

        expect(
            within(dialog).getByRole('button', { name: 'Sending…' }),
        ).toBeDisabled();
        expect(
            within(dialog).getByRole('button', { name: 'Cancel' }),
        ).toBeDisabled();

        await userEvent.keyboard('{Escape}');

        expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('cancelling sends nothing', async () => {
        const post = vi.spyOn(router, 'post').mockImplementation(() => {});
        setup();

        await userEvent.click(screen.getByRole('button', { name: 'Reject' }));
        await userEvent.click(screen.getByRole('button', { name: 'Cancel' }));

        expect(post).not.toHaveBeenCalled();
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('confirms, then deletes', async () => {
        const destroy = vi
            .spyOn(router, 'delete')
            .mockImplementation((_url, options) => {
                succeed((options ?? {}) as VisitOptions);
            });
        setup();

        await userEvent.click(screen.getByRole('button', { name: 'Delete' }));
        const dialog = screen.getByRole('dialog', { name: 'Delete PO-1?' });

        expect(dialog).toHaveTextContent('cannot be undone');

        await userEvent.click(
            within(dialog).getByRole('button', { name: 'Delete' }),
        );

        expect(destroy).toHaveBeenCalledOnce();
        expect(destroy.mock.calls[0][0]).toBe('/purchase-orders/PO-1');
    });
});
