import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'react';

export function Card({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
    return (
        <div
            className={cn(
                'rounded-2xl border border-neutral-200/80 bg-white shadow-sm shadow-brand-900/5 dark:border-neutral-800 dark:bg-neutral-900 dark:shadow-none',
                className,
            )}
            {...props}
        />
    );
}

export function CardHeader({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
    return <div className={cn('flex items-center justify-between gap-3 px-5 pt-5 pb-3', className)} {...props} />;
}

export function CardTitle({ className, ...props }: HTMLAttributes<HTMLHeadingElement>) {
    return (
        <h2
            className={cn(
                'flex items-center gap-2 text-sm font-semibold text-neutral-900 before:h-4 before:w-1 before:rounded-full before:bg-linear-to-b before:from-brand-400 before:to-brand-700 dark:text-neutral-100 dark:before:from-brand-300 dark:before:to-brand-500',
                className,
            )}
            {...props}
        />
    );
}

export function CardContent({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
    return <div className={cn('p-5', className)} {...props} />;
}
