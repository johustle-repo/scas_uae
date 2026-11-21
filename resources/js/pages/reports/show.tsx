import {
    ChevronDownIcon,
    CloseIcon,
    DownloadIcon,
    SearchIcon,
} from '@/components/icons';
import { PageHeader } from '@/components/page-header';
import { Card } from '@/components/ui/card';
import { AuthenticatedLayout } from '@/layouts/authenticated-layout';
import { reportFormats, reportStyle, reportToneClasses } from '@/lib/reports';
import { cn } from '@/lib/utils';
import reportsRoutes from '@/routes/reports';
import { Head } from '@inertiajs/react';
import { type ReactElement, type ReactNode, useMemo, useState } from 'react';

interface Report {
    key: string;
    title: string;
    description: string;
    headings: string[];
    rows: string[][];
}

/** Columns whose values are categories, shown as badges. */
const BADGE_COLUMNS = ['Status', 'Type'];

export default function ReportShow({ report }: { report: Report }) {
    const style = reportStyle(report.key);

    return (
        <>
            <Head title={report.title} />

            <PageHeader
                back={{ href: reportsRoutes.index.url(), label: 'All reports' }}
                title={report.title}
                icon={style.icon}
                description={report.description}
                actions={<DownloadButtons reportKey={report.key} />}
            />

            {report.key === 'summary' ? (
                <SummaryView rows={report.rows} />
            ) : (
                <TableView report={report} />
            )}
        </>
    );
}

function DownloadButtons({ reportKey }: { reportKey: string }) {
    return (
        <div className="inline-flex overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
            {reportFormats.map((option, index) => (
                <a
                    key={option.format}
                    href={reportsRoutes.download.url({
                        report: reportKey,
                        format: option.format,
                    })}
                    download
                    className={cn(
                        'flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-neutral-700 hover:bg-brand-50 hover:text-brand-800 dark:text-neutral-200 dark:hover:bg-brand-950 dark:hover:text-brand-200',
                        index > 0 &&
                            'border-l border-neutral-200 dark:border-neutral-700',
                    )}
                >
                    {index === 0 && <DownloadIcon className="size-3.5" />}
                    {option.label}
                </a>
            ))}
        </div>
    );
}

function SummaryView({ rows }: { rows: string[][] }) {
    const total = Number(rows[0]?.[1] ?? 0);

    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {rows.map(([measure, count], index) => {
                const value = Number(count);
                const share = total > 0 ? Math.round((value / total) * 100) : 0;
                const isTotal = index === 0;

                return (
                    <Card
                        key={measure}
                        className={cn(
                            'p-5',
                            isTotal &&
                                'border-brand-200 bg-linear-to-br from-brand-50 to-white sm:col-span-2 lg:col-span-1 dark:border-brand-800 dark:from-brand-950/60 dark:to-neutral-900',
                        )}
                    >
                        <p className="text-xs text-neutral-600 dark:text-neutral-300">
                            {measure}
                        </p>
                        <div className="mt-1 flex items-baseline justify-between gap-2">
                            <p className="text-3xl font-bold text-neutral-900 dark:text-neutral-50">
                                {value}
                            </p>
                            {!isTotal && measure !== 'Archived records' && (
                                <span className="text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                    {share}% of active dogs
                                </span>
                            )}
                        </div>
                        {!isTotal && measure !== 'Archived records' && (
                            <div className="mt-3 h-1.5 overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                                <div
                                    className="h-full rounded-full bg-brand-500"
                                    style={{ width: `${share}%` }}
                                />
                            </div>
                        )}
                    </Card>
                );
            })}
        </div>
    );
}

