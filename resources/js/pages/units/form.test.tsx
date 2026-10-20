import { router } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import UnitForm from '@/pages/units/form';
import type { UnitOfMeasure } from '@/types/master-data';

afterEach(() => {
    vi.restoreAllMocks();
});

const unit: UnitOfMeasure = {
    provider: 'esb',
    id: 4,
    name: 'KG',
    metricId: 2,
    metricName: 'Weight',
    notes: 'Kilogram',
    taxId: 9,
    active: true,
    createdBy: 'admin',
    createdAt: '2026-01-10T01:00:00Z',
    editedBy: null,
    editedAt: null,
};

describe('Unit form', () => {
    it('creates a unit', async () => {
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        render(<UnitForm unit={null} />);

        expect(screen.getByRole('heading', { name: 'New unit' })).toBeVisible();

        await userEvent.type(screen.getByLabelText('Name'), 'PCS');
        await userEvent.type(screen.getByLabelText('Notes'), 'Pieces');
        await userEvent.click(
            screen.getByRole('button', { name: 'Create unit' }),
        );

        expect(visit).toHaveBeenCalledTimes(1);
        const [url, options] = visit.mock.calls[0];
        expect(url).toEqual(expect.stringMatching(/\/units$/));
        expect(options).toMatchObject({
            method: 'post',
            data: { name: 'PCS', metricId: null, taxId: null, notes: 'Pieces' },
        });
    });

    it('edits a unit, starting from what the ERP has', async () => {
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        render(<UnitForm unit={unit} />);

        expect(screen.getByRole('heading', { name: 'Edit KG' })).toBeVisible();
        expect(screen.getByLabelText('Name')).toHaveValue('KG');
        expect(screen.getByLabelText('Metric')).toHaveValue('Weight');

        await userEvent.click(
            screen.getByRole('button', { name: 'Save changes' }),
        );

        const [url, options] = visit.mock.calls[0];
        expect(url).toEqual(expect.stringMatching(/\/units\/4$/));
        expect(options).toMatchObject({
            method: 'put',
            data: { name: 'KG', metricId: 2, taxId: 9, notes: 'Kilogram' },
        });
    });
});
