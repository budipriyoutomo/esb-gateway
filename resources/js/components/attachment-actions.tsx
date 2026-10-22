import { router } from '@inertiajs/react';
import { Paperclip } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    /** The record's name, for the dialog and the result message. */
    name: string;
    count: number;
    canManage: boolean;
    uploadUrl: string;
    /** Omit where the ERP cannot remove attachments. */
    deleteUrl?: string;
    /** The file input's accept list (default PDF, JPEG, PNG). */
    accept?: string;
    /** Why no files can be attached right now; hides the upload. */
    uploadBlocked?: string | null;
    /** One image per upload, sent as "file" ([ESB-046]). */
    single?: boolean;
};

/**
 * A record's attachments in the ERP ([ESB-032], [ESB-036]): how many there
 * are, and attaching more or removing all of them (the ERP offers no single
 * delete). The files themselves are not opened from the gateway.
 */
export function AttachmentActions({
    name,
    count,
    canManage,
    uploadUrl,
    deleteUrl,
    accept = '.pdf,.jpg,.jpeg,.png',
    uploadBlocked = null,
    single = false,
}: Props) {
    const [files, setFiles] = useState<File[]>([]);
    const [confirming, setConfirming] = useState(false);
    const [processing, setProcessing] = useState(false);

    const visit = {
        preserveScroll: true,
        onStart: () => setProcessing(true),
        onFinish: () => setProcessing(false),
    };

    function upload(event: FormEvent) {
        event.preventDefault();
        router.post(
            uploadUrl,
            single ? { file: files[0], name } : { files, name },
            {
                ...visit,
                forceFormData: true,
                onSuccess: () => setFiles([]),
            },
        );
    }

    function removeAll() {
        if (deleteUrl === undefined) {
            return;
        }

        router.delete(deleteUrl, {
            ...visit,
            data: { name },
            onFinish: () => {
                setProcessing(false);
                setConfirming(false);
            },
        });
    }

    return (
        <section className="grid gap-3">
            <h2 className="flex items-center gap-2 text-base font-medium">
                <Paperclip className="size-4" aria-hidden />
                Attachments
            </h2>
            <p className="text-muted-foreground text-sm">
                {count === 0
                    ? 'No attachments'
                    : count === 1
                      ? '1 attachment'
                      : `${count} attachments`}
            </p>

            {canManage && uploadBlocked !== null && (
                <div className="flex flex-wrap items-center gap-2">
                    <p className="text-muted-foreground text-sm">
                        {uploadBlocked}
                    </p>
                    {count > 0 && deleteUrl !== undefined && (
                        <Button
                            type="button"
                            size="sm"
                            variant="destructive"
                            disabled={processing}
                            onClick={() => setConfirming(true)}
                        >
                            Remove all
                        </Button>
                    )}
                </div>
            )}

            {canManage && uploadBlocked === null && (
                <form
                    onSubmit={upload}
                    noValidate
                    className="flex flex-wrap items-end gap-2"
                >
                    <div className="grid gap-1.5 sm:w-80">
                        <Label htmlFor="attachment-files">
                            {single ? 'Image to attach' : 'Files to attach'}
                        </Label>
                        <Input
                            id="attachment-files"
                            type="file"
                            multiple={!single}
                            accept={accept}
                            onChange={(event) =>
                                setFiles(Array.from(event.target.files ?? []))
                            }
                        />
                    </div>
                    <Button
                        type="submit"
                        size="sm"
                        disabled={processing || files.length === 0}
                    >
                        Attach
                    </Button>
                    {count > 0 && deleteUrl !== undefined && (
                        <Button
                            type="button"
                            size="sm"
                            variant="destructive"
                            disabled={processing}
                            onClick={() => setConfirming(true)}
                        >
                            Remove all
                        </Button>
                    )}
                </form>
            )}

            <Dialog
                open={confirming}
                onOpenChange={(open) => !processing && setConfirming(open)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Remove the attachments of {name}?
                        </DialogTitle>
                        <DialogDescription>
                            The ERP deletes all {count} attachments straight
                            away; it cannot remove just one. This cannot be
                            undone from the gateway.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            disabled={processing}
                            onClick={() => setConfirming(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            disabled={processing}
                            onClick={removeAll}
                        >
                            Remove all
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </section>
    );
}
