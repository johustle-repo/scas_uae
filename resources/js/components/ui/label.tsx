import { cn } from '@/lib/utils';
import { type LabelHTMLAttributes, forwardRef } from 'react';

export const Label = forwardRef<HTMLLabelElement, LabelHTMLAttributes<HTMLLabelElement>>(
    ({ className, ...props }, ref) => (
        <label
            ref={ref}
            className={cn(
                'mb-1.5 block text-xs font-medium text-neutral-700 dark:text-neutral-300',
                className,
            )}
            {...props}
        />
    ),
);
Label.displayName = 'Label';
