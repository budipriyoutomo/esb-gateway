import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { ImportLinks } from '@/components/import-links';

describe('ImportLinks', () => {
    it('links to each import screen', () => {
        render(
            <ImportLinks
                canImport
                imports={[
                    { kind: 'products-create', label: 'Import new' },
                    { kind: 'products-update', label: 'Import changes' },
                ]}
            />,
        );

        expect(
            screen.getByRole('link', { name: 'Import new' }),
        ).toHaveAttribute('href', '/imports/products-create');
        expect(
            screen.getByRole('link', { name: 'Import changes' }),
        ).toHaveAttribute('href', '/imports/products-update');
    });

    it('renders nothing without the permission', () => {
        render(
            <ImportLinks
                canImport={false}
                imports={[{ kind: 'units', label: 'Import' }]}
            />,
        );

        expect(screen.queryByRole('link')).not.toBeInTheDocument();
    });
});
