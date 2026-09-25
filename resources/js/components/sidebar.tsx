import { destroy } from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import {
    ArchiveIcon,
    BuildingIcon,
    ChevronDownIcon,
    CloseIcon,
    DashboardIcon,
    FlowerIcon,
    GlobeIcon,
    HeartIcon,
    HistoryIcon,
    HomeIcon,
    LogoutIcon,
    MenuIcon,
    MoonIcon,
    PawIcon,
    PlaneIcon,
    PlusIcon,
    ReturnIcon,
    SunIcon,
    UsersIcon,
    type IconProps,
} from '@/components/icons';
import { AppLogo } from '@/components/app-logo';
import { UserAvatar } from '@/components/user-avatar';
import { useAppearance } from '@/lib/appearance';
import { useCan } from '@/lib/permissions';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import dogsRoutes from '@/routes/dogs';
import settingsRoutes from '@/routes/settings';
import type { Auth, User } from '@/types/auth';
import type { DogStatus } from '@/types/dog';
import { Form, Link, usePage } from '@inertiajs/react';
import { type ComponentType, type ReactNode, useState } from 'react';

interface NavItem {
    label: string;
    href: string;
    icon: ComponentType<IconProps>;
    active: boolean;
}

const statusNavItems: {
    status: DogStatus;
    label: string;
    icon: ComponentType<IconProps>;
}[] = [
    { status: 'at_scas', label: 'At SCAS', icon: BuildingIcon },
    { status: 'local_foster', label: 'Local Foster', icon: HomeIcon },
    {
        status: 'international_foster',
        label: 'International Foster',
        icon: GlobeIcon,
    },
    { status: 'adopted_uae', label: 'Adopted in the UAE', icon: HeartIcon },
    {
        status: 'adopted_internationally',
        label: 'Adopted Internationally',
        icon: PlaneIcon,
    },
    {
        status: 'returned_rehoming',
        label: 'Returned / Rehoming',
        icon: ReturnIcon,
    },
    { status: 'memorial', label: 'Memorial', icon: FlowerIcon },
];

interface SidebarProps {
    open: boolean;
    user: User;
    onToggle: () => void;
    /** "rail" docks beside the page on desktop; "drawer" slides over it on small screens. */
    variant?: 'rail' | 'drawer';
}

