import { Head, router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
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
import Heading from '@/components/heading';
import { index as usersIndex } from '@/routes/users';
import { update as updateUserRole } from '@/routes/users/roles';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string | null;
    canAssignRole: boolean;
};

type Props = {
    users: UserRow[];
    roles: string[];
};

export default function UsersIndex({ users: rows, roles }: Props) {
    function assignRole(user: UserRow, role: string) {
        router.put(
            updateUserRole(user.id).url,
            { role },
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Users" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Users"
                    description="Application users and the role each one holds. A user has exactly one role."
                />

                <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-2">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Role</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={3}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        No users yet.
                                    </TableCell>
                                </TableRow>
                            )}

                            {rows.map((user) => (
                                <TableRow key={user.id}>
                                    <TableCell className="font-medium">
                                        {user.name}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {user.email}
                                    </TableCell>
                                    <TableCell>
                                        {user.canAssignRole ? (
                                            <Select
                                                value={user.role ?? undefined}
                                                onValueChange={(role) => {
                                                    assignRole(user, role);
                                                }}
                                            >
                                                <SelectTrigger className="w-56">
                                                    <SelectValue placeholder="No role" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {roles.map((role) => (
                                                        <SelectItem
                                                            key={role}
                                                            value={role}
                                                        >
                                                            {role}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        ) : (
                                            <Badge variant="secondary">
                                                {user.role ?? 'No role'}
                                            </Badge>
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

UsersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Users',
            href: usersIndex(),
        },
    ],
};
