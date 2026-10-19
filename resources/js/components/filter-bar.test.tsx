import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vite-plus/test';
import { FilterBar } from '@/components/filter-bar';
import type { ActiveFilter } from '@/components/filter-bar';

function setup(
    overrides: Partial<{
        active: ActiveFilter[];
        defaultAdvancedOpen: boolean;
    }> = {},
) {
    const handlers = {
        onSearchChange: vi.fn(),
        onSubmit: vi.fn(),
        onRemove: vi.fn(),
        onReset: vi.fn(),
    };

    render(
        <FilterBar
            id="po"
            search={{
                value: 'acme',
                onChange: handlers.onSearchChange,
                placeholder: 'Number, supplier, notes…',
            }}
            onSubmit={handlers.onSubmit}
            active={overrides.active ?? []}
            onRemove={handlers.onRemove}
            onReset={handlers.onReset}
            defaultAdvancedOpen={overrides.defaultAdvancedOpen}
        >
            <label htmlFor="po-branch">Branch</label>
            <input id="po-branch" />
        </FilterBar>,
    );

    return handlers;
}

describe('FilterBar', () => {
    it('always shows the main search, labelled', () => {
        setup();

        expect(screen.getByRole('searchbox', { name: 'Search' })).toHaveValue(
            'acme',
        );
    });

    it('reports typing in the search box', async () => {
        const { onSearchChange } = setup();

        await userEvent.type(screen.getByRole('searchbox'), 'x');

        expect(onSearchChange).toHaveBeenLastCalledWith('acmex');
    });

    it('submits on Enter and with the Apply button', async () => {
        const { onSubmit } = setup();

        await userEvent.type(screen.getByRole('searchbox'), '{Enter}');
        await userEvent.click(screen.getByRole('button', { name: 'Apply' }));

        expect(onSubmit).toHaveBeenCalledTimes(2);
    });

    it('hides advanced filters until asked', async () => {
        setup();

        const toggle = screen.getByRole('button', { name: 'More filters' });

        expect(toggle).toHaveAttribute('aria-expanded', 'false');
        expect(screen.queryByLabelText('Branch')).not.toBeInTheDocument();

        await userEvent.click(toggle);

        expect(toggle).toHaveAttribute('aria-expanded', 'true');
        expect(screen.getByLabelText('Branch')).toBeVisible();
    });

    it('can start with advanced filters open', () => {
        setup({ defaultAdvancedOpen: true });

        expect(screen.getByLabelText('Branch')).toBeVisible();
    });

    it('shows a removable chip per active filter, and a reset', async () => {
        const { onRemove, onReset } = setup({
            active: [
                { key: 'dateFrom', label: 'From 20 Mar 2026' },
                { key: 'branchId', label: 'Branch: Jakarta' },
            ],
        });

        await userEvent.click(
            screen.getByRole('button', {
                name: 'Remove filter: Branch: Jakarta',
            }),
        );
        await userEvent.click(
            screen.getByRole('button', { name: 'Reset filters' }),
        );

        expect(onRemove).toHaveBeenCalledExactlyOnceWith('branchId');
        expect(onReset).toHaveBeenCalledOnce();
    });

    it('offers no reset when nothing is filtered', () => {
        setup();

        expect(
            screen.queryByRole('button', { name: 'Reset filters' }),
        ).not.toBeInTheDocument();
    });

    it('is a search landmark', () => {
        setup();

        expect(screen.getByRole('search')).toBeInTheDocument();
    });
});
