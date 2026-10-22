import { readdirSync, readFileSync } from 'node:fs';
import { join, relative } from 'node:path';
import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

// TODO-modules.md UI-5: checks over the whole frontend that a component test
// of one page would not catch.

const ROOT = join(__dirname, '..');

function sourceFiles(dir: string): string[] {
    return readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
        const path = join(dir, entry.name);

        if (entry.isDirectory()) {
            return entry.name === 'routes' ||
                entry.name === 'actions' ||
                entry.name === 'wayfinder'
                ? []
                : sourceFiles(path);
        }

        return entry.name.endsWith('.tsx') && !entry.name.includes('.test.')
            ? [path]
            : [];
    });
}

describe('icon-only buttons', () => {
    it('all have an accessible name (aria-label or visually hidden text)', () => {
        const unnamed: string[] = [];

        for (const file of sourceFiles(ROOT)) {
            const source = readFileSync(file, 'utf8');
            const opening = /<Button\b[^>]*?size="icon"/gs;

            for (const match of source.matchAll(opening)) {
                const start = match.index;
                const end = source.indexOf('</Button>', start);
                const element = source.slice(
                    start,
                    end === -1 ? undefined : end,
                );

                if (!/aria-label|sr-only/.test(element)) {
                    const line = source.slice(0, start).split('\n').length;
                    unnamed.push(`${relative(ROOT, file)}:${String(line)}`);
                }
            }
        }

        expect(unnamed).toEqual([]);
    });
});

describe('page titles', () => {
    // One h1 per page, from PageHeader (TODO-modules.md UI-5 / UI-6). Auth,
    // settings, the welcome and error pages have layouts of their own.
    const OWN_LAYOUT = /^(auth|settings)[\\/]|^(welcome|error)\.tsx$/;

    it('every application page names itself with PageHeader', () => {
        const pages = join(ROOT, 'pages');
        // Components that render a PageHeader of their own.
        const titled =
            /<(PageHeader|RecordForm|ReferenceDataIndex|ReferenceDataShow)\b/;
        const untitled = sourceFiles(pages)
            .map((file) => relative(pages, file))
            .filter((file) => !OWN_LAYOUT.test(file))
            .filter(
                (file) => !titled.test(readFileSync(join(pages, file), 'utf8')),
            );

        expect(untitled).toEqual([]);
    });
});

describe('colours', () => {
    // Text and surfaces use the theme tokens, whose contrast in light and
    // dark is checked by theme-tokens.test.ts (TODO-modules.md UI-1 / UI-5).
    // shadcn primitives (components/ui) are left as generated.
    const PALETTE =
        /\b(?:text|bg|border|ring)-(?:red|green|blue|gray|neutral|zinc|slate|amber|yellow|emerald)-\d{2,3}\b/;

    it('come from the theme tokens, not the raw palette', () => {
        const raw = sourceFiles(ROOT)
            .filter((file) => !/[\\/]components[\\/]ui[\\/]/.test(file))
            .filter((file) => PALETTE.test(readFileSync(file, 'utf8')))
            .map((file) => relative(ROOT, file));

        expect(raw).toEqual([]);
    });
});

describe('touch targets', () => {
    // pointer-coarse: phones and tablets, whatever their width. 44px is the
    // WCAG 2.5.5 target size.
    it('make every button at least 44px on a touch screen', () => {
        render(
            <>
                <Button>Default</Button>
                <Button size="sm">Small</Button>
                <Button size="lg">Large</Button>
                <Button size="icon" aria-label="Icon" />
            </>,
        );

        for (const button of screen.getAllByRole('button')) {
            expect(button.className).toContain('pointer-coarse:min-h-11');
        }

        expect(
            screen.getByRole('button', { name: 'Icon' }).className,
        ).toContain('pointer-coarse:min-w-11');
    });

    it('make text inputs 44px tall on a touch screen', () => {
        render(<Input aria-label="Name" />);

        expect(
            screen.getByRole('textbox', { name: 'Name' }).className,
        ).toContain('pointer-coarse:h-11');
    });
});
