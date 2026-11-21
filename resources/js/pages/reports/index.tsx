import {
    AlertIcon,
    ArrowRightIcon,
    BuildingIcon,
    DownloadIcon,
    FileIcon,
    HeartIcon,
    HomeIcon,
    PawIcon,
    type IconProps,
} from '@/components/icons';
import { PageHeader } from '@/components/page-header';
import { AuthenticatedLayout } from '@/layouts/authenticated-layout';
import {
    reportFormats,
    reportGroups,
    reportStyle,
    reportToneClasses,
} from '@/lib/reports';
import { cn } from '@/lib/utils';
import reportsRoutes from '@/routes/reports';
import { Head, Link } from '@inertiajs/react';
import type { ComponentType, ReactElement } from 'react';

interface ReportSummary {
    key: string;
    title: string;
    description: string;
}

interface ReportsIndexProps {
    reports: ReportSummary[];
    /** Deferred: undefined until loaded after the first render. */
    rowCounts?: Record<string, number>;
    /** Deferred: the summary report's [measure, count] rows. */
    summary?: [string, string][];
}

const headlines: {
    label: string;
    measures: string[];
    icon: ComponentType<IconProps>;
    report: string;
    attention?: boolean;
}[] = [
    {
        label: 'Total dogs',
        measures: ['Total dogs'],
        icon: PawIcon,
        report: 'all_dogs',
    },
    {
        label: 'At SCAS',
        measures: ['Currently at SCAS'],
        icon: BuildingIcon,
        report: 'at_scas',
    },
    {
        label: 'In foster',
        measures: ['In foster (local)', 'In foster (international)'],
        icon: HomeIcon,
        report: 'in_foster',
    },
    {
        label: 'Adopted',
        measures: ['Adopted (UAE)', 'Adopted (international)'],
        icon: HeartIcon,
        report: 'adopted',
    },
    {
        label: 'Need updating',
        measures: ['Records requiring updates'],
        icon: AlertIcon,
        report: 'needs_update',
        attention: true,
    },
];

export default function ReportsIndex({
    reports,
    rowCounts,
    summary,
}: ReportsIndexProps) {
    const measure = (names: string[]): number | undefined =>
        summary === undefined
            ? undefined
            : names.reduce(
                  (total, name) =>
                      total +
                      Number(
                          summary.find(([label]) => label === name)?.[1] ?? 0,
                      ),
                  0,
              );

    return (
        <>
            <Head title="Reports" />

            <PageHeader
                title="Reports"
                icon={FileIcon}
                description="Standard reports on dogs, placements, medical status and intake. Open a report to explore it, or download it straight away."
            />

            <div className="mb-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                {headlines.map((headline) => {
                    const value = measure(headline.measures);
                    const needsAttention =
                        headline.attention && (value ?? 0) > 0;

                    return (
                        <Link
                            key={headline.label}
                            href={reportsRoutes.show.url(headline.report)}
                            className="group relative overflow-hidden rounded-2xl border border-brand-100 bg-linear-to-br from-white to-brand-50 p-4 shadow-sm shadow-brand-900/5 transition hover:-translate-y-0.5 hover:shadow-md dark:border-neutral-800 dark:from-neutral-900 dark:to-brand-950/50 dark:shadow-none"
                        >
                            <headline.icon
                                aria-hidden="true"
                                strokeWidth={1.25}
                                className="pointer-events-none absolute -right-3 -bottom-4 size-20 text-brand-100 transition-transform group-hover:scale-110 group-hover:-rotate-6 dark:text-brand-900/40"
                            />
                            <p className="relative text-xs text-neutral-600 dark:text-neutral-300">
                                {headline.label}
                            </p>
                            {value === undefined ? (
                                <div className="relative mt-2 h-8 w-14 animate-pulse rounded-md bg-brand-100 dark:bg-neutral-800" />
                            ) : (
                                <p
                                    className={cn(
                                        'relative mt-1 text-3xl font-bold',
                                        needsAttention
                                            ? 'text-warning-600 dark:text-warning-500'
                                            : 'text-neutral-900 dark:text-neutral-50',
                                    )}
                                >
                                    {value}
                                </p>
                            )}
                        </Link>
                    );
                })}
            </div>

            <div className="space-y-8">
                {reportGroups.map((group) => {
                    const groupReports = reports.filter(
                        (report) => reportStyle(report.key).group === group.key,
                    );

                    if (groupReports.length === 0) {
                        return null;
                    }

                    return (
                        <section key={group.key}>
                            <div className="mb-3 flex items-baseline gap-3">
                                <h2 className="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                                    {group.title}
                                </h2>
                                <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                    {group.description}
                                </p>
                            </div>
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                                {groupReports.map((report) => (
                                    <ReportCard
                                        key={report.key}
                                        report={report}
                                        rowCount={rowCounts?.[report.key]}
                                        loading={rowCounts === undefined}
                                    />
                                ))}
                            </div>
                        </section>
                    );
                })}
            </div>
        </>
    );
}

function ReportCard({
    report,
    rowCount,
    loading,
}: {
    report: ReportSummary;
    rowCount?: number;
    loading: boolean;
}) {
    const style = reportStyle(report.key);

    return (
        <article className="group relative flex flex-col rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-brand-800">
            <div className="flex items-start gap-3">
                <span
                    className={cn(
                        'flex size-10 shrink-0 items-center justify-center rounded-xl ring-1',
                        reportToneClasses[style.tone],
                    )}
                >
                    <style.icon className="size-5" />
                </span>
                <div className="min-w-0 flex-1">
                    <div className="flex items-start justify-between gap-2">
                        <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                            <Link
                                href={reportsRoutes.show.url(report.key)}
                                className="after:absolute after:inset-0 after:rounded-2xl focus-visible:outline-none"
                            >
                                {report.title}
                            </Link>
                        </h3>
                        {loading ? (
                            <span className="h-5 w-12 shrink-0 animate-pulse rounded-full bg-neutral-100 dark:bg-neutral-800" />
                        ) : (
                            <span className="shrink-0 rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
                                {rowCount} {rowCount === 1 ? 'row' : 'rows'}
                            </span>
                        )}
                    </div>
                    <p className="mt-1 text-xs leading-relaxed text-neutral-600 dark:text-neutral-400">
                        {report.description}
                    </p>
                </div>
            </div>

            <div className="mt-5 flex items-center justify-between gap-2 border-t border-neutral-100 pt-3 dark:border-neutral-800">
                <span className="flex items-center gap-1 text-xs font-medium text-brand-700 dark:text-brand-300">
                    Open report
                    <ArrowRightIcon className="size-3 transition-transform group-hover:translate-x-0.5" />
                </span>
                <div className="relative z-10 flex items-center gap-1">
                    <DownloadIcon
                        aria-hidden="true"
                        className="size-3.5 text-neutral-400"
                    />
                    {reportFormats.map((option) => (
                        <a
                            key={option.format}
                            href={reportsRoutes.download.url({
                                report: report.key,
                                format: option.format,
                            })}
                            download
                            aria-label={`Download ${report.title} as ${option.label}`}
                            className="rounded-md px-1.5 py-0.5 text-[11px] font-medium text-neutral-600 hover:bg-brand-50 hover:text-brand-800 dark:text-neutral-300 dark:hover:bg-brand-950 dark:hover:text-brand-200"
                        >
                            {option.label}
                        </a>
                    ))}
                </div>
            </div>
        </article>
    );
}

ReportsIndex.layout = (page: ReactElement) => (
    <AuthenticatedLayout>{page}</AuthenticatedLayout>
);
