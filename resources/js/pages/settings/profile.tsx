import { destroy as logout } from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import { update as updatePassword } from '@/actions/App/Http/Controllers/Settings/PasswordController';
import { update as updateProfile } from '@/actions/App/Http/Controllers/Settings/ProfileController';
import {
    destroy as destroyAvatar,
    store as storeAvatar,
} from '@/actions/App/Http/Controllers/Settings/AvatarController';
import { destroy as destroySessions } from '@/actions/App/Http/Controllers/Settings/SessionController';
import { AppearancePicker } from '@/components/appearance-picker';
import {
    AlertIcon,
    CalendarIcon,
    CheckIcon,
    ChevronRightIcon,
    HistoryIcon,
    KeyIcon,
    LogoutIcon,
    MonitorIcon,
    PawIcon,
    PencilIcon,
    SettingsIcon,
    TrashIcon,
    UploadIcon,
    UserIcon,
    UsersIcon,
    type IconProps,
} from '@/components/icons';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ConfirmButton } from '@/components/ui/confirm-button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { PasswordInput } from '@/components/ui/password-input';
import { UserAvatar } from '@/components/user-avatar';
import { AuthenticatedLayout } from '@/layouts/authenticated-layout';
import { useCan } from '@/lib/permissions';
import { cn } from '@/lib/utils';
import settingsRoutes from '@/routes/settings';
import type { Auth } from '@/types/auth';
import { formatDate, formatDateTime } from '@/types/dog';
import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import {
    type ChangeEvent,
    type ComponentType,
    type ReactElement,
    type ReactNode,
    useEffect,
    useRef,
    useState,
} from 'react';

interface BrowserSession {
    id: string;
    ip_address: string | null;
    browser: string;
    platform: string;
    is_mobile: boolean;
    is_current: boolean;
    last_active_at: string;
}

interface ActivityEntry {
    id: number;
    action: string;
    description: string;
    ip_address: string | null;
    created_at: string;
}

interface ProfileSettingsProps {
    sessions: BrowserSession[];
    recentActivity: ActivityEntry[];
    lastLoginAt: string | null;
}

