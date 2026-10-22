import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { LookupChips } from '@/components/lookup-chips';
import * as lookups from '@/lib/lookups';

afterEach(() => {
    vi.restoreAllMocks();
});

const outlet = {
    value: '2',
    label: 'OUTLET Tangerang 1',
    code: 'OUT1',
    meta: {},
};

describe('LookupChips', () => {
    it('adds a picked option once and removes it again', async () => {
        vi.spyOn(lookups, 'fetchLookup').mockResolvedValue({
            options: [outlet],
            hasMore: false,
        });
        const onChange = vi.fn();
        const { rerender } = render(
            <LookupChips
                id="branches"
                label="Branches"
                kind="branches"
                value={[]}
                onChange={onChange}
                debounceMs={0}
            />,
        );

        await userEvent.click(
            screen.getByRole('combobox', { name: 'Branches' }),
        );
        await userEvent.click(
            await screen.findByRole('option', { name: /OUTLET Tangerang 1/ }),
        );

        expect(onChange).toHaveBeenLastCalledWith([outlet]);

        rerender(
            <LookupChips
                id="branches"
                label="Branches"
                kind="branches"
                value={[outlet]}
                onChange={onChange}
                debounceMs={0}
            />,
        );

        expect(screen.getByText('OUTLET Tangerang 1')).toBeVisible();
        await userEvent.click(
            screen.getByRole('button', { name: 'Remove OUTLET Tangerang 1' }),
        );

        expect(onChange).toHaveBeenLastCalledWith([]);
    });
});
