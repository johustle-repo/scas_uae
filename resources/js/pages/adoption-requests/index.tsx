import { update } from '@/actions/App/Http/Controllers/AdoptionRequestController';
import { CheckIcon, HeartIcon, HomeIcon, SearchIcon } from '@/components/icons';
import { PageHeader } from '@/components/page-header';
import { Badge, type BadgeTone } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Modal } from '@/components/ui/modal';
import { Pagination } from '@/components/ui/pagination';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { AuthenticatedLayout } from '@/layouts/authenticated-layout';
import { cn } from '@/lib/utils';
import adoptionRequestsRoutes from '@/routes/adoption-requests';
import dogsRoutes from '@/routes/dogs';
import {
    formatDate,
    formatDateTime,
    type Option,
    type Paginated,
} from '@/types/dog';
import { Form, Head, Link, router } from '@inertiajs/react';
import { type FormEvent, type ReactElement, useState } from 'react';

type RequestStatus = 'new' | 'contacted' | 'approved' | 'declined';

interface AdoptionRequest {
    id: number;
    full_name: string;
    email: string;
    phone: string;
    city: string;
    home_type: string;
    has_garden: boolean;
    has_children: boolean;
    other_pets: string | null;
    experience: string | null;
    message: string;
    status: RequestStatus;
    staff_notes: string | null;
    reviewer: string | null;
    reviewed_at: string | null;
    created_at: string;
    dog: {
        id: number;
        name: string;
        scas_id: string;
        photo_url: string | null;
        current_status: string;
    } | null;
}

interface AdoptionRequestsProps {
    requests: Paginated<AdoptionRequest>;
    filters: { status?: RequestStatus; q?: string };
    statusCounts: Partial<Record<RequestStatus, number>>;
    statusOptions: Option[];
    homeTypeLabels: Record<string, string>;
}

const STATUS_TONES: Record<RequestStatus, BadgeTone> = {
    new: 'brand',
    contacted: 'info',
    approved: 'success',
    declined: 'neutral',
};

