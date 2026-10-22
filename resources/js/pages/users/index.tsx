import { Head, router } from '@inertiajs/react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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

function assignRole(user: UserRow, role: string) {
    router.put(updateUserRole(user.id).url, { role }, { preserveScroll: true });
}

export default function UsersIndex({ users: rows, roles }: Props) {
    const columns: DataTableColumn<UserRow>[] = [
        {
            key: 'name',
            header: 'Name',
            className: 'font-medium',
            cell: (user) => user.name,
        },
        {
            key: 'email',
            header: 'Email',
            className: 'text-muted-foreground',
            cell: (user) => user.email,
        },
        {
            key: 'role',
            header: 'Role',
            cell: (user) =>
                user.canAssignRole ? (
                    <Select
                        value={user.role ?? undefined}
                        onValueChange={(role) => {
                            assignRole(user, role);
                        }}
                    >
                        <SelectTrigger
                            className="w-56"
                            // One picker per row: the name says whose role it is.
                            aria-label={`Role for ${user.name}`}
                        >
                            <SelectValue placeholder="No role" />
                        </SelectTrigger>
                        <SelectContent>
                            {roles.map((role) => (
                                <SelectItem key={role} value={role}>
                                    {role}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                ) : (
                    <Badge variant="secondary">{user.role ?? 'No role'}</Badge>
                ),
        },
    ];

    return (
        <>
            <Head title="Users" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Users"
                    description="Application users and the role each one holds. A user has exactly one role."
                />

                <DataTable
                    caption="Users"
                    columns={columns}
                    rows={rows}
                    rowKey={(user) => String(user.id)}
                    empty="No users yet."
                />
            </div>
        </>
    );
}
