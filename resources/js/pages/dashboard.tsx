import { DogAvatar } from '@/components/dogs/dog-avatar';
import { StatusBadge } from '@/components/dogs/status-badge';
import {
    AlertIcon,
    ArrowRightIcon,
    BuildingIcon,
    DashboardIcon,
    GlobeIcon,
    HeartIcon,
    HomeIcon,
    PawIcon,
    SearchIcon,
    type IconProps,
} from '@/components/icons';
import { PageHeader } from '@/components/page-header';
import { buttonClasses } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeaderCell,
    TableRow,
} from '@/components/ui/table';
import { AuthenticatedLayout } from '@/layouts/authenticated-layout';
import { cn } from '@/lib/utils';
import dogsRoutes from '@/routes/dogs';
import { formatDate, type Dog } from '@/types/dog';
import { Head, Link, router } from '@inertiajs/react';
import {
    type ComponentType,
    type FormEvent,
    type ReactElement,
    useState,
} from 'react';

interface DashboardStats {
    total: number;
    atScas: number;
    inFoster: number;
    adopted: number;
    internationalPlacements: number;
    needsReview: number;
}

interface DashboardProps {
    stats: DashboardStats;
    recentlyUpdated: Dog[];
}

const statCards: {
    key: keyof DashboardStats;
    label: string;
    icon: ComponentType<IconProps>;
    query?: Record<string, string | string[]>;
    attention?: boolean;
}[] = [
    { key: 'total', label: 'Total Dogs', icon: PawIcon },
    {
        key: 'atScas',
        label: 'Currently at SCAS',
        icon: BuildingIcon,
        query: { status: 'at_scas' },
    },
    {
        key: 'inFoster',
        label: 'In foster',
        icon: HomeIcon,
        query: { status: ['local_foster', 'international_foster'] },
    },
    {
        key: 'adopted',
        label: 'Adopted',
        icon: HeartIcon,
        query: { status: ['adopted_uae', 'adopted_internationally'] },
    },
    {
        key: 'internationalPlacements',
        label: 'International placements',
        icon: GlobeIcon,
        query: { placement: 'international' },
    },
    {
        key: 'needsReview',
        label: 'Needs update',
        icon: AlertIcon,
        query: { needs_review: '1' },
        attention: true,
    },
];

export default function Dashboard({ stats, recentlyUpdated }: DashboardProps) {
    const [query, setQuery] = useState('');

    function submitSearch(e: FormEvent) {
        e.preventDefault();
        router.get(dogsRoutes.index.url(), query ? { q: query } : {});
    }

    return (
        <>
            <Head title="Dashboard" />

            <PageHeader
                title="SCAS Dog Database"
                icon={DashboardIcon}
                description="Central record of all rescue dogs, their medical history, placement history and current status."
                actions={
                    <form
                        onSubmit={submitSearch}
                        className="flex items-center gap-1.5"
                        role="search"
                    >
                        <input
                            type="search"
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder="Search by name or ID..."
                            aria-label="Search dogs by name or ID"
                            className="w-56 rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-xs text-neutral-900 placeholder:text-neutral-400 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                        />
                        <button
                            type="submit"
                            className="rounded-lg bg-brand-800 p-2 text-white hover:bg-brand-900 dark:bg-brand-600"
                            aria-label="Search"
                        >
                            <SearchIcon className="size-3.5" />
                        </button>
                    </form>
                }
            />

            <div className="mb-6 grid grid-cols-2 gap-6 md:grid-cols-3">
                {statCards.map((card) => {
                    const needsAttention =
                        card.attention && stats[card.key] > 0;

                    return (
                        <Link
                            key={card.key}
                            href={dogsRoutes.index.url(
                                card.query ? { query: card.query } : undefined,
                            )}
                            className="group relative block overflow-hidden rounded-2xl border border-brand-100 bg-linear-to-br from-white to-brand-50 p-5 shadow-sm shadow-brand-900/5 transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-md hover:shadow-brand-900/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 dark:border-neutral-800 dark:from-neutral-900 dark:to-brand-950/50 dark:shadow-none dark:hover:border-brand-800"
                        >
                            <card.icon
                                aria-hidden="true"
                                className="pointer-events-none absolute -right-4 -bottom-5 size-24 text-brand-100 transition-transform group-hover:scale-110 group-hover:-rotate-6 dark:text-brand-900/40"
                                strokeWidth={1.25}
                            />
                            <div className="relative flex items-start justify-between gap-2">
                                <p className="text-sm text-neutral-600 dark:text-neutral-300">
                                    {card.label}
                                </p>
                                <span
                                    className={cn(
                                        'flex size-8 shrink-0 items-center justify-center rounded-lg',
                                        needsAttention
                                            ? 'bg-warning-50 text-warning-600 dark:bg-warning-700/20 dark:text-warning-500'
                                            : 'bg-brand-100 text-brand-700 ring-1 ring-brand-200/60 dark:bg-brand-900 dark:text-brand-300 dark:ring-brand-800',
                                    )}
                                >
                                    <card.icon className="size-4" />
                                </span>
                            </div>
                            <div className="relative mt-1 flex items-end justify-between">
                                <p className="text-3xl font-bold text-neutral-900 dark:text-neutral-50">
                                    {stats[card.key]}
                                </p>
                                <span className="flex items-center gap-1 text-xs font-medium text-brand-700 opacity-0 transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100 dark:text-brand-300">
                                    View
                                    <ArrowRightIcon className="size-3" />
                                </span>
                            </div>
                        </Link>
                    );
                })}
            </div>

            <h2 className="mb-3 text-base font-semibold text-neutral-900 dark:text-neutral-100">
                Recently Updated Records
            </h2>
            <Card className="overflow-hidden">
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableHeaderCell>Dog</TableHeaderCell>
                            <TableHeaderCell>Status</TableHeaderCell>
                            <TableHeaderCell>Location</TableHeaderCell>
                            <TableHeaderCell>Last Updated</TableHeaderCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {recentlyUpdated.map((dog) => (
                            <TableRow key={dog.id}>
                                <TableCell>
                                    <Link
                                        href={dogsRoutes.show.url(dog.id)}
                                        className="flex items-center gap-3 font-medium text-neutral-900 hover:text-brand-700 dark:text-neutral-100 dark:hover:text-brand-300"
                                    >
                                        <DogAvatar
                                            dog={dog}
                                            className="size-8"
                                        />
                                        {dog.name}
                                    </Link>
                                </TableCell>
                                <TableCell>
                                    <StatusBadge status={dog.current_status} />
                                </TableCell>
                                <TableCell>
                                    {dog.current_location ?? '—'}
                                </TableCell>
                                <TableCell>
                                    {formatDate(dog.updated_at)}
                                </TableCell>
                            </TableRow>
                        ))}
                        {recentlyUpdated.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={4}
                                    className="py-6 text-center text-neutral-400"
                                >
                                    No dogs recorded yet.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </Card>

            <div className="mt-6 flex justify-end">
                <Link
                    href={dogsRoutes.index.url()}
                    className={buttonClasses('primary', 'sm')}
                >
                    View All Records
                    <ArrowRightIcon className="size-3.5" />
                </Link>
            </div>
        </>
    );
}

Dashboard.layout = (page: ReactElement) => (
    <AuthenticatedLayout>{page}</AuthenticatedLayout>
);
