import { render, screen, within } from '@testing-library/react';
import type { ComponentProps } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import Dashboard from '@/pages/dashboard';
import type { Permission } from '@/types';

const granted: { permissions: Permission[] } = { permissions: [] };

vi.mock('@/hooks/use-permissions', () => ({
    usePermissions: () => ({
        can: (permission: Permission) =>
            granted.permissions.includes(permission),
        canAny: (...candidates: Permission[]) =>
            candidates.some((permission) =>
                granted.permissions.includes(permission),
            ),
    }),
}));

beforeEach(() => {
    granted.permissions = ['dashboard.view'];
});

type Props = ComponentProps<typeof Dashboard>;

function props(overrides: Partial<Props> = {}): Props {
    return {
        metrics: {
            windowHours: 24,
            total: 3,
            successful: 2,
            failed: 1,
            successRate: 66.7,
            averageDurationMs: 120,
        },
        byOutcome: { success: 2, timeout: 1 },
        byFamily: {
            core: {
                total: 2,
                failed: 1,
                successRate: 50,
                lastOutcome: 'timeout',
                lastCallAt: '2026-10-21T01:00:00+00:00',
            },
            oms: {
                total: 1,
                failed: 0,
                successRate: 100,
                lastOutcome: 'success',
                lastCallAt: '2026-10-21T02:00:00+00:00',
            },
        },
        recentFailures: [],
        esb: { environment: 'staging_int', authMechanism: 'static_token' },
        queue: { pending: 0 },
        ...overrides,
    };
}

function card(name: string) {
    return screen.getByRole('region', { name });
}

describe('Dashboard', () => {
    it('says per family whether its last call went through', () => {
        render(<Dashboard {...props()} />);

        const status = card('ESB connection status');
        const core = within(status).getByRole('listitem', { name: /core/ });
        const oms = within(status).getByRole('listitem', { name: /oms/ });

        expect(core).toHaveTextContent('Last call failed (timeout)');
        expect(core).toHaveTextContent('21 Okt 2026 08:00');
        expect(oms).toHaveTextContent('Last call succeeded');
    });

    it('hides the approvals card from someone who approves nothing', () => {
        render(<Dashboard {...props({ pendingApprovals: [] })} />);

        expect(
            screen.queryByRole('region', { name: 'Waiting for approval' }),
        ).not.toBeInTheDocument();
    });

    it('shows the approvals card as loading until the counts arrive', () => {
        granted.permissions = ['dashboard.view', 'disbursements.approve'];
        render(<Dashboard {...props()} />);

        expect(card('Waiting for approval')).toHaveAttribute(
            'aria-busy',
            'true',
        );
    });

    it('links each count to the waiting documents, and says when a count is unknown', () => {
        granted.permissions = [
            'dashboard.view',
            'disbursements.approve',
            'memorial-journals.approve',
        ];
        render(
            <Dashboard
                {...props({
                    pendingApprovals: [
                        {
                            key: 'disbursements',
                            title: 'Disbursements',
                            count: 4,
                            href: '/disbursements?statusId=38',
                        },
                        {
                            key: 'memorial-journals',
                            title: 'Memorial journals',
                            count: null,
                            href: '/memorial-journals?statusId=38',
                        },
                    ],
                })}
            />,
        );

        const approvals = card('Waiting for approval');
        expect(approvals).toHaveAttribute('aria-busy', 'false');

        const disbursements = within(approvals).getByRole('link', {
            name: /Disbursements/,
        });
        expect(disbursements).toHaveAttribute(
            'href',
            '/disbursements?statusId=38',
        );
        expect(disbursements).toHaveTextContent('4');

        expect(
            within(approvals).getByRole('link', { name: /Memorial journals/ }),
        ).toHaveTextContent('Unknown');
    });

    it('offers only the shortcuts the person may use', () => {
        granted.permissions = [
            'dashboard.view',
            'disbursements.manage',
            'integration-logs.view',
        ];
        render(<Dashboard {...props()} />);

        const shortcuts = within(card('Shortcuts')).getAllByRole('link');

        expect(shortcuts.map((link) => link.textContent)).toEqual([
            'New disbursement',
            'Integration logs',
        ]);
        expect(shortcuts[0]).toHaveAttribute('href', '/disbursements/create');
    });

    it('hides the shortcuts card when there is nothing to offer', () => {
        render(<Dashboard {...props()} />);

        expect(
            screen.queryByRole('region', { name: 'Shortcuts' }),
        ).not.toBeInTheDocument();
    });
});
