import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vite-plus/test';
import { ChipRemoveButton } from '@/components/chip-remove-button';

describe('ChipRemoveButton', () => {
    it('is a named button that removes its chip', async () => {
        const onRemove = vi.fn();
        render(
            <ChipRemoveButton
                label="Remove filter: Acme"
                onRemove={onRemove}
            />,
        );

        await userEvent.click(
            screen.getByRole('button', { name: 'Remove filter: Acme' }),
        );

        expect(onRemove).toHaveBeenCalledOnce();
    });

    it('reaches 44px on a touch screen without growing the chip', () => {
        render(<ChipRemoveButton label="Remove" onRemove={() => {}} />);

        // The hit area grows through a pseudo-element; the icon stays small.
        expect(screen.getByRole('button').className).toContain(
            'pointer-coarse:after:-inset-3.5',
        );
    });
});
