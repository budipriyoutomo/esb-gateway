import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { FormActions } from '@/components/form-actions';

describe('FormActions', () => {
    it('groups the form buttons under a label', () => {
        render(
            <FormActions>
                <button type="button">Cancel</button>
                <button type="submit">Save</button>
            </FormActions>,
        );

        const group = screen.getByRole('group', { name: 'Form actions' });

        expect(group).toContainElement(
            screen.getByRole('button', { name: 'Save' }),
        );
    });

    it('stays in view on long forms when sticky', () => {
        render(
            <FormActions sticky>
                <button type="submit">Save</button>
            </FormActions>,
        );

        expect(screen.getByRole('group')).toHaveAttribute(
            'data-sticky',
            'true',
        );
    });

    it('is not sticky by default', () => {
        render(
            <FormActions>
                <button type="submit">Save</button>
            </FormActions>,
        );

        expect(screen.getByRole('group')).not.toHaveAttribute('data-sticky');
    });

    it('can say why saving is not possible yet', () => {
        render(
            <FormActions hint="Add at least one line.">
                <button type="submit" disabled>
                    Save
                </button>
            </FormActions>,
        );

        expect(screen.getByText('Add at least one line.')).toBeVisible();
    });
});
