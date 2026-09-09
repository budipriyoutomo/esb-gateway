import { Head, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { usePermissions } from '@/hooks/use-permissions';
import { index as esbConnectionsIndex } from '@/routes/esb-connections';

type Connection = {
    family: string;
    baseUrl: string | null;
    configured: boolean;
    probeAvailable: boolean;
    note: string | null;
};

type ProbeResult = {
    family: string;
    reachable: boolean | null;
    outcome: string | null;
    message: string;
};

type Props = {
    connections: Connection[];
    environment: string;
    authMechanism: string;
};

export default function EsbConnectionsIndex({
    connections,
    environment,
    authMechanism,
}: Props) {
    const { can } = usePermissions();
    const probe = usePage().props.probe as ProbeResult | undefined;

    function runProbe(family: string) {
        router.post(
            `/esb-connections/${family}/test`,
            {},
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="ESB connections" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="ESB connections"
                    description={`Environment: ${environment}. Authentication: ${authMechanism}.`}
                />

                <p className="text-muted-foreground max-w-3xl text-sm">
                    Base URLs and credentials are configuration, not records in
                    this application, so they are shown here but cannot be
                    edited. Changing one is a deployment action. Credentials
                    never leave the server.
                </p>

                {probe && (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="font-mono text-xs">
                                {probe.family}
                            </span>
                            <Badge
                                variant={
                                    probe.reachable === true
                                        ? 'default'
                                        : probe.reachable === false
                                          ? 'destructive'
                                          : 'secondary'
                                }
                            >
                                {probe.reachable === true
                                    ? 'Reachable'
                                    : probe.reachable === false
                                      ? 'Unreachable'
                                      : 'Not probed'}
                            </Badge>
                            {probe.outcome && (
                                <span className="text-muted-foreground text-xs">
                                    {probe.outcome}
                                </span>
                            )}
                        </div>
                        <p className="text-muted-foreground mt-2 text-sm">
                            {probe.message}
                        </p>
                        {probe.reachable === true && probe.outcome && (
                            <p className="text-muted-foreground mt-1 text-xs">
                                The ERP service answered, so the host is up.
                                What failed is the gateway credential&rsquo;s
                                privileges for this operation.
                            </p>
                        )}
                    </div>
                )}

                <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-2">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Family</TableHead>
                                <TableHead>Base URL</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {connections.map((connection) => (
                                <TableRow key={connection.family}>
                                    <TableCell className="font-mono text-xs">
                                        {connection.family}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground font-mono text-xs">
                                        {connection.baseUrl ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                connection.configured
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {connection.configured
                                                ? 'Configured'
                                                : 'Not configured'}
                                        </Badge>
                                        {connection.note && (
                                            <p className="text-muted-foreground mt-1 max-w-md text-xs">
                                                {connection.note}
                                            </p>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {can('esb-connections.test') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                disabled={
                                                    !connection.configured ||
                                                    !connection.probeAvailable
                                                }
                                                onClick={() => {
                                                    runProbe(connection.family);
                                                }}
                                            >
                                                Test
                                            </Button>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}

EsbConnectionsIndex.layout = {
    breadcrumbs: [
        {
            title: 'ESB connections',
            href: esbConnectionsIndex(),
        },
    ],
};
