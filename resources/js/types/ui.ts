import type { ReactNode } from 'react';
import type { BreadcrumbItem } from '@/types/navigation';

export type AppLayoutProps = {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
};

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};

/** Flashed by HandlesDocumentWrites when a write may or may not have applied. */
export type UnconfirmedWrite = {
    /** e.g. "authorize PO-0001". */
    action: string;
    /** Correlation id of the ESB call. */
    reference: string;
};

export type AuthLayoutProps = {
    children?: ReactNode;
    name?: string;
    title?: string;
    description?: string;
};
