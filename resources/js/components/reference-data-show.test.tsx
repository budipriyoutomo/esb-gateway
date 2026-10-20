import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { DetailField } from '@/components/detail-fields';
import { ReferenceDataShow } from '@/components/reference-data-show';

function fields() {
    return screen
        .getAllByRole('term')
        .map((term) => [
            term.textContent,
            term.nextElementSibling?.textContent,
        ]);
}

describe('ReferenceDataShow', () => {
    it('shows the record’s fields, then status and audit', () => {
        render(
            <ReferenceDataShow
                title="KG"
                back={{ href: '/units', label: 'Units' }}
                record={{
                    active: true,
                    createdBy: 'admin',
                    createdAt: '2026-01-10 08:00:00',
                    editedBy: null,
                    editedAt: null,
                }}
            >
                <DetailField label="Metric" value="Weight" />
            </ReferenceDataShow>,
        );

        expect(screen.getByRole('heading', { name: 'KG' })).toBeVisible();
        expect(screen.getByRole('link', { name: 'Units' })).toHaveAttribute(
            'href',
            '/units',
        );
        expect(fields()).toEqual([
            ['Metric', 'Weight'],
            ['Status', 'Active'],
            ['Created', 'admin · 10 Jan 2026 08:00'],
            ['Last edited', '—'],
        ]);
    });

    it('says inactive, and a dash when the status is unknown', () => {
        const { rerender } = render(
            <ReferenceDataShow
                title="X"
                back={{ href: '/x', label: 'X' }}
                record={{
                    active: false,
                    createdBy: null,
                    createdAt: null,
                    editedBy: null,
                    editedAt: null,
                }}
            >
                {null}
            </ReferenceDataShow>,
        );

        expect(fields()[0]).toEqual(['Status', 'Inactive']);

        rerender(
            <ReferenceDataShow
                title="X"
                back={{ href: '/x', label: 'X' }}
                record={{
                    active: null,
                    createdBy: null,
                    createdAt: null,
                    editedBy: null,
                    editedAt: null,
                }}
            >
                {null}
            </ReferenceDataShow>,
        );

        expect(fields()[0]).toEqual(['Status', '—']);
    });

    it('shows the page actions next to the title', () => {
        render(
            <ReferenceDataShow
                title="KG"
                back={{ href: '/units', label: 'Units' }}
                record={{
                    active: true,
                    createdBy: null,
                    createdAt: null,
                    editedBy: null,
                    editedAt: null,
                }}
                actions={<button type="button">Delete</button>}
            >
                {null}
            </ReferenceDataShow>,
        );

        expect(
            screen.getByRole('group', { name: 'Page actions' }),
        ).toContainElement(screen.getByRole('button', { name: 'Delete' }));
    });
});
