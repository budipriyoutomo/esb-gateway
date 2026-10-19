import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { PageHeader } from '@/components/page-header';

describe('PageHeader', () => {
    it('renders the title, description and actions', () => {
        render(
            <PageHeader
                title="PO-0001"
                description="Read live from the ERP."
                actions={<button type="button">Authorize</button>}
            />,
        );

        expect(
            screen.getByRole('heading', { name: 'PO-0001' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Read live from the ERP.')).toBeVisible();
        expect(
            screen.getByRole('button', { name: 'Authorize' }),
        ).toBeInTheDocument();
    });

    it('links back to the list when asked', () => {
        render(
            <PageHeader
                title="PO-0001"
                back={{ href: '/purchase-orders', label: 'Purchase orders' }}
            />,
        );

        expect(
            screen.getByRole('link', { name: 'Purchase orders' }),
        ).toHaveAttribute('href', '/purchase-orders');
    });

    it('renders no back link or action area by default', () => {
        render(<PageHeader title="Purchase orders" />);

        expect(screen.queryByRole('link')).not.toBeInTheDocument();
        expect(screen.queryByRole('group')).not.toBeInTheDocument();
    });

    it('labels the action area for assistive technology', () => {
        render(
            <PageHeader
                title="PO-0001"
                actions={<button type="button">Delete</button>}
            />,
        );

        expect(
            screen.getByRole('group', { name: 'Page actions' }),
        ).toContainElement(screen.getByRole('button', { name: 'Delete' }));
    });
});
