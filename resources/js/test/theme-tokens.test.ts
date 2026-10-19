import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vite-plus/test';

/**
 * Guards the colour tokens in resources/css/app.css (TODO-modules.md UI-1):
 * dark mode defines everything light mode does, and every text-on-surface
 * pair meets WCAG AA (4.5:1) in both modes.
 */

const css = readFileSync(
    resolve(process.cwd(), 'resources/css/app.css'),
    'utf8',
);

function block(selector: string): Record<string, string> {
    const start = css.indexOf(`${selector} {`);

    if (start === -1) {
        throw new Error(`No ${selector} block in app.css`);
    }

    const body = css.slice(start, css.indexOf('}', start));
    const tokens: Record<string, string> = {};

    for (const match of body.matchAll(/--([\w-]+):\s*([^;]+);/g)) {
        tokens[match[1]] = match[2].trim();
    }

    return tokens;
}

const light = block(':root');
const dark = block('.dark');

// OKLCH -> linear sRGB (Björn Ottosson's matrices), then WCAG luminance.
function luminance(value: string): number {
    const match = /^oklch\(([\d.]+)\s+([\d.]+)\s+([\d.]+)\)$/.exec(value);

    if (match === null) {
        throw new Error(`Not an oklch() colour: ${value}`);
    }

    const [l, c, h] = match.slice(1).map(Number);
    const a = c * Math.cos((h * Math.PI) / 180);
    const b = c * Math.sin((h * Math.PI) / 180);

    const l_ = (l + 0.3963377774 * a + 0.2158037573 * b) ** 3;
    const m_ = (l - 0.1055613458 * a - 0.0638541728 * b) ** 3;
    const s_ = (l - 0.0894841775 * a - 1.291485548 * b) ** 3;

    const clamp = (v: number) => Math.min(1, Math.max(0, v));
    const r = clamp(4.0767416621 * l_ - 3.3077115913 * m_ + 0.2309699292 * s_);
    const g = clamp(-1.2684380046 * l_ + 2.6097574011 * m_ - 0.3413193965 * s_);
    const bl = clamp(-0.0041960863 * l_ - 0.7034186147 * m_ + 1.707614701 * s_);

    return 0.2126 * r + 0.7152 * g + 0.0722 * bl;
}

function contrast(foreground: string, background: string): number {
    const [hi, lo] = [luminance(foreground), luminance(background)].sort(
        (x, y) => y - x,
    );

    return (hi + 0.05) / (lo + 0.05);
}

// [text token, surface token]
const TEXT_PAIRS: [string, string][] = [
    ['foreground', 'background'],
    ['card-foreground', 'card'],
    ['popover-foreground', 'popover'],
    ['primary-foreground', 'primary'],
    ['secondary-foreground', 'secondary'],
    ['muted-foreground', 'background'],
    ['muted-foreground', 'muted'],
    ['accent-foreground', 'accent'],
    ['destructive-foreground', 'background'],
    ['success', 'background'],
    ['warning', 'background'],
    ['info', 'background'],
    ['sidebar-foreground', 'sidebar'],
    ['sidebar-primary-foreground', 'sidebar-primary'],
    ['sidebar-accent-foreground', 'sidebar-accent'],
];

describe('theme tokens', () => {
    it('defines every light token in dark mode too', () => {
        expect(Object.keys(dark).sort()).toEqual(
            Object.keys(light)
                .filter((name) => name !== 'radius')
                .sort(),
        );
    });

    it('defines status colours for badges and notices', () => {
        for (const name of ['success', 'warning', 'info']) {
            expect(light).toHaveProperty(name);
            expect(dark).toHaveProperty(name);
        }
    });

    for (const [mode, tokens] of [
        ['light', light],
        ['dark', dark],
    ] as const) {
        it.each(TEXT_PAIRS)(
            `${mode}: %s on %s meets WCAG AA`,
            (text, surface) => {
                expect(tokens[text], text).toBeDefined();
                expect(tokens[surface], surface).toBeDefined();
                expect(
                    contrast(tokens[text], tokens[surface]),
                ).toBeGreaterThanOrEqual(4.5);
            },
        );
    }
});
