import { Loader2, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { KeyboardEvent } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export type ComboboxOption = {
    value: string;
    label: string;
    code?: string | null;
};

export type LoadOptions<T extends ComboboxOption = ComboboxOption> = (
    search: string,
    page: number,
) => Promise<{ options: T[]; hasMore: boolean }>;

type Props<T extends ComboboxOption> = {
    id: string;
    label: string;
    /** The selected option's value, or null. */
    value: string | null;
    /** Text shown for the selected value while the list is closed. */
    selectedLabel?: string | null;
    onChange: (option: T | null) => void;
    loadOptions: LoadOptions<T>;
    placeholder?: string;
    disabled?: boolean;
    /** Delay before a typed search is sent (ms). */
    debounceMs?: number;
};

/**
 * A searchable single-select picker (WAI-ARIA 1.2 combobox with a listbox
 * popup). Options are loaded page by page from `loadOptions`; an answer
 * that arrives after a newer search is ignored.
 */
export function Combobox<T extends ComboboxOption>({
    id,
    label,
    value,
    selectedLabel = null,
    onChange,
    loadOptions,
    placeholder = 'Search…',
    disabled = false,
    debounceMs = 250,
}: Props<T>) {
    const [open, setOpen] = useState(false);
    const [text, setText] = useState('');
    const [options, setOptions] = useState<T[]>([]);
    const [hasMore, setHasMore] = useState(false);
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [active, setActive] = useState(-1);
    const request = useRef(0);

    const listboxId = `${id}-listbox`;
    const optionId = (index: number) => `${id}-option-${index}`;

    function load(search: string, nextPage: number) {
        const ticket = ++request.current;
        setLoading(true);
        setError(null);

        loadOptions(search, nextPage)
            .then((result) => {
                if (ticket !== request.current) {
                    return;
                }

                setOptions((current) =>
                    nextPage === 1
                        ? result.options
                        : [...current, ...result.options],
                );
                setHasMore(result.hasMore);
                setPage(nextPage);
            })
            .catch((failure: unknown) => {
                if (ticket !== request.current) {
                    return;
                }

                setError(
                    failure instanceof Error
                        ? failure.message
                        : 'Could not load the list. Try again.',
                );
            })
            .finally(() => {
                if (ticket === request.current) {
                    setLoading(false);
                }
            });
    }

    // Search whenever the list is open and the text changes.
    useEffect(() => {
        if (!open) {
            return;
        }

        const timer = window.setTimeout(
            () => {
                setActive(-1);
                load(text.trim(), 1);
            },
            text === '' ? 0 : debounceMs,
        );

        return () => {
            window.clearTimeout(timer);
        };
    }, [open, text]);

    function openList() {
        if (disabled || open) {
            return;
        }

        setText('');
        setOpen(true);
    }

    function close() {
        // Any answer still on its way is no longer wanted.
        request.current++;
        setOpen(false);
        setLoading(false);
        setActive(-1);
        setText('');
    }

    function choose(option: T) {
        onChange(option);
        close();
    }

    function onKeyDown(event: KeyboardEvent<HTMLInputElement>) {
        if (event.key === 'ArrowDown') {
            event.preventDefault();

            if (!open) {
                openList();

                return;
            }

            setActive((index) => Math.min(index + 1, options.length - 1));
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActive((index) => Math.max(index - 1, 0));
        } else if (event.key === 'Enter' && open) {
            event.preventDefault();

            if (active >= 0 && options[active]) {
                choose(options[active]);
            }
        } else if (event.key === 'Escape' && open) {
            event.preventDefault();
            close();
        }
    }

    return (
        <div className="grid gap-1.5">
            <Label htmlFor={id}>{label}</Label>
            <div className="relative">
                <Input
                    id={id}
                    role="combobox"
                    aria-expanded={open}
                    aria-controls={listboxId}
                    aria-autocomplete="list"
                    aria-activedescendant={
                        open && active >= 0 ? optionId(active) : undefined
                    }
                    autoComplete="off"
                    disabled={disabled}
                    placeholder={placeholder}
                    value={open ? text : (selectedLabel ?? '')}
                    className={cn(value !== null && !disabled && 'pr-8')}
                    onClick={openList}
                    onFocus={openList}
                    onBlur={close}
                    onKeyDown={onKeyDown}
                    onChange={(event) => {
                        setText(event.target.value);

                        if (!open) {
                            setOpen(true);
                        }
                    }}
                />

                {value !== null && !disabled && !open && (
                    <button
                        type="button"
                        aria-label={`Clear ${label}`}
                        className="text-muted-foreground hover:text-foreground absolute top-1/2 right-2 -translate-y-1/2 rounded-sm p-0.5"
                        onClick={() => {
                            onChange(null);
                        }}
                    >
                        <X aria-hidden className="size-4" />
                    </button>
                )}

                {open && (
                    <div className="bg-popover text-popover-foreground absolute z-50 mt-1 w-full rounded-md border shadow-md">
                        <ul
                            id={listboxId}
                            role="listbox"
                            aria-label={label}
                            className="max-h-64 overflow-auto p-1"
                        >
                            {options.map((option, index) => (
                                <li
                                    key={option.value}
                                    id={optionId(index)}
                                    role="option"
                                    aria-selected={index === active}
                                    className={cn(
                                        'flex cursor-pointer items-center justify-between gap-2 rounded-sm px-2 py-1.5 text-sm',
                                        index === active &&
                                            'bg-accent text-accent-foreground',
                                        option.value === value && 'font-medium',
                                    )}
                                    // Keep focus in the input so blur does
                                    // not close the list before the click.
                                    onMouseDown={(event) => {
                                        event.preventDefault();
                                    }}
                                    onMouseEnter={() => {
                                        setActive(index);
                                    }}
                                    onClick={() => {
                                        choose(option);
                                    }}
                                >
                                    <span>{option.label}</span>
                                    {option.code && (
                                        <span className="text-muted-foreground font-mono text-xs">
                                            {option.code}
                                        </span>
                                    )}
                                </li>
                            ))}
                        </ul>

                        <div className="px-3 pb-2 text-sm">
                            {loading && (
                                <p
                                    role="status"
                                    className="text-muted-foreground flex items-center gap-2 pt-2"
                                >
                                    <Loader2
                                        aria-hidden
                                        className="size-4 animate-spin"
                                    />
                                    Loading…
                                </p>
                            )}
                            {error && (
                                <p
                                    role="alert"
                                    className="text-destructive-foreground pt-2"
                                >
                                    {error}
                                </p>
                            )}
                            {!loading && !error && options.length === 0 && (
                                <p className="text-muted-foreground pt-2">
                                    No matches.
                                </p>
                            )}
                            {!loading && !error && hasMore && (
                                <button
                                    type="button"
                                    className="text-primary pt-2 underline-offset-4 hover:underline"
                                    onMouseDown={(event) => {
                                        event.preventDefault();
                                    }}
                                    onClick={() => {
                                        load(text.trim(), page + 1);
                                    }}
                                >
                                    Load more
                                </button>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
