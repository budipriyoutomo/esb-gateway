import { ChipRemoveButton } from '@/components/chip-remove-button';
import { LookupCombobox } from '@/components/lookup-combobox';
import { Badge } from '@/components/ui/badge';
import type { LookupKind, LookupOption } from '@/lib/lookups';

type Props = {
    id: string;
    label: string;
    kind: LookupKind;
    value: LookupOption[];
    onChange: (options: LookupOption[]) => void;
    placeholder?: string;
    debounceMs?: number;
};

/**
 * Several picks from one lookup ([ESB-019]): each pick is added once, and
 * shown as a chip that can be removed.
 */
export function LookupChips({
    id,
    label,
    kind,
    value,
    onChange,
    placeholder = 'Search to add…',
    debounceMs,
}: Props) {
    return (
        <div className="grid gap-2">
            <LookupCombobox
                id={id}
                label={label}
                kind={kind}
                value={null}
                onChange={(option) => {
                    if (
                        option !== null &&
                        !value.some((picked) => picked.value === option.value)
                    ) {
                        onChange([...value, option]);
                    }
                }}
                placeholder={placeholder}
                debounceMs={debounceMs}
            />
            {value.length > 0 && (
                <ul className="flex flex-wrap gap-1.5" aria-label={label}>
                    {value.map((option) => (
                        <li key={option.value}>
                            <Badge variant="secondary" className="gap-1 pr-1">
                                {option.label}
                                <ChipRemoveButton
                                    label={`Remove ${option.label}`}
                                    onRemove={() =>
                                        onChange(
                                            value.filter(
                                                (picked) =>
                                                    picked.value !==
                                                    option.value,
                                            ),
                                        )
                                    }
                                />
                            </Badge>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
