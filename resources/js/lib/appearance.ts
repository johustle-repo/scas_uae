import { useCallback, useEffect, useState, useSyncExternalStore } from 'react';

export type Appearance = 'light' | 'dark' | 'system';
export type ColorTheme = 'purple' | 'blue' | 'teal' | 'green' | 'rose';

/** Kept in sync with HandleAppearance::THEMES and the palettes in app.css. */
export const COLOR_THEMES: {
    value: ColorTheme;
    label: string;
    swatch: string;
}[] = [
    { value: 'purple', label: 'Purple', swatch: 'oklch(0.4 0.19 299)' },
    { value: 'blue', label: 'Blue', swatch: 'oklch(0.4 0.18 262)' },
    { value: 'teal', label: 'Teal', swatch: 'oklch(0.4 0.114 192)' },
    { value: 'green', label: 'Green', swatch: 'oklch(0.4 0.124 152)' },
    { value: 'rose', label: 'Rose', swatch: 'oklch(0.4 0.18 12)' },
];

const ONE_YEAR = 60 * 60 * 24 * 365;
const darkQuery = () => window.matchMedia('(prefers-color-scheme: dark)');

function readCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : null;
}

function writeCookie(name: string, value: string): void {
    try {
        document.cookie = `${name}=${value}; path=/; max-age=${ONE_YEAR}; SameSite=Lax`;
    } catch {
        // Ignore cookie write failures (e.g. blocked storage).
    }
}

function applyAppearance(appearance: Appearance): void {
    const isDark =
        appearance === 'dark' ||
        (appearance === 'system' && darkQuery().matches);

    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
}

function currentAppearance(): Appearance {
    const value = readCookie('appearance');

    return value === 'light' || value === 'dark' ? value : 'system';
}

function currentTheme(): ColorTheme {
    const value = document.documentElement.dataset.theme;

    return COLOR_THEMES.some((theme) => theme.value === value)
        ? (value as ColorTheme)
        : 'purple';
}

/** Shared across every component using the hook, so all controls stay in sync. */
const listeners = new Set<() => void>();

function subscribe(listener: () => void): () => void {
    listeners.add(listener);

    return () => listeners.delete(listener);
}

function notify(): void {
    listeners.forEach((listener) => listener());
}

/**
 * The viewer's colour mode and accent theme. Saved in cookies so the server
 * renders the right colours on the next page load.
 */
export function useAppearance() {
    const appearance = useSyncExternalStore(subscribe, currentAppearance);
    const theme = useSyncExternalStore(subscribe, currentTheme);
    const [systemIsDark, setSystemIsDark] = useState(() => darkQuery().matches);

    useEffect(() => {
        const query = darkQuery();

        function follow() {
            setSystemIsDark(query.matches);

            if (currentAppearance() === 'system') {
                applyAppearance('system');
            }
        }

        query.addEventListener('change', follow);

        return () => query.removeEventListener('change', follow);
    }, []);

    const setAppearance = useCallback((value: Appearance) => {
        writeCookie('appearance', value);
        applyAppearance(value);
        notify();
    }, []);

    const setTheme = useCallback((value: ColorTheme) => {
        writeCookie('theme', value);
        document.documentElement.dataset.theme = value;
        notify();
    }, []);

    const isDark =
        appearance === 'dark' || (appearance === 'system' && systemIsDark);

    return { appearance, setAppearance, theme, setTheme, isDark };
}
