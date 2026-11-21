import {
    cancel,
    confirm,
    preview,
} from '@/actions/App/Http/Controllers/Dogs/RegisterImportController';
import { UploadIcon } from '@/components/icons';
import { Button, buttonClasses } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Modal } from '@/components/ui/modal';
import { cn } from '@/lib/utils';
import { Form, router, usePage } from '@inertiajs/react';
import { type ReactNode, useState } from 'react';

interface ImportedDog {
    scas_id: string;
    name: string;
}

interface RegisterImportResult {
    created: ImportedDog[];
    updated: (ImportedDog & { fields: string[] })[];
    unchanged: number;
    skipped: { row: number; message: string }[];
    warnings: { row: number; scas_id: string | null; message: string }[];
}

type Step = 'upload' | 'preview' | 'done';

/**
 * "Import Register" button: uploads the Dog Register (.xlsx or .csv),
 * previews which dogs would be added, updated or skipped, and imports only
 * once staff confirm.
 */
export function RegisterImport() {
    const { flash, errors } = usePage<{
        flash: {
            importResult: RegisterImportResult | null;
            importPreview: (RegisterImportResult & { fileName: string }) | null;
        };
        errors: Record<string, string>;
    }>().props;
    const [open, setOpen] = useState(false);
    const [step, setStep] = useState<Step>('upload');
    const [confirming, setConfirming] = useState(false);
    const previewResult = step === 'preview' ? flash.importPreview : null;
    const result = step === 'done' ? flash.importResult : null;

    function close() {
        if (step === 'preview') {
            router.delete(cancel.url(), { preserveScroll: true });
        }

        setOpen(false);
        setStep('upload');
    }

    function confirmImport() {
        router.post(
            confirm.url(),
            {},
            {
                preserveScroll: true,
                onStart: () => setConfirming(true),
                onFinish: () => setConfirming(false),
                onSuccess: () => setStep('done'),
                onError: () => setStep('upload'),
            },
        );
    }

    return (
        <>
            <button
                type="button"
                onClick={() => setOpen(true)}
                className={cn(buttonClasses('primary', 'sm'), 'w-full')}
            >
                <UploadIcon className="size-3.5" />
                Import Register
            </button>

            <Modal
                open={open}
                onClose={close}
                size="lg"
                title={
                    result
                        ? 'Import complete'
                        : previewResult
                          ? 'Check before importing'
                          : 'Import Dog Register'
                }
                description={
                    result
                        ? undefined
                        : previewResult
                          ? `Preview of ${previewResult.fileName}. Nothing has been saved yet.`
                          : 'Upload the Dog Register (.xlsx or .csv). Dogs are matched by Dog ID: existing dogs are updated with the filled-in cells, blank cells keep the current details, and new Dog IDs are added. You will see a preview before anything is saved.'
                }
            >
                {result ? (
                    <ImportSummary
                        result={result}
                        actions={
                            <Button size="sm" onClick={close}>
                                Done
                            </Button>
                        }
                    />
                ) : previewResult ? (
                    <ImportSummary
                        result={previewResult}
                        actions={
                            <>
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    onClick={close}
                                    disabled={confirming}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    size="sm"
                                    onClick={confirmImport}
                                    disabled={confirming}
                                >
                                    {confirming
                                        ? 'Importing…'
                                        : 'Confirm import'}
                                </Button>
                            </>
                        }
                    />
                ) : (
                    <Form
                        action={preview()}
                        options={{ preserveScroll: true }}
                        onSuccess={() => setStep('preview')}
                    >
                        {({ errors: formErrors, processing, progress }) => (
                            <div className="space-y-4">
                                <Field
                                    label="Dog Register file"
                                    htmlFor="register-file"
                                    error={formErrors.file ?? errors.file}
                                >
                                    <Input
                                        id="register-file"
                                        name="file"
                                        type="file"
                                        accept=".xlsx,.csv"
                                        required
                                        className="file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1 file:text-xs file:font-medium file:text-brand-800 dark:file:bg-brand-900/60 dark:file:text-brand-200"
                                    />
                                </Field>
                                {progress?.percentage !== undefined && (
                                    <progress
                                        value={progress.percentage}
                                        max={100}
                                        className="h-1.5 w-full accent-brand-600"
                                    >
                                        {progress.percentage}%
                                    </progress>
                                )}
                                <div className="flex justify-end gap-2">
                                    <Button
                                        variant="secondary"
                                        size="sm"
                                        onClick={close}
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="submit"
                                        size="sm"
                                        disabled={processing}
                                    >
                                        {processing
                                            ? 'Checking…'
                                            : 'Check file'}
                                    </Button>
                                </div>
                            </div>
                        )}
                    </Form>
                )}
            </Modal>
        </>
    );
}

function ImportSummary({
    result,
    actions,
}: {
    result: RegisterImportResult;
    actions: ReactNode;
}) {
    const counts = [
        { label: 'Added', value: result.created.length },
        { label: 'Updated', value: result.updated.length },
        { label: 'Unchanged', value: result.unchanged },
        { label: 'Skipped', value: result.skipped.length },
    ];

    return (
        <div className="space-y-4">
            <dl className="grid grid-cols-4 gap-2 text-center">
                {counts.map((count) => (
                    <div
                        key={count.label}
                        className="rounded-lg border border-neutral-200 py-2 dark:border-neutral-700"
                    >
                        <dd className="text-lg font-semibold">{count.value}</dd>
                        <dt className="text-xs text-neutral-500 dark:text-neutral-400">
                            {count.label}
                        </dt>
                    </div>
                ))}
            </dl>

            <div className="max-h-80 space-y-4 overflow-y-auto pr-1 text-xs">
                <SummarySection
                    title="Needs checking"
                    items={result.warnings.map(
                        (warning) =>
                            `Row ${warning.row}${warning.scas_id ? ` (${warning.scas_id})` : ''}: ${warning.message}`,
                    )}
                    tone="warning"
                />
                <SummarySection
                    title="Skipped rows"
                    items={result.skipped.map(
                        (skipped) => `Row ${skipped.row}: ${skipped.message}`,
                    )}
                    tone="warning"
                />
                <SummarySection
                    title="Added"
                    items={result.created.map(
                        (dog) => `${dog.scas_id} · ${dog.name}`,
                    )}
                />
                <SummarySection
                    title="Updated"
                    items={result.updated.map((dog) => (
                        <>
                            <span className="font-medium">
                                {dog.scas_id} · {dog.name}
                            </span>
                            <span className="text-neutral-500 dark:text-neutral-400">
                                {' '}
                                — {dog.fields.join(', ')}
                            </span>
                        </>
                    ))}
                />
            </div>

            <div className="flex justify-end gap-2">{actions}</div>
        </div>
    );
}

function SummarySection({
    title,
    items,
    tone = 'neutral',
}: {
    title: string;
    items: ReactNode[];
    tone?: 'neutral' | 'warning';
}) {
    if (items.length === 0) {
        return null;
    }

    return (
        <section>
            <h3
                className={cn(
                    'mb-1.5 font-semibold',
                    tone === 'warning'
                        ? 'text-warning-700 dark:text-warning-500'
                        : 'text-neutral-900 dark:text-neutral-100',
                )}
            >
                {title} ({items.length})
            </h3>
            <ul className="space-y-1 text-neutral-700 dark:text-neutral-300">
                {items.map((item, index) => (
                    <li key={index}>{item}</li>
                ))}
            </ul>
        </section>
    );
}
