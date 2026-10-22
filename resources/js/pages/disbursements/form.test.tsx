import { router } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import DisbursementForm from '@/pages/disbursements/form';
import type { Disbursement } from '@/types/disbursements';

afterEach(() => {
    vi.restoreAllMocks();
});

function disbursement(overrides: Partial<Disbursement> = {}): Disbursement {
    return {
        provider: 'esb',
        number: 'DB202603250001',
        date: '2026-03-25',
        statusId: 1,
        statusName: 'New',
        branchId: 3,
        branchName: 'Outlet Senopati',
        paymentId: 2,
        paymentName: 'Credit',
        cashBankCoaNo: null,
        supplierId: 14,
        supplierName: 'PT Properti Jaya',
        currencyId: 1,
        currencyName: 'Rupiah',
        currencySign: 'Rp',
        rate: 1,
        creditTerm: 30,
        invoiceDueDate: '2026-04-24',
        reference: 'INV-RUKO-03',
        notes: 'Sewa ruko Maret',
        bankName: null,
        bankAccountName: null,
        bankAccountNumber: null,
        costCenter: null,
        project: null,
        grandTotal: 15000000.5,
        releasePaymentCode: null,
        lines: [
            {
                branchId: 3,
                branchName: 'Outlet Senopati',
                coaNo: '6101',
                coaDescription: 'Beban Sewa',
                description: 'Sewa Maret',
                amount: 15000000.5,
            },
        ],
        linkedDocuments: [],
        attachmentCount: 0,
        createdBy: null,
        createdAt: null,
        editedBy: null,
        editedAt: null,
        authorizedBy: null,
        authorizedAt: null,
        ...overrides,
    };
}

function submitted(visit: { mock: { calls: unknown[][] } }) {
    const [url, options] = visit.mock.calls[0] as [
        string,
        { method: string; data: unknown },
    ];

    return { url, method: options.method, data: options.data };
}

describe('Disbursement form', () => {
    it('starts as cash, asking for the cash / bank account', () => {
        render(<DisbursementForm disbursement={null} />);

        expect(
            screen.getByRole('heading', { name: 'New disbursement' }),
        ).toBeVisible();
        expect(screen.getByLabelText('Payment')).toHaveValue('1');
        expect(screen.getByLabelText('Cash / bank account')).toBeVisible();
        expect(
            screen.queryByLabelText('Credit term, days (optional)'),
        ).not.toBeInTheDocument();
    });

    it('switching to credit asks for the supplier and drops the cash account', async () => {
        render(<DisbursementForm disbursement={null} />);

        await userEvent.selectOptions(screen.getByLabelText('Payment'), '2');

        expect(screen.getByLabelText('Supplier')).toBeVisible();
        expect(
            screen.getByLabelText('Credit term, days (optional)'),
        ).toBeVisible();
        expect(
            screen.queryByLabelText('Cash / bank account'),
        ).not.toBeInTheDocument();
    });

    it('sends an edited credit disbursement in the request’s names', async () => {
        const visit = vi.spyOn(router, 'visit').mockImplementation(() => {});
        render(<DisbursementForm disbursement={disbursement()} />);

        expect(
            screen.getByRole('heading', { name: 'Edit DB202603250001' }),
        ).toBeVisible();

        await userEvent.clear(screen.getByLabelText('Amount, line 1'));
        await userEvent.type(screen.getByLabelText('Amount, line 1'), '1500');
        await userEvent.click(
            screen.getByRole('button', { name: 'Save changes' }),
        );

        const sent = submitted(visit);
        expect(sent.url).toMatch(/\/disbursements\/DB202603250001$/);
        expect(sent.method).toBe('put');
        expect(sent.data).toEqual({
            date: '2026-03-25',
            branchId: 3,
            paymentId: 2,
            currencyId: 1,
            rate: 1,
            cashBankCoaNo: null,
            supplierId: 14,
            creditTerm: 30,
            reference: 'INV-RUKO-03',
            invoiceDueDate: '2026-04-24',
            notes: 'Sewa ruko Maret',
            lines: [
                {
                    branchId: 3,
                    coaNo: '6101',
                    description: 'Sewa Maret',
                    amount: 1500,
                },
            ],
        });
    });

    it('reads lines from the ERP’s Excel form without saving anything', async () => {
        const post = vi.spyOn(router, 'post').mockImplementation(() => {});
        render(<DisbursementForm disbursement={null} />);

        const button = screen.getByRole('button', { name: 'Read lines' });
        expect(button).toBeDisabled();

        await userEvent.upload(
            screen.getByLabelText("Fill lines from the ERP's Excel form"),
            new File(['PK'], 'form.xlsx'),
        );
        await userEvent.click(button);

        expect(post).toHaveBeenCalledTimes(1);
        const [url, data, options] = post.mock.calls[0] as [
            string,
            { file: File },
            { forceFormData: boolean; onFlash: (flash: unknown) => void },
        ];
        expect(url).toMatch(/\/disbursements\/form-lines$/);
        expect(data.file.name).toBe('form.xlsx');
        expect(options.forceFormData).toBe(true);
    });
});