export function Sidebar({
    open,
    user,
    onToggle,
    variant = 'rail',
}: SidebarProps) {
    const { url, props } = usePage<{ auth: Auth }>();
    const can = useCan();
    const currentUrl = new URL(url, window.location.origin);
    const path = currentUrl.pathname;
    const currentStatus = currentUrl.searchParams.get('status');
    const isShowingArchived =
        path === '/dogs' && currentUrl.searchParams.get('archived') === 'only';

    const mainItems: NavItem[] = [
        {
            label: 'Dashboard',
            href: dashboard.url(),
            icon: DashboardIcon,
            active: path === '/dashboard',
        },
        {
            label: 'All Dogs',
            href: dogsRoutes.index.url(),
            icon: PawIcon,
            active:
                path.startsWith('/dogs') &&
                path !== '/dogs/create' &&
                !currentStatus &&
                !isShowingArchived,
        },
        ...(can('manage-dogs')
            ? [
                  {
                      label: 'Add New Dog',
                      href: dogsRoutes.create.url(),
                      icon: PlusIcon,
                      active: path === '/dogs/create',
                  },
              ]
            : []),
        {
            label: 'Archived',
            href: dogsRoutes.index.url({ query: { archived: 'only' } }),
            icon: ArchiveIcon,
            active: isShowingArchived,
        },
    ];

    const statusItems: NavItem[] = statusNavItems.map((item) => ({
        label: item.label,
        href: dogsRoutes.index.url({ query: { status: item.status } }),
        icon: item.icon,
        active: path === '/dogs' && currentStatus === item.status,
    }));

    const adminItems: NavItem[] = [
        ...(can('manage-users')
            ? [
                  {
                      label: 'Users',
                      href: settingsRoutes.users.index.url(),
                      icon: UsersIcon,
                      active: path === '/settings/users',
                  },
              ]
            : []),
        ...(can('view-audit-log')
            ? [
                  {
                      label: 'System Logs',
                      href: settingsRoutes.logs.index.url(),
                      icon: HistoryIcon,
                      active: path === '/settings/logs',
                  },
              ]
            : []),
    ];

    return (
        <aside
            className={cn(
                'relative flex h-full shrink-0 flex-col overflow-hidden bg-linear-to-b from-brand-900 to-brand-950 text-brand-50 dark:from-brand-950 dark:to-neutral-950 dark:border-r dark:border-white/5',
                variant === 'rail' && 'transition-[width] duration-200',
                open ? (variant === 'rail' ? 'w-64' : 'w-72') : 'w-16',
            )}
        >
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 [background-image:radial-gradient(circle,white_1px,transparent_1px)] [background-size:20px_20px] opacity-[0.05]"
            />
            <div
                aria-hidden="true"
                className="pointer-events-none absolute -top-20 -left-20 size-60 rounded-full bg-brand-500/25 blur-3xl"
            />
            <PawIcon
                aria-hidden="true"
                className="pointer-events-none absolute -right-10 -bottom-6 size-48 rotate-[-20deg] text-white/[0.04]"
                strokeWidth={1}
            />

            <div
                className={cn(
                    'relative flex items-center gap-3 border-b border-white/10 px-3 py-4',
                    !open && 'flex-col gap-2',
                )}
            >
                <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white p-1.5 shadow-lg ring-4 shadow-black/20 ring-white/10">
                    <AppLogo className="size-full" />
                </div>
                {open && (
                    <p className="min-w-0 flex-1 text-sm leading-tight font-semibold">
                        Second Chance
                        <br />
                        <span className="text-xs font-medium text-brand-300">
                            Animal Sanctuary
                        </span>
                    </p>
                )}
                <button
                    type="button"
                    onClick={onToggle}
                    className="rounded-lg p-1.5 text-brand-200 hover:bg-white/10 hover:text-white focus-visible:outline-2 focus-visible:outline-white"
                    aria-label={
                        variant === 'drawer'
                            ? 'Close menu'
                            : open
                              ? 'Collapse sidebar'
                              : 'Expand sidebar'
                    }
                >
                    {variant === 'drawer' ? (
                        <CloseIcon className="size-4" />
                    ) : (
                        <MenuIcon className="size-4" />
                    )}
                </button>
            </div>

            <nav className="relative flex-1 space-y-5 overflow-y-auto px-2.5 py-4">
                <NavSection label="Menu" open={open}>
                    <NavList items={mainItems} open={open} />
                </NavSection>

                <StatusGroup items={statusItems} open={open} />

                {adminItems.length > 0 && (
                    <NavSection label="Administration" open={open}>
                        <NavList items={adminItems} open={open} />
                    </NavSection>
                )}
            </nav>

            <div className="relative border-t border-white/10 p-2.5">
                <div
                    className={cn(
                        'flex items-center gap-2 rounded-xl bg-white/[0.07] p-2 ring-1 ring-white/10',
                        !open && 'flex-col',
                    )}
                >
                    <Link
                        href={settingsRoutes.profile.edit.url()}
                        title="My profile & settings"
                        className={cn(
                            'flex min-w-0 flex-1 items-center gap-2.5 rounded-lg p-1 hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-white',
                            path === '/settings/profile' && 'bg-white/10',
                        )}
                    >
                        <UserAvatar
                            user={user}
                            className="size-8 text-xs ring-2 ring-white/20"
                        />
                        {open && (
                            <span className="min-w-0 leading-tight">
                                <span className="block truncate text-xs font-semibold text-white">
                                    {user.name}
                                </span>
                                <span className="block truncate text-[11px] text-brand-300">
                                    {props.auth.roleLabel}
                                </span>
                            </span>
                        )}
                    </Link>
                    <div className={cn('flex gap-1', !open && 'flex-col')}>
                        <DarkModeToggle />
                        <Form action={destroy()} className="flex">
                            {({ processing }) => (
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className={cn(
                                        footerButtonClasses,
                                        'hover:bg-red-500/80',
                                    )}
                                    title="Log out"
                                    aria-label="Log out"
                                >
                                    <LogoutIcon className="size-4" />
                                </button>
                            )}
                        </Form>
                    </div>
                </div>
            </div>
        </aside>
    );
}

