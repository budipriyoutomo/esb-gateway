import { router } from '@inertiajs/react';
import { useEffect } from 'react';

const MESSAGE = 'You have unsaved changes. Leave this page and lose them?';

/**
 * Warns before a form with unsaved changes is left: on an Inertia link or
 * back/forward (GET visits) and on closing or reloading the tab. The form's
 * own submission (any non-GET visit) is never blocked.
 *
 * Pass Inertia's `form.isDirty`, and false once the form has been saved.
 */
export function useUnsavedChangesWarning(isDirty: boolean): void {
    useEffect(() => {
        if (!isDirty) {
            return;
        }

        const offBefore = router.on('before', (event) => {
            if (event.detail.visit.method !== 'get') {
                return;
            }

            if (!window.confirm(MESSAGE)) {
                event.preventDefault();
            }
        });

        function onBeforeUnload(event: BeforeUnloadEvent) {
            // Browsers show their own text; preventDefault asks them to.
            event.preventDefault();
        }

        window.addEventListener('beforeunload', onBeforeUnload);

        return () => {
            offBefore();
            window.removeEventListener('beforeunload', onBeforeUnload);
        };
    }, [isDirty]);
}
