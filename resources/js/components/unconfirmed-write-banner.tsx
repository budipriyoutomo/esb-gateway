import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { UnconfirmedWriteNotice } from '@/components/unconfirmed-write-notice';
import type { UnconfirmedWrite } from '@/types/ui';

/**
 * Shows the UnconfirmedWriteNotice a controller flashed
 * (HandlesDocumentWrites::flashUnconfirmedWrite) on the page it redirected
 * to. Unlike a toast it stays up while the person checks the re-read status,
 * until dismissed or the next visit.
 */
export function UnconfirmedWriteBanner() {
    const write = usePage().flash.unconfirmedWrite as
        | UnconfirmedWrite
        | undefined;
    const [dismissed, setDismissed] = useState<string | null>(null);

    if (!write || dismissed === write.reference) {
        return null;
    }

    return (
        <div className="px-4 pt-4">
            <UnconfirmedWriteNotice
                action={write.action}
                reference={write.reference}
                onDismiss={() => setDismissed(write.reference)}
            />
        </div>
    );
}
