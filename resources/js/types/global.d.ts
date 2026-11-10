import type { Abilities, Auth, Tenant } from '@/types/auth';

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
            tenant: Tenant | null;
            can: Abilities;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
