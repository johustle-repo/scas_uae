import { DogAvatar } from '@/components/dogs/dog-avatar';
import {
    FilterPanel,
    filterValues,
    visitDogList,
    type DogFilters,
    type MultiFilterKey,
} from '@/components/dogs/filter-panel';
import { StatusBadge } from '@/components/dogs/status-badge';
import {
    AlertIcon,
    ArchiveIcon,
    ChevronDownIcon,
    CloseIcon,
    PawIcon,
    PlusIcon,
    ResetIcon,
    SearchIcon,
} from '@/components/icons';
import { PageHeader } from '@/components/page-header';
import { buttonClasses } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Pagination } from '@/components/ui/pagination';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeaderCell,
    TableRow,
} from '@/components/ui/table';
import { AuthenticatedLayout } from '@/layouts/authenticated-layout';
import { useCan } from '@/lib/permissions';
import { cn } from '@/lib/utils';
import dogsRoutes from '@/routes/dogs';
import {
    formatAge,
    formatDate,
    placementLabel,
    type Dog,
    type Option,
    type Paginated,
} from '@/types/dog';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { type FormEvent, type ReactElement, useState } from 'react';

interface DogsIndexProps {
    dogs: Paginated<Dog>;
    filters: DogFilters;
    statusOptions: Option[];
    genderOptions: Option[];
    sizeOptions: Option[];
    placementOptions: Option[];
}

