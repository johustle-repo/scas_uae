import {
    destroy,
    download,
    store,
} from '@/actions/App/Http/Controllers/Settings/BackupController';
import { DownloadIcon, FolderIcon, TrashIcon } from '@/components/icons';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button, buttonClasses } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ConfirmButton } from '@/components/ui/confirm-button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeaderCell,
    TableRow,
} from '@/components/ui/table';
import { AuthenticatedLayout } from '@/layouts/authenticated-layout';
import { formatDateTime, type AuditLogEntry } from '@/types/dog';
import { Form, Head } from '@inertiajs/react';
import type { ReactElement } from 'react';

interface StoredBackup {
    file: string;
    size: number;
    created_at: string;
}

interface BackupsIndexProps {
    backups: StoredBackup[];
    healthy: boolean;
    totalSize: number;
    history: Pick<
        AuditLogEntry,
        'id' | 'user' | 'action' | 'description' | 'created_at'
    >[];
}

function formatBytes(bytes: number): string {
    if (bytes < 1024 * 1024) {
        return `${Math.max(1, Math.round(bytes / 1024))} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function BackupsIndex({
    backups,
    healthy,
    totalSize,
    history,
}: BackupsIndexProps) {
    const newest = backups[0];

    return (
        <>
            <Head title="Backups" />

            <PageHeader
                title="Backups"
                icon={FolderIcon}
                description="Backups of the database and every uploaded photo and document. A backup runs automatically every night; you can also run one now."
                actions={
                    <Form action={store()} options={{ preserveScroll: true }}>
                        {({ processing, errors }) => (
                            <div className="flex flex-col items-end gap-1">
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={processing}
                                >
                                    {processing
                                        ? 'Backing up…'
                                        : 'Run Backup Now'}
                                </Button>
                                {errors.backup && (
                                    <p className="max-w-xs text-right text-xs text-red-600">
                                        {errors.backup}
                                    </p>
                                )}
                            </div>
                        )}
                    </Form>
                }
            />

            <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Card className="p-5">
                    <p className="text-xs text-neutral-500 dark:text-neutral-400">
                        Status
                    </p>
                    <div className="mt-2">
                        <Badge tone={healthy ? 'success' : 'warning'}>
                            {healthy
                                ? 'Healthy'
                                : newest
                                  ? 'Last backup is over a day old'
                                  : 'No backups yet'}
                        </Badge>
                    </div>
                </Card>
                <Card className="p-5">
                    <p className="text-xs text-neutral-500 dark:text-neutral-400">
                        Last backup
                    </p>
                    <p className="mt-2 text-sm font-semibold">
                        {newest ? formatDateTime(newest.created_at) : '—'}
                    </p>
                </Card>
                <Card className="p-5">
                    <p className="text-xs text-neutral-500 dark:text-neutral-400">
                        Stored
                    </p>
                    <p className="mt-2 text-sm font-semibold">
                        {backups.length} backups · {formatBytes(totalSize)}
                    </p>
                </Card>
            </div>

            <Card className="mb-6 overflow-hidden">
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableHeaderCell>Backup</TableHeaderCell>
                            <TableHeaderCell>Created</TableHeaderCell>
                            <TableHeaderCell>Size</TableHeaderCell>
                            <TableHeaderCell className="text-right">
                                Actions
                            </TableHeaderCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {backups.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={4}
                                    className="py-8 text-center text-neutral-500"
                                >
                                    No backups have been made yet.
                                </TableCell>
                            </TableRow>
                        )}
                        {backups.map((backup) => (
                            <TableRow key={backup.file}>
                                <TableCell className="font-mono text-xs">
                                    {backup.file}
                                </TableCell>
                                <TableCell>
                                    {formatDateTime(backup.created_at)}
                                </TableCell>
                                <TableCell>
                                    {formatBytes(backup.size)}
                                </TableCell>
                                <TableCell>
                                    <div className="flex justify-end gap-2">
                                        <a
                                            href={download.url(backup.file)}
                                            className={buttonClasses(
                                                'secondary',
                                                'sm',
                                            )}
                                        >
                                            <DownloadIcon className="size-3.5" />
                                            Download
                                        </a>
                                        <ConfirmButton
                                            action={destroy(backup.file)}
                                            title="Delete backup?"
                                            message={`${backup.file} will be permanently deleted.`}
                                            confirmLabel="Delete"
                                            variant="danger"
                                            size="sm"
                                        >
                                            <TrashIcon className="size-3.5" />
                                            Delete
                                        </ConfirmButton>
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </Card>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Backup history</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-0">
                        {history.length === 0 ? (
                            <p className="text-xs text-neutral-500">
                                No backup activity yet.
                            </p>
                        ) : (
                            <ul className="divide-y divide-neutral-100 text-xs dark:divide-neutral-800">
                                {history.map((entry) => (
                                    <li
                                        key={entry.id}
                                        className="flex justify-between gap-3 py-2"
                                    >
                                        <span
                                            className={
                                                entry.action === 'backup.failed'
                                                    ? 'text-red-600'
                                                    : undefined
                                            }
                                        >
                                            {entry.description}
                                            {entry.user
                                                ? ` · ${entry.user.name}`
                                                : ' · scheduled'}
                                        </span>
                                        <span className="shrink-0 text-neutral-500">
                                            {formatDateTime(entry.created_at)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Restoring a backup</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 pt-0 text-xs text-neutral-600 dark:text-neutral-300">
                        <p>
                            Restoring replaces all current data, so it is done
                            by the system administrator rather than from this
                            page:
                        </p>
                        <ol className="list-decimal space-y-1 pl-4">
                            <li>Download the backup and unzip it.</li>
                            <li>
                                Import <code>db-dumps/mysql-*.sql</code> into
                                the database (phpMyAdmin → Import, or{' '}
                                <code>mysql scas-db &lt; dump.sql</code>).
                            </li>
                            <li>
                                Copy the <code>private</code> folder back to{' '}
                                <code>storage/app/private</code> on the server.
                            </li>
                            <li>
                                Run <code>php artisan optimize:clear</code>.
                            </li>
                        </ol>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

BackupsIndex.layout = (page: ReactElement) => (
    <AuthenticatedLayout>{page}</AuthenticatedLayout>
);
