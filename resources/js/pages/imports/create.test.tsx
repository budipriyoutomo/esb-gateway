import { router } from '@inertiajs/react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import ImportCreate from '@/pages/imports/create';

const flash: { importErrors?: unknown } = {};

vi.mock('@inertiajs/react', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/react')>()),
    Head: () => null,
    usePage: () => ({ flash, props: {} }),
}));

afterEach(() => {
    vi.restoreAllMocks();
    delete flash.importErrors;
});

const units = {
    kind: 'units',
    label: 'units',
    extensions: ['xlsx'],
    queued: true,
    maxKilobytes: 2048,
    listUrl: '/units',
};

describe('Master data import', () => {
    it('says what it imports, what it takes, and that the ERP imports later', () => {
        render(<ImportCreate import={units} />);

        expect(
            screen.getByRole('heading', { name: 'Import units' }),
        ).toBeVisible();
        expect(screen.getByLabelText('Excel file')).toHaveAttribute(
            'accept',
            '.xlsx',
        );
        expect(screen.getByText(/imports it in the background/)).toBeVisible();
        expect(screen.getByRole('button', { name: 'Import' })).toBeDisabled();
    });

    it('sends the chosen file as form data', async () => {
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        render(<ImportCreate import={units} />);

        const file = new File(['PK'], 'units.xlsx');
        await userEvent.upload(screen.getByLabelText('Excel file'), file);
        expect(screen.getByRole('button', { name: 'Import' })).toBeEnabled();
        await userEvent.click(screen.getByRole('button', { name: 'Import' }));

        const [url, options] = visit.mock.calls[0];
        expect(url).toEqual(expect.stringMatching(/\/imports\/units$/));
        expect(options).toMatchObject({
            method: 'post',
            forceFormData: true,
            data: { file },
        });
    });

    it('lists the rows the ERP refused', () => {
        flash.importErrors = [
            { row: 3, field: 'uomName', message: 'Unit name already used' },
        ];
        render(<ImportCreate import={units} />);

        const table = screen.getByRole('table', { name: 'Refused rows' });
        expect(within(table).getByText('Unit name already used')).toBeVisible();
        expect(within(table).getByText('3')).toBeVisible();
    });
});
