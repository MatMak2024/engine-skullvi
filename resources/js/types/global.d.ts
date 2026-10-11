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
            sidebarOpen: boolean;
            talent: {
                isCandidate: boolean;
                isSuspended: boolean;
                suspensionReason: string | null;
                candidateRemoved: boolean;
                roles: string[];
            };
            notifications: {
                unread: number;
                items: {
                    id: string;
                    title: string;
                    body: string;
                    read: boolean;
                }[];
            };
            [key: string]: unknown;
        };
    }
}
