import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { ReactNode } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';

type Props = {
    title: string;
    description?: string;
    /** A link back to the parent list, shown above the title. */
    back?: { href: NonNullable<InertiaLinkProps['href']>; label: string };
    /** Primary actions, right of the title (wraps below on narrow screens). */
    actions?: ReactNode;
};

/**
 * The top of every page: optional back link, title, short description and
 * the page's main actions. Breadcrumbs stay in the layout.
 */
export function PageHeader({ title, description, back, actions }: Props) {
    return (
        <div className="grid gap-2">
            {back && (
                <div>
                    <Button variant="ghost" size="sm" asChild className="-ml-2">
                        <Link href={back.href}>
                            <ArrowLeft className="size-4" aria-hidden />
                            {back.label}
                        </Link>
                    </Button>
                </div>
            )}

            <div className="flex flex-wrap items-start justify-between gap-3">
                <Heading title={title} description={description} level={1} />
                {actions && (
                    <div
                        role="group"
                        aria-label="Page actions"
                        className="flex flex-wrap items-center gap-2"
                    >
                        {actions}
                    </div>
                )}
            </div>
        </div>
    );
}
