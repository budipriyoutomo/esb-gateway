import { describe, expect, it } from 'vite-plus/test';
import {
    formatAmount,
    formatDate,
    formatDateTime,
    formatQuantity,
} from '@/lib/format';

describe('formatAmount', () => {
    it('uses id-ID grouping with two decimals', () => {
        expect(formatAmount(1234567.891)).toBe('1.234.567,89');
        expect(formatAmount(0)).toBe('0,00');
        expect(formatAmount(-1500)).toBe('-1.500,00');
    });

    it('prefixes the currency sign when there is one', () => {
        expect(formatAmount(1500, 'Rp')).toBe('Rp 1.500,00');
        expect(formatAmount(1500, null)).toBe('1.500,00');
        expect(formatAmount(1500, '')).toBe('1.500,00');
    });

    it('shows a dash for a missing value', () => {
        expect(formatAmount(null)).toBe('—');
        expect(formatAmount(null, 'Rp')).toBe('—');
    });
});

describe('formatQuantity', () => {
    it('keeps up to four decimals and drops trailing zeros', () => {
        expect(formatQuantity(1000)).toBe('1.000');
        expect(formatQuantity(2.5)).toBe('2,5');
        expect(formatQuantity(1.23456)).toBe('1,2346');
    });

    it('shows a dash for a missing value', () => {
        expect(formatQuantity(null)).toBe('—');
    });
});

describe('formatDate', () => {
    it('formats a business date as dd MMM yyyy without shifting the day', () => {
        expect(formatDate('2026-03-20')).toBe('20 Mar 2026');
        expect(formatDate('2026-05-01')).toBe('01 Mei 2026');
        expect(formatDate('2026-12-31')).toBe('31 Des 2026');
    });

    it('keeps the wall-clock date of a zoneless ERP date-time', () => {
        expect(formatDate('2026-03-20 23:30:00')).toBe('20 Mar 2026');
    });

    it('converts a zoned timestamp to Asia/Jakarta', () => {
        // 18:30 UTC is 01:30 the next day in Jakarta (UTC+7).
        expect(formatDate('2026-03-20T18:30:00Z')).toBe('21 Mar 2026');
        expect(formatDate('2026-03-20T18:30:00.000000Z')).toBe('21 Mar 2026');
    });

    it('returns an unrecognised value unchanged rather than hiding it', () => {
        expect(formatDate('20/03/2026')).toBe('20/03/2026');
        expect(formatDate('2026-13-45')).toBe('2026-13-45');
    });

    it('shows a dash for a missing value', () => {
        expect(formatDate(null)).toBe('—');
        expect(formatDate(undefined)).toBe('—');
        expect(formatDate('')).toBe('—');
        expect(formatDate('   ')).toBe('—');
    });
});

describe('formatDateTime', () => {
    it('converts a zoned timestamp to Asia/Jakarta with a 24-hour time', () => {
        expect(formatDateTime('2026-03-20T02:15:00Z')).toBe(
            '20 Mar 2026 09:15',
        );
        expect(formatDateTime('2026-03-20T09:15:00+07:00')).toBe(
            '20 Mar 2026 09:15',
        );
        expect(formatDateTime('2026-03-20T17:05:00.000000Z')).toBe(
            '21 Mar 2026 00:05',
        );
    });

    it('keeps the wall-clock time of a zoneless ERP date-time', () => {
        expect(formatDateTime('2026-03-20 09:15:00')).toBe('20 Mar 2026 09:15');
        expect(formatDateTime('2026-03-20T09:15')).toBe('20 Mar 2026 09:15');
    });

    it('shows a business date without a time', () => {
        expect(formatDateTime('2026-03-20')).toBe('20 Mar 2026');
    });

    it('returns an unrecognised value unchanged and a dash for none', () => {
        expect(formatDateTime('yesterday')).toBe('yesterday');
        expect(formatDateTime(null)).toBe('—');
    });
});
