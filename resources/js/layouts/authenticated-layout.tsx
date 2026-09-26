import { FlashToast } from '@/components/flash-toast';
import { MenuIcon } from '@/components/icons';
import { AppLogo } from '@/components/app-logo';
import { Sidebar } from '@/components/sidebar';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import type { Auth } from '@/types/auth';
import { Link, router, usePage } from '@inertiajs/react';
import { type ReactNode, useEffect, useState } from 'react';

export function AuthenticatedLayout({ children }: { children: ReactNode }) {
    const { auth, sidebarOpen } = usePage<{
        auth: Auth;
        sidebarOpen: boolean;
    }>().props;
    const [open, setOpen] = useState(sidebarOpen);
    const [drawerOpen, setDrawerOpen] = useState(false);

    useEffect(() => router.on('navigate', () => setDrawerOpen(false)), []);

    useEffect(() => {
        if (!drawerOpen) {
            return;
        }

        function closeOnEscape(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                setDrawerOpen(false);
            }
        }

        window.addEventListener('keydown', closeOnEscape);

        return () => window.removeEventListener('keydown', closeOnEscape);
    }, [drawerOpen]);

    function toggleSidebar() {
        const next = !open;
        setOpen(next);

        try {
            document.cookie = `sidebar_open=${next ? 'true' : 'false'}; path=/; max-age=31536000`;
        } catch {
            // Ignore cookie write failures (e.g. blocked storage).
        }
    }

    return (
        <div className="flex h-dvh flex-col bg-canvas lg:flex-row dark:bg-neutral-950">
            <div className="hidden lg:flex">
                <Sidebar
                    open={open}
                    user={auth.user}
                    onToggle={toggleSidebar}
                />
            </div>

            <header className="flex shrink-0 items-center gap-3 bg-linear-to-r from-brand-800 to-brand-950 px-4 py-3 text-white lg:hidden">
                <button
                    type="button"
                    onClick={() => setDrawerOpen(true)}
                    className="-ml-1 rounded-md p-1.5 hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-white"
                    aria-label="Open menu"
                    aria-expanded={drawerOpen}
                >
                    <MenuIcon className="size-5" />
                </button>
                <Link
                    href={home.url()}
                    className="flex min-w-0 items-center gap-3 rounded-md focus-visible:outline-2 focus-visible:outline-white"
                >
                    <span className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-white p-1">
                        <AppLogo className="size-full" />
                    </span>
                    <span className="truncate text-sm font-semibold">
                        Second Chance Animal Sanctuary
                    </span>
                </Link>
            </header>

            <div
                className={cn(
                    'fixed inset-0 z-40 lg:hidden',
                    !drawerOpen && 'pointer-events-none',
                )}
                aria-hidden={!drawerOpen}
            >
                <div
                    className={cn(
                        'absolute inset-0 bg-brand-950/50 transition-opacity',
                        drawerOpen ? 'opacity-100' : 'opacity-0',
                    )}
                    onClick={() => setDrawerOpen(false)}
                />
                <div
                    className={cn(
                        'absolute inset-y-0 left-0 transition-transform duration-200',
                        drawerOpen ? 'translate-x-0' : '-translate-x-full',
                    )}
                    inert={!drawerOpen}
                >
                    <Sidebar
                        open
                        variant="drawer"
                        user={auth.user}
                        onToggle={() => setDrawerOpen(false)}
                    />
                </div>
            </div>

            <main className="relative isolate min-w-0 flex-1 [scrollbar-gutter:stable] overflow-y-auto bg-canvas dark:bg-neutral-950">
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-x-0 top-0 -z-10 h-80 bg-linear-to-b from-brand-100/70 to-transparent dark:from-brand-950/50"
                />
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-x-0 top-0 -z-10 h-96 [background-image:radial-gradient(circle,var(--color-brand-200)_1px,transparent_1px)] [mask-image:linear-gradient(to_bottom,black,transparent)] [background-size:22px_22px] opacity-50 dark:opacity-10"
                />
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute -top-24 right-0 -z-10 size-96 rounded-full bg-brand-300/25 blur-3xl dark:bg-brand-700/15"
                />
                <div className="mx-auto w-full max-w-screen-2xl p-4 sm:p-6 lg:p-8">
                    {children}
                </div>
            </main>

            <FlashToast />
        </div>
    );
}
