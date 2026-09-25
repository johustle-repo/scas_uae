import {
    CheckIcon,
    MonitorIcon,
    MoonIcon,
    SunIcon,
    type IconProps,
} from '@/components/icons';
import { COLOR_THEMES, useAppearance, type Appearance } from '@/lib/appearance';
import { cn } from '@/lib/utils';
import type { ComponentType } from 'react';

const MODES: {
    value: Appearance;
    label: string;
    icon: ComponentType<IconProps>;
}[] = [
    { value: 'light', label: 'Light', icon: SunIcon },
    { value: 'dark', label: 'Dark', icon: MoonIcon },
    { value: 'system', label: 'System', icon: MonitorIcon },
];

export function AppearancePicker() {
    const { appearance, setAppearance, theme, setTheme } = useAppearance();

    return (
        <div className="space-y-5">
            <fieldset>
                <legend className="mb-2 text-xs font-medium text-neutral-700 dark:text-neutral-300">
                    Mode
                </legend>
                <div className="grid grid-cols-3 gap-1 rounded-lg border border-neutral-200 bg-neutral-50 p-1 dark:border-neutral-700 dark:bg-neutral-800/60">
                    {MODES.map((mode) => (
                        <button
                            key={mode.value}
                            type="button"
                            onClick={() => setAppearance(mode.value)}
                            aria-pressed={appearance === mode.value}
                            className={cn(
                                'flex items-center justify-center gap-1.5 rounded-md px-2 py-1.5 text-xs font-medium transition-colors focus-visible:outline-2 focus-visible:outline-brand-600',
                                appearance === mode.value
                                    ? 'bg-white text-brand-800 shadow-sm dark:bg-neutral-900 dark:text-brand-200'
                                    : 'text-neutral-500 hover:text-neutral-800 dark:text-neutral-400 dark:hover:text-neutral-100',
                            )}
                        >
                            <mode.icon className="size-3.5" />
                            {mode.label}
                        </button>
                    ))}
                </div>
            </fieldset>

            <fieldset>
                <legend className="mb-2 text-xs font-medium text-neutral-700 dark:text-neutral-300">
                    Accent colour
                </legend>
                <div className="flex flex-wrap gap-3">
                    {COLOR_THEMES.map((option) => (
                        <button
                            key={option.value}
                            type="button"
                            onClick={() => setTheme(option.value)}
                            aria-pressed={theme === option.value}
                            aria-label={`${option.label} theme`}
                            title={option.label}
                            className="group flex flex-col items-center gap-1.5 focus-visible:outline-none"
                        >
                            <span
                                className={cn(
                                    'flex size-9 items-center justify-center rounded-full text-white ring-offset-2 transition-shadow group-focus-visible:ring-2 group-focus-visible:ring-neutral-400 dark:ring-offset-neutral-900',
                                    theme === option.value &&
                                        'ring-2 ring-neutral-900 dark:ring-white',
                                )}
                                style={{ backgroundColor: option.swatch }}
                            >
                                {theme === option.value && (
                                    <CheckIcon
                                        className="size-4"
                                        strokeWidth={2.5}
                                    />
                                )}
                            </span>
                            <span className="text-[11px] text-neutral-600 dark:text-neutral-400">
                                {option.label}
                            </span>
                        </button>
                    ))}
                </div>
            </fieldset>
        </div>
    );
}
