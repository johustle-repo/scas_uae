import { cn } from '@/lib/utils';

/**
 * The SCAS paw-and-heart logo.
 */
export function AppLogo({ className }: { className?: string }) {
    return (
        <img
            src="/images/scas-logo.svg"
            alt="Second Chance Animal Sanctuary"
            className={cn('shrink-0 object-contain', className)}
        />
    );
}
