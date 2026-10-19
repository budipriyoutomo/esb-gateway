import type { InertiaLinkProps } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { DetailField, DetailFields } from '@/components/detail-fields';
import { PageHeader } from '@/components/page-header';
import { formatDateTime } from '@/lib/format';

type Audit = {
    active: boolean | null;
    createdBy: string | null;
    createdAt: string | null;
    editedBy: string | null;
    editedAt: string | null;
};

function byline(name: string | null, at: string | null): string | null {
    const parts = [name, at === null ? null : formatDateTime(at)].filter(
        (part) => part !== null,
    );

    return parts.length === 0 ? null : parts.join(' · ');
}

function activeLabel(active: boolean | null): string | null {
    if (active === null) {
        return null;
    }

    return active ? 'Active' : 'Inactive';
}

/**
 * The detail page of a read-only master data record: back link, title, its
 * own fields, then status and who created / last edited it.
 */
export function ReferenceDataShow({
    title,
    back,
    record,
    children,
    extra,
}: {
    title: string;
    back: { href: NonNullable<InertiaLinkProps['href']>; label: string };
    record: Audit;
    /** The record's own DetailField entries. */
    children: ReactNode;
    /** Further sections below the fields. */
    extra?: ReactNode;
}) {
    return (
        <div className="flex h-full flex-1 flex-col gap-6 p-4">
            <PageHeader
                title={title}
                description="Read live from the ERP."
                back={back}
            />

            <DetailFields>
                {children}
                <DetailField
                    label="Status"
                    value={activeLabel(record.active)}
                />
                <DetailField
                    label="Created"
                    value={byline(record.createdBy, record.createdAt)}
                />
                <DetailField
                    label="Last edited"
                    value={byline(record.editedBy, record.editedAt)}
                />
            </DetailFields>

            {extra}
        </div>
    );
}
