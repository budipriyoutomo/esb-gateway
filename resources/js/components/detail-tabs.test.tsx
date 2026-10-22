import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vite-plus/test';
import { DetailTabs } from '@/components/detail-tabs';

const tabs = [
    { value: 'prices', label: 'Prices', count: 2, content: <p>Price table</p> },
    { value: 'package', label: 'Package', content: <p>Package groups</p> },
    { value: 'extras', label: 'Extras', count: 0, content: <p>No extras.</p> },
];

describe('DetailTabs', () => {
    it('is a named tab list with the first tab open', () => {
        render(<DetailTabs label="Menu sections" tabs={tabs} />);

        expect(
            screen.getByRole('tablist', { name: 'Menu sections' }),
        ).toBeVisible();
        expect(screen.getByRole('tab', { name: /Prices/ })).toHaveAttribute(
            'aria-selected',
            'true',
        );
        expect(screen.getByRole('tabpanel')).toHaveTextContent('Price table');
    });

    it('shows how many rows a tab holds, zero included', () => {
        render(<DetailTabs label="Menu sections" tabs={tabs} />);

        expect(screen.getByRole('tab', { name: 'Prices 2' })).toBeVisible();
        expect(screen.getByRole('tab', { name: 'Extras 0' })).toBeVisible();
        expect(screen.getByRole('tab', { name: 'Package' })).toBeVisible();
    });

    it('switches panel on click', async () => {
        const user = userEvent.setup();
        render(<DetailTabs label="Menu sections" tabs={tabs} />);

        await user.click(screen.getByRole('tab', { name: /Extras/ }));

        expect(screen.getByRole('tabpanel')).toHaveTextContent('No extras.');
    });

    it('moves between tabs with the arrow keys', async () => {
        const user = userEvent.setup();
        render(<DetailTabs label="Menu sections" tabs={tabs} />);

        await user.click(screen.getByRole('tab', { name: /Prices/ }));
        await user.keyboard('{ArrowRight}');

        expect(screen.getByRole('tab', { name: 'Package' })).toHaveFocus();
        expect(screen.getByRole('tabpanel')).toHaveTextContent(
            'Package groups',
        );
    });

    it('can open on another tab', () => {
        render(
            <DetailTabs
                label="Menu sections"
                tabs={tabs}
                defaultValue="extras"
            />,
        );

        expect(screen.getByRole('tabpanel')).toHaveTextContent('No extras.');
    });
});
