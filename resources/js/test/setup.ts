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
