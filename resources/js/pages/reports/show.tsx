import { DownloadIcon, FileIcon } from '@/components/icons';
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
import reportsRoutes from '@/routes/reports';
import { Head } from '@inertiajs/react';
import type { ReactElement } from 'react';

interface Report {
    key: string;
    title: string;
    description: string;
    headings: string[];
    rows: string[][];
}

const formats = [
    { format: 'csv', label: 'CSV' },
    { format: 'excel', label: 'Excel' },
    { format: 'pdf', label: 'PDF' },
] as const;

export default function ReportShow({ report }: { report: Report }) {
    return (
        <>
            <Head title={report.title} />

            <PageHeader
                back={{ href: reportsRoutes.index.url(), label: 'All reports' }}
                title={report.title}
                icon={FileIcon}
                description={`${report.description} ${report.rows.length} ${report.rows.length === 1 ? 'row' : 'rows'}.`}
                actions={
                    <div className="flex gap-2">
                        {formats.map((option) => (
                            <a
                                key={option.format}
                                href={reportsRoutes.download.url({
                                    report: report.key,
                                    format: option.format,
                                })}
                                download
                                className={buttonClasses('secondary', 'sm')}
                            >
                                <DownloadIcon className="size-3.5" />
                                {option.label}
                            </a>
                        ))}
                    </div>
                }
            />

            <Card className="overflow-x-auto">
                <Table>
                    <TableHead>
                        <TableRow>
                            {report.headings.map((heading) => (
                                <TableHeaderCell key={heading}>
                                    {heading}
                                </TableHeaderCell>
                            ))}
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {report.rows.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={report.headings.length}
                                    className="py-8 text-center text-neutral-500"
                                >
                                    No records.
                                </TableCell>
                            </TableRow>
                        )}
                        {report.rows.map((row, rowIndex) => (
                            <TableRow key={rowIndex}>
                                {row.map((value, columnIndex) => (
                                    <TableCell key={columnIndex}>
                                        {value || '—'}
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </Card>
        </>
    );
}

ReportShow.layout = (page: ReactElement) => (
    <AuthenticatedLayout>{page}</AuthenticatedLayout>
);