export default function ProfileSettings({
    sessions,
    recentActivity,
    lastLoginAt,
}: ProfileSettingsProps) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const can = useCan();

    const systemLinks = [
        ...(can('manage-users')
            ? [
                  {
                      label: 'Manage Users',
                      description: 'Staff accounts, roles and access',
                      href: settingsRoutes.users.index.url(),
                      icon: UsersIcon,
                  },
              ]
            : []),
        ...(can('view-audit-log')
            ? [
                  {
                      label: 'System Logs',
                      description: 'Audit trail of every change',
                      href: settingsRoutes.logs.index.url(),
                      icon: HistoryIcon,
                  },
              ]
            : []),
    ];

    return (
        <>
            <Head title="Settings" />

            <PageHeader
                title="Settings"
                icon={SettingsIcon}
                description="Manage your account, security and preferences."
            />

            <AccountHero auth={auth} lastLoginAt={lastLoginAt} />

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-5">
                <div className="space-y-6 lg:col-span-3">
                    <ProfileInformationCard auth={auth} />
                    <PasswordCard />
                    <SessionsCard sessions={sessions} />
                </div>

                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <SectionHeader
                            icon={MonitorIcon}
                            title="Appearance"
                            description="Colour mode and accent, saved to this browser."
                        />
                        <CardContent className="pt-0">
                            <AppearancePicker />
                        </CardContent>
                    </Card>

                    <RecentActivityCard entries={recentActivity} />

                    {systemLinks.length > 0 && (
                        <Card>
                            <SectionHeader
                                icon={SettingsIcon}
                                title="Administration"
                                description="Tools available to your role."
                            />
                            <CardContent className="space-y-2 pt-0">
                                {systemLinks.map((link) => (
                                    <Link
                                        key={link.href}
                                        href={link.href}
                                        className="group flex items-center gap-3 rounded-xl border border-neutral-200 p-3 transition hover:border-brand-300 hover:bg-brand-50 dark:border-neutral-800 dark:hover:border-brand-700 dark:hover:bg-brand-950/40"
                                    >
                                        <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-700 dark:bg-brand-900/60 dark:text-brand-300">
                                            <link.icon className="size-4" />
                                        </span>
                                        <span className="min-w-0 flex-1">
                                            <span className="block text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                                {link.label}
                                            </span>
                                            <span className="block truncate text-xs text-neutral-500 dark:text-neutral-400">
                                                {link.description}
                                            </span>
                                        </span>
                                        <ChevronRightIcon className="size-4 text-neutral-400 transition-transform group-hover:translate-x-0.5" />
                                    </Link>
                                ))}
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </>
    );
}

ProfileSettings.layout = (page: ReactElement) => (
    <AuthenticatedLayout>{page}</AuthenticatedLayout>
);

const AVATAR_MAX_BYTES = 4 * 1024 * 1024;
const AVATAR_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

function AccountHero({
    auth,
    lastLoginAt,
}: {
    auth: Auth;
    lastLoginAt: string | null;
}) {
    const fileInput = useRef<HTMLInputElement>(null);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    useEffect(
        () => () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
        },
        [previewUrl],
    );

    function upload(event: ChangeEvent<HTMLInputElement>) {
        const file = event.target.files?.[0];
        event.target.value = '';

        if (!file) {
            return;
        }

        if (!AVATAR_TYPES.includes(file.type)) {
            setError('Choose a JPG, PNG or WebP image.');

            return;
        }

        if (file.size > AVATAR_MAX_BYTES) {
            setError('The photo must be 4 MB or smaller.');

            return;
        }

        setError(null);
        setPreviewUrl(URL.createObjectURL(file));

        router.post(
            storeAvatar.url(),
            { avatar: file },
            {
                forceFormData: true,
                preserveScroll: true,
                onStart: () => setUploading(true),
                onError: (errors) =>
                    setError(
                        errors.avatar ?? 'The photo could not be uploaded.',
                    ),
                onFinish: () => {
                    setUploading(false);
                    setPreviewUrl(null);
                },
            },
        );
    }

    function chooseFile() {
        fileInput.current?.click();
    }

    return (
        <Card className="mb-6 overflow-hidden">
            <div
                aria-hidden="true"
                className="relative h-24 overflow-hidden bg-linear-to-r from-brand-700 via-brand-800 to-brand-950"
            >
                <div className="absolute inset-0 [background-image:radial-gradient(circle,white_1px,transparent_1px)] [background-size:18px_18px] opacity-10" />
                <PawIcon
                    className="absolute top-3 right-10 size-20 rotate-12 text-white/10"
                    strokeWidth={1}
                />
                <PawIcon
                    className="absolute top-10 right-36 size-9 -rotate-12 text-white/10"
                    strokeWidth={1.25}
                />
            </div>

            <div className="flex flex-col gap-4 px-5 pb-5 sm:flex-row sm:items-end sm:gap-5">
                <div className="relative -mt-12 w-fit shrink-0">
                    <button
                        type="button"
                        onClick={chooseFile}
                        disabled={uploading}
                        className="group relative block rounded-2xl bg-white shadow-lg ring-4 ring-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 dark:bg-neutral-900 dark:ring-neutral-900"
                        aria-label={
                            auth.user.avatar_url
                                ? 'Change profile photo'
                                : 'Upload profile photo'
                        }
                    >
                        <UserAvatar
                            user={auth.user}
                            src={previewUrl}
                            className="size-24 rounded-2xl text-4xl"
                        />
                        <span
                            className={cn(
                                'absolute inset-0 flex flex-col items-center justify-center gap-1 rounded-2xl bg-brand-950/60 text-[11px] font-medium text-white transition-opacity',
                                uploading
                                    ? 'opacity-100'
                                    : 'opacity-0 group-hover:opacity-100 group-focus-visible:opacity-100',
                            )}
                        >
                            {uploading ? (
                                <span className="size-5 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                            ) : (
                                <UploadIcon className="size-5" />
                            )}
                            {uploading ? 'Uploading…' : 'Change photo'}
                        </span>
                    </button>
                    <input
                        ref={fileInput}
                        type="file"
                        accept={AVATAR_TYPES.join(',')}
                        className="hidden"
                        onChange={upload}
                    />
                </div>

                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <h2 className="truncate text-xl font-bold tracking-tight text-neutral-900 dark:text-neutral-50">
                            {auth.user.name}
                        </h2>
                        <Badge tone="brand">{auth.roleLabel}</Badge>
                    </div>
                    <p className="mt-0.5 truncate text-sm text-neutral-500 dark:text-neutral-400">
                        {auth.user.email}
                    </p>
                    <div className="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs text-neutral-600 dark:text-neutral-300">
                        <span className="inline-flex items-center gap-1.5">
                            <CalendarIcon className="size-3.5 text-brand-600 dark:text-brand-400" />
                            Member since {formatDate(auth.user.created_at)}
                        </span>
                        <span className="inline-flex items-center gap-1.5">
                            <HistoryIcon className="size-3.5 text-brand-600 dark:text-brand-400" />
                            {lastLoginAt
                                ? `Previous sign-in ${formatDateTime(lastLoginAt)}`
                                : 'First sign-in'}
                        </span>
                    </div>
                    {error && (
                        <p
                            role="alert"
                            className="mt-2 flex items-center gap-1.5 text-xs text-red-600 dark:text-red-400"
                        >
                            <AlertIcon className="size-3.5" />
                            {error}
                        </p>
                    )}
                </div>

                <div className="flex flex-wrap gap-2">
                    <Button
                        size="sm"
                        variant="secondary"
                        onClick={chooseFile}
                        disabled={uploading}
                    >
                        <UploadIcon className="size-3.5" />
                        {auth.user.avatar_url ? 'Change photo' : 'Upload photo'}
                    </Button>
                    {auth.user.avatar_url && (
                        <ConfirmButton
                            size="sm"
                            variant="ghost"
                            action={destroyAvatar()}
                            title="Remove profile photo?"
                            message="Your initial will be shown instead."
                            confirmLabel="Remove"
                        >
                            <TrashIcon className="size-3.5" />
                            Remove
                        </ConfirmButton>
                    )}
                    <Form action={logout()}>
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant="secondary"
                                size="sm"
                                disabled={processing}
                                className="text-red-600 hover:border-red-200 hover:bg-red-50 dark:text-red-400 dark:hover:border-red-900 dark:hover:bg-red-950/40"
                            >
                                <LogoutIcon className="size-3.5" />
                                Log out
                            </Button>
                        )}
                    </Form>
                </div>
            </div>
        </Card>
    );
}

