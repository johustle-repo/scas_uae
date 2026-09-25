import { EyeIcon } from '@/components/icons';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { type InputHTMLAttributes, useState } from 'react';

/**
 * A password field with a show/hide toggle.
 */
export function PasswordInput({
    className,
    ...props
}: Omit<InputHTMLAttributes<HTMLInputElement>, 'type'>) {
    const [visible, setVisible] = useState(false);

    return (
        <div className="relative">
            <Input
                {...props}
                type={visible ? 'text' : 'password'}
                className={cn('pr-10', className)}
            />
            <button
                type="button"
                onClick={() => setVisible((value) => !value)}
                aria-label={visible ? 'Hide password' : 'Show password'}
                aria-pressed={visible}
                className={cn(
                    'absolute top-1/2 right-1.5 flex size-7 -translate-y-1/2 items-center justify-center rounded-md transition hover:bg-neutral-100 dark:hover:bg-neutral-800',
                    visible
                        ? 'text-brand-600 dark:text-brand-400'
                        : 'text-neutral-400',
                )}
            >
                <EyeIcon className="size-4" />
            </button>
        </div>
    );
}
