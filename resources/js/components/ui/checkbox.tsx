import { cn } from '@/lib/utils';
import { type InputHTMLAttributes, forwardRef } from 'react';

export const Checkbox = forwardRef<HTMLInputElement, Omit<InputHTMLAttributes<HTMLInputElement>, 'type'>>(
    ({ className, ...props }, ref) => (
        <input
            ref={ref}
            type="checkbox"
            className={cn(
                'size-3.5 shrink-0 rounded border-neutral-300 accent-brand-700 focus:ring-brand-500 dark:border-neutral-600 dark:accent-brand-400',
                className,
            )}
            {...props}
        />
    ),
);
Checkbox.displayName = 'Checkbox';
