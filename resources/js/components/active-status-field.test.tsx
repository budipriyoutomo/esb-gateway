import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vite-plus/test';
import { ActiveStatusField } from '@/components/active-status-field';

describe('ActiveStatusField', () => {
    it('is a labelled select showing "Any status" for no filter', () => {
        render(<ActiveStatusField id="x" value="" onChange={() => {}} />);

        expect(
            screen.getByRole('combobox', { name: 'Status' }),
        ).toHaveTextContent('Any status');
    });

    it('shows the applied status', () => {
        render(<ActiveStatusField id="x" value="0" onChange={() => {}} />);

        expect(
            screen.getByRole('combobox', { name: 'Status' }),
        ).toHaveTextContent('Inactive');
    });

    it('gives "1", "0", or "" for any status', async () => {
        const onChange = vi.fn();
        const user = userEvent.setup();
        const { rerender } = render(
            <ActiveStatusField id="x" value="" onChange={onChange} />,
        );

        await user.click(screen.getByRole('combobox', { name: 'Status' }));
        await user.click(screen.getByRole('option', { name: 'Active' }));
        expect(onChange).toHaveBeenLastCalledWith('1');

        rerender(<ActiveStatusField id="x" value="1" onChange={onChange} />);
        await user.click(screen.getByRole('combobox', { name: 'Status' }));
        await user.click(screen.getByRole('option', { name: 'Any status' }));
        expect(onChange).toHaveBeenLastCalledWith('');
    });
});
