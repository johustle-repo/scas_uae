import { ArrowRightIcon, FileIcon } from '@/components/icons';
import { PageHeader } from '@/components/page-header';
import { AuthenticatedLayout } from '@/layouts/authenticated-layout';
import reportsRoutes from '@/routes/reports';
import { Head, Link } from '@inertiajs/react';
import type { ReactElement } from 'react';

interface ReportSummary {
    key: string;
    title: string;
    description: string;
}

export default function ReportsIndex({
    reports,
}: {
    reports: ReportSummary[];
}) {
    return (
        <>
            <Head title="Reports" />

            <PageHeader
                title="Reports"
                icon={FileIcon}
                description="Standard reports on dogs, placements, medical status and intake. Open a report to view it or download it as CSV, Excel or PDF."
            />

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                {reports.map((report) => (
                    <Link
                        key={report.key}
                        href={reportsRoutes.show.url(report.key)}
                        className="group flex flex-col justify-between rounded-2xl border border-neutral-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-brand-800"
                    >
                        <div>
                            <h2 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                {report.title}
                            </h2>
                            <p className="mt-1 text-xs text-neutral-600 dark:text-neutral-400">
                                {report.description}
                            </p>
                        </div>
                        <span className="mt-4 flex items-center gap-1 text-xs font-medium text-brand-700 dark:text-brand-300">
                            Open report
                            <ArrowRightIcon className="size-3 transition-transform group-hover:translate-x-0.5" />
                        </span>
                    </Link>
                ))}
            </div>
        </>
    );
}

ReportsIndex.layout = (page: ReactElement) => (
    <AuthenticatedLayout>{page}</AuthenticatedLayout>
);