export default function AdoptionRequests({
    requests,
    filters,
    statusCounts,
    statusOptions,
    homeTypeLabels,
}: AdoptionRequestsProps) {
    const [search, setSearch] = useState(filters.q ?? '');
    const [selected, setSelected] = useState<AdoptionRequest | null>(null);
    const totalCount = Object.values(statusCounts).reduce(
        (sum, count) => sum + (count ?? 0),
        0,
    );
    const statusLabel = (status: string) =>
        statusOptions.find((option) => option.value === status)?.label ??
        status;

    function applyFilters(next: { status?: string; q?: string }) {
        router.get(
            adoptionRequestsRoutes.index.url(),
            Object.fromEntries(
                Object.entries({ ...filters, ...next }).filter(
                    ([, value]) => value,
                ),
            ),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function submitSearch(event: FormEvent) {
        event.preventDefault();
        applyFilters({ q: search });
    }

    const tabs: { value: string; label: string; count: number }[] = [
        { value: '', label: 'All', count: totalCount },
        ...statusOptions.map((option) => ({
            value: option.value,
            label: option.label,
            count: statusCounts[option.value as RequestStatus] ?? 0,
        })),
    ];

    return (
        <>
            <Head title="Adoption Requests" />

            <PageHeader
                title="Adoption Requests"
                icon={HeartIcon}
                description="Applications sent by visitors from the public adoption page."
            />

            <Card className="mb-6 overflow-hidden">
                <div className="flex flex-col gap-3 border-b border-neutral-100 p-4 lg:flex-row lg:items-center lg:justify-between dark:border-neutral-800">
                    <div
                        role="tablist"
                        aria-label="Filter by status"
                        className="flex flex-wrap gap-1.5"
                    >
                        {tabs.map((tab) => {
                            const active = (filters.status ?? '') === tab.value;

                            return (
                                <button
                                    key={tab.value || 'all'}
                                    type="button"
                                    role="tab"
                                    aria-selected={active}
                                    onClick={() =>
                                        applyFilters({ status: tab.value })
                                    }
                                    className={cn(
                                        'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition',
                                        active
                                            ? 'bg-brand-800 text-white shadow-sm dark:bg-brand-600'
                                            : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800',
                                    )}
                                >
                                    {tab.label}
                                    <span
                                        className={cn(
                                            'rounded-full px-1.5 text-[10px] font-semibold',
                                            active
                                                ? 'bg-white/20'
                                                : 'bg-neutral-200/70 dark:bg-neutral-700',
                                        )}
                                    >
                                        {tab.count}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                    <form
                        onSubmit={submitSearch}
                        role="search"
                        className="relative lg:w-72"
                    >
                        <SearchIcon className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-400" />
                        <Input
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search name, email, phone or dog…"
                            aria-label="Search adoption requests"
                            className="pl-9"
                        />
                    </form>
                </div>

                {requests.data.length === 0 ? (
                    <div className="flex flex-col items-center px-6 py-16 text-center">
                        <span className="flex size-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-950/60 dark:text-brand-300">
                            <HeartIcon className="size-5" />
                        </span>
                        <p className="mt-3 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                            {totalCount === 0
                                ? 'No adoption requests yet'
                                : 'No requests match these filters'}
                        </p>
                        <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            Requests appear here when visitors apply from the
                            landing page.
                        </p>
                    </div>
                ) : (
                    <ul className="divide-y divide-neutral-100 dark:divide-neutral-800">
                        {requests.data.map((request) => (
                            <li key={request.id}>
                                <button
                                    type="button"
                                    onClick={() => setSelected(request)}
                                    className="flex w-full items-center gap-4 px-4 py-4 text-left transition hover:bg-brand-50/50 focus-visible:bg-brand-50 focus-visible:outline-none dark:hover:bg-neutral-800/50 dark:focus-visible:bg-neutral-800"
                                >
                                    <DogThumb dog={request.dog} />
                                    <div className="min-w-0 flex-1">
                                        <p className="flex flex-wrap items-center gap-2">
                                            <span className="font-medium text-neutral-900 dark:text-neutral-100">
                                                {request.full_name}
                                            </span>
                                            <span className="text-xs text-neutral-400">
                                                wants to adopt
                                            </span>
                                            <span className="font-medium text-brand-700 dark:text-brand-300">
                                                {request.dog?.name ??
                                                    'a dog (record removed)'}
                                            </span>
                                        </p>
                                        <p className="mt-0.5 truncate text-xs text-neutral-500 dark:text-neutral-400">
                                            {request.city} ·{' '}
                                            {homeTypeLabels[request.home_type]}{' '}
                                            · {request.message}
                                        </p>
                                    </div>
                                    <div className="hidden shrink-0 text-right sm:block">
                                        <Badge
                                            tone={STATUS_TONES[request.status]}
                                        >
                                            {statusLabel(request.status)}
                                        </Badge>
                                        <p className="mt-1 text-xs text-neutral-400">
                                            {formatDate(request.created_at)}
                                        </p>
                                    </div>
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>

            {requests.last_page > 1 && (
                <Pagination
                    links={requests.links}
                    total={requests.total}
                    currentPage={requests.current_page}
                    perPage={requests.per_page}
                    lastPage={requests.last_page}
                />
            )}

            <Modal
                open={selected !== null}
                onClose={() => setSelected(null)}
                title={
                    selected
                        ? `${selected.full_name}'s request`
                        : 'Adoption request'
                }
                description={
                    selected
                        ? `Received ${formatDateTime(selected.created_at)}`
                        : undefined
                }
                size="lg"
            >
                {selected && (
                    <RequestDetails
                        key={selected.id}
                        request={selected}
                        statusOptions={statusOptions}
                        homeTypeLabels={homeTypeLabels}
                        onDone={() => setSelected(null)}
                    />
                )}
            </Modal>
        </>
    );
}

AdoptionRequests.layout = (page: ReactElement) => (
    <AuthenticatedLayout>{page}</AuthenticatedLayout>
);

function RequestDetails({
    request,
    statusOptions,
    homeTypeLabels,
    onDone,
}: {
    request: AdoptionRequest;
    statusOptions: Option[];
    homeTypeLabels: Record<string, string>;
    onDone: () => void;
}) {
    const answers: [string, string][] = [
        ['Email', request.email],
        ['Phone', request.phone],
        ['City', request.city],
        ['Home', homeTypeLabels[request.home_type] ?? request.home_type],
        ['Garden / outdoor space', request.has_garden ? 'Yes' : 'No'],
        ['Children at home', request.has_children ? 'Yes' : 'No'],
        ['Other pets', request.other_pets ?? '—'],
        ['Experience with dogs', request.experience ?? '—'],
    ];

    return (
        <div className="space-y-5">
            {request.dog && (
                <Link
                    href={dogsRoutes.show.url(request.dog.id)}
                    className="flex items-center gap-3 rounded-2xl border border-neutral-200 p-3 transition hover:border-brand-300 hover:bg-brand-50/50 dark:border-neutral-800 dark:hover:border-brand-800 dark:hover:bg-brand-950/30"
                >
                    <DogThumb dog={request.dog} />
                    <div>
                        <p className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                            {request.dog.name}
                        </p>
                        <p className="text-xs text-neutral-500 dark:text-neutral-400">
                            {request.dog.scas_id} · Open dog profile
                        </p>
                    </div>
                </Link>
            )}

            <div className="flex flex-wrap gap-2">
                <a
                    href={`mailto:${request.email}?subject=${encodeURIComponent(`Your adoption request${request.dog ? ` for ${request.dog.name}` : ''}`)}`}
                    className="inline-flex items-center gap-1.5 rounded-lg border border-neutral-200 px-3 py-1.5 text-xs font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800"
                >
                    Email {request.full_name.split(' ')[0]}
                </a>
                <a
                    href={`tel:${request.phone}`}
                    className="inline-flex items-center gap-1.5 rounded-lg border border-neutral-200 px-3 py-1.5 text-xs font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800"
                >
                    Call
                </a>
                <a
                    href={`https://wa.me/${request.phone.replace(/\D/g, '')}`}
                    target="_blank"
                    rel="noreferrer"
                    className="inline-flex items-center gap-1.5 rounded-lg border border-neutral-200 px-3 py-1.5 text-xs font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800"
                >
                    WhatsApp
                </a>
            </div>

            <dl className="grid grid-cols-1 gap-x-6 gap-y-3 rounded-2xl bg-neutral-50 p-4 text-sm sm:grid-cols-2 dark:bg-neutral-800/50">
                {answers.map(([label, value]) => (
                    <div key={label}>
                        <dt className="text-xs text-neutral-500 dark:text-neutral-400">
                            {label}
                        </dt>
                        <dd className="mt-0.5 font-medium break-words text-neutral-900 dark:text-neutral-100">
                            {value}
                        </dd>
                    </div>
                ))}
            </dl>

            <div>
                <p className="text-xs font-semibold text-neutral-500 dark:text-neutral-400">
                    Why they want to adopt
                </p>
                <p className="mt-1.5 text-sm leading-relaxed whitespace-pre-line text-neutral-800 dark:text-neutral-200">
                    {request.message}
                </p>
            </div>

            <Form
                action={update(request.id)}
                options={{ preserveScroll: true }}
                onSuccess={onDone}
                className="space-y-4 border-t border-neutral-100 pt-5 dark:border-neutral-800"
            >
                {({ errors, processing }) => (
                    <>
                        <Field
                            label="Status"
                            htmlFor="status"
                            error={errors.status}
                        >
                            <Select
                                id="status"
                                name="status"
                                defaultValue={request.status}
                            >
                                {statusOptions.map((option) => (
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
                            label="Team notes"
                            htmlFor="staff_notes"
                            error={errors.staff_notes}
                            hint="Only visible to staff."
                        >
                            <Textarea
                                id="staff_notes"
                                name="staff_notes"
                                rows={3}
                                defaultValue={request.staff_notes ?? ''}
                                placeholder="e.g. Called on Monday, home visit booked for Saturday."
                            />
                        </Field>
                        {request.reviewer && request.reviewed_at && (
                            <p className="flex items-center gap-1.5 text-xs text-neutral-500 dark:text-neutral-400">
                                <CheckIcon className="size-3.5" />
                                Status last changed by {
                                    request.reviewer
                                } on{' '}
                                {formatDateTime(request.reviewed_at)}
                            </p>
                        )}
                        <div className="flex justify-end gap-2">
                            <Button variant="secondary" onClick={onDone}>
                                Close
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving…' : 'Save'}
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </div>
    );
}

function DogThumb({ dog }: { dog: AdoptionRequest['dog'] }) {
    if (dog?.photo_url) {
        return (
            <img
                src={dog.photo_url}
                alt={dog.name}
                className="size-12 shrink-0 rounded-xl object-cover"
            />
        );
    }

    return (
        <span className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700 dark:bg-brand-900/60 dark:text-brand-300">
            {dog ? (
                <span className="text-sm font-bold">{dog.name.charAt(0)}</span>
            ) : (
                <HomeIcon className="size-5" />
            )}
        </span>
    );
}