function TableView({ report }: { report: Report }) {
    const style = reportStyle(report.key);
    const [query, setQuery] = useState('');
    const [sort, setSort] = useState<{ column: number; ascending: boolean }>({
        column: 0,
        ascending: true,
    });

    const rows = useMemo(() => {
        const needle = query.trim().toLowerCase();
        const filtered = needle
            ? report.rows.filter((row) =>
                  row.some((value) => value.toLowerCase().includes(needle)),
              )
            : report.rows;

        return [...filtered].sort((a, b) => {
            const order = (a[sort.column] ?? '').localeCompare(
                b[sort.column] ?? '',
                undefined,
                { numeric: true, sensitivity: 'base' },
            );

            return sort.ascending ? order : -order;
        });
    }, [report.rows, query, sort]);

    function toggleSort(column: number) {
        setSort((current) => ({
            column,
            ascending: current.column === column ? !current.ascending : true,
        }));
    }

    return (
        <Card className="overflow-hidden">
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-100 px-4 py-3 dark:border-neutral-800">
                <div className="flex items-center gap-2">
                    <span
                        className={cn(
                            'rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1',
                            reportToneClasses[style.tone],
                        )}
                    >
                        {rows.length === report.rows.length
                            ? `${report.rows.length} ${report.rows.length === 1 ? 'row' : 'rows'}`
                            : `${rows.length} of ${report.rows.length}`}
                    </span>
                    <span className="text-xs text-neutral-500 dark:text-neutral-400">
                        {report.headings.length} columns
                    </span>
                </div>
                <div className="relative w-full sm:w-64">
                    <SearchIcon className="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-neutral-400" />
                    <input
                        type="search"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Search this report…"
                        aria-label="Search this report"
                        className="w-full rounded-lg border border-neutral-200 bg-white py-1.5 pr-8 pl-8 text-xs text-neutral-900 placeholder:text-neutral-400 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                    />
                    {query && (
                        <button
                            type="button"
                            onClick={() => setQuery('')}
                            aria-label="Clear search"
                            className="absolute top-1/2 right-2 -translate-y-1/2 rounded p-0.5 text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-200"
                        >
                            <CloseIcon className="size-3" />
                        </button>
                    )}
                </div>
            </div>

            <div className="max-h-[70vh] overflow-auto">
                <table className="w-full text-left text-xs">
                    <thead className="sticky top-0 z-10 bg-neutral-50/95 backdrop-blur dark:bg-neutral-900/95">
                        <tr>
                            {report.headings.map((heading, column) => (
                                <th
                                    key={heading}
                                    scope="col"
                                    aria-sort={
                                        sort.column === column
                                            ? sort.ascending
                                                ? 'ascending'
                                                : 'descending'
                                            : 'none'
                                    }
                                    className="border-b border-neutral-200 px-4 py-2.5 font-semibold whitespace-nowrap text-neutral-600 dark:border-neutral-800 dark:text-neutral-300"
                                >
                                    <button
                                        type="button"
                                        onClick={() => toggleSort(column)}
                                        className="inline-flex items-center gap-1 hover:text-brand-700 dark:hover:text-brand-300"
                                    >
                                        {heading}
                                        <ChevronDownIcon
                                            className={cn(
                                                'size-3 transition',
                                                sort.column === column
                                                    ? 'text-brand-600 dark:text-brand-400'
                                                    : 'text-transparent',
                                                sort.column === column &&
                                                    !sort.ascending &&
                                                    'rotate-180',
                                            )}
                                        />
                                    </button>
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-neutral-100 dark:divide-neutral-800">
                        {rows.length === 0 && (
                            <tr>
                                <td
                                    colSpan={report.headings.length}
                                    className="px-4 py-12 text-center text-neutral-500"
                                >
                                    {query
                                        ? `No rows match “${query}”.`
                                        : 'No records in this report yet.'}
                                </td>
                            </tr>
                        )}
                        {rows.map((row, rowIndex) => (
                            <tr
                                key={rowIndex}
                                className="transition-colors odd:bg-white even:bg-neutral-50/60 hover:bg-brand-50/60 dark:odd:bg-neutral-900 dark:even:bg-neutral-800/30 dark:hover:bg-brand-950/40"
                            >
                                {row.map((value, column) => (
                                    <td
                                        key={column}
                                        className="px-4 py-2.5 align-top text-neutral-700 dark:text-neutral-200"
                                    >
                                        <CellValue
                                            value={value}
                                            heading={report.headings[column]}
                                        />
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </Card>
    );
}

function CellValue({
    value,
    heading,
}: {
    value: string;
    heading: string;
}): ReactNode {
    if (value === '') {
        return (
            <span className="text-neutral-300 dark:text-neutral-600">—</span>
        );
    }

    if (heading === 'SCAS ID') {
        return (
            <span className="font-mono font-medium whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                {value}
            </span>
        );
    }

    if (heading === 'Name') {
        return (
            <span className="font-semibold text-neutral-900 dark:text-neutral-100">
                {value}
            </span>
        );
    }

    if (value === 'Yes' || value === 'No') {
        return (
            <span
                className={cn(
                    'inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium ring-1',
                    value === 'Yes'
                        ? 'bg-success-50 text-success-700 ring-success-500/30 dark:bg-success-700/20 dark:text-success-500 dark:ring-success-700/40'
                        : 'bg-neutral-100 text-neutral-600 ring-neutral-200 dark:bg-neutral-800 dark:text-neutral-300 dark:ring-neutral-700',
                )}
            >
                {value}
            </span>
        );
    }

    if (BADGE_COLUMNS.includes(heading)) {
        return (
            <span className="inline-flex rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-medium whitespace-nowrap text-brand-800 ring-1 ring-brand-200 dark:bg-brand-900/50 dark:text-brand-200 dark:ring-brand-800">
                {value}
            </span>
        );
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return <span className="whitespace-nowrap">{value}</span>;
    }

    return value;
}

ReportShow.layout = (page: ReactElement) => (
    <AuthenticatedLayout>{page}</AuthenticatedLayout>
);
