import { router } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

type Decision = 'authorize' | 'reject';

type Props = {
    /** Lower-case document name, e.g. "purchase order". */
    documentLabel: string;
    number: string;
    /** POST target taking { decision, reason? }. */
    decisionUrl: string;
    /** DELETE target; omit when the document cannot be deleted here. */
    deleteUrl?: string;
    /** Permission checks done by the page (esb.invoke + <module>.approve). */
    canDecide: boolean;
    /** Permission checks done by the page (esb.invoke + <module>.manage). */
    canDelete: boolean;
    /**
     * Set when a rejection carries a reason. `max` mirrors the FormRequest's
     * REASON_MAX; `required: false` lets it be left blank (default: required).
     */
    rejectReason?: { max: number; required?: boolean };
};

const textareaClass =
    'border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-20 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50';

function decisionCopy(decision: Decision, label: string, withReason: boolean) {
    const tail =
        'The ERP records the gateway as the approver; the gateway records you.';

    return decision === 'authorize'
        ? {
              action: 'Authorize',
              title: `Authorize this ${label}?`,
              description: `The ${label} is authorized in the ERP straight away. ${tail}`,
          }
        : {
              action: 'Reject',
              title: `Reject this ${label}?`,
              description: `The ${label} is rejected in the ERP straight away${
                  withReason ? ', with the reason below' : ''
              }. ${tail}`,
          };
}

/**
 * Authorize / reject / delete for an approvable document, each behind a
 * confirmation. The writes are not idempotent: every control is disabled
 * while one is in flight, and the server holds a lock per document too.
 * Outcomes arrive as a flash toast after the redirect.
 */
export function ApprovalActions({
    documentLabel,
    number,
    decisionUrl,
    deleteUrl,
    canDecide,
    canDelete,
    rejectReason,
}: Props) {
    const [pending, setPending] = useState<Decision | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [reason, setReason] = useState('');
    const [error, setError] = useState<string | undefined>(undefined);
    const [processing, setProcessing] = useState(false);

    const mayDelete = canDelete && deleteUrl !== undefined;

    if (!canDecide && !mayDelete) {
        return null;
    }

    function closeDecision() {
        setPending(null);
        setReason('');
        setError(undefined);
    }

    function submitDecision() {
        if (pending === null) {
            return;
        }

        router.post(
            decisionUrl,
            pending === 'reject' && rejectReason
                ? { decision: pending, reason }
                : { decision: pending },
            {
                preserveScroll: true,
                onStart: () => {
                    setProcessing(true);
                },
                onSuccess: () => {
                    closeDecision();
                },
                onError: (errors) => {
                    // A validation error keeps the dialog open so it can be
                    // corrected; nothing was sent to the ERP.
                    setError(errors.reason ?? errors.decision);
                },
                onFinish: () => {
                    setProcessing(false);
                },
            },
        );
    }

    function submitDelete() {
        if (deleteUrl === undefined) {
            return;
        }

        router.delete(deleteUrl, {
            onStart: () => {
                setProcessing(true);
            },
            onFinish: () => {
                setProcessing(false);
                setDeleting(false);
            },
        });
    }

    const copy =
        pending === null
            ? null
            : decisionCopy(pending, documentLabel, rejectReason !== undefined);
    const reasonMissing =
        pending === 'reject' &&
        rejectReason !== undefined &&
        rejectReason.required !== false &&
        reason.trim() === '';

    return (
        <>
            {canDecide && (
                <>
                    <Button
                        size="sm"
                        disabled={processing}
                        onClick={() => {
                            setPending('authorize');
                        }}
                    >
                        Authorize
                    </Button>
                    <Button
                        size="sm"
                        variant="destructive"
                        disabled={processing}
                        onClick={() => {
                            setPending('reject');
                        }}
                    >
                        Reject
                    </Button>
                </>
            )}
            {mayDelete && (
                <Button
                    size="sm"
                    variant="outline"
                    disabled={processing}
                    onClick={() => {
                        setDeleting(true);
                    }}
                >
                    Delete
                </Button>
            )}

            <Dialog
                open={pending !== null}
                onOpenChange={(open) => {
                    if (!open && !processing) {
                        closeDecision();
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{copy?.title}</DialogTitle>
                        <DialogDescription>
                            {copy?.description}
                        </DialogDescription>
                    </DialogHeader>

                    {pending === 'reject' && rejectReason && (
                        <div className="grid gap-1.5">
                            <Label htmlFor={`${number}-reject-reason`}>
                                {rejectReason.required === false
                                    ? 'Reason (optional)'
                                    : 'Reason'}
                            </Label>
                            <textarea
                                id={`${number}-reject-reason`}
                                className={textareaClass}
                                maxLength={rejectReason.max}
                                value={reason}
                                disabled={processing}
                                onChange={(event) => {
                                    setReason(event.target.value);
                                }}
                            />
                        </div>
                    )}

                    <InputError message={error} />

                    <DialogFooter className="gap-2">
                        <Button
                            variant="outline"
                            disabled={processing}
                            onClick={closeDecision}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant={
                                pending === 'reject' ? 'destructive' : 'default'
                            }
                            disabled={processing || reasonMissing}
                            onClick={submitDecision}
                        >
                            {processing ? 'Sending…' : copy?.action}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={deleting}
                onOpenChange={(open) => {
                    if (!processing) {
                        setDeleting(open);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete {number}?</DialogTitle>
                        <DialogDescription>
                            The {documentLabel} is deleted in the ERP straight
                            away. This cannot be undone from the gateway.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <Button
                            variant="outline"
                            disabled={processing}
                            onClick={() => {
                                setDeleting(false);
                            }}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            disabled={processing}
                            onClick={submitDelete}
                        >
                            {processing ? 'Deleting…' : 'Delete'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
