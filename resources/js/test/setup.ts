// Shared setup for component tests (ADR-024).
import '@testing-library/jest-dom/vitest';
import { cleanup } from '@testing-library/react';
import { afterEach } from 'vite-plus/test';

// Unmount whatever a test rendered, so the next test starts from an empty
// document.
afterEach(() => {
    cleanup();
});
