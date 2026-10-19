import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vite-plus/test';
import { DateRangeField } from '@/components/date-range-field';

describe('DateRangeField', () => {
    it('labels both ends of the range', () => {
        render(
            <DateRangeField
                id="po-date"
                label="Order date"
                from=""
                to=""
                onChange={() => {}}
            />,
        );

        expect(screen.getByLabelText('Order date from')).toHaveAttribute(
            'type',
            'date',
        );
        expect(screen.getByLabelText('Order date to')).toHaveAttribute(
            'type',
            'date',
        );
    });

    it('reports a change to either end with the other kept', async () => {
        const onChange = vi.fn();
        render(
            <DateRangeField
                id="po-date"
                label="Order date"
                from="2026-03-01"
                to=""
                onChange={onChange}
            />,
        );

        await userEvent.type(
            screen.getByLabelText('Order date to'),
            '2026-03-31',
        );

        expect(onChange).toHaveBeenLastCalledWith({
            from: '2026-03-01',
            to: '2026-03-31',
        });
    });

    it('does not let the end come before the start', () => {
        render(
            <DateRangeField
                id="po-date"
                label="Order date"
                from="2026-03-10"
                to=""
                onChange={() => {}}
            />,
        );

        expect(screen.getByLabelText('Order date to')).toHaveAttribute(
            'min',
            '2026-03-10',
        );
    });
});
