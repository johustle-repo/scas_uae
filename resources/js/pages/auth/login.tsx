import { store } from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import {
    EyeIcon,
    HeartIcon,
    HomeIcon,
    KeyIcon,
    PawIcon,
    UserIcon,
} from '@/components/icons';
import { AppLogo } from '@/components/app-logo';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Form } from '@inertiajs/react';
import { useState } from 'react';

const highlights = [
    {
        icon: PawIcon,
        title: 'One central record',
        text: 'Every rescue dog, from intake onward.',
    },
    {
        icon: HomeIcon,
        title: 'Foster & adoption',
        text: 'Placement history at a glance.',
    },
    {
        icon: HeartIcon,
        title: 'Health details',
        text: 'Medical, vaccination and microchip info.',
    },
];

/** Scattered icons decorating the background behind the login form. */
const formDecorations = [
    { icon: PawIcon, left: '8%', top: '12%', size: 44, rotate: -20 },
    { icon: HeartIcon, left: '22%', top: '28%', size: 22, rotate: -12 },
    { icon: PawIcon, left: '84%', top: '18%', size: 32, rotate: 25 },
    { icon: HeartIcon, left: '90%', top: '44%', size: 26, rotate: 15 },
    { icon: PawIcon, left: '6%', top: '58%', size: 30, rotate: 10 },
    { icon: HomeIcon, left: '14%', top: '82%', size: 28, rotate: -8 },
    { icon: PawIcon, left: '80%', top: '74%', size: 48, rotate: -30 },
    { icon: HeartIcon, left: '66%', top: '8%', size: 18, rotate: 8 },
];

/** Positions for the decorative paw-print trail walking across the hero panel. */
const pawTrail = [
    { left: '8%', top: '72%', rotate: 20, opacity: 0.05 },
    { left: '18%', top: '62%', rotate: 35, opacity: 0.07 },
    { left: '30%', top: '68%', rotate: 25, opacity: 0.09 },
    { left: '41%', top: '57%', rotate: 40, opacity: 0.11 },
    { left: '53%', top: '63%', rotate: 30, opacity: 0.13 },
    { left: '64%', top: '51%', rotate: 45, opacity: 0.15 },
];

