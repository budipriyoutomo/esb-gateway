import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type Props = {
    /** The button, and the confirm button in the dialog, e.g. "Finish". */
    label: string;
    title: string;
    description: string;
    /** PATCH target. */
    url: string;
    variant?: 'default' | 'outline' | 'destructive';
};

/**
 * One state change on a document behind a confirmation, e.g. finishing a
 * goods transfer request. The write is not idempotent: the controls are
 * disabled while it is in flight, and the server holds a lock per document.
 * The outcome arrives as a flash toast after the redirect.
 */
export function ConfirmAction({
    label,
    title,
    description,
    url,
    variant = 'outline',
}: Props) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    function submit() {
        router.patch(
            url,
            {},
            {
                preserveScroll: true,
                onStart: () => {
                    setProcessing(true);
                },
                onFinish: () => {
                    setProcessing(false);
                    setOpen(false);
                },
            },
        );
    }

    return (
        <>
            <Button
                size="sm"
                variant={variant}
                disabled={processing}
                onClick={() => {
                    setOpen(true);
                }}
            >
                {label}
            </Button>

            <Dialog
                open={open}
                onOpenChange={(next) => {
                    if (!processing) {
                        setOpen(next);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{title}</DialogTitle>
                        <DialogDescription>{description}</DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <Button
                            variant="outline"
                            disabled={processing}
                            onClick={() => {
                                setOpen(false);
                            }}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant={
                                variant === 'outline' ? 'default' : variant
                            }
                            disabled={processing}
                            onClick={submit}
                        >
                            {processing ? 'Sending…' : label}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
