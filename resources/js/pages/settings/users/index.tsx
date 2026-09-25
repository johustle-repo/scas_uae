import {
    resetPassword,
    store,
    update,
    updateStatus,
} from '@/actions/App/Http/Controllers/Settings/UserController';
import {
    AlertIcon,
    CheckIcon,
    KeyIcon,
    PencilIcon,
    PlusIcon,
    SearchIcon,
    UsersIcon,
    type IconProps,
} from '@/components/icons';
import { PageHeader } from '@/components/page-header';
import { Badge, type BadgeTone } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ConfirmButton } from '@/components/ui/confirm-button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Modal } from '@/components/ui/modal';
import { PasswordInput } from '@/components/ui/password-input';
import { Select } from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeaderCell,
    TableRow,
} from '@/components/ui/table';
import { AuthenticatedLayout } from '@/layouts/authenticated-layout';
import { cn } from '@/lib/utils';
import settingsRoutes from '@/routes/settings';
import { UserAvatar } from '@/components/user-avatar';
import type { Auth, RoleOption, User, UserRole } from '@/types/auth';
import { formatDate } from '@/types/dog';
import { Form, Head, usePage } from '@inertiajs/react';
import {
    type ComponentType,
    type ReactElement,
    type ReactNode,
    useState,
} from 'react';

interface UsersIndexProps {
    users: User[];
    roleOptions: RoleOption[];
}

type Dialog =
    | { type: 'create' }
    | { type: 'edit'; user: User }
    | { type: 'password'; user: User }
    | null;

type StatusFilter = 'all' | 'active' | 'deactivated';

const ROLE_TONES: Record<UserRole, BadgeTone> = {
    admin: 'brand',
    records_manager: 'info',
    medical_staff: 'success',
    placement_coordinator: 'warning',
    read_only: 'neutral',
};

