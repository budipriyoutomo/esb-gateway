import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { LookupCombobox } from '@/components/lookup-combobox';
import * as lookups from '@/lib/lookups';

afterEach(() => {
    vi.restoreAllMocks();
});

const location = {
    value: '100',
    label: 'Gudang Utama',
    code: null,
    meta: { branchId: 1 },
};

describe('LookupCombobox', () => {
    it('loads its kind for the given branch', async () => {
        const fetchLookup = vi
            .spyOn(lookups, 'fetchLookup')
            .mockResolvedValue({ options: [location], hasMore: false });
        const onChange = vi.fn();

        render(
            <LookupCombobox
                id="location"
                label="Location"
                kind="locations"
                branchId={1}
                value={null}
                onChange={onChange}
                debounceMs={0}
            />,
        );

        await userEvent.click(
            screen.getByRole('combobox', { name: 'Location' }),
        );
        await userEvent.click(
            await screen.findByRole('option', { name: 'Gudang Utama' }),
        );

        expect(fetchLookup).toHaveBeenCalledWith(
            'locations',
            { search: '', page: 1, branchId: 1 },
            expect.any(AbortSignal),
        );
        // The full option, meta included, reaches the form.
        expect(onChange).toHaveBeenCalledExactlyOnceWith(location);
    });

    it('waits for a branch when its kind needs one', async () => {
        const fetchLookup = vi.spyOn(lookups, 'fetchLookup');

        render(
            <LookupCombobox
                id="product"
                label="Product"
                kind="transfer-products"
                branchId={null}
                value={null}
                onChange={() => {}}
            />,
        );

        const input = screen.getByRole('combobox', { name: 'Product' });

        expect(input).toBeDisabled();
        expect(input).toHaveAttribute('placeholder', 'Choose a branch first');

        await userEvent.click(input);

        expect(fetchLookup).not.toHaveBeenCalled();
    });
});
