import { router } from '@inertiajs/react';
import { renderHook } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { useUnsavedChangesWarning } from '@/hooks/use-unsaved-changes-warning';

type BeforeHandler = (event: {
    detail: { visit: { method: string } };
    preventDefault: () => void;
}) => void;

afterEach(() => {
    vi.restoreAllMocks();
});

function captureBefore() {
    const off = vi.fn();
    let handler: BeforeHandler | null = null;

    // Only the parts of Inertia's event the hook reads.
    const fakeOn = (type: string, callback: BeforeHandler) => {
        if (type === 'before') {
            handler = callback;
        }

        return off;
    };

    vi.spyOn(router, 'on').mockImplementation(
        fakeOn as unknown as typeof router.on,
    );

    return {
        off,
        visit(method: string) {
            const preventDefault = vi.fn();
            handler?.({ detail: { visit: { method } }, preventDefault });

            return preventDefault;
        },
        get registered() {
            return handler !== null;
        },
    };
}

describe('useUnsavedChangesWarning', () => {
    it('does nothing while the form is clean', () => {
        const before = captureBefore();
        const add = vi.spyOn(window, 'addEventListener');

        renderHook(() => {
            useUnsavedChangesWarning(false);
        });

        expect(before.registered).toBe(false);
        expect(add).not.toHaveBeenCalledWith('beforeunload', expect.anything());
    });

    it('asks before leaving to another page with unsaved changes', () => {
        const before = captureBefore();
        const confirm = vi.spyOn(window, 'confirm').mockReturnValue(false);

        renderHook(() => {
            useUnsavedChangesWarning(true);
        });

        const prevented = before.visit('get');

        expect(confirm).toHaveBeenCalledOnce();
        expect(prevented).toHaveBeenCalledOnce();
    });

    it('lets the person leave when they confirm', () => {
        const before = captureBefore();
        vi.spyOn(window, 'confirm').mockReturnValue(true);

        renderHook(() => {
            useUnsavedChangesWarning(true);
        });

        expect(before.visit('get')).not.toHaveBeenCalled();
    });

    it('never blocks the form submitting itself', () => {
        const before = captureBefore();
        const confirm = vi.spyOn(window, 'confirm');

        renderHook(() => {
            useUnsavedChangesWarning(true);
        });

        expect(before.visit('post')).not.toHaveBeenCalled();
        expect(before.visit('put')).not.toHaveBeenCalled();
        expect(confirm).not.toHaveBeenCalled();
    });

    it('warns on closing or reloading the tab', () => {
        captureBefore();
        renderHook(() => {
            useUnsavedChangesWarning(true);
        });

        const event = new Event('beforeunload', { cancelable: true });
        window.dispatchEvent(event);

        expect(event.defaultPrevented).toBe(true);
    });

    it('stops listening once the form is clean again', () => {
        const before = captureBefore();
        const remove = vi.spyOn(window, 'removeEventListener');
        const { rerender } = renderHook(
            ({ dirty }) => {
                useUnsavedChangesWarning(dirty);
            },
            { initialProps: { dirty: true } },
        );

        rerender({ dirty: false });

        expect(before.off).toHaveBeenCalledOnce();
        expect(remove).toHaveBeenCalledWith(
            'beforeunload',
            expect.any(Function),
        );
    });
});
