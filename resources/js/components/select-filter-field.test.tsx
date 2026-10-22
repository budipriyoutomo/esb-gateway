import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vite-plus/test';
import { SelectFilterField } from '@/components/select-filter-field';

const options = [
    { value: 'core', label: 'core' },
    { value: 'oms', label: 'oms' },
];

describe('SelectFilterField', () => {
    it('is a labelled select showing its "any" text for no filter', () => {
        render(
            <SelectFilterField
                id="family"
                label="Family"
                anyLabel="Any family"
                options={options}
                value=""
                onChange={() => {}}
            />,
        );

        expect(
            screen.getByRole('combobox', { name: 'Family' }),
        ).toHaveTextContent('Any family');
    });

    it('gives the chosen value, or "" for any', async () => {
        const onChange = vi.fn();
        const user = userEvent.setup();
        render(
            <SelectFilterField
                id="family"
                label="Family"
                anyLabel="Any family"
                options={options}
                value="core"
                onChange={onChange}
            />,
        );

        await user.click(screen.getByRole('combobox', { name: 'Family' }));
        await user.click(screen.getByRole('option', { name: 'oms' }));
        expect(onChange).toHaveBeenLastCalledWith('oms');

        await user.click(screen.getByRole('combobox', { name: 'Family' }));
        await user.click(screen.getByRole('option', { name: 'Any family' }));
        expect(onChange).toHaveBeenLastCalledWith('');
    });
});
