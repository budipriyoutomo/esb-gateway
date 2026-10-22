import { SelectFilterField } from '@/components/select-filter-field';

type Props = {
    id: string;
    /** "1" active, "0" inactive, "" any — the flagActive query value. */
    value: string;
    onChange: (value: string) => void;
};

const OPTIONS = [
    { value: '1', label: 'Active' },
    { value: '0', label: 'Inactive' },
];

/**
 * The active / inactive filter of the master data lists (flagActive). The
 * pages apply it as soon as it changes.
 */
export function ActiveStatusField({ id, value, onChange }: Props) {
    return (
        <SelectFilterField
            id={id}
            label="Status"
            anyLabel="Any status"
            options={OPTIONS}
            value={value}
            onChange={onChange}
            className="w-40"
        />
    );
}