export default function DogsIndex({
    dogs,
    filters,
    statusOptions,
    genderOptions,
    sizeOptions,
    placementOptions,
}: DogsIndexProps) {
    const { url } = usePage();
    const can = useCan();
    const [query, setQuery] = useState(filters.q ?? '');
    const [panelOpen, setPanelOpen] = useState(
        new URL(url, window.location.origin).searchParams.get('panel') ===
            'filters',
    );

    const hasActiveFilters = Object.values(filters).some(
        (value) => filterValues(value).length > 0,
    );

    function submitSearch(e: FormEvent) {
        e.preventDefault();
        visitDogList({ ...filters, q: query || undefined });
    }

    function resetAll() {
        setQuery('');
        visitDogList({});
    }

    function summarize(key: MultiFilterKey, options: Option[]): string {
        const selected = filterValues(filters[key]);

        if (selected.length === 0) {
            return 'All';
        }

        if (selected.length === 1) {
            return (
                options.find((option) => option.value === selected[0])?.label ??
                selected[0]
            );
        }

        return `${selected.length} selected`;
    }

    const pills: { key: MultiFilterKey; label: string; options: Option[] }[] = [
        { key: 'status', label: 'Status', options: statusOptions },
        { key: 'placement', label: 'Placement', options: placementOptions },
        { key: 'gender', label: 'Gender', options: genderOptions },
        { key: 'size', label: 'Size', options: sizeOptions },
    ];

    const showingArchived = filters.archived === 'only';

    return (
        <>
            <Head title={showingArchived ? 'Archived Records' : 'All Dogs'} />

            <PageHeader
                title={showingArchived ? 'Archived Records' : 'All Dogs'}
                icon={showingArchived ? ArchiveIcon : PawIcon}
                description={
                    showingArchived
                        ? 'Dogs whose records have been archived. Archived dogs are hidden from other lists and statistics.'
                        : 'Complete database of all dogs registered with SCAS.'
                }
                actions={
                    can('manage-dogs') && (
                        <Link
                            href={dogsRoutes.create.url()}
                            className={buttonClasses('primary', 'sm')}
                        >
                            <PlusIcon className="size-3.5" />
                            Add New Dog
                        </Link>
                    )
                }
            />

            <div className="mb-6 flex flex-wrap items-center gap-2">
                <form
                    onSubmit={submitSearch}
                    role="search"
                    className="relative w-full sm:w-64"
                >
                    <SearchIcon className="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-neutral-400" />
                    <input
                        type="search"
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Name, ID, microchip or breed"
                        aria-label="Search dogs"
                        className="w-full rounded-lg border border-neutral-200 bg-white py-1.5 pr-3 pl-8 text-xs text-neutral-900 placeholder:text-neutral-400 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                    />
                </form>

                {pills.map((pill) => (
                    <button
                        key={pill.key}
                        type="button"
                        onClick={() => setPanelOpen(true)}
                        className={cn(
                            'inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs transition-colors',
                            filterValues(filters[pill.key]).length > 0
                                ? 'border-brand-300 bg-brand-50 text-brand-800 dark:border-brand-700 dark:bg-brand-950 dark:text-brand-200'
                                : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200',
                        )}
                    >
                        <span className="font-medium">{pill.label}:</span>
                        {summarize(pill.key, pill.options)}
                        <ChevronDownIcon className="size-3" />
                    </button>
                ))}

                {filters.needs_review && (
                    <button
                        type="button"
                        onClick={() =>
                            visitDogList({
                                ...filters,
                                needs_review: undefined,
                            })
                        }
                        className="inline-flex items-center gap-1.5 rounded-lg border border-warning-500/40 bg-warning-50 px-3 py-1.5 text-xs font-medium text-warning-700 hover:bg-warning-50/70 dark:bg-warning-700/20 dark:text-warning-500"
                        aria-label="Remove needs update filter"
                    >
                        <AlertIcon className="size-3.5" />
                        Needs update
                        <CloseIcon className="size-3" />
                    </button>
                )}

                {showingArchived && (
                    <button
                        type="button"
                        onClick={() =>
                            visitDogList({ ...filters, archived: undefined })
                        }
                        className="inline-flex items-center gap-1.5 rounded-lg border border-neutral-300 bg-neutral-100 px-3 py-1.5 text-xs font-medium text-neutral-700 hover:bg-neutral-200 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-200"
                        aria-label="Show active records instead"
                    >
                        <ArchiveIcon className="size-3.5" />
                        Archived only
                        <CloseIcon className="size-3" />
                    </button>
                )}

                <div className="ml-auto flex items-center gap-1">
                    {hasActiveFilters && (
                        <button
                            type="button"
                            onClick={resetAll}
                            className="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs text-neutral-500 hover:text-brand-700 dark:text-neutral-400"
                        >
                            <ResetIcon className="size-3.5" />
                            Reset
                        </button>
                    )}
                    {panelOpen && (
                        <button
                            type="button"
                            onClick={() => setPanelOpen(false)}
                            className="rounded-lg p-1.5 text-neutral-500 hover:bg-neutral-100 dark:hover:bg-neutral-800"
                            aria-label="Close filters"
                        >
                            <CloseIcon className="size-3.5" />
                        </button>
                    )}
                </div>
            </div>

            {panelOpen && (
                <FilterPanel
                    filters={filters}
                    statusOptions={statusOptions}
                    placementOptions={placementOptions}
                    genderOptions={genderOptions}
                    sizeOptions={sizeOptions}
                    onClose={() => setPanelOpen(false)}
                />
            )}

            <Card className="overflow-hidden">
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableHeaderCell>Photo</TableHeaderCell>
                            <TableHeaderCell>Dog</TableHeaderCell>
                            <TableHeaderCell>ID</TableHeaderCell>
                            <TableHeaderCell>Gender</TableHeaderCell>
                            <TableHeaderCell>Age</TableHeaderCell>
                            <TableHeaderCell>Current Status</TableHeaderCell>
                            <TableHeaderCell>Placement</TableHeaderCell>
                            <TableHeaderCell>Location</TableHeaderCell>
                            <TableHeaderCell>Last Updated</TableHeaderCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {dogs.data.map((dog) => (
                            <TableRow
                                key={dog.id}
                                className="cursor-pointer"
                                onClick={() =>
                                    router.visit(dogsRoutes.show.url(dog.id))
                                }
                            >
                                <TableCell className="py-2">
                                    <DogAvatar dog={dog} className="size-10" />
                                </TableCell>
                                <TableCell>
                                    <Link
                                        href={dogsRoutes.show.url(dog.id)}
                                        className="font-semibold text-neutral-900 hover:text-brand-700 dark:text-neutral-100 dark:hover:text-brand-300"
                                        onClick={(e) => e.stopPropagation()}
                                    >
                                        {dog.name}
                                    </Link>
                                </TableCell>
                                <TableCell className="whitespace-nowrap text-neutral-500">
                                    {dog.scas_id}
                                </TableCell>
                                <TableCell className="capitalize">
                                    {dog.gender ?? '—'}
                                </TableCell>
                                <TableCell className="whitespace-nowrap">
                                    {formatAge(dog.date_of_birth)}
                                </TableCell>
                                <TableCell>
                                    <StatusBadge status={dog.current_status} />
                                </TableCell>
                                <TableCell>
                                    {placementLabel(dog.current_status) ?? '—'}
                                </TableCell>
                                <TableCell>
                                    {dog.current_location ?? '—'}
                                </TableCell>
                                <TableCell className="whitespace-nowrap">
                                    {formatDate(dog.updated_at)}
                                </TableCell>
                            </TableRow>
                        ))}
                        {dogs.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={9}
                                    className="py-10 text-center text-neutral-400"
                                >
                                    No dogs match these filters.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </Card>

            <Pagination
                links={dogs.links}
                total={dogs.total}
                currentPage={dogs.current_page}
                perPage={dogs.per_page}
                lastPage={dogs.last_page}
            />
        </>
    );
}

DogsIndex.layout = (page: ReactElement) => (
    <AuthenticatedLayout>{page}</AuthenticatedLayout>
);
