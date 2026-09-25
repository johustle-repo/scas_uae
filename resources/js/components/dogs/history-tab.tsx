import { AuditChanges } from '@/components/audit-changes';
import { HistoryIcon } from '@/components/icons';
import { Card } from '@/components/ui/card';
import { formatDateTime, type AuditLogEntry } from '@/types/dog';
import { useState } from 'react';

/**
 * Timeline of every recorded change to this dog and its related records (spec §11.1).
 * `history` is a deferred prop, so it is undefined until it loads.
 */
export function HistoryTab({
    history,
}: {
    history: AuditLogEntry[] | undefined;
}) {
    if (history === undefined) {
        return (
            <Card
                className="space-y-4 p-5"
                aria-busy="true"
                aria-label="Loading history"
            >
                {[0, 1, 2, 3].map((row) => (
                    <div key={row} className="flex animate-pulse gap-3">
                        <div className="size-8 shrink-0 rounded-full bg-neutral-100 dark:bg-neutral-800" />
                        <div className="flex-1 space-y-2">
                            <div className="h-3 w-2/3 rounded bg-neutral-100 dark:bg-neutral-800" />
                            <div className="h-2.5 w-1/3 rounded bg-neutral-100 dark:bg-neutral-800" />
                        </div>
                    </div>
                ))}
            </Card>
        );
    }

    if (history.length === 0) {
        return (
            <Card className="flex flex-col items-center justify-center px-4 py-10 text-center">
                <HistoryIcon className="mb-2 size-6 text-brand-700 dark:text-brand-300" />
                <p className="text-sm font-medium text-neutral-800 dark:text-neutral-100">
                    No recorded changes yet
                </p>
                <p className="text-xs text-neutral-500 dark:text-neutral-400">
                    Changes made from now on will appear here.
                </p>
            </Card>
        );
    }

    return (
        <Card className="p-5">
            <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                Record history
            </h3>
            <ol className="relative space-y-5 border-l border-neutral-200 pl-6 dark:border-neutral-700">
                {history.map((entry) => (
                    <HistoryEntry key={entry.id} entry={entry} />
                ))}
            </ol>
        </Card>
    );
}

function HistoryEntry({ entry }: { entry: AuditLogEntry }) {
    const [expanded, setExpanded] = useState(false);
    const hasChanges = Boolean(entry.old_values || entry.new_values);

    return (
        <li className="relative">
            <span className="absolute top-1 -left-[1.95rem] size-3 rounded-full border-2 border-white bg-brand-500 dark:border-neutral-900" />
            <p className="text-sm text-neutral-800 dark:text-neutral-100">
                <span className="font-medium">
                    {entry.user?.name ?? 'System'}
                </span>{' '}
                <span className="text-neutral-600 dark:text-neutral-300">
                    {entry.description.charAt(0).toLowerCase() +
                        entry.description.slice(1)}
                </span>
            </p>
            <p className="mt-0.5 text-xs text-neutral-400">
                {formatDateTime(entry.created_at)}
                {hasChanges && (
                    <>
                        {' · '}
                        <button
                            type="button"
                            onClick={() => setExpanded((value) => !value)}
                            className="font-medium text-brand-700 hover:underline dark:text-brand-300"
                            aria-expanded={expanded}
                        >
                            {expanded ? 'Hide details' : 'Show details'}
                        </button>
                    </>
                )}
            </p>
            {expanded && (
                <AuditChanges
                    oldValues={entry.old_values}
                    newValues={entry.new_values}
                    className="mt-2"
                />
            )}
        </li>
    );
}
