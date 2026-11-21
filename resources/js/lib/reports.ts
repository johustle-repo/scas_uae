import {
    AlertIcon,
    BuildingIcon,
    CalendarIcon,
    DashboardIcon,
    GlobeIcon,
    HeartIcon,
    HistoryIcon,
    HomeIcon,
    PawIcon,
    PlusIcon,
    ReturnIcon,
    type IconProps,
} from '@/components/icons';
import type { ComponentType } from 'react';

export type ReportTone = 'brand' | 'sky' | 'rose' | 'amber' | 'emerald';

export interface ReportStyle {
    icon: ComponentType<IconProps>;
    tone: ReportTone;
    group: 'overview' | 'placements' | 'health';
}

/** Icon, colour and section for each report key in App\Support\DogReports. */
export const reportStyles: Record<string, ReportStyle> = {
    summary: { icon: DashboardIcon, tone: 'brand', group: 'overview' },
    all_dogs: { icon: PawIcon, tone: 'brand', group: 'overview' },
    needs_update: { icon: AlertIcon, tone: 'amber', group: 'overview' },
    at_scas: { icon: BuildingIcon, tone: 'sky', group: 'placements' },
    in_foster: { icon: HomeIcon, tone: 'sky', group: 'placements' },
    adopted: { icon: HeartIcon, tone: 'rose', group: 'placements' },
    international: { icon: GlobeIcon, tone: 'sky', group: 'placements' },
    foster: { icon: HistoryIcon, tone: 'sky', group: 'placements' },
    adoption: { icon: ReturnIcon, tone: 'rose', group: 'placements' },
    medical: { icon: PlusIcon, tone: 'emerald', group: 'health' },
    intake: { icon: CalendarIcon, tone: 'emerald', group: 'health' },
};

export const reportGroups: {
    key: ReportStyle['group'];
    title: string;
    description: string;
}[] = [
    {
        key: 'overview',
        title: 'Overview',
        description: 'The whole database at a glance.',
    },
    {
        key: 'placements',
        title: 'Placements',
        description:
            'Where dogs are now, and their foster and adoption history.',
    },
    {
        key: 'health',
        title: 'Health & Intake',
        description: 'Medical status and how dogs came to SCAS.',
    },
];

export const reportToneClasses: Record<ReportTone, string> = {
    brand: 'bg-brand-100 text-brand-700 ring-brand-200 dark:bg-brand-900/60 dark:text-brand-300 dark:ring-brand-800',
    sky: 'bg-sky-100 text-sky-700 ring-sky-200 dark:bg-sky-900/40 dark:text-sky-300 dark:ring-sky-800',
    rose: 'bg-rose-100 text-rose-700 ring-rose-200 dark:bg-rose-900/40 dark:text-rose-300 dark:ring-rose-800',
    amber: 'bg-warning-50 text-warning-700 ring-warning-500/30 dark:bg-warning-700/20 dark:text-warning-500 dark:ring-warning-700/40',
    emerald:
        'bg-success-50 text-success-700 ring-success-500/30 dark:bg-success-700/20 dark:text-success-500 dark:ring-success-700/40',
};

export const reportFormats = [
    { format: 'csv', label: 'CSV' },
    { format: 'excel', label: 'Excel' },
    { format: 'pdf', label: 'PDF' },
] as const;

export function reportStyle(key: string): ReportStyle {
    return reportStyles[key] ?? reportStyles.all_dogs;
}
