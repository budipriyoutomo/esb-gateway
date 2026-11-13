import { Badge } from '@/components/ui/badge';

/** Whether a closing recap has every mapping it needs (ADR-032). */
export function ClosingReadinessBadge({
    ready,
    missing,
}: {
    ready: boolean;
    missing: number;
}) {
    return ready ? (
        <Badge variant="default">Ready</Badge>
    ) : (
        <Badge variant="destructive">
            {missing} {missing === 1 ? 'mapping' : 'mappings'} missing
        </Badge>
    );
}
