import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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

type ConnectionSource = 'database' | 'override' | 'config' | 'none';

type Connection = {
    family: string;
    baseUrl: string | null;
    configured: boolean;
    probeAvailable: boolean;
    source: ConnectionSource;
    hasCredential: boolean;
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
    canManage: boolean;
};

const SOURCE_LABEL: Record<ConnectionSource, string> = {
    database: 'Edited here',
    override: 'Env override',
    config: 'Configuration',
    none: 'Undocumented',
};

/**
 * A database row outranks an environment override, which outranks the family
 * table. Saying which one won is what stops an operator debugging an override
 * that has no effect by guesswork.
 */
const SOURCE_HINT: Record<ConnectionSource, string> = {
    database:
        'A connection saved in this screen, which takes precedence over the environment override and config/esb.php.',
    override: 'An ESB_BASE_URL_* environment override.',
    config: 'The family table in config/esb.php.',
    none: 'No base URL is documented for this family in this environment.',
};

export default function EsbConnectionsIndex({
    connections,
    environment,
    authMechanism,
    canManage,
}: Props) {
    const { can } = usePermissions();
    const probe = usePage().props.probe as ProbeResult | undefined;
    const [editing, setEditing] = useState<Connection | null>(null);

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
                    A connection saved here overrides{' '}
                    <code>config/esb.php</code> for this environment, so an ESB
                    can be repointed without a deploy. Credentials are encrypted
                    at rest and never shown again after they are saved &mdash;
                    the most this screen will tell you is whether one is stored.
                    Every change is recorded in the audit log.
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
                                <TableHead>Source</TableHead>
                                <TableHead>Credential</TableHead>
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
                                        <span
                                            className="text-muted-foreground text-xs"
                                            title={
                                                SOURCE_HINT[connection.source]
                                            }
                                        >
                                            {SOURCE_LABEL[connection.source]}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <span className="text-muted-foreground text-xs">
                                            {connection.hasCredential
                                                ? 'Stored'
                                                : 'From environment'}
                                        </span>
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
                                    <TableCell className="space-x-2 text-right">
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
                                        {canManage && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => {
                                                    setEditing(connection);
                                                }}
                                            >
                                                Edit
                                            </Button>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>

            {editing && (
                <EditConnectionDialog
                    connection={editing}
                    environment={environment}
                    onClose={() => {
                        setEditing(null);
                    }}
                />
            )}
        </>
    );
}

function EditConnectionDialog({
    connection,
    environment,
    onClose,
}: {
    connection: Connection;
    environment: string;
    onClose: () => void;
}) {
    const form = useForm({
        base_url: connection.baseUrl ?? '',
        auth_mechanism: 'static_token',
        static_token: '',
        username: '',
        password: '',
        is_active: true,
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();

        form.put(`/esb-connections/${connection.family}`, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    }

    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open) {
                    onClose();
                }
            }}
        >
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>
                            Edit the {connection.family} connection
                        </DialogTitle>
                        <DialogDescription>
                            This repoints the {connection.family} family in the{' '}
                            {environment} environment. Traffic moves as soon as
                            you save.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="base_url">Base URL</Label>
                            <Input
                                id="base_url"
                                value={form.data.base_url}
                                onChange={(event) => {
                                    form.setData(
                                        'base_url',
                                        event.target.value,
                                    );
                                }}
                                placeholder="https://"
                            />
                            {form.errors.base_url && (
                                <p className="text-destructive text-xs">
                                    {form.errors.base_url}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="auth_mechanism">
                                Authentication
                            </Label>
                            <Select
                                value={form.data.auth_mechanism}
                                onValueChange={(value) => {
                                    form.setData('auth_mechanism', value);
                                }}
                            >
                                <SelectTrigger id="auth_mechanism">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="static_token">
                                        Static token
                                    </SelectItem>
                                    <SelectItem value="login">
                                        Username and password
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {form.data.auth_mechanism === 'static_token' ? (
                            <div className="grid gap-2">
                                <Label htmlFor="static_token">
                                    Static token
                                </Label>
                                <Input
                                    id="static_token"
                                    type="password"
                                    autoComplete="off"
                                    value={form.data.static_token}
                                    onChange={(event) => {
                                        form.setData(
                                            'static_token',
                                            event.target.value,
                                        );
                                    }}
                                    placeholder={
                                        connection.hasCredential
                                            ? 'Stored — leave blank to keep it'
                                            : ''
                                    }
                                />
                                {form.errors.static_token && (
                                    <p className="text-destructive text-xs">
                                        {form.errors.static_token}
                                    </p>
                                )}
                            </div>
                        ) : (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="username">Username</Label>
                                    <Input
                                        id="username"
                                        autoComplete="off"
                                        value={form.data.username}
                                        onChange={(event) => {
                                            form.setData(
                                                'username',
                                                event.target.value,
                                            );
                                        }}
                                    />
                                    {form.errors.username && (
                                        <p className="text-destructive text-xs">
                                            {form.errors.username}
                                        </p>
                                    )}
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="password">Password</Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        autoComplete="off"
                                        value={form.data.password}
                                        onChange={(event) => {
                                            form.setData(
                                                'password',
                                                event.target.value,
                                            );
                                        }}
                                        placeholder={
                                            connection.hasCredential
                                                ? 'Stored — leave blank to keep it'
                                                : ''
                                        }
                                    />
                                    {form.errors.password && (
                                        <p className="text-destructive text-xs">
                                            {form.errors.password}
                                        </p>
                                    )}
                                </div>
                            </>
                        )}

                        <p className="text-muted-foreground text-xs">
                            A saved credential is encrypted and never shown
                            again. Leaving a credential field blank keeps the
                            one already stored.
                        </p>
                    </div>

                    <DialogFooter className="gap-2">
                        {connection.source === 'database' && (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                    router.delete(
                                        `/esb-connections/${connection.family}`,
                                        {
                                            preserveScroll: true,
                                            onSuccess: onClose,
                                        },
                                    );
                                }}
                            >
                                Revert to configuration
                            </Button>
                        )}
                        <Button type="submit" disabled={form.processing}>
                            Save
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
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
