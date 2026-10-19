import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vite-plus/test';
import { Combobox } from '@/components/combobox';
import type { ComboboxOption, LoadOptions } from '@/components/combobox';

const branches: ComboboxOption[] = [
    { value: '1', label: 'Jakarta Pusat', code: 'JKT' },
    { value: '2', label: 'Bandung', code: 'BDG' },
    { value: '3', label: 'Surabaya', code: 'SBY' },
];

function loader(
    pages: Record<number, { options: ComboboxOption[]; hasMore: boolean }> = {
        1: { options: branches, hasMore: false },
    },
) {
    return vi.fn<LoadOptions>((_search, page) =>
        Promise.resolve(pages[page] ?? { options: [], hasMore: false }),
    );
}

function setup(
    props: Partial<Parameters<typeof Combobox>[0]> = {},
    load = loader(),
) {
    const onChange = vi.fn();
    render(
        <Combobox
            id="branch"
            label="Branch"
            value={null}
            onChange={onChange}
            loadOptions={load}
            debounceMs={0}
            {...props}
        />,
    );

    return {
        onChange,
        load,
        input: screen.getByRole('combobox', { name: 'Branch' }),
    };
}

describe('Combobox', () => {
    it('is a labelled, collapsed combobox until used', () => {
        const { input } = setup();

        expect(input).toHaveAttribute('aria-expanded', 'false');
        expect(screen.queryByRole('listbox')).not.toBeInTheDocument();
    });

    it('loads and lists options when opened', async () => {
        const { input, load } = setup();

        await userEvent.click(input);

        expect(await screen.findAllByRole('option')).toHaveLength(3);
        expect(input).toHaveAttribute('aria-expanded', 'true');
        expect(load).toHaveBeenCalledWith('', 1);
    });

    it('searches as the person types', async () => {
        const { input, load } = setup();

        await userEvent.type(input, 'band');

        await waitFor(() => {
            expect(load).toHaveBeenLastCalledWith('band', 1);
        });
    });

    it('selects with the keyboard', async () => {
        const { input, onChange } = setup();

        await userEvent.click(input);
        await screen.findAllByRole('option');
        await userEvent.keyboard('{ArrowDown}{ArrowDown}');

        expect(screen.getByRole('option', { name: /Bandung/ })).toHaveAttribute(
            'aria-selected',
            'true',
        );
        expect(input).toHaveAttribute(
            'aria-activedescendant',
            screen.getByRole('option', { name: /Bandung/ }).id,
        );

        await userEvent.keyboard('{Enter}');

        expect(onChange).toHaveBeenCalledExactlyOnceWith(branches[1]);
        expect(screen.queryByRole('listbox')).not.toBeInTheDocument();
    });

    it('selects with a click', async () => {
        const { input, onChange } = setup();

        await userEvent.click(input);
        await userEvent.click(
            await screen.findByRole('option', { name: /Surabaya/ }),
        );

        expect(onChange).toHaveBeenCalledExactlyOnceWith(branches[2]);
    });

    it('closes on Escape without changing the value', async () => {
        const { input, onChange } = setup();

        await userEvent.click(input);
        await screen.findAllByRole('option');
        await userEvent.keyboard('{Escape}');

        expect(screen.queryByRole('listbox')).not.toBeInTheDocument();
        expect(onChange).not.toHaveBeenCalled();
    });

    it('shows the selected option and can clear it', async () => {
        const { input, onChange } = setup({
            value: '2',
            selectedLabel: 'Bandung',
        });

        expect(input).toHaveValue('Bandung');

        await userEvent.click(
            screen.getByRole('button', { name: 'Clear Branch' }),
        );

        expect(onChange).toHaveBeenCalledExactlyOnceWith(null);
    });

    it('says so when nothing matches', async () => {
        const { input } = setup(
            {},
            loader({ 1: { options: [], hasMore: false } }),
        );

        await userEvent.click(input);

        expect(await screen.findByText('No matches.')).toBeVisible();
    });

    it('reports a load failure without crashing', async () => {
        const failing = vi.fn<LoadOptions>(() =>
            Promise.reject(
                new Error('Too many ERP requests from this account.'),
            ),
        );
        const { input } = setup({}, failing);

        await userEvent.click(input);

        expect(await screen.findByRole('alert')).toHaveTextContent(
            'Too many ERP requests from this account.',
        );
    });

    it('loads the next page on request', async () => {
        const load = loader({
            1: { options: branches.slice(0, 2), hasMore: true },
            2: { options: branches.slice(2), hasMore: false },
        });
        const { input } = setup({}, load);

        await userEvent.click(input);
        await userEvent.click(
            await screen.findByRole('button', { name: 'Load more' }),
        );

        expect(await screen.findAllByRole('option')).toHaveLength(3);
        expect(load).toHaveBeenLastCalledWith('', 2);
        expect(
            screen.queryByRole('button', { name: 'Load more' }),
        ).not.toBeInTheDocument();
    });

    it('does nothing while disabled', async () => {
        const { input, load } = setup({ disabled: true });

        await userEvent.click(input);

        expect(input).toBeDisabled();
        expect(load).not.toHaveBeenCalled();
    });

    it('ignores an answer that arrives after a newer search', async () => {
        let resolveSlow: (value: {
            options: ComboboxOption[];
            hasMore: boolean;
        }) => void = () => {};
        const load = vi.fn<LoadOptions>((search) =>
            search === 'j'
                ? new Promise((resolve) => {
                      resolveSlow = resolve;
                  })
                : Promise.resolve({ options: [branches[1]], hasMore: false }),
        );
        const { input } = setup({}, load);

        await userEvent.type(input, 'j');
        await userEvent.clear(input);
        await userEvent.type(input, 'b');
        await screen.findByRole('option', { name: /Bandung/ });

        resolveSlow({ options: [branches[0]], hasMore: false });

        await waitFor(() => {
            expect(screen.getAllByRole('option')).toHaveLength(1);
        });
        expect(screen.getByRole('option', { name: /Bandung/ })).toBeVisible();
    });
});
