import { ChevronLeftIcon, ChevronRightIcon } from '@/components/icons';
import { cn } from '@/lib/utils';
import type { PaginationLink } from '@/types/dog';
import { router } from '@inertiajs/react';

interface PaginationProps {
    links: PaginationLink[];
    total: number;
    currentPage: number;
    perPage: number;
    lastPage: number;
}

/**
 * "Showing 1–15 of 165" summary with numbered page links.
 */
export function Pagination({
    links,
    total,
    currentPage,
    perPage,
    lastPage,
}: PaginationProps) {
    const firstItem = total === 0 ? 0 : (currentPage - 1) * perPage + 1;
    const lastItem = Math.min(currentPage * perPage, total);

    return (
        <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs">
            <span className="text-neutral-500 dark:text-neutral-400">
                Showing {firstItem}–{lastItem} of {total}
            </span>
            {lastPage > 1 && (
                <nav className="flex items-center gap-1" aria-label="Pagination">
                    {links.map((link, index) => {
                        const isPrevious = index === 0;
                        const isNext = index === links.length - 1;
                        const label =
                            isPrevious || isNext
                                ? null
                                : link.label.replace(/&hellip;/, '…');

                        return (
                            <button
                                key={index}
                                type="button"
                                disabled={!link.url}
                                onClick={() =>
                                    link.url &&
                                    router.get(link.url, {}, { preserveState: true })
                                }
                                aria-label={
                                    isPrevious
                                        ? 'Previous page'
                                        : isNext
                                          ? 'Next page'
                                          : `Page ${label}`
                                }
                                aria-current={link.active ? 'page' : undefined}
                                className={cn(
                                    'flex h-7 min-w-7 items-center justify-center rounded-md px-1.5 transition-colors disabled:opacity-40',
                                    link.active
                                        ? 'bg-brand-800 font-semibold text-white dark:bg-brand-600'
                                        : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800',
                                )}
                            >
                                {isPrevious && <ChevronLeftIcon className="size-3.5" />}
                                {isNext && <ChevronRightIcon className="size-3.5" />}
                                {label}
                            </button>
                        );
                    })}
                </nav>
            )}
        </div>
    );
}
