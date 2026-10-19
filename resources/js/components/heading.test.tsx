import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import Heading from '@/components/heading';

describe('Heading', () => {
    it('renders the title as a heading', () => {
        render(<Heading title="Purchase orders" />);

        expect(
            screen.getByRole('heading', { level: 2, name: 'Purchase orders' }),
        ).toBeInTheDocument();
    });

    it('shows the description only when one is given', () => {
        const { rerender } = render(<Heading title="Purchase orders" />);

        expect(
            screen.queryByText('Read from the ERP.'),
        ).not.toBeInTheDocument();

        rerender(
            <Heading
                title="Purchase orders"
                description="Read from the ERP."
            />,
        );

        expect(screen.getByText('Read from the ERP.')).toBeVisible();
    });
});