export default function Login() {
    const [isPasswordVisible, setIsPasswordVisible] = useState(false);

    return (
        <>
            <Head title="Log in" />
            <div className="flex min-h-dvh bg-canvas dark:bg-neutral-950">
                <aside className="relative hidden w-[46%] flex-col justify-between overflow-hidden bg-linear-to-br from-brand-700 via-brand-900 to-brand-950 p-12 text-white lg:flex">
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute inset-0 opacity-[0.08] [background-image:radial-gradient(circle,white_1px,transparent_1px)] [background-size:22px_22px]"
                    />
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute -top-32 -left-24 size-96 rounded-full bg-brand-400/30 blur-3xl"
                    />
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute -right-24 -bottom-40 size-[28rem] rounded-full bg-brand-500/25 blur-3xl"
                    />
                    {pawTrail.map((paw) => (
                        <PawIcon
                            key={paw.left}
                            aria-hidden="true"
                            className="pointer-events-none absolute size-10 text-white"
                            style={{
                                left: paw.left,
                                top: paw.top,
                                opacity: paw.opacity,
                                transform: `rotate(${paw.rotate}deg)`,
                            }}
                        />
                    ))}
                    <PawIcon
                        aria-hidden="true"
                        className="pointer-events-none absolute -right-20 -bottom-20 size-96 text-white/[0.06]"
                        strokeWidth={0.75}
                    />

                    <div className="relative flex items-center gap-3">
                        <span className="flex size-12 items-center justify-center rounded-2xl bg-white p-1.5 shadow-lg ring-4 shadow-brand-950/30 ring-white/15">
                            <AppLogo className="size-full" />
                        </span>
                        <p className="text-sm leading-tight font-semibold tracking-wide">
                            Second Chance
                            <br />
                            <span className="text-brand-200">
                                Animal Sanctuary
                            </span>
                        </p>
                    </div>

                    <div className="relative">
                        <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium text-brand-100 backdrop-blur">
                            <span className="size-1.5 rounded-full bg-emerald-400 shadow-[0_0_8px] shadow-emerald-400" />
                            SCAS Dog Database
                        </span>
                        <h2 className="mt-5 max-w-md text-4xl leading-[1.1] font-bold tracking-tight xl:text-5xl">
                            Giving every dog a{' '}
                            <span className="bg-linear-to-r from-brand-200 to-white bg-clip-text text-transparent">
                                second chance.
                            </span>
                        </h2>
                        <p className="mt-4 max-w-sm text-sm leading-relaxed text-brand-100/80">
                            The shared home for rescue records, placements and
                            care across the sanctuary team.
                        </p>

                        <ul className="mt-10 grid max-w-lg gap-3">
                            {highlights.map((item) => (
                                <li
                                    key={item.title}
                                    className="flex items-center gap-4 rounded-2xl border border-white/10 bg-white/[0.07] p-3.5 backdrop-blur-sm transition hover:bg-white/[0.11]"
                                >
                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/15 text-white ring-1 ring-white/20">
                                        <item.icon className="size-5" />
                                    </span>
                                    <div>
                                        <p className="text-sm font-semibold">
                                            {item.title}
                                        </p>
                                        <p className="text-xs text-brand-100/75">
                                            {item.text}
                                        </p>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <p className="relative text-xs text-brand-200/80">
                        © {new Date().getFullYear()} Second Chance Animal
                        Sanctuary · United Arab Emirates
                    </p>
                </aside>

                <main className="relative flex flex-1 items-center justify-center overflow-hidden px-4 py-10">
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute inset-0 bg-linear-to-br from-brand-50 via-canvas to-brand-100/70 dark:from-neutral-950 dark:via-neutral-950 dark:to-brand-950/40"
                    />
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute inset-0 opacity-70 [background-image:linear-gradient(to_right,var(--color-brand-200)_1px,transparent_1px),linear-gradient(to_bottom,var(--color-brand-200)_1px,transparent_1px)] [background-size:48px_48px] [mask-image:radial-gradient(ellipse_at_center,black_10%,transparent_65%)] dark:opacity-10"
                    />
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute -top-24 -right-24 size-96 rounded-full bg-brand-300/40 blur-3xl dark:bg-brand-700/25"
                    />
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute -bottom-32 -left-20 size-[26rem] rounded-full bg-brand-200/60 blur-3xl dark:bg-brand-800/20"
                    />
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute top-1/2 left-1/2 size-[34rem] -translate-x-1/2 -translate-y-1/2 rounded-full border border-brand-200/70 dark:border-brand-900/40"
                    />
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute top-1/2 left-1/2 size-[46rem] -translate-x-1/2 -translate-y-1/2 rounded-full border border-dashed border-brand-200/60 dark:border-brand-900/30"
                    />
                    {formDecorations.map(
                        ({ icon: DecorationIcon, ...item }) => (
                            <DecorationIcon
                                key={`${item.left}-${item.top}`}
                                aria-hidden="true"
                                className="pointer-events-none absolute text-brand-400/40 dark:text-brand-500/20"
                                style={{
                                    left: item.left,
                                    top: item.top,
                                    width: item.size,
                                    height: item.size,
                                    transform: `rotate(${item.rotate}deg)`,
                                }}
                            />
                        ),
                    )}
                    <svg
                        aria-hidden="true"
                        viewBox="0 0 1440 320"
                        preserveAspectRatio="none"
                        className="pointer-events-none absolute inset-x-0 bottom-0 h-40 w-full text-brand-200/50 dark:text-brand-950/40"
                    >
                        <path
                            fill="currentColor"
                            d="M0 224l60-16c60-16 180-48 300-42.7C480 171 600 213 720 218.7 840 224 960 192 1080 170.7 1200 149 1320 139 1380 133.3l60-5.3V320H0Z"
                        />
                    </svg>
                    <svg
                        aria-hidden="true"
                        viewBox="0 0 1440 320"
                        preserveAspectRatio="none"
                        className="pointer-events-none absolute inset-x-0 bottom-0 h-28 w-full text-brand-300/40 dark:text-brand-900/40"
                    >
                        <path
                            fill="currentColor"
                            d="M0 160l80 26.7C160 213 320 267 480 256s320-85 480-96 320 43 400 64l80 21.3V320H0Z"
                        />
                    </svg>

                    <div className="relative w-full max-w-md">
                        <div className="rounded-3xl border border-white/60 bg-white/80 p-8 shadow-xl shadow-brand-900/10 backdrop-blur-xl sm:p-10 dark:border-neutral-800 dark:bg-neutral-900/80 dark:shadow-black/40">
                            <div className="mb-8">
                                <span className="mb-6 flex size-14 items-center justify-center rounded-2xl bg-white p-2 shadow-lg ring-1 shadow-brand-800/15 ring-brand-100 dark:ring-white/10">
                                    <AppLogo className="size-full" />
                                </span>
                                <h1 className="text-3xl font-bold tracking-tight text-neutral-900 dark:text-neutral-50">
                                    Welcome back
                                </h1>
                                <p className="mt-1.5 text-sm text-neutral-500 dark:text-neutral-400">
                                    Log in to the SCAS Dog Database.
                                </p>
                            </div>

                            <Form
                                action={store()}
                                resetOnSuccess={['password']}
                            >
                                {({ errors, processing }) => (
                                    <div className="space-y-5">
                                        <div>
                                            <Label htmlFor="email">
                                                Email Address
                                            </Label>
                                            <div className="relative">
                                                <UserIcon className="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-neutral-400" />
                                                <Input
                                                    id="email"
                                                    name="email"
                                                    type="email"
                                                    required
                                                    autoFocus
                                                    autoComplete="username"
                                                    placeholder="you@scas.ae"
                                                    className="h-11 rounded-xl pl-10"
                                                />
                                            </div>
                                            {errors.email && (
                                                <p className="mt-1.5 text-xs text-red-600">
                                                    {errors.email}
                                                </p>
                                            )}
                                        </div>

                                        <div>
                                            <Label htmlFor="password">
                                                Password
                                            </Label>
                                            <div className="relative">
                                                <KeyIcon className="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-neutral-400" />
                                                <Input
                                                    id="password"
                                                    name="password"
                                                    type={
                                                        isPasswordVisible
                                                            ? 'text'
                                                            : 'password'
                                                    }
                                                    required
                                                    autoComplete="current-password"
                                                    placeholder="••••••••"
                                                    className="h-11 rounded-xl pr-11 pl-10"
                                                />
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setIsPasswordVisible(
                                                            (visible) =>
                                                                !visible,
                                                        )
                                                    }
                                                    aria-label={
                                                        isPasswordVisible
                                                            ? 'Hide password'
                                                            : 'Show password'
                                                    }
                                                    aria-pressed={
                                                        isPasswordVisible
                                                    }
                                                    className={`absolute top-1/2 right-2 flex size-8 -translate-y-1/2 items-center justify-center rounded-lg transition hover:bg-neutral-100 dark:hover:bg-neutral-800 ${isPasswordVisible ? 'text-brand-600 dark:text-brand-400' : 'text-neutral-400'}`}
                                                >
                                                    <EyeIcon className="size-4" />
                                                </button>
                                            </div>
                                            {errors.password && (
                                                <p className="mt-1.5 text-xs text-red-600">
                                                    {errors.password}
                                                </p>
                                            )}
                                        </div>

                                        <label className="flex cursor-pointer items-center gap-2 text-sm text-neutral-600 select-none dark:text-neutral-400">
                                            <Checkbox
                                                name="remember"
                                                value="1"
                                                className="size-4"
                                            />
                                            Keep me signed in
                                        </label>

                                        <Button
                                            type="submit"
                                            className="group h-11 w-full rounded-xl bg-linear-to-r from-brand-700 to-brand-900 text-sm shadow-lg shadow-brand-800/25 transition hover:shadow-brand-800/40 hover:brightness-110"
                                            disabled={processing}
                                        >
                                            {processing ? (
                                                'Logging in…'
                                            ) : (
                                                <span className="flex items-center justify-center gap-2">
                                                    Log In
                                                    <PawIcon className="size-4 transition-transform group-hover:translate-x-0.5 group-hover:rotate-12" />
                                                </span>
                                            )}
                                        </Button>
                                    </div>
                                )}
                            </Form>
                        </div>

                        <p className="mt-6 flex items-center justify-center gap-2 text-xs text-neutral-500 dark:text-neutral-400">
                            <KeyIcon className="size-3.5" />
                            Authorised SCAS team members only.
                        </p>
                    </div>
                </main>
            </div>
        </>
    );
}
