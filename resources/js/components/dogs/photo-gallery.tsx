import {
    destroy as destroyPhoto,
    makeProfile,
    store as storePhoto,
} from '@/actions/App/Http/Controllers/Dogs/PhotoController';
import { PawIcon, StarIcon, TrashIcon, UploadIcon } from '@/components/icons';
import { Button } from '@/components/ui/button';
import { ConfirmButton } from '@/components/ui/confirm-button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Modal } from '@/components/ui/modal';
import { useCan } from '@/lib/permissions';
import type { Dog, DogPhoto, PhotoKind } from '@/types/dog';
import { Form, router } from '@inertiajs/react';
import { useState } from 'react';

interface PhotoGalleryProps {
    dog: Dog;
    photos: DogPhoto[];
    /** Kind given to newly uploaded photos. */
    uploadKind: PhotoKind;
    emptyText: string;
}

export function PhotoGallery({
    dog,
    photos,
    uploadKind,
    emptyText,
}: PhotoGalleryProps) {
    const can = useCan();
    const canManage = can('manage-documents');
    const [uploading, setUploading] = useState(false);
    const [viewing, setViewing] = useState<DogPhoto | null>(null);

    return (
        <div>
            {photos.length === 0 ? (
                <div className="flex h-32 flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-neutral-200 bg-neutral-50/60 text-xs text-neutral-400 dark:border-neutral-700 dark:bg-neutral-800/40">
                    <PawIcon className="size-6" />
                    {emptyText}
                </div>
            ) : (
                <ul className="grid grid-cols-3 gap-2">
                    {photos.map((photo) => {
                        return (
                            <li key={photo.id} className="relative">
                                <button
                                    type="button"
                                    onClick={() => setViewing(photo)}
                                    className="block w-full overflow-hidden rounded-lg focus-visible:outline-2 focus-visible:outline-brand-600"
                                    aria-label={photo.caption ?? 'View photo'}
                                >
                                    <img
                                        src={photo.url}
                                        alt={
                                            photo.caption ??
                                            `Photo of ${dog.name}`
                                        }
                                        loading="lazy"
                                        className="aspect-square w-full object-cover transition-transform hover:scale-105"
                                    />
                                </button>
                                {photo.is_profile && (
                                    <span className="absolute top-1 left-1 rounded bg-brand-800/90 px-1.5 py-0.5 text-[10px] font-medium text-white">
                                        Profile
                                    </span>
                                )}
                            </li>
                        );
                    })}
                </ul>
            )}

            {canManage && (
                <Button
                    variant="secondary"
                    size="sm"
                    className="mt-3"
                    onClick={() => setUploading(true)}
                >
                    <UploadIcon className="size-3.5" />
                    Upload photos
                </Button>
            )}

            <Modal
                open={uploading}
                onClose={() => setUploading(false)}
                title="Upload photos"
                description="JPG, PNG or WebP, up to 8 MB each. You can select up to 10 at once."
            >
                <Form
                    action={storePhoto(dog.id)}
                    onSuccess={() => setUploading(false)}
                    options={{ preserveScroll: true }}
                >
                    {({ errors, processing, progress }) => (
                        <div className="space-y-4">
                            <input
                                type="hidden"
                                name="kind"
                                value={uploadKind}
                            />
                            <Field
                                label="Photos"
                                htmlFor="photos"
                                error={
                                    errors.photos ??
                                    Object.entries(errors).find(([key]) =>
                                        key.startsWith('photos.'),
                                    )?.[1]
                                }
                            >
                                <Input
                                    id="photos"
                                    name="photos[]"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    multiple
                                    required
                                    className="file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1 file:text-xs file:font-medium file:text-brand-800 dark:file:bg-brand-900/60 dark:file:text-brand-200"
                                />
                            </Field>
                            <Field
                                label="Caption (optional)"
                                htmlFor="caption"
                                error={errors.caption}
                            >
                                <Input
                                    id="caption"
                                    name="caption"
                                    maxLength={255}
                                />
                            </Field>
                            {progress && (
                                <progress
                                    value={progress.percentage}
                                    max={100}
                                    className="h-1.5 w-full overflow-hidden rounded-full accent-brand-600"
                                >
                                    {progress.percentage}%
                                </progress>
                            )}
                            <div className="flex justify-end gap-2">
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    onClick={() => setUploading(false)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={processing}
                                >
                                    {processing ? 'Uploading…' : 'Upload'}
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </Modal>

            <Modal
                open={viewing !== null}
                onClose={() => setViewing(null)}
                title={viewing?.caption ?? dog.name}
                size="lg"
            >
                {viewing && (
                    <div>
                        <img
                            src={viewing.url}
                            alt={viewing.caption ?? `Photo of ${dog.name}`}
                            className="max-h-[65vh] w-full rounded-lg object-contain"
                        />
                        {canManage && (
                            <div className="mt-4 flex flex-wrap justify-end gap-2">
                                {!viewing.is_profile && (
                                    <Button
                                        variant="secondary"
                                        size="sm"
                                        onClick={() =>
                                            router.visit(
                                                makeProfile([
                                                    dog.id,
                                                    viewing.id,
                                                ]).url,
                                                {
                                                    method: 'patch',
                                                    preserveScroll: true,
                                                    onSuccess: () =>
                                                        setViewing(null),
                                                },
                                            )
                                        }
                                    >
                                        <StarIcon className="size-3.5" />
                                        Use as profile photo
                                    </Button>
                                )}
                                <ConfirmButton
                                    variant="secondary"
                                    size="sm"
                                    className="text-red-600 dark:text-red-400"
                                    action={destroyPhoto([dog.id, viewing.id])}
                                    onSuccess={() => setViewing(null)}
                                    title="Delete this photo?"
                                    message="The photo will be permanently removed."
                                    confirmLabel="Delete photo"
                                >
                                    <TrashIcon className="size-3.5" />
                                    Delete
                                </ConfirmButton>
                            </div>
                        )}
                    </div>
                )}
            </Modal>
        </div>
    );
}
