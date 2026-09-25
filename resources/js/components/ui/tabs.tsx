import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

export interface TabItem {
    value: string;
    label: string;
}

interface TabsProps {
    items: TabItem[];
    value: string;
    onChange: (value: string) => void;
    className?: string;
}

export function Tabs({ items, value, onChange, className }: TabsProps) {
    return (
        <div
            role="tablist"
            className={cn(
                'flex gap-1 overflow-x-auto rounded-lg border border-neutral-200 bg-neutral-50 p-1 dark:border-neutral-800 dark:bg-neutral-900',
                className,
            )}
        >
            {items.map((item) => (
                <button
                    key={item.value}
                    type="button"
                    role="tab"
                    aria-selected={value === item.value}
                    onClick={() => onChange(item.value)}
                    className={cn(
                        'shrink-0 rounded-md px-4 py-1.5 text-xs font-medium transition-colors',
                        value === item.value
                            ? 'bg-brand-800 text-white shadow-sm dark:bg-brand-600'
                            : 'text-neutral-500 hover:bg-white hover:text-neutral-800 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-neutral-100',
                    )}
                >
                    {item.label}
                </button>
            ))}
        </div>
    );
}

export function TabPanel({ active, children }: { active: boolean; children: ReactNode }) {
    if (!active) {
        return null;
    }

    return <div className="pt-6">{children}</div>;
}
