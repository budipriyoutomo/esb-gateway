import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

// Radix Select does not allow an empty value, so "any" has its own.
const ANY = '__any__';

export type SelectFilterOption = { value: string; label: string };

type Props = {
    id: string;
    label: string;
    /** The text for "no filter", e.g. "Any status". */
    anyLabel: string;
    options: SelectFilterOption[];
    /** The applied value; "" for no filter. */
    value: string;
    onChange: (value: string) => void;
    className?: string;
};

/**
 * A labelled select inside a FilterBar with an "any" choice for no filter.
 * The list pages apply it as soon as it changes.
 */
export function SelectFilterField({
    id,
    label,
    anyLabel,
    options,
    value,
    onChange,
    className,
}: Props) {
    return (
        <div className="grid gap-1.5">
            <Label htmlFor={id}>{label}</Label>
            <Select
                value={value === '' ? ANY : value}
                onValueChange={(next) => {
                    onChange(next === ANY ? '' : next);
                }}
            >
                <SelectTrigger id={id} className={cn('w-44', className)}>
                    <SelectValue placeholder={anyLabel} />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value={ANY}>{anyLabel}</SelectItem>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
