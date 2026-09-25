import { cn } from '@/lib/utils';
import type { User } from '@/types/auth';
import { useState } from 'react';

interface UserAvatarProps {
    user: Pick<User, 'name' | 'avatar_url'>;
    className?: string;
    /** Overrides the saved photo, e.g. to preview a file before it is uploaded. */
    src?: string | null;
}

/**
 * A user's profile photo, falling back to their initial when there is no
 * photo or it fails to load.
 */
export function UserAvatar({ user, className, src }: UserAvatarProps) {
    const imageUrl = src ?? user.avatar_url;
    const [failedUrl, setFailedUrl] = useState<string | null>(null);

    if (imageUrl && failedUrl !== imageUrl) {
        return (
            <img
                src={imageUrl}
                alt={user.name}
                onError={() => setFailedUrl(imageUrl)}
                className={cn('shrink-0 rounded-full object-cover', className)}
            />
        );
    }

    return (
        <span
            aria-hidden="true"
            className={cn(
                'flex shrink-0 items-center justify-center rounded-full bg-linear-to-br from-brand-200 to-brand-400 font-semibold text-brand-950 dark:from-brand-300 dark:to-brand-600',
                className,
            )}
        >
            {user.name.charAt(0).toUpperCase()}
        </span>
    );
}
