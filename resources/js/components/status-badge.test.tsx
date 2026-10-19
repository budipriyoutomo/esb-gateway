import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { StatusBadge, statusTone } from '@/components/status-badge';

describe('statusTone', () => {
    it.each([
        ['New', 'neutral'],
        ['Draft', 'neutral'],
        ['Waiting Approval', 'warning'],
        ['Pending', 'warning'],
        ['Menunggu Persetujuan', 'warning'],
        ['Authorized', 'success'],
        ['Approved', 'success'],
        ['Disetujui', 'success'],
        ['Rejected', 'danger'],
        ['Cancelled', 'danger'],
        ['Ditolak', 'danger'],
        ['Closed', 'info'],
        ['Finished', 'info'],
        ['Selesai', 'info'],
        ['Something the ERP invented', 'neutral'],
    ] as const)('%s → %s', (status, tone) => {
        expect(statusTone(status)).toBe(tone);
    });

    it('ignores case and surrounding space', () => {
        expect(statusTone('  AUTHORIZED ')).toBe('success');
    });

    it('does not read "unauthorized" or "not approved" as approved', () => {
        expect(statusTone('Unauthorized')).toBe('neutral');
        expect(statusTone('Not Approved')).toBe('neutral');
    });
});

describe('StatusBadge', () => {
    it('shows the ERP status text unchanged', () => {
        render(<StatusBadge status="Waiting Approval" />);

        expect(screen.getByText('Waiting Approval')).toBeInTheDocument();
    });

    it('exposes the tone for styling and tests', () => {
        render(<StatusBadge status="Authorized" />);

        expect(screen.getByText('Authorized')).toHaveAttribute(
            'data-tone',
            'success',
        );
    });

    it('shows a dash when there is no status', () => {
        render(<StatusBadge status={null} />);

        expect(screen.getByText('—')).toBeInTheDocument();
    });
});
