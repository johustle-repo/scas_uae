import type { Auth, Permission } from '@/types/auth';
import { usePage } from '@inertiajs/react';

/**
 * Whether the signed-in user holds a permission. The server enforces every
 * permission too; this only hides controls the user cannot use.
 */
export function useCan(): (permission: Permission) => boolean {
    const { auth } = usePage<{ auth: Auth }>().props;

    return (permission) => auth.permissions.includes(permission);
}
