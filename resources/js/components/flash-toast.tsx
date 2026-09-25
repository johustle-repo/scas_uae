import { CheckIcon, CloseIcon } from '@/components/icons';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/**
 * Shows the server's one-off success message (e.g. "Photo uploaded.") and
 * hides it after a few seconds.
 */
export function FlashToast() {
    const { flash } = usePage<{ flash: { success: string | null } }>().props;
    const [message, setMessage] = useState<string | null>(null);

    useEffect(() => {
        if (!flash.success) {
            return;
        }

        setMessage(flash.success);
        const timer = window.setTimeout(() => setMessage(null), 4000);

        return () => window.clearTimeout(timer);
    }, [flash]);

    if (!message) {
        return null;
    }

    return (
        <div
            role="status"
            aria-live="polite"
            className="fixed right-4 bottom-4 z-50 flex max-w-sm items-center gap-3 rounded-xl border border-success-500/30 bg-white px-4 py-3 text-sm text-neutral-800 shadow-lg dark:bg-neutral-900 dark:text-neutral-100"
        >
            <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-success-50 text-success-600 dark:bg-success-700/20">
                <CheckIcon className="size-3.5" strokeWidth={2.5} />
            </span>
            <span className="flex-1">{message}</span>
            <button
                type="button"
                onClick={() => setMessage(null)}
                className="rounded-md p-1 text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-200"
                aria-label="Dismiss"
            >
                <CloseIcon className="size-3.5" />
            </button>
        </div>
    );
}
