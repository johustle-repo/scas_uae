export type UserRole =
    | 'admin'
    | 'records_manager'
    | 'medical_staff'
    | 'placement_coordinator'
    | 'read_only';

/** Mirrors App\Enums\Permission. */
export type Permission =
    | 'manage-dogs'
    | 'manage-medical'
    | 'manage-placements'
    | 'manage-documents'
    | 'manage-users'
    | 'view-audit-log';

export type User = {
    id: number;
    name: string;
    email: string;
    avatar_url: string | null;
    role: UserRole;
    is_active: boolean;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
};

export type Auth = {
    user: User;
    roleLabel: string;
    permissions: Permission[];
};

export type RoleOption = {
    value: UserRole;
    label: string;
    description: string;
};
