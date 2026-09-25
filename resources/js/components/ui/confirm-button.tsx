import { Button, type ButtonProps } from '@/components/ui/button';
import { Modal } from '@/components/ui/modal';
import { router } from '@inertiajs/react';
import { type ReactNode, useState } from 'react';

interface ConfirmButtonProps extends Omit<ButtonProps, 'onClick'> {
    /** Route definition to submit once the user confirms. */
    action: { url: string; method: 'get' | 'post' | 'put' | 'patch' | 'delete' };
    data?: Record<string, string | number | boolean | null>;
    title: string;
    message: ReactNode;
    confirmLabel?: string;
    destructive?: boolean;
    onSuccess?: () => void;
    children: ReactNode;
}

/**
 * A button that asks for confirmation before performing an action (spec §23.1).
 */
export function ConfirmButton({
    action,
    data = {},
    title,
    message,
    confirmLabel = 'Confirm',
    destructive = true,
    onSuccess,
    children,
    ...buttonProps
}: ConfirmButtonProps) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    function confirm() {
        router.visit(action.url, {
            method: action.method,
            data,
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onSuccess: () => onSuccess?.(),
            onFinish: () => {
                setProcessing(false);
                setOpen(false);
            },
        });
    }

    return (
        <>
            <Button {...buttonProps} onClick={() => setOpen(true)}>
                {children}
            </Button>
            <Modal
                open={open}
                onClose={() => setOpen(false)}
                title={title}
                size="sm"
            >
                <div className="text-sm text-neutral-600 dark:text-neutral-300">
                    {message}
                </div>
                <div className="mt-6 flex justify-end gap-2">
                    <Button
                        variant="secondary"
                        size="sm"
                        onClick={() => setOpen(false)}
                    >
                        Cancel
                    </Button>
                    <Button
                        variant={destructive ? 'danger' : 'primary'}
                        size="sm"
                        disabled={processing}
                        onClick={confirm}
                    >
                        {confirmLabel}
                    </Button>
                </div>
            </Modal>
        </>
    );
}