function SectionHeader({
    icon: SectionIcon,
    title,
    description,
    action,
}: {
    icon: ComponentType<IconProps>;
    title: string;
    description: string;
    action?: ReactNode;
}) {
    return (
        <CardHeader className="items-start">
            <div className="flex min-w-0 items-start gap-3">
                <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700 dark:bg-brand-900/60 dark:text-brand-300">
                    <SectionIcon className="size-4" />
                </span>
                <div className="min-w-0">
                    <CardTitle className="before:hidden">{title}</CardTitle>
                    <p className="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                        {description}
                    </p>
                </div>
            </div>
            {action}
        </CardHeader>
    );
}

function ProfileInformationCard({ auth }: { auth: Auth }) {
    return (
        <Card>
            <SectionHeader
                icon={UserIcon}
                title="Profile Information"
                description="Your name and the email address you sign in with."
            />
            <CardContent className="pt-0">
                <Form
                    action={updateProfile()}
                    options={{ preserveScroll: true }}
                >
                    {({ errors, processing, isDirty }) => (
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <Field
                                label="Full name"
                                htmlFor="name"
                                error={errors.name}
                            >
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={auth.user.name}
                                    autoComplete="name"
                                    required
                                />
                            </Field>
                            <Field
                                label="Email address"
                                htmlFor="email"
                                error={errors.email}
                            >
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    defaultValue={auth.user.email}
                                    autoComplete="email"
                                    required
                                />
                            </Field>
                            <FormFooter>
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={processing || !isDirty}
                                >
                                    <PencilIcon className="size-3.5" />
                                    {processing ? 'Saving…' : 'Save Profile'}
                                </Button>
                            </FormFooter>
                        </div>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}

function PasswordCard() {
    const [newPassword, setNewPassword] = useState('');

    return (
        <Card>
            <SectionHeader
                icon={KeyIcon}
                title="Change Password"
                description="Confirm your current password, then choose a new one."
            />
            <CardContent className="pt-0">
                <Form
                    action={updatePassword()}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    resetOnError={['current_password']}
                    onSuccess={() => setNewPassword('')}
                >
                    {({ errors, processing }) => (
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <Field
                                label="Current password"
                                htmlFor="current_password"
                                error={errors.current_password}
                                className="md:col-span-2"
                            >
                                <PasswordInput
                                    id="current_password"
                                    name="current_password"
                                    autoComplete="current-password"
                                    required
                                />
                            </Field>
                            <Field
                                label="New password"
                                htmlFor="password"
                                error={errors.password}
                            >
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    autoComplete="new-password"
                                    required
                                    onChange={(event) =>
                                        setNewPassword(event.target.value)
                                    }
                                />
                            </Field>
                            <Field
                                label="Confirm new password"
                                htmlFor="password_confirmation"
                            >
                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    autoComplete="new-password"
                                    required
                                />
                            </Field>
                            <PasswordStrength
                                password={newPassword}
                                className="md:col-span-2"
                            />
                            <FormFooter>
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={processing}
                                >
                                    <KeyIcon className="size-3.5" />
                                    {processing
                                        ? 'Updating…'
                                        : 'Update Password'}
                                </Button>
                            </FormFooter>
                        </div>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}

function SessionsCard({ sessions }: { sessions: BrowserSession[] }) {
    const [confirming, setConfirming] = useState(false);
    const otherSessionCount = sessions.filter((s) => !s.is_current).length;

    return (
        <Card>
            <SectionHeader
                icon={MonitorIcon}
                title="Browser Sessions"
                description="Devices currently signed in to your account."
            />
            <CardContent className="space-y-4 pt-0">
                {sessions.length === 0 ? (
                    <p className="text-sm text-neutral-500 dark:text-neutral-400">
                        Session details are not available.
                    </p>
                ) : (
                    <ul className="divide-y divide-neutral-100 overflow-hidden rounded-xl border border-neutral-200 dark:divide-neutral-800 dark:border-neutral-800">
                        {sessions.map((session) => (
                            <li
                                key={session.id}
                                className="flex items-center gap-3 px-4 py-3"
                            >
                                <span
                                    className={cn(
                                        'flex size-9 shrink-0 items-center justify-center rounded-lg',
                                        session.is_current
                                            ? 'bg-success-50 text-success-600 dark:bg-success-700/20 dark:text-success-500'
                                            : 'bg-neutral-100 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400',
                                    )}
                                >
                                    <MonitorIcon className="size-4" />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                        {session.browser} on {session.platform}
                                        {session.is_mobile && ' (mobile)'}
                                    </p>
                                    <p className="truncate text-xs text-neutral-500 dark:text-neutral-400">
                                        {session.ip_address ?? 'Unknown IP'} ·{' '}
                                        {session.is_current
                                            ? 'Active now'
                                            : `Last active ${relativeTime(session.last_active_at)}`}
                                    </p>
                                </div>
                                {session.is_current && (
                                    <Badge tone="success">This device</Badge>
                                )}
                            </li>
                        ))}
                    </ul>
                )}

                {otherSessionCount > 0 && !confirming && (
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <p className="text-xs text-neutral-500 dark:text-neutral-400">
                            Don't recognise a device? Sign it out.
                        </p>
                        <Button
                            size="sm"
                            variant="secondary"
                            onClick={() => setConfirming(true)}
                        >
                            <LogoutIcon className="size-3.5" />
                            Log out other sessions
                        </Button>
                    </div>
                )}

                {confirming && (
                    <Form
                        action={destroySessions()}
                        options={{ preserveScroll: true }}
                        resetOnError
                        onSuccess={() => setConfirming(false)}
                        className="rounded-xl border border-warning-500/40 bg-warning-50 p-4 dark:bg-warning-700/10"
                    >
                        {({ errors, processing }) => (
                            <div className="space-y-3">
                                <p className="flex items-start gap-2 text-xs text-neutral-700 dark:text-neutral-300">
                                    <AlertIcon className="mt-px size-4 shrink-0 text-warning-600" />
                                    Enter your password to sign out of{' '}
                                    {otherSessionCount} other{' '}
                                    {otherSessionCount === 1
                                        ? 'session'
                                        : 'sessions'}
                                    . This device stays signed in.
                                </p>
                                <Field
                                    label="Password"
                                    htmlFor="session_password"
                                    error={errors.password}
                                >
                                    <PasswordInput
                                        id="session_password"
                                        name="password"
                                        autoComplete="current-password"
                                        required
                                        autoFocus
                                    />
                                </Field>
                                <div className="flex justify-end gap-2">
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        onClick={() => setConfirming(false)}
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="submit"
                                        size="sm"
                                        variant="danger"
                                        disabled={processing}
                                    >
                                        {processing
                                            ? 'Signing out…'
                                            : 'Log out other sessions'}
                                    </Button>
                                </div>
                            </div>
                        )}
                    </Form>
                )}
            </CardContent>
        </Card>
    );
}

function RecentActivityCard({ entries }: { entries: ActivityEntry[] }) {
    return (
        <Card>
            <SectionHeader
                icon={HistoryIcon}
                title="Recent Activity"
                description="Your latest actions in the database."
            />
            <CardContent className="pt-0">
                {entries.length === 0 ? (
                    <p className="text-sm text-neutral-500 dark:text-neutral-400">
                        No activity recorded yet.
                    </p>
                ) : (
                    <ol className="relative space-y-4 border-l-2 border-brand-100 pl-5 dark:border-brand-900/60">
                        {entries.map((entry) => (
                            <li key={entry.id} className="relative">
                                <span
                                    className={cn(
                                        'absolute top-1 -left-[26px] size-2.5 rounded-full ring-4 ring-white dark:ring-neutral-900',
                                        entry.action.startsWith('auth.')
                                            ? 'bg-neutral-300 dark:bg-neutral-600'
                                            : 'bg-brand-500',
                                    )}
                                />
                                <p className="text-sm text-neutral-800 dark:text-neutral-200">
                                    {entry.description}
                                </p>
                                <p className="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                                    {relativeTime(entry.created_at)}
                                    {entry.ip_address &&
                                        ` · ${entry.ip_address}`}
                                </p>
                            </li>
                        ))}
                    </ol>
                )}
            </CardContent>
        </Card>
    );
}

function FormFooter({ children }: { children: ReactNode }) {
    return (
        <div className="flex items-center justify-end gap-2 border-t border-neutral-100 pt-4 md:col-span-2 dark:border-neutral-800">
            {children}
        </div>
    );
}

const STRENGTH_CHECKS: { label: string; test: (value: string) => boolean }[] = [
    { label: '8+ characters', test: (value) => value.length >= 8 },
    {
        label: 'Upper & lower case',
        test: (value) => /[a-z]/.test(value) && /[A-Z]/.test(value),
    },
    { label: 'A number', test: (value) => /\d/.test(value) },
    { label: 'A symbol', test: (value) => /[^A-Za-z0-9]/.test(value) },
];

const STRENGTH_LEVELS = [
    { label: 'Too weak', bar: 'bg-red-500' },
    { label: 'Weak', bar: 'bg-red-500' },
    { label: 'Fair', bar: 'bg-warning-500' },
    { label: 'Good', bar: 'bg-success-500' },
    { label: 'Strong', bar: 'bg-success-600' },
];

/**
 * Guidance only: the server's password rules remain the source of truth.
 */
function PasswordStrength({
    password,
    className,
}: {
    password: string;
    className?: string;
}) {
    const passed = STRENGTH_CHECKS.filter((check) => check.test(password));
    const level = STRENGTH_LEVELS[passed.length];

    return (
        <div className={className}>
            <div className="flex items-center gap-3">
                <div className="grid flex-1 grid-cols-4 gap-1.5">
                    {STRENGTH_CHECKS.map((check, index) => (
                        <span
                            key={check.label}
                            className={cn(
                                'h-1.5 rounded-full transition-colors',
                                password && index < passed.length
                                    ? level.bar
                                    : 'bg-neutral-200 dark:bg-neutral-800',
                            )}
                        />
                    ))}
                </div>
                <span className="w-16 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400">
                    {password ? level.label : 'Strength'}
                </span>
            </div>
            <ul className="mt-2 flex flex-wrap gap-x-4 gap-y-1">
                {STRENGTH_CHECKS.map((check) => {
                    const met = check.test(password);

                    return (
                        <li
                            key={check.label}
                            className={cn(
                                'inline-flex items-center gap-1 text-xs',
                                met
                                    ? 'text-success-600 dark:text-success-500'
                                    : 'text-neutral-400',
                            )}
                        >
                            <CheckIcon className="size-3" />
                            {check.label}
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}

/** "3 minutes ago"-style label for a timestamp. */
function relativeTime(value: string): string {
    const seconds = Math.round((new Date(value).getTime() - Date.now()) / 1000);
    const units: [Intl.RelativeTimeFormatUnit, number][] = [
        ['year', 31536000],
        ['month', 2592000],
        ['week', 604800],
        ['day', 86400],
        ['hour', 3600],
        ['minute', 60],
    ];
    const format = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });

    for (const [unit, size] of units) {
        if (Math.abs(seconds) >= size) {
            return format.format(Math.round(seconds / size), unit);
        }
    }

    return 'just now';
}
