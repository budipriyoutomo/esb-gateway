import { router } from '@inertiajs/react';
import { ArrowDown, ArrowUp } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';

type Props = {
    /** The record's name, for the result message. */
    name: string;
    /** PATCH route taking { direction: 'up' | 'down' }. */
    url: string;
    canManage: boolean;
};

/**
 * Move a record one place up or down in its list (COA reorder, [ESB-031]).
 * No confirmation: the opposite move undoes it. Both buttons are disabled
 * while one move is in flight.
 */
export function MoveActions({ name, url, canManage }: Props) {
    const [processing, setProcessing] = useState(false);

    if (!canManage) {
        return null;
    }

    function move(direction: 'up' | 'down') {
        router.patch(
            url,
            { direction, name },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );
    }

    return (
        <>
            <Button
                size="sm"
                variant="outline"
                disabled={processing}
                onClick={() => move('up')}
            >
                <ArrowUp className="size-4" aria-hidden />
                Move up
            </Button>
            <Button
                size="sm"
                variant="outline"
                disabled={processing}
                onClick={() => move('down')}
            >
                <ArrowDown className="size-4" aria-hidden />
                Move down
            </Button>
        </>
    );
}