const footerButtonClasses =
    'flex items-center justify-center rounded-lg p-2 text-brand-100 transition-colors hover:text-white focus-visible:outline-2 focus-visible:outline-white disabled:opacity-50';

function DarkModeToggle() {
    const { isDark, setAppearance } = useAppearance();

    return (
        <button
            type="button"
            onClick={() => setAppearance(isDark ? 'light' : 'dark')}
            className={cn(footerButtonClasses, 'hover:bg-white/15')}
            title={isDark ? 'Switch to light mode' : 'Switch to dark mode'}
            aria-label={isDark ? 'Switch to light mode' : 'Switch to dark mode'}
        >
            {isDark ? (
                <SunIcon className="size-4" />
            ) : (
                <MoonIcon className="size-4" />
            )}
        </button>
    );
}

function NavSection({
    label,
    open,
    children,
}: {
    label: string;
    open: boolean;
    children: ReactNode;
}) {
    return (
        <div>
            {open ? (
                <p className="mb-1.5 px-2.5 text-[10px] font-semibold tracking-[0.12em] text-brand-300/80 uppercase">
                    {label}
                </p>
            ) : (
                <div className="mx-auto mb-2 h-px w-6 bg-white/15" />
            )}
            {children}
        </div>
    );
}

function NavList({ items, open }: { items: NavItem[]; open: boolean }) {
    return (
        <ul className="space-y-0.5">
            {items.map((item) => (
                <li key={item.href}>
                    <Link
                        href={item.href}
                        title={item.label}
                        className={cn(
                            'group relative flex items-center gap-3 rounded-lg px-2.5 py-2 text-[13px] text-brand-100/85 transition-colors hover:bg-white/10 hover:text-white focus-visible:outline-2 focus-visible:outline-white',
                            item.active &&
                                'bg-white/[0.14] font-medium text-white shadow-sm ring-1 ring-white/10',
                            !open && 'justify-center px-0',
                        )}
                    >
                        {item.active && (
                            <span className="absolute top-1/2 -left-2.5 h-5 w-1 -translate-y-1/2 rounded-r-full bg-brand-300" />
                        )}
                        <item.icon
                            className={cn(
                                'size-[18px] shrink-0',
                                item.active
                                    ? 'text-brand-200'
                                    : 'text-brand-300/80 group-hover:text-brand-200',
                            )}
                        />
                        {open && <span className="truncate">{item.label}</span>}
                    </Link>
                </li>
            ))}
        </ul>
    );
}

function StatusGroup({ items, open }: { items: NavItem[]; open: boolean }) {
    const [expanded, setExpanded] = useState(true);

    if (!open) {
        return (
            <NavSection label="By Status" open={open}>
                <NavList items={items} open={open} />
            </NavSection>
        );
    }

    return (
        <div>
            <button
                type="button"
                onClick={() => setExpanded((value) => !value)}
                className="mb-1.5 flex w-full items-center justify-between rounded-md px-2.5 text-[10px] font-semibold tracking-[0.12em] text-brand-300/80 uppercase hover:text-brand-200"
                aria-expanded={expanded}
            >
                By Status
                <ChevronDownIcon
                    className={cn(
                        'size-3.5 transition-transform',
                        !expanded && '-rotate-90',
                    )}
                />
            </button>
            {expanded && <NavList items={items} open={open} />}
        </div>
    );
}
