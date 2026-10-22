import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { UnconfirmedWriteBanner } from '@/components/unconfirmed-write-banner';

const page: { flash: Record<string, unknown> } = { flash: {} };

vi.mock('@inertiajs/react', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/react')>()),
    usePage: () => page,
}));

beforeEach(() => {
    page.flash = {};
});

describe('UnconfirmedWriteBanner', () => {
    it('renders nothing when no write is unconfirmed', () => {
        const { container } = render(<UnconfirmedWriteBanner />);

        expect(container).toBeEmptyDOMElement();
    });

    it('shows the flashed action and reference', () => {
        page.flash = {
            unconfirmedWrite: {
                action: 'authorize PO-1',
                reference: 'corr-123',
            },
        };

        render(<UnconfirmedWriteBanner />);

        expect(screen.getByRole('alert')).toHaveTextContent(
            'The ERP did not confirm the request to authorize PO-1',
        );
        expect(screen.getByText('corr-123')).toBeVisible();
    });

    it('can be dismissed', async () => {
        page.flash = {
            unconfirmedWrite: { action: 'reject PO-1', reference: 'corr-1' },
        };
        const user = userEvent.setup();

        render(<UnconfirmedWriteBanner />);
        await user.click(screen.getByRole('button', { name: 'Dismiss' }));

        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('comes back for a newer unconfirmed write after a dismissal', async () => {
        page.flash = {
            unconfirmedWrite: { action: 'reject PO-1', reference: 'corr-1' },
        };
        const user = userEvent.setup();

        const { rerender } = render(<UnconfirmedWriteBanner />);
        await user.click(screen.getByRole('button', { name: 'Dismiss' }));

        page.flash = {
            unconfirmedWrite: { action: 'finish GTR-2', reference: 'corr-2' },
        };
        rerender(<UnconfirmedWriteBanner />);

        expect(screen.getByRole('alert')).toHaveTextContent('finish GTR-2');
    });
});
