import type { ReactNode } from 'react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

export type DetailTab = {
    value: string;
    label: string;
    /** Rows the tab holds, shown next to the label; omit when not a list. */
    count?: number;
    content: ReactNode;
};

/**
 * The sections of a detail page below its header fields, one at a time.
 * Radix Tabs (ADR-029) gives the tablist / tab / tabpanel roles and the
 * arrow-key movement.
 */
export function DetailTabs({
    label,
    tabs,
    defaultValue,
}: {
    /** Accessible name of the tab list, e.g. "Menu sections". */
    label: string;
    tabs: DetailTab[];
    defaultValue?: string;
}) {
    return (
        <Tabs defaultValue={defaultValue ?? tabs[0]?.value}>
            <TabsList aria-label={label}>
                {tabs.map((tab) => (
                    <TabsTrigger key={tab.value} value={tab.value}>
                        {tab.label}
                        {/* A space so the name reads "Extras 3", not "Extras3". */}
                        {tab.count !== undefined && ' '}
                        {tab.count !== undefined && (
                            <span className="bg-background/60 text-muted-foreground rounded px-1.5 text-xs tabular-nums">
                                {tab.count}
                            </span>
                        )}
                    </TabsTrigger>
                ))}
            </TabsList>

            {tabs.map((tab) => (
                <TabsContent
                    key={tab.value}
                    value={tab.value}
                    className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
                >
                    {tab.content}
                </TabsContent>
            ))}
        </Tabs>
    );
}
