/** Mirrors App\Services\DogAlerts. */
export type AlertTone = 'danger' | 'warning' | 'info' | 'neutral';

export interface AlertItem {
    dog_id: number;
    scas_id: string;
    name: string;
    detail: string;
}

export interface AlertGroup {
    key: string;
    title: string;
    tone: AlertTone;
    items: AlertItem[];
}

export interface AlertSummary {
    key: string;
    title: string;
    tone: AlertTone;
    count: number;
}

export const alertToneClasses: Record<AlertTone, string> = {
    danger: 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-900/30 dark:text-red-300 dark:ring-red-800',
    warning:
        'bg-warning-50 text-warning-700 ring-warning-500/30 dark:bg-warning-700/20 dark:text-warning-500 dark:ring-warning-700/40',
    info: 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-900/30 dark:text-sky-300 dark:ring-sky-800',
    neutral:
        'bg-neutral-100 text-neutral-600 ring-neutral-200 dark:bg-neutral-800 dark:text-neutral-300 dark:ring-neutral-700',
};
