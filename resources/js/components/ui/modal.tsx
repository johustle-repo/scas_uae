import { CloseIcon } from '@/components/icons';
import { cn } from '@/lib/utils';
import { type ReactNode, useEffect, useRef } from 'react';

interface ModalProps {
    open: boolean;
    onClose: () => void;
    title: string;
    description?: ReactNode;
    children: ReactNode;
    size?: 'sm' | 'md' | 'lg';
}

/**
 * Accessible modal built on the native <dialog> element, which handles focus
 * trapping and closing with Escape.
 */
export function Modal({
    open,
    onClose,
    title,
    description,
    children,
    size = 'md',
}: ModalProps) {
    const dialogRef = useRef<HTMLDialogElement>(null);

    useEffect(() => {
        const dialog = dialogRef.current;

        if (!dialog) {
            return;
        }

        if (open && !dialog.open) {
            dialog.showModal();
            dialog
                .querySelector<HTMLElement>(
                    'input:not([type=hidden]), select, textarea',
                )
                ?.focus();
        } else if (!open && dialog.open) {
            dialog.close();
        }
    }, [open]);

    return (
        <dialog
            ref={dialogRef}
            onClose={onClose}
            onCancel={(event) => {
                event.preventDefault();
                onClose();
            }}
            onClick={(event) => {
                if (event.target === dialogRef.current) {
                    onClose();
                }
            }}
            className={cn(
                'm-auto w-[calc(100%-2rem)] rounded-2xl border border-neutral-200 bg-white p-0 text-neutral-900 shadow-xl backdrop:bg-brand-950/50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100',
                size === 'sm' && 'max-w-sm',
                size === 'md' && 'max-w-lg',
                size === 'lg' && 'max-w-2xl',
            )}
            aria-labelledby="modal-title"
        >
            {open && (
                <div className="p-6">
                    <div className="mb-5 flex items-start justify-between gap-4">
                        <div>
                            <h2
                                id="modal-title"
                                className="text-base font-semibold"
                            >
                                {title}
                            </h2>
                            {description && (
                                <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                    {description}
                                </p>
                            )}
                        </div>
                        <button
                            type="button"
                            onClick={onClose}
                            className="-mt-1 -mr-1 rounded-md p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700 dark:hover:bg-neutral-800"
                            aria-label="Close"
                        >
                            <CloseIcon className="size-4" />
                        </button>
                    </div>
                    {children}
                </div>
            )}
        </dialog>
    );
}
