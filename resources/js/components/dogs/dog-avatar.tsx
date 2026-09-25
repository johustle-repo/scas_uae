import { PawIcon } from '@/components/icons';
import { cn } from '@/lib/utils';
import type { Dog } from '@/types/dog';

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
    const shape = rounded === 'full' ? 'rounded-full' : 'rounded-xl';

    if (dog.photo_url) {
        return (
            <img
                src={dog.photo_url}
                alt={dog.name}
                loading="lazy"
                className={cn('size-9 shrink-0 object-cover', shape, className)}
            />
        );
    }

    return (
        <div
            className={cn(
                'flex size-9 shrink-0 items-center justify-center bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-300',
                shape,
                className,
            )}
            aria-label={dog.name}
        >
            <PawIcon className="size-1/2" />
        </div>
    );
}
