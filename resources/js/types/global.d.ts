import type { Auth } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            // One-shot messages a controller flashed on redirect.
            flash: {
                status: string | null;
                probe: unknown;
            };
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
