import { router } from '@inertiajs/react';
import { FileSearch, Search } from 'lucide-react';
import { useEffect, useId, useState } from 'react';
import type { KeyboardEvent } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { usePermissions } from '@/hooks/use-permissions';
import {
    documentTargets,
    flattenNavigation,
    mainNavigation,
    visibleNavigation,
} from '@/lib/navigation';
import { cn } from '@/lib/utils';

type PaletteOption = {
    key: string;
    label: string;
    hint: string | null;
    href: string;
    kind: 'page' | 'document';
};

/**
 * Text that can be a document number: one path segment of letters, digits,
 * dot, dash or underscore, with at least one digit (every ERP number the
 * gateway reads has one). Anything else is never put in a URL.
 */
const DOCUMENT_NUMBER = /^(?=.*\d)[A-Za-z0-9][A-Za-z0-9._-]{0,63}$/;

/**
 * Ctrl+K / ⌘K: jump to a page of the menu, or open a document by its number
 * in one of the modules that read by number (TODO-modules.md UI-3).
 *
 * Only what the person may open is offered; the server still authorizes
 * every page. A number is not looked up first -- the module's page answers
 * 404 when the ERP has no such document.
 */
export function CommandPalette() {
    const { can } = usePermissions();
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [active, setActive] = useState(0);
    const id = useId();
    const listboxId = `${id}-listbox`;

    useEffect(() => {
        function onKeyDown(event: globalThis.KeyboardEvent) {
            if (
                event.key.toLowerCase() === 'k' &&
                (event.ctrlKey || event.metaKey)
            ) {
                event.preventDefault();
                setOpen((current) => !current);
            }
        }

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, []);

    const options = paletteOptions(query.trim(), can);
    const activeIndex = Math.min(active, Math.max(options.length - 1, 0));
    const optionId = (index: number) => `${id}-option-${index}`;

    function changeOpen(next: boolean) {
        setOpen(next);
        setQuery('');
        setActive(0);
    }

    function choose(option: PaletteOption) {
        changeOpen(false);
        router.visit(option.href);
    }

    function onInputKeyDown(event: KeyboardEvent<HTMLInputElement>) {
        if (options.length === 0) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActive((activeIndex + 1) % options.length);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActive((activeIndex - 1 + options.length) % options.length);
        } else if (event.key === 'Enter') {
            event.preventDefault();
            choose(options[activeIndex]!);
        }
    }

    return (
        <>
            <Button
                type="button"
                variant="outline"
                size="sm"
                className="text-muted-foreground gap-2"
                aria-keyshortcuts="Control+K Meta+K"
                onClick={() => changeOpen(true)}
            >
                <Search aria-hidden="true" />
                <span className="sr-only sm:not-sr-only">Search</span>
                <kbd
                    aria-hidden="true"
                    className="bg-muted hidden rounded px-1.5 font-mono text-[10px] sm:inline"
                >
                    Ctrl K
                </kbd>
            </Button>

            <Dialog open={open} onOpenChange={changeOpen}>
                <DialogContent className="top-[20%] translate-y-0 gap-0 p-0 sm:max-w-lg">
                    <DialogTitle className="sr-only">Go to</DialogTitle>
                    <DialogDescription className="sr-only">
                        Type a page name, or a document number to open it. Use
                        the arrow keys to choose and Enter to open.
                    </DialogDescription>

                    <div className="border-b p-3 pr-12">
                        <Input
                            role="combobox"
                            aria-label="Search pages or type a document number"
                            aria-expanded="true"
                            aria-controls={listboxId}
                            aria-autocomplete="list"
                            aria-activedescendant={
                                options.length > 0
                                    ? optionId(activeIndex)
                                    : undefined
                            }
                            autoComplete="off"
                            placeholder="Search pages or type a document number…"
                            value={query}
                            onChange={(event) => {
                                setQuery(event.target.value);
                                setActive(0);
                            }}
                            onKeyDown={onInputKeyDown}
                        />
                    </div>

                    <ul
                        id={listboxId}
                        role="listbox"
                        aria-label="Results"
                        className="max-h-80 overflow-y-auto p-2"
                    >
                        {options.map((option, index) => (
                            <li
                                key={option.key}
                                id={optionId(index)}
                                role="option"
                                aria-selected={index === activeIndex}
                                className={cn(
                                    'flex cursor-pointer items-center gap-2 rounded-md px-3 py-2 text-sm',
                                    index === activeIndex &&
                                        'bg-accent text-accent-foreground',
                                )}
                                onMouseMove={() => setActive(index)}
                                onMouseDown={(event) => event.preventDefault()}
                                onClick={() => choose(option)}
                            >
                                {option.kind === 'document' && (
                                    <FileSearch
                                        className="size-4 shrink-0"
                                        aria-hidden="true"
                                    />
                                )}
                                <span className="truncate">{option.label}</span>
                                {option.hint && (
                                    <span className="text-muted-foreground ml-auto shrink-0 text-xs">
                                        {option.hint}
                                    </span>
                                )}
                            </li>
                        ))}
                    </ul>

                    {options.length === 0 && (
                        <p className="text-muted-foreground px-5 pb-5 text-sm">
                            Nothing matches.
                        </p>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

function paletteOptions(
    query: string,
    can: ReturnType<typeof usePermissions>['can'],
): PaletteOption[] {
    const needle = query.toLowerCase();

    const pages = flattenNavigation(visibleNavigation(mainNavigation, can))
        .filter(
            (link) =>
                needle === '' ||
                link.title.toLowerCase().includes(needle) ||
                (link.group?.toLowerCase().includes(needle) ?? false),
        )
        .map((link): PaletteOption => ({
            key: `page:${link.href}`,
            label: link.title,
            hint: link.group,
            href: link.href,
            kind: 'page',
        }));

    const documents = DOCUMENT_NUMBER.test(query)
        ? documentTargets
              .filter((target) => can(target.permission))
              .map((target): PaletteOption => ({
                  key: `document:${target.title}`,
                  label: `Open ${query} in ${target.title}`,
                  hint: null,
                  href: target.href(query),
                  kind: 'document',
              }))
        : [];

    return [...pages, ...documents];
}
