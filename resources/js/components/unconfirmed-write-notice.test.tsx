import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vite-plus/test';
import { UnconfirmedWriteNotice } from '@/components/unconfirmed-write-notice';

describe('UnconfirmedWriteNotice', () => {
    it('says plainly that the outcome is unknown and what to do', () => {
        render(
            <UnconfirmedWriteNotice
                action="authorize PO-1"
                reference="corr-123"
            />,
        );

        const notice = screen.getByRole('alert');

        expect(notice).toHaveTextContent(
            'The ERP did not confirm the request to authorize PO-1',
        );
        expect(notice).toHaveTextContent(
            'It may or may not have been applied. Check the status below before trying again.',
        );
    });

    it('shows the reference to quote to support', () => {
        render(
            <UnconfirmedWriteNotice
                action="delete PO-1"
                reference="corr-123"
            />,
        );

        expect(screen.getByText('corr-123')).toBeVisible();
    });

    it('can be dismissed when the page allows it', async () => {
        const onDismiss = vi.fn();
        render(
            <UnconfirmedWriteNotice
                action="reject PO-1"
                reference="corr-123"
                onDismiss={onDismiss}
            />,
        );

        await userEvent.click(screen.getByRole('button', { name: 'Dismiss' }));

        expect(onDismiss).toHaveBeenCalledOnce();
    });

    it('has no dismiss button otherwise', () => {
        render(
            <UnconfirmedWriteNotice
                action="reject PO-1"
                reference="corr-123"
            />,
        );

        expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });
});
