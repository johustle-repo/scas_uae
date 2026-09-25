import {
    destroy as destroyDocument,
    replace as replaceDocument,
    store as storeDocument,
} from '@/actions/App/Http/Controllers/Dogs/DocumentController';
import {
    DownloadIcon,
    EyeIcon,
    FileIcon,
    TrashIcon,
    UploadIcon,
} from '@/components/icons';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ConfirmButton } from '@/components/ui/confirm-button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Modal } from '@/components/ui/modal';
import { Select } from '@/components/ui/select';
import { useCan } from '@/lib/permissions';
import {
    formatDate,
    formatFileSize,
    type Dog,
    type DogDocument,
    type Option,
} from '@/types/dog';
import { Form } from '@inertiajs/react';
import { useState } from 'react';

const ACCEPTED_TYPES =
    '.pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.csv,.txt';

interface DocumentsTabProps {
    dog: Dog;
    categoryOptions: Option[];
}

export function DocumentsTab({ dog, categoryOptions }: DocumentsTabProps) {
    const can = useCan();
    const canManage = can('manage-documents');
    const [uploading, setUploading] = useState(false);
    const [replacing, setReplacing] = useState<DogDocument | null>(null);
    const [category, setCategory] = useState('');
    const documents = dog.documents ?? [];
    const categoryLabel = (value: string) =>
        categoryOptions.find((option) => option.value === value)?.label ??
        value;
    const visible = category
        ? documents.filter((document) => document.category === category)
        : documents;

    return (
        <Card className="p-5">
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                    Documents
                </h3>
                <div className="flex items-center gap-2">
                    {documents.length > 0 && (
                        <Select
                            aria-label="Filter by category"
                            value={category}
                            onChange={(event) =>
                                setCategory(event.target.value)
                            }
                            className="py-1.5 text-xs"
                        >
                            <option value="">All categories</option>
                            {categoryOptions.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </Select>
                    )}
                    {canManage && (
                        <Button size="sm" onClick={() => setUploading(true)}>
                            <UploadIcon className="size-3.5" />
                            Upload document
                        </Button>
                    )}
                </div>
            </div>

            {visible.length === 0 ? (
                <div className="flex flex-col items-center justify-center rounded-lg bg-brand-50 px-4 py-10 text-center dark:bg-brand-950/40">
                    <FileIcon className="mb-2 size-6 text-brand-700 dark:text-brand-300" />
                    <p className="text-sm font-medium text-neutral-800 dark:text-neutral-100">
                        {documents.length === 0
                            ? 'No documents yet'
                            : 'No documents in this category'}
                    </p>
                    <p className="text-xs text-neutral-500 dark:text-neutral-400">
                        Vaccination certificates, vet records, adoption forms,
                        passports and more.
                    </p>
                </div>
            ) : (
                <ul className="divide-y divide-neutral-100 rounded-lg border border-neutral-200 dark:divide-neutral-800 dark:border-neutral-800">
                    {visible.map((document) => (
                        <li
                            key={document.id}
                            className="flex flex-wrap items-center gap-3 px-4 py-3"
                        >
                            <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300">
                                <FileIcon className="size-4" />
                            </span>
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                    {document.title}
                                </p>
                                <p className="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-neutral-500 dark:text-neutral-400">
                                    <Badge tone="brand">
                                        {categoryLabel(document.category)}
                                    </Badge>
                                    <span>{document.original_name}</span>
                                    <span>
                                        · {formatFileSize(document.size)}
                                    </span>
                                    <span>
                                        · {formatDate(document.updated_at)}
                                        {document.uploader &&
                                            ` by ${document.uploader.name}`}
                                    </span>
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-1">
                                <a
                                    href={document.view_url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800"
                                >
                                    <EyeIcon className="size-3.5" />
                                    View
                                </a>
                                <a
                                    href={document.download_url}
                                    className="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800"
                                >
                                    <DownloadIcon className="size-3.5" />
                                    Download
                                </a>
                                {canManage && (
                                    <>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() =>
                                                setReplacing(document)
                                            }
                                        >
                                            <UploadIcon className="size-3.5" />
                                            Replace
                                        </Button>
                                        <ConfirmButton
                                            variant="ghost"
                                            size="sm"
                                            className="text-red-600 dark:text-red-400"
                                            action={destroyDocument([
                                                dog.id,
                                                document.id,
                                            ])}
                                            title={`Remove “${document.title}”?`}
                                            message="The document and its file will be permanently deleted."
                                            confirmLabel="Remove"
                                        >
                                            <TrashIcon className="size-3.5" />
                                            Remove
                                        </ConfirmButton>
                                    </>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            <Modal
                open={uploading}
                onClose={() => setUploading(false)}
                title="Upload document"
                description="PDF, image, Word, Excel, CSV or text file, up to 10 MB."
            >
                <Form
                    action={storeDocument(dog.id)}
                    onSuccess={() => setUploading(false)}
                    options={{ preserveScroll: true }}
                >
                    {({ errors, processing, progress }) => (
                        <div className="space-y-4">
                            <Field
                                label="Category"
                                htmlFor="document-category"
                                error={errors.category}
                            >
                                <Select
                                    id="document-category"
                                    name="category"
                                    required
                                    defaultValue=""
                                >
                                    <option value="" disabled>
                                        Select a category
                                    </option>
                                    {categoryOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </Select>
                            </Field>
                            <Field
                                label="Title"
                                htmlFor="document-title"
                                error={errors.title}
                                hint="Leave blank to use the file name."
                            >
                                <Input
                                    id="document-title"
                                    name="title"
                                    maxLength={255}
                                />
                            </Field>
                            <Field
                                label="File"
                                htmlFor="document-file"
                                error={errors.file}
                            >
                                <FileInput id="document-file" />
                            </Field>
                            <UploadProgress percentage={progress?.percentage} />
                            <ModalActions
                                processing={processing}
                                onCancel={() => setUploading(false)}
                                label="Upload"
                            />
                        </div>
                    )}
                </Form>
            </Modal>

            <Modal
                open={replacing !== null}
                onClose={() => setReplacing(null)}
                title={
                    replacing
                        ? `Replace “${replacing.title}”`
                        : 'Replace document'
                }
                description="The new file keeps this document's title and category. The old file is deleted."
            >
                {replacing && (
                    <Form
                        action={replaceDocument([dog.id, replacing.id])}
                        onSuccess={() => setReplacing(null)}
                        options={{ preserveScroll: true }}
                    >
                        {({ errors, processing, progress }) => (
                            <div className="space-y-4">
                                <Field
                                    label="New file"
                                    htmlFor="replace-file"
                                    error={errors.file}
                                >
                                    <FileInput id="replace-file" />
                                </Field>
                                <UploadProgress
                                    percentage={progress?.percentage}
                                />
                                <ModalActions
                                    processing={processing}
                                    onCancel={() => setReplacing(null)}
                                    label="Replace file"
                                />
                            </div>
                        )}
                    </Form>
                )}
            </Modal>
        </Card>
    );
}

function FileInput({ id }: { id: string }) {
    return (
        <Input
            id={id}
            name="file"
            type="file"
            accept={ACCEPTED_TYPES}
            required
            className="file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1 file:text-xs file:font-medium file:text-brand-800 dark:file:bg-brand-900/60 dark:file:text-brand-200"
        />
    );
}

function UploadProgress({ percentage }: { percentage?: number }) {
    if (percentage === undefined) {
        return null;
    }

    return (
        <progress
            value={percentage}
            max={100}
            className="h-1.5 w-full accent-brand-600"
        >
            {percentage}%
        </progress>
    );
}

function ModalActions({
    processing,
    onCancel,
    label,
}: {
    processing: boolean;
    onCancel: () => void;
    label: string;
}) {
    return (
        <div className="flex justify-end gap-2">
            <Button variant="secondary" size="sm" onClick={onCancel}>
                Cancel
            </Button>
            <Button type="submit" size="sm" disabled={processing}>
                {processing ? 'Uploading…' : label}
            </Button>
        </div>
    );
}
