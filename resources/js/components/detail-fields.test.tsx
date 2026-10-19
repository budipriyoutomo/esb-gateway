import { render, screen, within } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import {
    DetailField,
    DetailFields,
    DetailSummary,
} from '@/components/detail-fields';

describe('DetailFields', () => {
    it('pairs each label with its value as a description list', () => {
        render(
            <DetailFields>
                <DetailField label="Supplier" value="Acme" />
                <DetailField label="Branch" value="Jakarta" />
            </DetailFields>,
        );

        const terms = screen.getAllByRole('term');
        const values = screen.getAllByRole('definition');

        expect(terms.map((t) => t.textContent)).toEqual(['Supplier', 'Branch']);
        expect(values.map((d) => d.textContent)).toEqual(['Acme', 'Jakarta']);
    });

    it('shows a dash for a missing or blank value', () => {
        render(
            <DetailFields>
                <DetailField label="Project" value={null} />
                <DetailField label="Notes" value="" />
            </DetailFields>,
        );

        expect(
            screen.getAllByRole('definition').map((d) => d.textContent),
        ).toEqual(['—', '—']);
    });

    it('accepts rich values', () => {
        render(
            <DetailFields>
                <DetailField
                    label="Status"
                    value={<strong>Authorized</strong>}
                />
            </DetailFields>,
        );

        expect(
            within(screen.getByRole('definition')).getByText('Authorized'),
        ).toBeInTheDocument();
    });
});

describe('DetailSummary', () => {
    it('shows the key facts in a labelled region', () => {
        render(
            <DetailSummary
                label="Purchase order summary"
                items={[
                    { label: 'Date', value: '20 Mar 2026' },
                    { label: 'Total', value: 'Rp 1.500,00', align: 'right' },
                ]}
            />,
        );

        const region = screen.getByRole('region', {
            name: 'Purchase order summary',
        });

        expect(within(region).getByText('Rp 1.500,00')).toBeVisible();
        expect(
            within(region)
                .getAllByRole('term')
                .map((t) => t.textContent),
        ).toEqual(['Date', 'Total']);
    });
});
