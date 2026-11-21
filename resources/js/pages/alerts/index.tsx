import { AlertIcon, CheckIcon } from '@/components/icons';
import { PageHeader } from '@/components/page-header';
import { Card } from '@/components/ui/card';
import { AuthenticatedLayout } from '@/layouts/authenticated-layout';
import { alertToneClasses, type AlertGroup } from '@/lib/alerts';
import { cn } from '@/lib/utils';
import dogsRoutes from '@/routes/dogs';
import { Head, Link } from '@inertiajs/react';
import type { ReactElement } from 'react';

export default function AlertsIndex({ groups }: { groups: AlertGroup[] }) {
    return (
        <>
            <Head title="Alerts" />

            <PageHeader
                title="Alerts"
                icon={AlertIcon}
                description="Follow-ups that need attention: vaccinations, vet checks, foster reviews, missing documents, upcoming placements and incomplete records."
            />

            {groups.length === 0 ? (
                <Card className="flex items-center gap-3 p-6 text-sm text-neutral-600 dark:text-neutral-300">
                    <CheckIcon className="size-5 text-success-600" />
                    Nothing needs attention right now.
                </Card>
            ) : (
                <div className="space-y-6">
                    {groups.map((group) => (
                        <section key={group.key} id={group.key}>
                            <h2 className="mb-2 flex items-center gap-2 text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                {group.title}
                                <span
                                    className={cn(
                                        'rounded-full px-2 py-0.5 text-xs ring-1',
                                        alertToneClasses[group.tone],
                                    )}
                                >
                                    {group.items.length}
                                </span>
                            </h2>
                            <Card className="divide-y divide-neutral-100 overflow-hidden dark:divide-neutral-800">
                                {group.items.map((item, index) => (
                                    <Link
                                        key={`${item.dog_id}-${index}`}
                                        href={dogsRoutes.show.url(item.dog_id)}
                                        className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 px-4 py-2.5 text-xs hover:bg-brand-50 dark:hover:bg-brand-950/40"
                                    >
                                        <span className="font-medium text-neutral-900 dark:text-neutral-100">
                                            {item.name}{' '}
                                            <span className="font-normal text-neutral-500">
                                                {item.scas_id}
                                            </span>
                                        </span>
                                        <span className="text-neutral-600 dark:text-neutral-300">
                                            {item.detail}
                                        </span>
                                    </Link>
                                ))}
                            </Card>
                        </section>
                    ))}
                </div>
            )}
        </>
    );
}

AlertsIndex.layout = (page: ReactElement) => (
    <AuthenticatedLayout>{page}</AuthenticatedLayout>
);
