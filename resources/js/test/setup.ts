// Shared setup for component tests (ADR-024).
import '@testing-library/jest-dom/vitest';
import { cleanup } from '@testing-library/react';
import { afterEach, vi } from 'vite-plus/test';

// Inertia's <Head> needs the head manager of a running Inertia app. Page
// tests render pages on their own, and the <title> is not what they test.
vi.mock('@inertiajs/react', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/react')>()),
    Head: () => null,
}));

// Unmount whatever a test rendered, so the next test starts from an empty
// document.
afterEach(() => {
    cleanup();
});

// jsdom has no ResizeObserver; Radix primitives (Checkbox) measure
// themselves with one. Nothing a test asserts depends on the sizes.
if (!('ResizeObserver' in globalThis)) {
    globalThis.ResizeObserver = class {
        observe() {}
        unobserve() {}
        disconnect() {}
    };
}

// jsdom has no matchMedia; useIsMobile reads one when the sidebar renders.
// Tests run at desktop width: no query matches.
if (typeof window.matchMedia !== 'function') {
    Object.defineProperty(window, 'matchMedia', {
        writable: true,
        value: (query: string): MediaQueryList =>
            ({
                matches: false,
                media: query,
                onchange: null,
                addEventListener: () => {},
                removeEventListener: () => {},
                addListener: () => {},
                removeListener: () => {},
                dispatchEvent: () => false,
            }) as MediaQueryList,
    });
}

// jsdom lacks the pointer-capture and scrolling APIs Radix Select calls when
// it opens. Nothing a test asserts depends on them.
if (typeof Element.prototype.hasPointerCapture !== 'function') {
    Element.prototype.hasPointerCapture = () => false;
    Element.prototype.releasePointerCapture = () => {};
}

if (typeof Element.prototype.scrollIntoView !== 'function') {
    Element.prototype.scrollIntoView = () => {};
}
