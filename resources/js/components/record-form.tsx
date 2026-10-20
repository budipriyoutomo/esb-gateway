import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import AlertError from '@/components/alert-error';
import { FormActions } from '@/components/form-actions';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useUnsavedChangesWarning } from '@/hooks/use-unsaved-changes-warning';

type Href = NonNullable<InertiaLinkProps['href']>;

type Props = {
    title: string;
    description?: string;
    back: { href: Href; label: string };
    cancelHref: Href;
    submitLabel: string;
    onSubmit: () => void;
    processing: boolean;
    /** Inertia's form.isDirty: leaving is confirmed while true. */
    isDirty: boolean;
    /** An error for the form as a whole (the "form" key). */
    formError?: string;
    children: ReactNode;
};

/**
 * The create / edit page of a master data record (TODO-modules.md §2, S3):
 * header, a form-wide error, the fields, then Cancel and the submit button.
 * Leaving with unsaved changes is confirmed.
 */
export function RecordForm({
    title,
    description,
    back,
    cancelHref,
    submitLabel,
    onSubmit,
    processing,
    isDirty,
    formError,
    children,
}: Props) {
    useUnsavedChangesWarning(isDirty);

    function submit(event: FormEvent) {
        event.preventDefault();
        onSubmit();
    }

    return (
        <div className="flex flex-col gap-6 p-4">
            <PageHeader title={title} description={description} back={back} />

            <form
                onSubmit={submit}
                noValidate
                className="flex max-w-2xl flex-col gap-6"
            >
                {formError && (
                    <AlertError
                        title="The ERP did not accept this."
                        errors={[formError]}
                    />
                )}

                {children}

                <FormActions>
                    <Button variant="outline" asChild>
                        <Link href={cancelHref}>Cancel</Link>
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Saving…' : submitLabel}
                    </Button>
                </FormActions>
            </form>
        </div>
    );
}

/**
 * One field: label above, the control, a hint and its error below.
 */
export function RecordFormField({
    id,
    label,
    error,
    hint,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    hint?: string;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            {children}
            {hint && <p className="text-muted-foreground text-sm">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}
