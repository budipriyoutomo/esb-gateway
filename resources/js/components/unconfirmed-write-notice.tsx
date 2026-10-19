import { TriangleAlert, X } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';

type Props = {
    /** What was attempted, e.g. "authorize PO-0001". */
    action: string;
    /** The correlation id of the ESB call, for support. */
    reference: string;
    onDismiss?: () => void;
};

/**
 * Shown when a write may or may not have reached the ERP
 * (EsbException::mayHaveBeenApplied()). The page below re-reads the
 * document, which is how the person checks the outcome; retrying blindly
 * could apply it twice.
 */
export function UnconfirmedWriteNotice({
    action,
    reference,
    onDismiss,
}: Props) {
    return (
        <Alert className="border-warning/30 bg-warning/10 text-foreground relative pr-10">
            <TriangleAlert aria-hidden className="text-warning" />
            <AlertTitle>
                The ERP did not confirm the request to {action}
            </AlertTitle>
            <AlertDescription className="text-foreground/80">
                <p>
                    It may or may not have been applied. Check the status below
                    before trying again.
                </p>
                <p className="text-xs">
                    Reference: <span className="font-mono">{reference}</span>
                </p>
            </AlertDescription>
            {onDismiss && (
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Dismiss"
                    className="absolute top-2 right-2 size-7"
                    onClick={onDismiss}
                >
                    <X aria-hidden className="size-4" />
                </Button>
            )}
        </Alert>
    );
}
