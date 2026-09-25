import { AuditChanges } from '@/components/audit-changes';
import { HistoryIcon, ResetIcon } from '@/components/icons';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Pagination } from '@/components/ui/pagination';
import { Select } from '@/components/ui/select';
import { AuthenticatedLayout } from '@/layouts/authenticated-layout';
import dogsRoutes from '@/routes/dogs';
import settingsRoutes from '@/routes/settings';
import {
    formatDateTime,
    type AuditLogEntry,
    type Option,
    type Paginated,
} from '@/types/dog';
import { Head, Link, router } from '@inertiajs/react';
import { type FormEvent, type ReactElement, useState } from 'react';

interface LogFilters {
    area?: string;
    user?: string;
    q?: string;
    from?: string;
    to?: string;
}

interface LogsIndexProps {
    logs: Paginated<AuditLogEntry>;
    filters: LogFilters;
    areaOptions: Option[];
    userOptions: Option[];
}

export default function LogsIndex({
    logs,
    filters,
    areaOptions,
    userOptions,
}: LogsIndexProps) {
    const [draft, setDraft] = useState<LogFilters>(filters);

    function apply(event: FormEvent) {
        event.preventDefault();
        router.get(
            settingsRoutes.logs.index.url(),
            Object.fromEntries(
                Object.entries(draft).filter(([, value]) => value),
            ),
            { preserveState: true, replace: true },
        );
    }

    function reset() {
        setDraft({});
        router.get(settingsRoutes.logs.index.url(), {}, { replace: true });
    }

    return (
        <>
            <Head title="System Logs" />

            <PageHeader
                back={{
                    href: settingsRoutes.profile.edit.url(),
                    label: 'Back to Settings',
                }}
                title="System Logs"
                icon={HistoryIcon}
                description="Audit trail of sign-ins and every change to dog records, documents and user accounts."
            />

            <Card className="mb-6 p-5">
                <form
                    onSubmit={apply}
                    className="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-6"
                >
                    <Field
                        label="Search"
                        htmlFor="log-q"
                        className="lg:col-span-2"
                    >
                        <Input
                            id="log-q"
                            type="search"
                            placeholder="Dog name, SCAS ID, user…"
                            value={draft.q ?? ''}
                            onChange={(e) =>
                                setDraft({ ...draft, q: e.target.value })
                            }
                        />
                    </Field>
                    <Field label="Area" htmlFor="log-area">
                        <Select
                            id="log-area"
                            value={draft.area ?? ''}
                            onChange={(e) =>
                                setDraft({ ...draft, area: e.target.value })
                            }
                        >
                            <option value="">All areas</option>
                            {areaOptions.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </Select>
                    </Field>
                    <Field label="User" htmlFor="log-user">
                        <Select
                            id="log-user"
                            value={draft.user ?? ''}
                            onChange={(e) =>
                                setDraft({ ...draft, user: e.target.value })
                            }
                        >
                            <option value="">Everyone</option>
                            {userOptions.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </Select>
                    </Field>
                    <Field label="From" htmlFor="log-from">
                        <Input
                            id="log-from"
                            type="date"
                            value={draft.from ?? ''}
                            onChange={(e) =>
                                setDraft({ ...draft, from: e.target.value })
                            }
                        />
                    </Field>
                    <Field label="To" htmlFor="log-to">
                        <Input
                            id="log-to"
                            type="date"
                            value={draft.to ?? ''}
                            onChange={(e) =>
                                setDraft({ ...draft, to: e.target.value })
                            }
                        />
                    </Field>
                    <div className="flex justify-end gap-2 sm:col-span-2 lg:col-span-6">
                        <Button variant="ghost" size="sm" onClick={reset}>
                            <ResetIcon className="size-3.5" />
                            Reset
                        </Button>
                        <Button type="submit" size="sm">
                            Apply filters
                        </Button>
                    </div>
                </form>
            </Card>

            <Card className="divide-y divide-neutral-100 dark:divide-neutral-800">
                {logs.data.map((log) => (
                    <LogRow key={log.id} log={log} />
                ))}
                {logs.data.length === 0 && (
                    <p className="px-5 py-10 text-center text-sm text-neutral-400">
                        No activity matches these filters.
                    </p>
                )}
            </Card>

            <Pagination
                links={logs.links}
                total={logs.total}
                currentPage={logs.current_page}
                perPage={logs.per_page}
                lastPage={logs.last_page}
            />
        </>
    );
}

function LogRow({ log }: { log: AuditLogEntry }) {
    const [expanded, setExpanded] = useState(false);
    const hasChanges = Boolean(log.old_values || log.new_values);

    return (
        <div className="px-5 py-3">
            <div className="flex flex-wrap items-start gap-x-4 gap-y-1">
                <span className="w-36 shrink-0 text-xs whitespace-nowrap text-neutral-500 tabular-nums dark:text-neutral-400">
                    {formatDateTime(log.created_at)}
                </span>
                <div className="min-w-0 flex-1 text-sm">
                    <p className="text-neutral-800 dark:text-neutral-100">
                        <span className="font-medium">
                            {log.user?.name ?? 'System'}
                        </span>{' '}
                        <span className="text-neutral-600 dark:text-neutral-300">
                            {log.description.charAt(0).toLowerCase() +
                                log.description.slice(1)}
                        </span>
                    </p>
                    <p className="mt-0.5 flex flex-wrap gap-x-3 text-xs text-neutral-400">
                        <code>{log.action}</code>
                        {log.dog && (
                            <Link
                                href={dogsRoutes.show.url(log.dog.id)}
                                className="text-brand-700 hover:underline dark:text-brand-300"
                            >
                                {log.dog.scas_id}
                            </Link>
                        )}
                        {log.ip_address && <span>IP {log.ip_address}</span>}
                    </p>
                </div>
                {hasChanges && (
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => setExpanded((value) => !value)}
                        aria-expanded={expanded}
                    >
                        {expanded ? 'Hide changes' : 'Show changes'}
                    </Button>
                )}
            </div>
            {expanded && (
                <AuditChanges
                    oldValues={log.old_values}
                    newValues={log.new_values}
                    className="mt-3 sm:ml-40"
                />
            )}
        </div>
    );
}

LogsIndex.layout = (page: ReactElement) => (
    <AuthenticatedLayout>{page}</AuthenticatedLayout>
);
