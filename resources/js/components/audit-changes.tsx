import { cn } from '@/lib/utils';

interface AuditChangesProps {
    oldValues: Record<string, unknown> | null;
    newValues: Record<string, unknown> | null;
    className?: string;
}

/** Fields that are internal bookkeeping rather than meaningful to staff. */
const HIDDEN_FIELDS = new Set(['id', 'dog_id', 'file_path', 'uploaded_by']);

function formatValue(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }

    if (typeof value === 'string') {
        return /^\d{4}-\d{2}-\d{2}/.test(value) ? value.slice(0, 10) : value;
    }

    if (typeof value === 'number') {
        return value.toString();
    }

    return JSON.stringify(value);
}

function fieldLabel(field: string): string {
    const label = field.replace(/_/g, ' ');

    return label.charAt(0).toUpperCase() + label.slice(1);
}

/**
 * Previous → new value table for one audit entry (spec §19.1).
 */
export function AuditChanges({
    oldValues,
    newValues,
    className,
}: AuditChangesProps) {
    const fields = [
        ...new Set([
            ...Object.keys(oldValues ?? {}),
            ...Object.keys(newValues ?? {}),
        ]),
    ].filter((field) => !HIDDEN_FIELDS.has(field));

    if (fields.length === 0) {
        return null;
    }

    const showOld = oldValues !== null;
    const showNew = newValues !== null;

    return (
        <div
            className={cn(
                'overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700',
                className,
            )}
        >
            <table className="w-full text-left text-xs">
                <thead className="bg-neutral-50 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">
                    <tr>
                        <th className="px-3 py-1.5 font-medium">Field</th>
                        {showOld && (
                            <th className="px-3 py-1.5 font-medium">
                                Previous
                            </th>
                        )}
                        {showNew && (
                            <th className="px-3 py-1.5 font-medium">New</th>
                        )}
                    </tr>
                </thead>
                <tbody className="divide-y divide-neutral-100 dark:divide-neutral-800">
                    {fields.map((field) => (
                        <tr key={field}>
                            <td className="px-3 py-1.5 font-medium text-neutral-700 dark:text-neutral-200">
                                {fieldLabel(field)}
                            </td>
                            {showOld && (
                                <td className="px-3 py-1.5 text-red-700 dark:text-red-300">
                                    {formatValue(oldValues?.[field])}
                                </td>
                            )}
                            {showNew && (
                                <td className="px-3 py-1.5 text-success-700 dark:text-success-500">
                                    {formatValue(newValues?.[field])}
                                </td>
                            )}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
