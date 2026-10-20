import { router } from '@inertiajs/react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { AttachmentActions } from '@/components/attachment-actions';

afterEach(() => {
    vi.restoreAllMocks();
});

function setup(count = 2, canManage = true) {
    render(
        <AttachmentActions
            name="CV Maju Jaya"
            count={count}
            canManage={canManage}
            uploadUrl="/suppliers/7/attachments"
            deleteUrl="/suppliers/7/attachments"
        />,
    );
}

describe('AttachmentActions', () => {
    it('says how many attachments there are', () => {
        setup(2, false);

        expect(screen.getByText('2 attachments')).toBeVisible();
        expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });

    it('uploads the chosen files as form data', async () => {
        const post = vi.spyOn(router, 'post').mockImplementation(() => {});
        setup();

        const files = [
            new File(['%PDF'], 'npwp.pdf'),
            new File(['x'], 'siup.jpg'),
        ];
        await userEvent.upload(screen.getByLabelText('Files to attach'), files);
        await userEvent.click(screen.getByRole('button', { name: 'Attach' }));

        expect(post).toHaveBeenCalledWith(
            '/suppliers/7/attachments',
            { files, name: 'CV Maju Jaya' },
            expect.objectContaining({
                forceFormData: true,
                preserveScroll: true,
            }),
        );
    });

    it('removes every attachment only after confirming', async () => {
        const destroy = vi.spyOn(router, 'delete').mockImplementation(() => {});
        setup();

        await userEvent.click(
            screen.getByRole('button', { name: 'Remove all' }),
        );
        const dialog = screen.getByRole('dialog');
        expect(dialog).toHaveTextContent('all 2 attachments');

        await userEvent.click(
            within(dialog).getByRole('button', { name: 'Remove all' }),
        );

        expect(destroy).toHaveBeenCalledWith(
            '/suppliers/7/attachments',
            expect.objectContaining({ data: { name: 'CV Maju Jaya' } }),
        );
    });

    it('offers no removal when there is nothing to remove', () => {
        setup(0);

        expect(screen.getByText('No attachments')).toBeVisible();
        expect(
            screen.queryByRole('button', { name: 'Remove all' }),
        ).not.toBeInTheDocument();
    });

    it('offers no removal where the ERP has none', () => {
        render(
            <AttachmentActions
                name="ST-1"
                count={2}
                canManage
                uploadUrl="/simple-transfers/ST-1/attachments"
            />,
        );

        expect(
            screen.queryByRole('button', { name: 'Remove all' }),
        ).not.toBeInTheDocument();
    });

    it('takes only the file types given', () => {
        render(
            <AttachmentActions
                name="GTR-1"
                count={0}
                canManage
                uploadUrl="/goods-transfer-requests/GTR-1/attachments"
                deleteUrl="/goods-transfer-requests/GTR-1/attachments"
                accept=".jpg,.jpeg,.png"
            />,
        );

        expect(screen.getByLabelText('Files to attach')).toHaveAttribute(
            'accept',
            '.jpg,.jpeg,.png',
        );
    });

    it('explains why no more files can be attached, and still offers removal', () => {
        render(
            <AttachmentActions
                name="GD-1"
                count={1}
                canManage
                uploadUrl="/goods-deliveries/GD-1/attachments"
                deleteUrl="/goods-deliveries/GD-1/attachments"
                uploadBlocked="Remove the current attachments first."
            />,
        );

        expect(
            screen.queryByLabelText('Files to attach'),
        ).not.toBeInTheDocument();
        expect(
            screen.getByText('Remove the current attachments first.'),
        ).toBeVisible();
        expect(
            screen.getByRole('button', { name: 'Remove all' }),
        ).toBeVisible();
    });
});
