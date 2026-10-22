export default function Heading({
    title,
    description,
    variant = 'default',
    level = 2,
}: {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
    /** 1 for the page title (PageHeader); 2 for a section. */
    level?: 1 | 2;
}) {
    const Tag = level === 1 ? 'h1' : 'h2';

    return (
        <header className={variant === 'small' ? '' : 'mb-8 space-y-0.5'}>
            <Tag
                className={
                    variant === 'small'
                        ? 'mb-0.5 text-base font-medium'
                        : 'text-xl font-semibold tracking-tight'
                }
            >
                {title}
            </Tag>
            {description && (
                <p className="text-muted-foreground text-sm">{description}</p>
            )}
        </header>
    );
}
