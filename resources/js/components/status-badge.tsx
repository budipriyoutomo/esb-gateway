import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export type StatusTone = 'neutral' | 'warning' | 'success' | 'danger' | 'info';

// The ERP's status vocabulary is not documented; only "New" and "Authorized"
// have been seen (tests/Fixtures). Tones are guessed from keywords in English
// and Indonesian, and anything unrecognised stays neutral. The text shown is
// always the ERP's own.
const NEGATED = /\b(not|un|belum)\s*(authori[sz]|approv|setuju)/;

const RULES: [StatusTone, RegExp][] = [
    ['danger', /reject|cancel|tolak|batal|void/],
    ['warning', /wait|pending|menunggu|review/],
    ['success', /authori[sz]|approv|setuju/],
    ['info', /close|finish|complete|selesai|done/],
];

export function statusTone(status: string): StatusTone {
    const text = status.trim().toLowerCase();

    if (NEGATED.test(text)) {
        return 'neutral';
    }

    return RULES.find(([, pattern]) => pattern.test(text))?.[0] ?? 'neutral';
}

const TONE_CLASSES: Record<StatusTone, string> = {
    neutral: 'bg-secondary text-secondary-foreground',
    warning: 'bg-warning/10 text-warning border-warning/30',
    success: 'bg-success/10 text-success border-success/30',
    danger: 'bg-destructive/10 text-destructive-foreground border-destructive/30',
    info: 'bg-info/10 text-info border-info/30',
};

/** An ERP document status, coloured by its tone. */
export function StatusBadge({ status }: { status: string | null }) {
    if (status === null || status.trim() === '') {
        return <span className="text-muted-foreground">—</span>;
    }

    const tone = statusTone(status);

    return (
        <Badge
            variant="outline"
            data-tone={tone}
            className={cn('border-transparent', TONE_CLASSES[tone])}
        >
            {status}
        </Badge>
    );
}
