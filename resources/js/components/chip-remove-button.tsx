import { X } from 'lucide-react';

type Props = {
    /** The accessible name, e.g. "Remove filter: Branch Jakarta". */
    label: string;
    onRemove: () => void;
};

/**
 * The × inside a chip (filter chips, picked lookups). The icon stays small;
 * on a touch screen a pseudo-element widens the hit area to 44px (WCAG
 * 2.5.5) without growing the chip (TODO-modules.md UI-5).
 */
export function ChipRemoveButton({ label, onRemove }: Props) {
    return (
        <button
            type="button"
            aria-label={label}
            className="hover:bg-background/60 focus-visible:ring-ring relative rounded-sm p-0.5 outline-none focus-visible:ring-2 pointer-coarse:after:absolute pointer-coarse:after:-inset-3.5"
            onClick={onRemove}
        >
            <X aria-hidden className="size-3" />
        </button>
    );
}
