import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type DateRange = { from: string; to: string };

type Props = {
    /** Prefix for element ids, unique on the page. */
    id: string;
    /** e.g. "Order date" — the inputs read "Order date from" / "… to". */
    label: string;
    from: string;
    to: string;
    onChange: (range: DateRange) => void;
};

/**
 * A from/to pair of native date inputs (yyyy-mm-dd, as the filters send
 * them). The end cannot be set before the start.
 */
export function DateRangeField({ id, label, from, to, onChange }: Props) {
    return (
        <fieldset className="flex items-end gap-2">
            <legend className="sr-only">{label}</legend>
            <div className="grid gap-1.5">
                <Label htmlFor={`${id}-from`}>{label} from</Label>
                <Input
                    id={`${id}-from`}
                    type="date"
                    value={from}
                    onChange={(event) => {
                        onChange({ from: event.target.value, to });
                    }}
                />
            </div>
            <div className="grid gap-1.5">
                <Label htmlFor={`${id}-to`}>
                    <span className="sr-only">{label} </span>to
                </Label>
                <Input
                    id={`${id}-to`}
                    type="date"
                    value={to}
                    min={from || undefined}
                    onChange={(event) => {
                        onChange({ from, to: event.target.value });
                    }}
                />
            </div>
        </fieldset>
    );
}
