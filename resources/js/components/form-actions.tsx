import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Props = {
    children: ReactNode;
    /** Keep the buttons in view at the bottom of long forms. */
    sticky?: boolean;
    /** Why the primary action is unavailable, shown beside the buttons. */
    hint?: string;
};

/**
 * The row of form buttons (secondary first, primary last). Field
 * conventions: label above the field, error (InputError) below it.
 */
export function FormActions({ children, sticky = false, hint }: Props) {
    return (
        <div
            role="group"
            aria-label="Form actions"
            data-sticky={sticky || undefined}
            className={cn(
                'flex flex-wrap items-center justify-end gap-2',
                sticky &&
                    'bg-background/95 supports-[backdrop-filter]:bg-background/80 sticky bottom-0 z-10 -mx-4 border-t px-4 py-3 backdrop-blur',
            )}
        >
            {hint && (
                <p className="text-muted-foreground mr-auto text-sm">{hint}</p>
            )}
            {children}
        </div>
    );
}
