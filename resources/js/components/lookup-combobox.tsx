import { useRef } from 'react';
import { Combobox } from '@/components/combobox';
import { fetchLookup } from '@/lib/lookups';
import type { LookupKind, LookupOption } from '@/lib/lookups';

/** Kinds that cannot be listed without a branch (LookupKind::requiresBranch()). */
const NEEDS_BRANCH: LookupKind[] = ['transfer-products'];

type Props = {
    id: string;
    label: string;
    kind: LookupKind;
    /** Narrows branch-bound lists; required for kinds that need one. */
    branchId?: number | null;
    value: string | null;
    selectedLabel?: string | null;
    onChange: (option: LookupOption | null) => void;
    placeholder?: string;
    disabled?: boolean;
    debounceMs?: number;
};

/**
 * A Combobox over one of the gateway's lookups (GET /lookups/{kind},
 * [ESB-019]). Only the newest request is kept; older ones are aborted.
 */
export function LookupCombobox({
    kind,
    branchId = null,
    placeholder,
    disabled = false,
    ...props
}: Props) {
    const inFlight = useRef<AbortController | null>(null);
    const waitingForBranch = NEEDS_BRANCH.includes(kind) && branchId === null;

    function loadOptions(search: string, page: number) {
        inFlight.current?.abort();
        const controller = new AbortController();
        inFlight.current = controller;

        return fetchLookup(kind, { search, page, branchId }, controller.signal);
    }

    return (
        <Combobox<LookupOption>
            {...props}
            loadOptions={loadOptions}
            disabled={disabled || waitingForBranch}
            placeholder={
                waitingForBranch ? 'Choose a branch first' : placeholder
            }
        />
    );
}
