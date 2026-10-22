import { useState } from 'react';
import type { ActiveFilter } from '@/components/filter-bar';
import { useListQuery } from '@/hooks/use-list-query';
import type { ListQuery } from '@/hooks/use-list-query';

export type FilterField = {
    /** The query parameter, as the index route reads it. */
    key: string;
    /** The chip text for an applied value. */
    chip: (value: string) => string;
};

type Options = {
    /**
     * Query values set outside the form (a select that applies at once),
     * kept on submit and on removing a chip.
     */
    keep?: string[];
};

function text(value: ListQuery[string]): string {
    if (value === undefined || value === null) {
        return '';
    }

    return Array.isArray(value) ? value.join(',') : String(value);
}

/**
 * The text and date fields of a list's FilterBar, the same way on every
 * page (TODO-modules.md UI-6): each field starts from what the server
 * applied, chips describe the applied filters (not what is being typed), a
 * removed chip clears its field too, and reset clears everything.
 *
 * Spread `bar` into FilterBar; bind fields to `values` / `set`.
 */
export function useFilterForm(
    url: string,
    filters: ListQuery,
    fields: FilterField[],
    { keep = [] }: Options = {},
) {
    const list = useListQuery(url, filters);
    const initial = () =>
        Object.fromEntries(
            fields.map((field) => [field.key, text(filters[field.key])]),
        );
    const [values, setValues] = useState<Record<string, string>>(initial);

    const kept: ListQuery = Object.fromEntries(
        keep.map((key) => [key, filters[key]]),
    );

    function set(key: string, value: string) {
        setValues((current) => ({ ...current, [key]: value }));
    }

    const active: ActiveFilter[] = fields.flatMap((field) => {
        const value = text(filters[field.key]).trim();

        return value === ''
            ? []
            : [{ key: field.key, label: field.chip(value) }];
    });

    const bar = {
        onSubmit: () => {
            list.apply({ ...kept, ...values });
        },
        active,
        onRemove: (key: string) => {
            set(key, '');
            list.apply({ ...filters, page: undefined, [key]: undefined });
        },
        onReset: () => {
            setValues(
                Object.fromEntries(fields.map((field) => [field.key, ''])),
            );
            list.reset();
        },
    };

    return { values, set, bar, apply: list.apply, goToPage: list.goToPage };
}