export default function UsersIndex({ users, roleOptions }: UsersIndexProps) {
    const { auth, errors } = usePage<{
        auth: Auth;
        errors: Record<string, string>;
    }>().props;
    const [dialog, setDialog] = useState<Dialog>(null);
    const [search, setSearch] = useState('');
    const [roleFilter, setRoleFilter] = useState('');
    const [statusFilter, setStatusFilter] = useState<StatusFilter>('all');
    const roleLabel = (role: string) =>
        roleOptions.find((option) => option.value === role)?.label ?? role;
    const close = () => setDialog(null);

    const activeCount = users.filter((user) => user.is_active).length;
    const countByRole = (role: string) =>
        users.filter((user) => user.role === role).length;
    const term = search.trim().toLowerCase();
    const visibleUsers = users.filter(
        (user) =>
            (term === '' ||
                user.name.toLowerCase().includes(term) ||
                user.email.toLowerCase().includes(term)) &&
            (roleFilter === '' || user.role === roleFilter) &&
            (statusFilter === 'all' ||
                user.is_active === (statusFilter === 'active')),
    );

    function clearFilters() {
        setSearch('');
        setRoleFilter('');
        setStatusFilter('all');
    }

    return (
        <>
            <Head title="Manage Users" />

            <PageHeader
                back={{
                    href: settingsRoutes.profile.edit.url(),
                    label: 'Back to Settings',
                }}
                title="Manage Users"
                icon={UsersIcon}
                description="Staff accounts, their roles and access to the SCAS Dog Database."
                actions={
                    <Button
                        size="sm"
                        onClick={() => setDialog({ type: 'create' })}
                    >
                        <PlusIcon className="size-3.5" />
                        Add User
                    </Button>
                }
            />

            {errors.is_active && (
                <p
                    role="alert"
                    className="mb-6 flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300"
                >
                    <AlertIcon className="size-4 shrink-0" />
                    {errors.is_active}
                </p>
            )}

            <div className="mb-6 grid grid-cols-2 gap-6 lg:grid-cols-4">
                <StatTile
                    icon={UsersIcon}
                    label="Total users"
                    value={users.length}
                />
                <StatTile
                    icon={CheckIcon}
                    label="Active"
                    value={activeCount}
                    tone="success"
                />
                <StatTile
                    icon={AlertIcon}
                    label="Deactivated"
                    value={users.length - activeCount}
                    tone={users.length - activeCount > 0 ? 'warning' : 'brand'}
                />
                <StatTile
                    icon={KeyIcon}
                    label="Administrators"
                    value={countByRole('admin')}
                />
            </div>

            <Card className="mb-6 overflow-hidden">
                <div className="flex flex-col gap-3 border-b border-neutral-100 p-4 sm:flex-row sm:items-center dark:border-neutral-800">
                    <div className="relative flex-1">
                        <SearchIcon className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-400" />
                        <Input
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search by name or email…"
                            aria-label="Search users"
                            className="pl-9"
                        />
                    </div>
                    <div className="grid grid-cols-2 gap-3 sm:flex">
                        <Select
                            value={roleFilter}
                            onChange={(event) =>
                                setRoleFilter(event.target.value)
                            }
                            aria-label="Filter by role"
                            className="sm:w-52"
                        >
                            <option value="">All roles</option>
                            {roleOptions.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </Select>
                        <Select
                            value={statusFilter}
                            onChange={(event) =>
                                setStatusFilter(
                                    event.target.value as StatusFilter,
                                )
                            }
                            aria-label="Filter by status"
                            className="sm:w-40"
                        >
                            <option value="all">All statuses</option>
                            <option value="active">Active</option>
                            <option value="deactivated">Deactivated</option>
                        </Select>
                    </div>
                </div>

                <Table>
                    <TableHead>
                        <TableRow>
                            <TableHeaderCell>User</TableHeaderCell>
                            <TableHeaderCell>Role</TableHeaderCell>
                            <TableHeaderCell>Status</TableHeaderCell>
                            <TableHeaderCell>Added</TableHeaderCell>
                            <TableHeaderCell className="text-right">
                                Actions
                            </TableHeaderCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {visibleUsers.map((user) => {
                            const isSelf = user.id === auth.user.id;

                            return (
                                <TableRow key={user.id}>
                                    <TableCell>
                                        <div
                                            className={cn(
                                                'flex items-center gap-3',
                                                !user.is_active && 'opacity-60',
                                            )}
                                        >
                                            <UserAvatar
                                                user={user}
                                                className="size-9 text-sm"
                                            />
                                            <div className="min-w-0">
                                                <p className="flex items-center gap-1.5 truncate font-medium text-neutral-900 dark:text-neutral-100">
                                                    {user.name}
                                                    {isSelf && (
                                                        <Badge
                                                            tone="brand"
                                                            className="px-1.5 py-0 text-[10px]"
                                                        >
                                                            You
                                                        </Badge>
                                                    )}
                                                </p>
                                                <p className="truncate text-xs text-neutral-500 dark:text-neutral-400">
                                                    {user.email}
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge tone={ROLE_TONES[user.role]}>
                                            {roleLabel(user.role)}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <span
                                            className={cn(
                                                'inline-flex items-center gap-1.5 text-xs font-medium',
                                                user.is_active
                                                    ? 'text-success-700 dark:text-success-500'
                                                    : 'text-neutral-500 dark:text-neutral-400',
                                            )}
                                        >
                                            <span
                                                className={cn(
                                                    'size-2 rounded-full',
                                                    user.is_active
                                                        ? 'bg-success-500 shadow-[0_0_0_3px] shadow-success-500/20'
                                                        : 'bg-neutral-400',
                                                )}
                                            />
                                            {user.is_active
                                                ? 'Active'
                                                : 'Deactivated'}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-xs whitespace-nowrap text-neutral-500 dark:text-neutral-400">
                                        {formatDate(user.created_at)}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex items-center justify-end gap-1">
                                            <IconButton
                                                label={`Edit ${user.name}`}
                                                onClick={() =>
                                                    setDialog({
                                                        type: 'edit',
                                                        user,
                                                    })
                                                }
                                            >
                                                <PencilIcon className="size-4" />
                                            </IconButton>
                                            <IconButton
                                                label={`Reset password for ${user.name}`}
                                                onClick={() =>
                                                    setDialog({
                                                        type: 'password',
                                                        user,
                                                    })
                                                }
                                            >
                                                <KeyIcon className="size-4" />
                                            </IconButton>
                                            {!isSelf &&
                                                (user.is_active ? (
                                                    <ConfirmButton
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
                                                        action={updateStatus(
                                                            user.id,
                                                        )}
                                                        data={{
                                                            is_active: false,
                                                        }}
                                                        title={`Deactivate ${user.name}?`}
                                                        message="They will be signed out and can no longer log in. You can reactivate the account at any time."
                                                        confirmLabel="Deactivate"
                                                    >
                                                        Deactivate
                                                    </ConfirmButton>
                                                ) : (
                                                    <ConfirmButton
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-success-700 hover:bg-success-50 dark:text-success-500 dark:hover:bg-success-700/20"
                                                        action={updateStatus(
                                                            user.id,
                                                        )}
                                                        data={{
                                                            is_active: true,
                                                        }}
                                                        title={`Reactivate ${user.name}?`}
                                                        message="They will be able to log in again with their existing password."
                                                        confirmLabel="Reactivate"
                                                        destructive={false}
                                                    >
                                                        Reactivate
                                                    </ConfirmButton>
                                                ))}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            );
                        })}
                        {visibleUsers.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={5} className="py-12">
                                    <div className="flex flex-col items-center text-center">
                                        <span className="mb-3 flex size-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-950/60 dark:text-brand-300">
                                            <SearchIcon className="size-5" />
                                        </span>
                                        <p className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                            No users match your filters
                                        </p>
                                        <button
                                            type="button"
                                            onClick={clearFilters}
                                            className="mt-2 text-xs font-medium text-brand-700 hover:underline dark:text-brand-300"
                                        >
                                            Clear filters
                                        </button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                <p className="border-t border-neutral-100 px-4 py-3 text-xs text-neutral-500 dark:border-neutral-800 dark:text-neutral-400">
                    Showing {visibleUsers.length} of {users.length}{' '}
                    {users.length === 1 ? 'user' : 'users'}
                </p>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>What each role can do</CardTitle>
                </CardHeader>
                <CardContent className="grid grid-cols-1 gap-4 pt-0 sm:grid-cols-2 lg:grid-cols-3">
                    {roleOptions.map((role) => (
                        <button
                            key={role.value}
                            type="button"
                            onClick={() =>
                                setRoleFilter((current) =>
                                    current === role.value ? '' : role.value,
                                )
                            }
                            aria-pressed={roleFilter === role.value}
                            className={cn(
                                'rounded-xl border p-4 text-left transition',
                                roleFilter === role.value
                                    ? 'border-brand-400 bg-brand-50 ring-1 ring-brand-400 dark:border-brand-600 dark:bg-brand-950/40 dark:ring-brand-600'
                                    : 'border-neutral-200 hover:border-brand-300 hover:bg-brand-50/50 dark:border-neutral-800 dark:hover:border-brand-800 dark:hover:bg-brand-950/20',
                            )}
                        >
                            <div className="flex items-center justify-between gap-2">
                                <Badge tone={ROLE_TONES[role.value]}>
                                    {role.label}
                                </Badge>
                                <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                    {countByRole(role.value)}{' '}
                                    {countByRole(role.value) === 1
                                        ? 'user'
                                        : 'users'}
                                </span>
                            </div>
                            <p className="mt-2 text-xs leading-relaxed text-neutral-600 dark:text-neutral-400">
                                {role.description}
                            </p>
                        </button>
                    ))}
                </CardContent>
            </Card>

            <Modal
                open={dialog?.type === 'create'}
                onClose={close}
                title="Add user"
                description="Share the password with them securely; they can change it from Settings."
            >
                <Form action={store()} onSuccess={close}>
                    {({ errors, processing }) => (
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <Field
                                label="Name"
                                htmlFor="new-name"
                                error={errors.name}
                            >
                                <Input id="new-name" name="name" required />
                            </Field>
                            <Field
                                label="Email"
                                htmlFor="new-email"
                                error={errors.email}
                            >
                                <Input
                                    id="new-email"
                                    name="email"
                                    type="email"
                                    required
                                />
                            </Field>
                            <RoleField
                                id="new-role"
                                roleOptions={roleOptions}
                                defaultValue="records_manager"
                                error={errors.role}
                            />
                            <Field
                                label="Password"
                                htmlFor="new-password"
                                error={errors.password}
                            >
                                <PasswordInput
                                    id="new-password"
                                    name="password"
                                    autoComplete="new-password"
                                    required
                                />
                            </Field>
                            <Field
                                label="Confirm password"
                                htmlFor="new-password-confirmation"
                            >
                                <PasswordInput
                                    id="new-password-confirmation"
                                    name="password_confirmation"
                                    autoComplete="new-password"
                                    required
                                />
                            </Field>
                            <ModalActions
                                processing={processing}
                                onCancel={close}
                                label="Add user"
                            />
                        </div>
                    )}
                </Form>
            </Modal>

            <Modal
                open={dialog?.type === 'edit'}
                onClose={close}
                title={
                    dialog?.type === 'edit'
                        ? `Edit ${dialog.user.name}`
                        : 'Edit user'
                }
            >
                {dialog?.type === 'edit' && (
                    <Form action={update(dialog.user.id)} onSuccess={close}>
                        {({ errors, processing }) => (
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <Field
                                    label="Name"
                                    htmlFor="edit-name"
                                    error={errors.name}
                                >
                                    <Input
                                        id="edit-name"
                                        name="name"
                                        defaultValue={dialog.user.name}
                                        required
                                    />
                                </Field>
                                <Field
                                    label="Email"
                                    htmlFor="edit-email"
                                    error={errors.email}
                                >
                                    <Input
                                        id="edit-email"
                                        name="email"
                                        type="email"
                                        defaultValue={dialog.user.email}
                                        required
                                    />
                                </Field>
                                <RoleField
                                    id="edit-role"
                                    roleOptions={roleOptions}
                                    defaultValue={dialog.user.role}
                                    error={errors.role}
                                />
                                <ModalActions
                                    processing={processing}
                                    onCancel={close}
                                    label="Save changes"
                                />
                            </div>
                        )}
                    </Form>
                )}
            </Modal>

            <Modal
                open={dialog?.type === 'password'}
                onClose={close}
                title={
                    dialog?.type === 'password'
                        ? `Reset password for ${dialog.user.name}`
                        : 'Reset password'
                }
                description="Set a new password and share it with them securely."
                size="sm"
            >
                {dialog?.type === 'password' && (
                    <Form
                        action={resetPassword(dialog.user.id)}
                        onSuccess={close}
                    >
                        {({ errors, processing }) => (
                            <div className="grid grid-cols-1 gap-4">
                                <Field
                                    label="New password"
                                    htmlFor="reset-password"
                                    error={errors.password}
                                >
                                    <PasswordInput
                                        id="reset-password"
                                        name="password"
                                        autoComplete="new-password"
                                        required
                                    />
                                </Field>
                                <Field
                                    label="Confirm new password"
                                    htmlFor="reset-password-confirmation"
                                >
                                    <PasswordInput
                                        id="reset-password-confirmation"
                                        name="password_confirmation"
                                        autoComplete="new-password"
                                        required
                                    />
                                </Field>
                                <ModalActions
                                    processing={processing}
                                    onCancel={close}
                                    label="Reset password"
                                />
                            </div>
                        )}
                    </Form>
                )}
            </Modal>
        </>
    );
}

function RoleField({
    id,
    roleOptions,
    defaultValue,
    error,
}: {
    id: string;
    roleOptions: RoleOption[];
    defaultValue: string;
    error?: string;
}) {
    const [value, setValue] = useState(defaultValue);

    return (
        <Field
            label="Role"
            htmlFor={id}
            error={error}
            hint={
                roleOptions.find((option) => option.value === value)
                    ?.description
            }
            className="sm:col-span-2"
        >
            <Select
                id={id}
                name="role"
                value={value}
                onChange={(event) => setValue(event.target.value)}
            >
                {roleOptions.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </Select>
        </Field>
    );
}

function ModalActions({
    processing,
    onCancel,
    label,
}: {
    processing: boolean;
    onCancel: () => void;
    label: string;
}) {
    return (
        <div className="mt-2 flex justify-end gap-2 sm:col-span-2">
            <Button variant="secondary" size="sm" onClick={onCancel}>
                Cancel
            </Button>
            <Button type="submit" size="sm" disabled={processing}>
                {label}
            </Button>
        </div>
    );
}

UsersIndex.layout = (page: ReactElement) => (
    <AuthenticatedLayout>{page}</AuthenticatedLayout>
);

function StatTile({
    icon: TileIcon,
    label,
    value,
    tone = 'brand',
}: {
    icon: ComponentType<IconProps>;
    label: string;
    value: number;
    tone?: 'brand' | 'success' | 'warning';
}) {
    return (
        <Card className="relative overflow-hidden p-5">
            <TileIcon
                aria-hidden="true"
                className="pointer-events-none absolute -right-3 -bottom-4 size-20 text-brand-100 dark:text-brand-900/40"
                strokeWidth={1.25}
            />
            <div className="relative flex items-center gap-3">
                <span
                    className={cn(
                        'flex size-10 shrink-0 items-center justify-center rounded-xl',
                        tone === 'success' &&
                            'bg-success-50 text-success-600 dark:bg-success-700/20 dark:text-success-500',
                        tone === 'warning' &&
                            'bg-warning-50 text-warning-600 dark:bg-warning-700/20 dark:text-warning-500',
                        tone === 'brand' &&
                            'bg-brand-100 text-brand-700 dark:bg-brand-900/60 dark:text-brand-300',
                    )}
                >
                    <TileIcon className="size-5" />
                </span>
                <div>
                    <p className="text-2xl leading-none font-bold text-neutral-900 dark:text-neutral-50">
                        {value}
                    </p>
                    <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                        {label}
                    </p>
                </div>
            </div>
        </Card>
    );
}

function IconButton({
    label,
    onClick,
    children,
}: {
    label: string;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            title={label}
            aria-label={label}
            className="flex size-8 items-center justify-center rounded-lg text-neutral-500 transition hover:bg-brand-50 hover:text-brand-700 focus-visible:outline-2 focus-visible:outline-brand-600 dark:text-neutral-400 dark:hover:bg-brand-950/50 dark:hover:text-brand-300"
        >
            {children}
        </button>
    );
}
