import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import { RecordForm, RecordFormField } from '@/components/record-form';

function setup(props: Partial<ComponentProps<typeof RecordForm>> = {}) {
    const onSubmit = vi.fn();
    render(
        <RecordForm
            title="New unit"
            back={{ href: '/units', label: 'Units' }}
            cancelHref="/units"
            submitLabel="Create unit"
            onSubmit={onSubmit}
            processing={false}
            isDirty={false}
            {...props}
        >
            <RecordFormField id="name" label="Name" error="Name is required">
                <input id="name" />
            </RecordFormField>
        </RecordForm>,
    );

    return { onSubmit };
}

describe('RecordForm', () => {
    it('shows the title, the fields and the actions', () => {
        setup();

        expect(screen.getByRole('heading', { name: 'New unit' })).toBeVisible();
        expect(screen.getByLabelText('Name')).toBeVisible();
        expect(screen.getByRole('link', { name: 'Cancel' })).toHaveAttribute(
            'href',
            '/units',
        );
        expect(
            screen.getByRole('button', { name: 'Create unit' }),
        ).toBeEnabled();
    });

    it('submits once, without a page reload', async () => {
        const { onSubmit } = setup();

        await userEvent.click(
            screen.getByRole('button', { name: 'Create unit' }),
        );

        expect(onSubmit).toHaveBeenCalledTimes(1);
    });

    it('cannot be submitted again while saving', () => {
        setup({ processing: true });

        expect(screen.getByRole('button', { name: /Saving/ })).toBeDisabled();
    });

    it('shows an error for the whole form, and errors under their field', () => {
        setup({ formError: 'The ERP refused the request.' });

        expect(screen.getByRole('alert')).toHaveTextContent(
            'The ERP refused the request.',
        );
        expect(screen.getByText('Name is required')).toBeVisible();
    });
});
