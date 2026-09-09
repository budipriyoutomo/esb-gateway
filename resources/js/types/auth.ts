import type { Permission, RoleName } from '@/types/permissions';

export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
    role: RoleName | null;
    /**
     * What the current user may do. Drives which controls render; the server
     * still authorizes every route and action.
     */
    permissions: Permission[];
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
