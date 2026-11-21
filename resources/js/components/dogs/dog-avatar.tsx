import { cn } from '@/lib/utils';
import type { Dog } from '@/types/dog';
import { useState } from 'react';

/**
 * Shown in place of a dog's photo when it has none (or it fails to load).
 */
export const DEFAULT_DOG_PHOTO = '/images/scas-logo.svg';

interface DogAvatarProps {
    dog: Pick<Dog, 'name' | 'photo_url'>;
    className?: string;
    rounded?: 'full' | 'xl';
}

export function DogAvatar({
    dog,
    className,
    rounded = 'full',
}: DogAvatarProps) {
    const [failedUrl, setFailedUrl] = useState<string | null>(null);
    const shape = rounded === 'full' ? 'rounded-full' : 'rounded-xl';

    if (dog.photo_url && dog.photo_url !== failedUrl) {
        return (
            <img
                src={dog.photo_url}
                alt={dog.name}
                loading="lazy"
                onError={() => setFailedUrl(dog.photo_url)}
                className={cn('size-9 shrink-0 object-cover', shape, className)}
            />
        );
    }

    return (
        <img
            src={DEFAULT_DOG_PHOTO}
            alt={dog.name}
            className={cn(
                'size-9 shrink-0 bg-brand-50 object-contain p-1 dark:bg-brand-900',
                shape,
                className,
            )}
        />
    );
}
