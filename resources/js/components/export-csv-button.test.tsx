import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { ExportCsvButton, exportQuery } from '@/components/export-csv-button';

describe('ExportCsvButton', () => {
    it('is a plain download link, not an Inertia visit', () => {
        render(<ExportCsvButton href="/units/export?search=KG" />);

        const link = screen.getByRole('link', { name: 'Export CSV' });
        expect(link).toHaveAttribute('href', '/units/export?search=KG');
        expect(link).toHaveAttribute('download');
    });
});

describe('exportQuery', () => {
    it('keeps the filters and drops paging and empty values', () => {
        expect(
            exportQuery({
                page: 3,
                limit: 50,
                search: 'KG',
                flagActive: 1,
                notes: '',
                branchId: undefined,
            }),
        ).toEqual({ search: 'KG', flagActive: 1 });
    });
});
