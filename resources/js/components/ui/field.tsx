import { Label } from '@/components/ui/label';
import type { ReactNode } from 'react';

interface FieldProps {
    label: string;
    htmlFor: string;
    error?: string;
    hint?: ReactNode;
    className?: string;
    children: ReactNode;
}

/**
 * A labelled form control with its hint and validation message.
 */
export function Field({
    label,
    htmlFor,
    error,
    hint,
    className,
    children,
}: FieldProps) {
    return (
        <div className={className}>
            <Label htmlFor={htmlFor}>{label}</Label>
            {children}
            {hint && !error && (
                <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                    {hint}
                </p>
            )}
            {error && (
                <p className="mt-1 text-xs text-red-600 dark:text-red-400">
                    {error}
                </p>
            )}
        </div>
    );
}
