import { ArrowLeftIcon, PawIcon, type IconProps } from '@/components/icons';
import { Link } from '@inertiajs/react';
import type { ComponentType, ReactNode } from 'react';

interface PageHeaderProps {
    title: ReactNode;
    description?: ReactNode;
    actions?: ReactNode;
    back?: { href?: string; onClick?: () => void; label: string };
    /** Shown in a tile beside the title. */
    icon?: ComponentType<IconProps>;
}

/**
 * The decorated banner at the top of each page: title, description, optional
 * back link and actions.
 */
export function PageHeader({
    title,
    description,
    actions,
    back,
    icon: HeaderIcon = PawIcon,
}: PageHeaderProps) {
    const backClasses =
        'relative mb-3 inline-flex items-center gap-1.5 text-xs font-medium text-neutral-500 hover:text-brand-700 dark:text-neutral-400 dark:hover:text-brand-300';

    return (
        <div className="relative mb-6 overflow-hidden rounded-2xl border border-brand-100 bg-linear-to-br from-brand-50 via-white to-brand-100/60 p-5 shadow-sm shadow-brand-900/5 dark:border-brand-900/40 dark:from-brand-950/70 dark:via-neutral-900 dark:to-brand-950/40 dark:shadow-none">
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 [background-image:radial-gradient(circle,var(--color-brand-200)_1px,transparent_1px)] [background-size:18px_18px] opacity-60 [mask-image:linear-gradient(to_left,black,transparent_70%)] dark:opacity-15"
            />
            <div
                aria-hidden="true"
                className="pointer-events-none absolute -top-16 -right-10 size-56 rounded-full bg-brand-300/30 blur-3xl dark:bg-brand-700/20"
            />
            <PawIcon
                aria-hidden="true"
                className="pointer-events-none absolute -right-6 -bottom-10 size-40 rotate-12 text-brand-200/60 dark:text-brand-800/30"
                strokeWidth={1}
            />

            {back &&
                (back.href ? (
                    <Link href={back.href} className={backClasses}>
                        <ArrowLeftIcon className="size-3.5" />
                        {back.label}
                    </Link>
                ) : (
                    <button
                        type="button"
                        onClick={back.onClick}
                        className={backClasses}
                    >
                        <ArrowLeftIcon className="size-3.5" />
                        {back.label}
                    </button>
                ))}
            <div className="relative flex flex-wrap items-center justify-between gap-4">
                <div className="flex min-w-0 items-center gap-4">
                    <span className="hidden size-12 shrink-0 items-center justify-center rounded-2xl bg-linear-to-br from-brand-600 to-brand-900 text-white shadow-lg shadow-brand-800/25 sm:flex dark:from-brand-500 dark:to-brand-800 dark:shadow-black/30">
                        <HeaderIcon className="size-6" />
                    </span>
                    <div className="min-w-0">
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-50">
                            {title}
                        </h1>
                        {description && (
                            <p className="mt-1 max-w-2xl text-sm text-neutral-600 dark:text-neutral-400">
                                {description}
                            </p>
                        )}
                    </div>
                </div>
                {actions && (
                    <div className="flex shrink-0 flex-wrap items-center gap-2">
                        {actions}
                    </div>
                )}
            </div>
        </div>
    );
}
