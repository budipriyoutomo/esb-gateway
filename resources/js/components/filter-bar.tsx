import { ChevronDown } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { ChipRemoveButton } from '@/components/chip-remove-button';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export type ActiveFilter = { key: string; label: string };

type Props = {
    /** Prefix for element ids, unique on the page. */
    id: string;
    search: {
        value: string;
        onChange: (value: string) => void;
        placeholder?: string;
        label?: string;
    };
    onSubmit: () => void;
    /** Filters currently applied (from the server's echo), shown as chips. */
    active: ActiveFilter[];
    onRemove: (key: string) => void;
    onReset: () => void;
    /** Advanced filter fields, behind "More filters". */
    children?: ReactNode;
    defaultAdvancedOpen?: boolean;
};

/**
 * The filter area above every list: the main search always visible,
 * advanced fields behind a toggle, applied filters as removable chips.
 * Enter submits.
 */
export function FilterBar({
    id,
    search,
    onSubmit,
    active,
    onRemove,
    onReset,
    children,
    defaultAdvancedOpen = false,
}: Props) {
    const [open, setOpen] = useState(defaultAdvancedOpen);

    function submit(event: FormEvent) {
        event.preventDefault();
        onSubmit();
    }

    return (
        <form role="search" onSubmit={submit} className="grid gap-3">
            <Collapsible open={open} onOpenChange={setOpen}>
                <div className="flex flex-wrap items-end gap-3">
                    <div className="grid w-full gap-1.5 sm:w-72">
                        <Label htmlFor={`${id}-search`}>
                            {search.label ?? 'Search'}
                        </Label>
                        <Input
                            id={`${id}-search`}
                            type="search"
                            value={search.value}
                            placeholder={search.placeholder}
                            onChange={(event) => {
                                search.onChange(event.target.value);
                            }}
                        />
                    </div>
                    <Button type="submit" variant="secondary">
                        Apply
                    </Button>
                    {children && (
                        <CollapsibleTrigger asChild>
                            <Button type="button" variant="ghost">
                                More filters
                                <ChevronDown
                                    aria-hidden
                                    className={cn(
                                        'size-4 transition-transform',
                                        open && 'rotate-180',
                                    )}
                                />
                            </Button>
                        </CollapsibleTrigger>
                    )}
                </div>

                {children && (
                    <CollapsibleContent className="pt-3">
                        <div className="flex flex-wrap items-end gap-3">
                            {children}
                        </div>
                    </CollapsibleContent>
                )}
            </Collapsible>

            {active.length > 0 && (
                <div className="flex flex-wrap items-center gap-2">
                    {active.map((filter) => (
                        <Badge
                            key={filter.key}
                            variant="secondary"
                            className="gap-1 pr-1"
                        >
                            {filter.label}
                            <ChipRemoveButton
                                label={`Remove filter: ${filter.label}`}
                                onRemove={() => {
                                    onRemove(filter.key);
                                }}
                            />
                        </Badge>
                    ))}
                    <Button
                        type="button"
                        variant="link"
                        size="sm"
                        onClick={onReset}
                    >
                        Reset filters
                    </Button>
                </div>
            )}
        </form>
    );
}
