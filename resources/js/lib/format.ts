/**
 * Display formatting shared by every page (TODO-modules.md UI-1).
 *
 * Display only: the ERP is the authority on every amount, quantity and date.
 * Nothing here is used to compute or to send values back.
 */

/** One place to change when the UI language is decided (TODO-modules.md §9). */
export const DISPLAY_LOCALE = 'id-ID';

/** Zoned timestamps (the gateway's own, e.g. created_at) are shown in this zone. */
export const DISPLAY_TIME_ZONE = 'Asia/Jakarta';

const EMPTY = '—';

const amountFormat = new Intl.NumberFormat(DISPLAY_LOCALE, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const quantityFormat = new Intl.NumberFormat(DISPLAY_LOCALE, {
    maximumFractionDigits: 4,
});

/** e.g. "1.234.567,89", or "Rp 1.500,00" with a sign. */
export function formatAmount(
    value: number | null | undefined,
    sign?: string | null,
): string {
    if (value === null || value === undefined) {
        return EMPTY;
    }

    const formatted = amountFormat.format(value);

    return sign ? `${sign} ${formatted}` : formatted;
}

/** e.g. "1.000", "2,5" — up to four decimals, no trailing zeros. */
export function formatQuantity(value: number | null | undefined): string {
    return value === null || value === undefined
        ? EMPTY
        : quantityFormat.format(value);
}

// "dd MMM yyyy" for a date already pinned to the wall clock (formatted in
// UTC so it is never shifted).
const wallDateFormat = new Intl.DateTimeFormat(DISPLAY_LOCALE, {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    timeZone: 'UTC',
});

const zonedPartsFormat = new Intl.DateTimeFormat('en-CA', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
    timeZone: DISPLAY_TIME_ZONE,
});

type WallClock = {
    year: number;
    month: number;
    day: number;
    hour: number | null;
    minute: number | null;
};

// 2026-03-20, 2026-03-20 09:15:00, 2026-03-20T09:15, optional fraction and
// zone (Z, +07:00, +0700).
const DATE_PATTERN =
    /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2})(?:\.\d+)?)?(Z|[+-]\d{2}:?\d{2})?)?$/;

/**
 * Reads the shapes the gateway knows. A zoneless value is the ERP's own wall
 * clock and is kept as-is; a zoned one is moved to DISPLAY_TIME_ZONE.
 * Anything else is null, so the caller shows the raw text.
 */
function toWallClock(value: string): WallClock | null {
    const match = DATE_PATTERN.exec(value);

    if (match === null) {
        return null;
    }

    const [, y, mo, d, h, mi, , zone] = match;
    const year = Number(y);
    const month = Number(mo);
    const day = Number(d);
    const probe = new Date(Date.UTC(year, month - 1, day));

    if (
        probe.getUTCFullYear() !== year ||
        probe.getUTCMonth() !== month - 1 ||
        probe.getUTCDate() !== day ||
        (h !== undefined && (Number(h) > 23 || Number(mi) > 59))
    ) {
        return null;
    }

    if (zone === undefined) {
        return {
            year,
            month,
            day,
            hour: h === undefined ? null : Number(h),
            minute: mi === undefined ? null : Number(mi),
        };
    }

    const instant = new Date(value);

    if (Number.isNaN(instant.getTime())) {
        return null;
    }

    const parts = Object.fromEntries(
        zonedPartsFormat
            .formatToParts(instant)
            .map((part) => [part.type, Number(part.value)]),
    );

    return {
        year: parts.year,
        month: parts.month,
        day: parts.day,
        hour: parts.hour,
        minute: parts.minute,
    };
}

function dateOf(clock: WallClock): string {
    return wallDateFormat.format(
        new Date(Date.UTC(clock.year, clock.month - 1, clock.day)),
    );
}

const pad = (n: number): string => String(n).padStart(2, '0');

/** e.g. "20 Mar 2026". Unrecognised text is returned unchanged. */
export function formatDate(value: string | null | undefined): string {
    const text = value?.trim() ?? '';

    if (text === '') {
        return EMPTY;
    }

    const clock = toWallClock(text);

    return clock === null ? text : dateOf(clock);
}

/**
 * e.g. "20 Mar 2026 09:15" (24-hour). A business date without a time is
 * shown as a date. Unrecognised text is returned unchanged.
 */
export function formatDateTime(value: string | null | undefined): string {
    const text = value?.trim() ?? '';

    if (text === '') {
        return EMPTY;
    }

    const clock = toWallClock(text);

    if (clock === null) {
        return text;
    }

    if (clock.hour === null || clock.minute === null) {
        return dateOf(clock);
    }

    return `${dateOf(clock)} ${pad(clock.hour)}:${pad(clock.minute)}`;
}
