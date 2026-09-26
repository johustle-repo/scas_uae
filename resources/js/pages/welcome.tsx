import {
    ArrowRightIcon,
    CalendarIcon,
    CheckIcon,
    GenderIcon,
    GlobeIcon,
    HeartIcon,
    HomeIcon,
    PawIcon,
    RulerIcon,
    SearchIcon,
    StarIcon,
    UsersIcon,
    type IconProps,
} from '@/components/icons';
import { store as storeApplication } from '@/actions/App/Http/Controllers/AdoptionApplicationController';
import { AppLogo } from '@/components/app-logo';
import { Checkbox } from '@/components/ui/checkbox';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Modal } from '@/components/ui/modal';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { dashboard, login } from '@/routes';
import type { User } from '@/types/auth';
import { Form, Head, Link, usePage } from '@inertiajs/react';
import { type ComponentType, useState } from 'react';

interface PublicDog {
    id: number;
    name: string;
    breed: string | null;
    gender: 'male' | 'female' | null;
    size: string | null;
    age: number | null;
    is_puppy: boolean;
    photo_url: string | null;
    personality: string | null;
    energy_level: string | null;
    good_with: ('dogs' | 'cats' | 'children')[];
    house_trained: boolean;
    is_rehoming: boolean;
}

interface AdoptionContact {
    email?: string;
    phone?: string;
    whatsapp?: string;
    instagram?: string;
}

interface WelcomeProps {
    adoptableDogs: PublicDog[];
    happyTails: PublicDog[];
    stats: { available: number; adopted: number; inFoster: number };
    contact: AdoptionContact;
}

type DogFilter = 'all' | 'puppies' | 'small' | 'large' | 'children' | 'cats';

const FILTERS: {
    value: DogFilter;
    label: string;
    test: (dog: PublicDog) => boolean;
}[] = [
    { value: 'all', label: 'All dogs', test: () => true },
    { value: 'puppies', label: 'Puppies', test: (dog) => dog.is_puppy },
    {
        value: 'small',
        label: 'Small & medium',
        test: (dog) => dog.size === 'Small' || dog.size === 'Medium',
    },
    {
        value: 'large',
        label: 'Large',
        test: (dog) => dog.size === 'Large' || dog.size === 'Giant',
    },
    {
        value: 'children',
        label: 'Good with kids',
        test: (dog) => dog.good_with.includes('children'),
    },
    {
        value: 'cats',
        label: 'Good with cats',
        test: (dog) => dog.good_with.includes('cats'),
    },
];

const PAGE_SIZE = 12;

const GOOD_WITH_LABELS: Record<PublicDog['good_with'][number], string> = {
    dogs: 'Dogs',
    cats: 'Cats',
    children: 'Kids',
};

const reasons: {
    icon: ComponentType<IconProps>;
    title: string;
    text: string;
}[] = [
    {
        icon: HeartIcon,
        title: 'Save a life',
        text: 'Every adoption frees a space at the sanctuary for the next dog who needs rescuing.',
    },
    {
        icon: UsersIcon,
        title: 'Know who you are meeting',
        text: 'Our team knows each dog — their quirks, energy and who they get along with.',
    },
    {
        icon: GlobeIcon,
        title: 'Here or abroad',
        text: 'We place dogs with loving families across the UAE and internationally.',
    },
    {
        icon: StarIcon,
        title: 'Gain a best friend',
        text: 'Rescue dogs have so much love to give to the family who gives them a chance.',
    },
];

const adoptionSteps: { title: string; text: string }[] = [
    {
        title: 'Find your match',
        text: 'Browse the dogs below and look for a personality that fits your home.',
    },
    {
        title: 'Get in touch',
        text: 'Tell us which dog caught your eye and a little about your family.',
    },
    {
        title: 'Meet & greet',
        text: "Spend time together so you both know it's the right fit.",
    },
    {
        title: 'Welcome home',
        text: 'Take your new best friend home and start your story together.',
    },
];

export default function Welcome({
    adoptableDogs,
    happyTails,
    stats,
    contact,
}: WelcomeProps) {
    const { auth } = usePage<{ auth: { user: User | null } }>().props;
    const [filter, setFilter] = useState<DogFilter>('all');
    const [visibleCount, setVisibleCount] = useState(PAGE_SIZE);
    const [selectedDog, setSelectedDog] = useState<PublicDog | null>(null);

    const activeFilter = FILTERS.find((item) => item.value === filter)!;
    const filteredDogs = adoptableDogs.filter(activeFilter.test);
    const heroDogs = adoptableDogs.slice(0, 3);

    function changeFilter(value: DogFilter) {
        setFilter(value);
        setVisibleCount(PAGE_SIZE);
    }

    return (
        <>
            <Head title="Adopt a Rescue Dog" />

            <div className="min-h-dvh bg-canvas text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">
                <header className="absolute inset-x-0 top-0 z-20">
                    <nav className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-5 sm:px-6 lg:px-8">
                        <a
                            href="#top"
                            className="flex items-center gap-3 text-white"
                        >
                            <span className="flex size-11 items-center justify-center rounded-xl bg-white p-1.5 shadow-lg ring-4 shadow-black/20 ring-white/15">
                                <AppLogo className="size-full" />
                            </span>
                            <span className="hidden text-sm leading-tight font-semibold sm:block">
                                Second Chance
                                <br />
                                <span className="font-medium text-brand-200">
                                    Animal Sanctuary
                                </span>
                            </span>
                        </a>
                        <div className="flex items-center gap-1 text-sm font-medium text-brand-100">
                            <a
                                href="#dogs"
                                className="hidden rounded-lg px-3 py-2 hover:bg-white/10 hover:text-white md:block"
                            >
                                Meet the dogs
                            </a>
                            <a
                                href="#how-to-adopt"
                                className="hidden rounded-lg px-3 py-2 hover:bg-white/10 hover:text-white md:block"
                            >
                                How to adopt
                            </a>
                            <a
                                href="#dogs"
                                className="ml-2 inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 font-semibold text-brand-900 shadow-lg shadow-black/20 transition hover:bg-brand-50"
                            >
                                <HeartIcon className="size-4" />
                                Adopt
                            </a>
                        </div>
                    </nav>
                </header>

                <section
                    id="top"
                    className="relative isolate overflow-hidden bg-linear-to-br from-brand-700 via-brand-900 to-brand-950 pt-32 pb-20 text-white sm:pt-40 lg:pb-28"
                >
                    <HeroDecoration />

                    <div className="mx-auto grid max-w-7xl items-center gap-14 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                        <div>
                            <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium text-brand-100 backdrop-blur">
                                <span className="size-1.5 animate-pulse rounded-full bg-emerald-400 shadow-[0_0_8px] shadow-emerald-400" />
                                {stats.available}{' '}
                                {stats.available === 1 ? 'dog is' : 'dogs are'}{' '}
                                waiting for a home
                            </span>
                            <h1 className="mt-6 text-4xl leading-[1.05] font-bold tracking-tight sm:text-5xl xl:text-6xl">
                                Every dog deserves a{' '}
                                <span className="bg-linear-to-r from-brand-200 to-white bg-clip-text text-transparent">
                                    second chance.
                                </span>
                            </h1>
                            <p className="mt-6 max-w-xl text-base leading-relaxed text-brand-100/85 sm:text-lg">
                                Our rescues were found on streets, farms and in
                                the desert across the UAE. Today they are safe,
                                cared for — and ready to meet the family who
                                will love them for life.
                            </p>
                            <div className="mt-10 flex flex-wrap items-center gap-4">
                                <a
                                    href="#dogs"
                                    className="group inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-semibold text-brand-900 shadow-xl shadow-black/20 transition hover:bg-brand-50"
                                >
                                    Meet the dogs
                                    <ArrowRightIcon className="size-4 transition-transform group-hover:translate-x-0.5" />
                                </a>
                                <a
                                    href="#how-to-adopt"
                                    className="inline-flex items-center gap-2 rounded-xl border border-white/20 px-5 py-3 text-sm font-medium text-white transition hover:bg-white/10"
                                >
                                    How adoption works
                                </a>
                            </div>

                            <dl className="mt-12 grid max-w-lg grid-cols-3 gap-4">
                                {[
                                    {
                                        label: 'Looking for a home',
                                        value: stats.available,
                                    },
                                    { label: 'Adopted', value: stats.adopted },
                                    {
                                        label: 'In foster care',
                                        value: stats.inFoster,
                                    },
                                ].map((stat) => (
                                    <div
                                        key={stat.label}
                                        className="rounded-2xl border border-white/10 bg-white/[0.07] p-4 backdrop-blur-sm"
                                    >
                                        <dd className="text-2xl font-bold sm:text-3xl">
                                            {stat.value}
                                        </dd>
                                        <dt className="mt-1 text-xs text-brand-100/80">
                                            {stat.label}
                                        </dt>
                                    </div>
                                ))}
                            </dl>
                        </div>

                        {heroDogs.length > 0 && (
                            <HeroCollage
                                dogs={heroDogs}
                                onSelect={setSelectedDog}
                            />
                        )}
                    </div>
                </section>

                <section
                    id="dogs"
                    className="mx-auto max-w-7xl scroll-mt-6 px-4 py-24 sm:px-6 lg:px-8"
                >
                    <SectionIntro
                        eyebrow="Meet the dogs"
                        title="Looking for their forever home"
                        text="Each of these dogs is waiting for someone like you. Tap a dog to learn about their personality."
                    />

                    <div
                        role="group"
                        aria-label="Filter dogs"
                        className="mt-10 flex flex-wrap justify-center gap-2"
                    >
                        {FILTERS.map((item) => (
                            <button
                                key={item.value}
                                type="button"
                                onClick={() => changeFilter(item.value)}
                                aria-pressed={filter === item.value}
                                className={cn(
                                    'rounded-full border px-4 py-2 text-sm font-medium transition',
                                    filter === item.value
                                        ? 'border-brand-700 bg-brand-700 text-white shadow-md shadow-brand-800/20 dark:border-brand-500 dark:bg-brand-600'
                                        : 'border-neutral-200 bg-white text-neutral-600 hover:border-brand-300 hover:text-brand-700 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-300 dark:hover:border-brand-700 dark:hover:text-brand-300',
                                )}
                            >
                                {item.label}
                                <span className="ml-1.5 opacity-60">
                                    {adoptableDogs.filter(item.test).length}
                                </span>
                            </button>
                        ))}
                    </div>

                    {filteredDogs.length === 0 ? (
                        <div className="mt-12 flex flex-col items-center rounded-3xl border border-dashed border-neutral-300 px-6 py-16 text-center dark:border-neutral-700">
                            <span className="flex size-14 items-center justify-center rounded-2xl bg-brand-100 text-brand-700 dark:bg-brand-900/60 dark:text-brand-300">
                                <SearchIcon className="size-6" />
                            </span>
                            <p className="mt-4 font-semibold">
                                {adoptableDogs.length === 0
                                    ? 'Every dog has found a home — for now!'
                                    : 'No dogs match this filter right now'}
                            </p>
                            <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                New rescues arrive often, so please check back
                                soon.
                            </p>
                            {filter !== 'all' && (
                                <button
                                    type="button"
                                    onClick={() => changeFilter('all')}
                                    className="mt-4 text-sm font-medium text-brand-700 hover:underline dark:text-brand-300"
                                >
                                    Show all dogs
                                </button>
                            )}
                        </div>
                    ) : (
                        <>
                            <div className="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                {filteredDogs
                                    .slice(0, visibleCount)
                                    .map((dog) => (
                                        <DogCard
                                            key={dog.id}
                                            dog={dog}
                                            onSelect={() => setSelectedDog(dog)}
                                        />
                                    ))}
                            </div>
                            {filteredDogs.length > visibleCount && (
                                <div className="mt-10 text-center">
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setVisibleCount(
                                                (count) => count + PAGE_SIZE,
                                            )
                                        }
                                        className="inline-flex items-center gap-2 rounded-xl border border-brand-200 bg-white px-6 py-3 text-sm font-semibold text-brand-800 transition hover:bg-brand-50 dark:border-brand-800 dark:bg-neutral-900 dark:text-brand-200 dark:hover:bg-brand-950/40"
                                    >
                                        Show more dogs (
                                        {filteredDogs.length - visibleCount})
                                    </button>
                                </div>
                            )}
                        </>
                    )}
                </section>

                <section className="border-y border-brand-100 bg-linear-to-b from-brand-50 to-white py-24 dark:border-neutral-800 dark:from-brand-950/40 dark:to-neutral-950">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <SectionIntro
                            eyebrow="Why adopt"
                            title="Adopt, don't shop"
                            text="When you adopt from SCAS you change two lives: the dog you bring home, and the next rescue who takes their place."
                        />
                        <div className="mt-14 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                            {reasons.map((reason) => (
                                <div
                                    key={reason.title}
                                    className="rounded-2xl border border-neutral-200/80 bg-white p-6 shadow-sm shadow-brand-900/5 dark:border-neutral-800 dark:bg-neutral-900"
                                >
                                    <span className="flex size-11 items-center justify-center rounded-xl bg-linear-to-br from-brand-600 to-brand-900 text-white shadow-lg shadow-brand-800/20">
                                        <reason.icon className="size-5" />
                                    </span>
                                    <h3 className="mt-5 font-semibold">
                                        {reason.title}
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
                                        {reason.text}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                <section
                    id="how-to-adopt"
                    className="mx-auto max-w-7xl scroll-mt-6 px-4 py-24 sm:px-6 lg:px-8"
                >
                    <SectionIntro
                        eyebrow="How to adopt"
                        title="Four steps to a new best friend"
                        text="We'll guide you through every step so you and your new dog get the best possible start."
                    />
                    <ol className="relative mt-14 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                        {adoptionSteps.map((step, index) => (
                            <li
                                key={step.title}
                                className="relative rounded-2xl border border-neutral-200/80 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900"
                            >
                                <span className="flex size-10 items-center justify-center rounded-full bg-brand-700 text-sm font-bold text-white ring-4 ring-brand-100 dark:bg-brand-600 dark:ring-brand-950">
                                    {index + 1}
                                </span>
                                <h3 className="mt-5 font-semibold">
                                    {step.title}
                                </h3>
                                <p className="mt-2 text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
                                    {step.text}
                                </p>
                            </li>
                        ))}
                    </ol>
                    <ContactOptions
                        contact={contact}
                        className="mt-10 justify-center"
                    />
                </section>

                {happyTails.length > 0 && (
                    <section className="border-y border-brand-100 bg-linear-to-b from-white to-brand-50 py-24 dark:border-neutral-800 dark:from-neutral-950 dark:to-brand-950/30">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <SectionIntro
                                eyebrow="Happy tails"
                                title="Already home and loved"
                                text="Some of the dogs who found their families through SCAS."
                            />
                            <div className="mt-14 grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-6">
                                {happyTails.map((dog) => (
                                    <figure
                                        key={dog.id}
                                        className="group text-center"
                                    >
                                        <div className="relative overflow-hidden rounded-2xl shadow-md ring-1 shadow-brand-900/10 ring-neutral-200 dark:ring-neutral-800">
                                            <DogPhoto
                                                dog={dog}
                                                className="aspect-square transition-transform duration-500 group-hover:scale-105"
                                            />
                                            <span className="absolute top-2 right-2 flex size-7 items-center justify-center rounded-full bg-white text-rose-500 shadow">
                                                <HeartIcon className="size-4 fill-current" />
                                            </span>
                                        </div>
                                        <figcaption className="mt-3 text-sm font-semibold">
                                            {dog.name}
                                        </figcaption>
                                    </figure>
                                ))}
                            </div>
                        </div>
                    </section>
                )}

                <section className="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
                    <div className="relative isolate overflow-hidden rounded-3xl bg-linear-to-br from-brand-700 via-brand-800 to-brand-950 px-6 py-16 text-center text-white shadow-2xl shadow-brand-900/20 sm:px-12">
                        <HeroDecoration />
                        <PawIcon className="mx-auto size-12 text-brand-200" />
                        <h2 className="mt-5 text-3xl font-bold tracking-tight sm:text-4xl">
                            Your new best friend is waiting.
                        </h2>
                        <p className="mx-auto mt-4 max-w-xl text-brand-100/85">
                            Can't adopt right now? Fostering gives a dog a warm
                            home while they wait — and sharing this page helps
                            them be seen.
                        </p>
                        <div className="mt-8 flex flex-wrap justify-center gap-3">
                            <a
                                href="#dogs"
                                className="group inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-semibold text-brand-900 shadow-xl shadow-black/20 transition hover:bg-brand-50"
                            >
                                Meet the dogs
                                <ArrowRightIcon className="size-4 transition-transform group-hover:translate-x-0.5" />
                            </a>
                        </div>
                        <ContactOptions
                            contact={contact}
                            tone="dark"
                            className="mt-6 justify-center"
                        />
                    </div>
                </section>

                <footer className="border-t border-neutral-200 dark:border-neutral-800">
                    <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-8 text-xs text-neutral-500 sm:flex-row sm:px-6 lg:px-8 dark:text-neutral-400">
                        <span className="flex items-center gap-2">
                            <PawIcon className="size-4 text-brand-600 dark:text-brand-400" />
                            © {new Date().getFullYear()} Second Chance Animal
                            Sanctuary · United Arab Emirates
                        </span>
                        <Link
                            href={auth.user ? dashboard.url() : login.url()}
                            className="font-medium hover:text-brand-700 dark:hover:text-brand-300"
                        >
                            {auth.user ? 'Staff dashboard' : 'Staff login'}
                        </Link>
                    </div>
                </footer>
            </div>

            <DogModal
                dog={selectedDog}
                contact={contact}
                onClose={() => setSelectedDog(null)}
            />
        </>
    );
}

function DogCard({ dog, onSelect }: { dog: PublicDog; onSelect: () => void }) {
    return (
        <button
            type="button"
            onClick={onSelect}
            className="group flex flex-col overflow-hidden rounded-3xl border border-neutral-200/80 bg-white text-left shadow-sm shadow-brand-900/5 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-brand-900/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 dark:border-neutral-800 dark:bg-neutral-900"
        >
            <div className="relative overflow-hidden">
                <DogPhoto
                    dog={dog}
                    className="aspect-[4/3] transition-transform duration-500 group-hover:scale-105"
                />
                <div className="absolute top-3 left-3 flex flex-wrap gap-1.5">
                    {dog.is_puppy && <PhotoTag>Puppy</PhotoTag>}
                    {dog.is_rehoming && <PhotoTag>Needs a new home</PhotoTag>}
                </div>
            </div>
            <div className="flex flex-1 flex-col p-5">
                <div className="flex items-start justify-between gap-2">
                    <h3 className="text-lg font-bold tracking-tight">
                        {dog.name}
                    </h3>
                    <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-500 transition group-hover:bg-rose-500 group-hover:text-white dark:bg-rose-500/10">
                        <HeartIcon className="size-4" />
                    </span>
                </div>
                <p className="text-sm text-neutral-500 dark:text-neutral-400">
                    {dog.breed ?? 'Mixed breed'}
                </p>
                <DogFacts dog={dog} className="mt-3" />
                {dog.personality && (
                    <p className="mt-3 line-clamp-2 text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
                        {dog.personality}
                    </p>
                )}
                <span className="mt-auto inline-flex items-center gap-1.5 pt-4 text-sm font-semibold text-brand-700 dark:text-brand-300">
                    Meet {dog.name}
                    <ArrowRightIcon className="size-4 transition-transform group-hover:translate-x-1" />
                </span>
            </div>
        </button>
    );
}

function DogModal({
    dog,
    contact,
    onClose,
}: {
    dog: PublicDog | null;
    contact: AdoptionContact;
    onClose: () => void;
}) {
    return (
        <Modal
            open={dog !== null}
            onClose={onClose}
            title={dog ? `Meet ${dog.name}` : 'Meet the dog'}
            description={dog?.breed ?? 'Mixed breed'}
            size="lg"
        >
            {dog && (
                <DogModalContent
                    key={dog.id}
                    dog={dog}
                    contact={contact}
                    onClose={onClose}
                />
            )}
        </Modal>
    );
}

function DogModalContent({
    dog,
    contact,
    onClose,
}: {
    dog: PublicDog;
    contact: AdoptionContact;
    onClose: () => void;
}) {
    const [view, setView] = useState<'details' | 'apply' | 'sent'>('details');

    if (view === 'apply') {
        return (
            <AdoptionForm
                dog={dog}
                onBack={() => setView('details')}
                onSent={() => setView('sent')}
            />
        );
    }

    if (view === 'sent') {
        return (
            <div className="flex flex-col items-center py-6 text-center">
                <span className="flex size-16 items-center justify-center rounded-full bg-success-50 text-success-600 ring-8 ring-success-50/50 dark:bg-success-700/20 dark:text-success-500 dark:ring-success-700/10">
                    <CheckIcon className="size-8" strokeWidth={2.5} />
                </span>
                <h3 className="mt-6 text-xl font-bold">
                    Thank you for applying to adopt {dog.name}!
                </h3>
                <p className="mt-2 max-w-md text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
                    Our adoption team will review your request and contact you
                    to arrange a meet & greet. Every application is read by a
                    real person, so please allow a few days.
                </p>
                <button
                    type="button"
                    onClick={onClose}
                    className="mt-6 rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800 dark:bg-brand-600 dark:hover:bg-brand-500"
                >
                    Keep browsing
                </button>
            </div>
        );
    }

    return (
        <div className="grid gap-6 md:grid-cols-2">
            <DogPhoto dog={dog} className="aspect-square rounded-2xl" />
            <div>
                <DogFacts dog={dog} />

                {dog.personality && (
                    <p className="mt-4 text-sm leading-relaxed text-neutral-700 dark:text-neutral-300">
                        {dog.personality}
                    </p>
                )}

                <dl className="mt-5 space-y-3 text-sm">
                    {dog.good_with.length > 0 && (
                        <div>
                            <dt className="text-xs font-semibold text-neutral-500 dark:text-neutral-400">
                                Gets along with
                            </dt>
                            <dd className="mt-1.5 flex flex-wrap gap-1.5">
                                {dog.good_with.map((item) => (
                                    <span
                                        key={item}
                                        className="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-700 dark:bg-success-700/20 dark:text-success-500"
                                    >
                                        <CheckIcon className="size-3" />
                                        {GOOD_WITH_LABELS[item]}
                                    </span>
                                ))}
                            </dd>
                        </div>
                    )}
                    {dog.energy_level && (
                        <div className="flex items-center justify-between">
                            <dt className="text-xs font-semibold text-neutral-500 dark:text-neutral-400">
                                Energy level
                            </dt>
                            <dd className="font-medium">{dog.energy_level}</dd>
                        </div>
                    )}
                    {dog.house_trained && (
                        <div className="flex items-center justify-between">
                            <dt className="text-xs font-semibold text-neutral-500 dark:text-neutral-400">
                                House trained
                            </dt>
                            <dd className="font-medium">Yes</dd>
                        </div>
                    )}
                </dl>

                <div className="mt-6 rounded-2xl bg-brand-50 p-4 dark:bg-brand-950/40">
                    <p className="text-sm font-semibold text-brand-900 dark:text-brand-100">
                        Interested in {dog.name}?
                    </p>
                    <p className="mt-1 text-xs text-neutral-600 dark:text-neutral-400">
                        Send an adoption request and we'll arrange a meet &
                        greet.
                    </p>
                    <button
                        type="button"
                        onClick={() => setView('apply')}
                        className="group mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-800/20 transition hover:bg-brand-800 dark:bg-brand-600 dark:hover:bg-brand-500"
                    >
                        <HeartIcon className="size-4" />
                        Apply to adopt {dog.name}
                        <ArrowRightIcon className="size-4 transition-transform group-hover:translate-x-0.5" />
                    </button>
                    {Object.keys(contact).length > 0 && (
                        <>
                            <p className="mt-3 text-center text-xs text-neutral-500 dark:text-neutral-400">
                                or contact us directly
                            </p>
                            <ContactOptions
                                contact={contact}
                                dogName={dog.name}
                                className="mt-2 justify-center"
                            />
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}

const HOME_TYPE_OPTIONS = [
    { value: 'apartment', label: 'Apartment' },
    { value: 'villa', label: 'Villa / house with garden' },
    { value: 'townhouse', label: 'Townhouse' },
    { value: 'other', label: 'Other' },
];

function AdoptionForm({
    dog,
    onBack,
    onSent,
}: {
    dog: PublicDog;
    onBack: () => void;
    onSent: () => void;
}) {
    return (
        <Form
            action={storeApplication()}
            options={{ preserveScroll: true, preserveState: true }}
            onSuccess={onSent}
            className="space-y-5"
        >
            {({ errors, processing }) => (
                <>
                    <div className="flex items-center gap-4 rounded-2xl bg-brand-50 p-3 dark:bg-brand-950/40">
                        <DogPhoto
                            dog={dog}
                            className="size-14 shrink-0 rounded-xl"
                        />
                        <div className="min-w-0">
                            <p className="text-sm font-semibold text-brand-900 dark:text-brand-100">
                                Adoption request for {dog.name}
                            </p>
                            <p className="text-xs text-neutral-600 dark:text-neutral-400">
                                Takes about 2 minutes. Fields marked * are
                                required.
                            </p>
                        </div>
                    </div>

                    <input type="hidden" name="dog_id" value={dog.id} />
                    <div aria-hidden="true" className="absolute -left-[9999px]">
                        <label htmlFor="website">Leave this empty</label>
                        <input
                            id="website"
                            name="website"
                            type="text"
                            tabIndex={-1}
                            autoComplete="off"
                        />
                    </div>

                    {errors.dog_id && (
                        <p
                            role="alert"
                            className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-950/40 dark:text-red-300"
                        >
                            {errors.dog_id}
                        </p>
                    )}

                    <fieldset className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <legend className="mb-3 text-xs font-semibold tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                            About you
                        </legend>
                        <Field
                            label="Full name *"
                            htmlFor="full_name"
                            error={errors.full_name}
                        >
                            <Input
                                id="full_name"
                                name="full_name"
                                autoComplete="name"
                                required
                            />
                        </Field>
                        <Field
                            label="Email *"
                            htmlFor="email"
                            error={errors.email}
                        >
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                autoComplete="email"
                                required
                            />
                        </Field>
                        <Field
                            label="Phone / WhatsApp *"
                            htmlFor="phone"
                            error={errors.phone}
                        >
                            <Input
                                id="phone"
                                name="phone"
                                type="tel"
                                autoComplete="tel"
                                placeholder="+971 50 123 4567"
                                required
                            />
                        </Field>
                        <Field
                            label="City / Emirate *"
                            htmlFor="city"
                            error={errors.city}
                        >
                            <Input
                                id="city"
                                name="city"
                                autoComplete="address-level2"
                                placeholder="e.g. Dubai"
                                required
                            />
                        </Field>
                    </fieldset>

                    <fieldset className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <legend className="mb-3 text-xs font-semibold tracking-wide text-neutral-500 uppercase dark:text-neutral-400">
                            Your home
                        </legend>
                        <Field
                            label="Type of home *"
                            htmlFor="home_type"
                            error={errors.home_type}
                        >
                            <Select
                                id="home_type"
                                name="home_type"
                                defaultValue=""
                                required
                            >
                                <option value="" disabled>
                                    Choose…
                                </option>
                                {HOME_TYPE_OPTIONS.map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <div className="flex flex-col justify-end gap-2 pb-1">
                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox name="has_garden" value="1" />
                                We have a garden or outdoor space
                            </label>
                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox name="has_children" value="1" />
                                There are children in our home
                            </label>
                        </div>
                        <Field
                            label="Other pets at home"
                            htmlFor="other_pets"
                            error={errors.other_pets}
                            className="sm:col-span-2"
                        >
                            <Input
                                id="other_pets"
                                name="other_pets"
                                placeholder="e.g. one cat, 4 years old"
                            />
                        </Field>
                        <Field
                            label="Experience with dogs"
                            htmlFor="experience"
                            error={errors.experience}
                            className="sm:col-span-2"
                        >
                            <Input
                                id="experience"
                                name="experience"
                                placeholder="e.g. grew up with dogs, first-time owner…"
                            />
                        </Field>
                    </fieldset>

                    <Field
                        label={`Why would you like to adopt ${dog.name}? *`}
                        htmlFor="message"
                        error={errors.message}
                    >
                        <Textarea
                            id="message"
                            name="message"
                            rows={4}
                            placeholder="Tell us about your daily routine, who lives with you and what you're looking for in a dog."
                            required
                        />
                    </Field>

                    <div>
                        <label className="flex items-start gap-2 text-sm text-neutral-700 dark:text-neutral-300">
                            <Checkbox
                                name="agree"
                                value="1"
                                required
                                className="mt-0.5"
                            />
                            I agree that SCAS may contact me about this adoption
                            request and store my details for that purpose.
                        </label>
                        {errors.agree && (
                            <p className="mt-1 text-xs text-red-600 dark:text-red-400">
                                {errors.agree}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col-reverse gap-2 border-t border-neutral-100 pt-4 sm:flex-row sm:justify-between dark:border-neutral-800">
                        <button
                            type="button"
                            onClick={onBack}
                            className="rounded-xl px-4 py-2.5 text-sm font-medium text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800"
                        >
                            Back to {dog.name}
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-800/20 transition hover:bg-brand-800 disabled:opacity-60 dark:bg-brand-600 dark:hover:bg-brand-500"
                        >
                            <HeartIcon className="size-4" />
                            {processing ? 'Sending…' : 'Send adoption request'}
                        </button>
                    </div>
                </>
            )}
        </Form>
    );
}

function DogFacts({ dog, className }: { dog: PublicDog; className?: string }) {
    const facts = [
        dog.gender && {
            icon: GenderIcon,
            label: dog.gender === 'male' ? 'Boy' : 'Girl',
        },
        dog.age !== null && {
            icon: CalendarIcon,
            label: dog.is_puppy
                ? 'Under 1 year'
                : `${dog.age} ${dog.age === 1 ? 'year' : 'years'}`,
        },
        dog.size && { icon: RulerIcon, label: dog.size },
    ].filter(Boolean) as { icon: ComponentType<IconProps>; label: string }[];

    if (facts.length === 0) {
        return null;
    }

    return (
        <ul className={cn('flex flex-wrap gap-1.5', className)}>
            {facts.map((fact) => (
                <li
                    key={fact.label}
                    className="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-800 dark:bg-brand-950/60 dark:text-brand-200"
                >
                    <fact.icon className="size-3.5" />
                    {fact.label}
                </li>
            ))}
        </ul>
    );
}

function DogPhoto({ dog, className }: { dog: PublicDog; className?: string }) {
    const [failed, setFailed] = useState(false);

    if (dog.photo_url && !failed) {
        return (
            <img
                src={dog.photo_url}
                alt={`${dog.name}, looking for a home`}
                loading="lazy"
                onError={() => setFailed(true)}
                className={cn('w-full object-cover', className)}
            />
        );
    }

    return (
        <div
            role="img"
            aria-label={`${dog.name} — photo coming soon`}
            className={cn(
                'relative flex w-full items-center justify-center overflow-hidden bg-linear-to-br from-brand-100 via-brand-200 to-brand-300 dark:from-brand-900 dark:via-brand-950 dark:to-neutral-900',
                className,
            )}
        >
            <PawIcon
                aria-hidden="true"
                className="absolute -right-6 -bottom-6 size-32 rotate-12 text-white/40 dark:text-white/5"
                strokeWidth={1}
            />
            <div className="relative text-center">
                <span className="mx-auto flex size-16 items-center justify-center rounded-full bg-white/70 text-2xl font-bold text-brand-800 shadow-lg dark:bg-white/10 dark:text-brand-100">
                    {dog.name.charAt(0).toUpperCase()}
                </span>
                <span className="mt-2 block text-xs font-medium text-brand-800/80 dark:text-brand-200/70">
                    Photo coming soon
                </span>
            </div>
        </div>
    );
}

function PhotoTag({ children }: { children: string }) {
    return (
        <span className="rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-semibold text-brand-800 shadow-sm backdrop-blur">
            {children}
        </span>
    );
}

function ContactOptions({
    contact,
    dogName,
    tone = 'light',
    className,
}: {
    contact: AdoptionContact;
    dogName?: string;
    tone?: 'light' | 'dark';
    className?: string;
}) {
    const subject = dogName
        ? `Adoption enquiry: ${dogName}`
        : 'Adoption enquiry';
    const message = dogName
        ? `Hi SCAS, I'd love to know more about adopting ${dogName}.`
        : "Hi SCAS, I'd love to know more about adopting a dog.";
    const whatsappNumber = contact.whatsapp?.replace(/\D/g, '');

    const links = [
        contact.whatsapp && {
            label: 'WhatsApp',
            href: `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(message)}`,
        },
        contact.email && {
            label: 'Email',
            href: `mailto:${contact.email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(message)}`,
        },
        contact.phone && { label: 'Call', href: `tel:${contact.phone}` },
        contact.instagram && {
            label: 'Instagram',
            href: `https://instagram.com/${contact.instagram.replace(/^@/, '')}`,
        },
    ].filter(Boolean) as { label: string; href: string }[];

    if (links.length === 0) {
        return (
            <p
                className={cn(
                    'flex text-sm',
                    tone === 'dark'
                        ? 'text-brand-100/80'
                        : 'text-neutral-600 dark:text-neutral-400',
                    className,
                )}
            >
                Contact the SCAS team to arrange a meet & greet.
            </p>
        );
    }

    return (
        <div className={cn('flex flex-wrap gap-2', className)}>
            {links.map((link, index) => (
                <a
                    key={link.label}
                    href={link.href}
                    target={link.href.startsWith('http') ? '_blank' : undefined}
                    rel="noreferrer"
                    className={cn(
                        'inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-semibold transition',
                        index === 0
                            ? tone === 'dark'
                                ? 'bg-white text-brand-900 hover:bg-brand-50'
                                : 'bg-brand-700 text-white hover:bg-brand-800 dark:bg-brand-600 dark:hover:bg-brand-500'
                            : tone === 'dark'
                              ? 'border border-white/25 text-white hover:bg-white/10'
                              : 'border border-brand-200 bg-white text-brand-800 hover:bg-brand-50 dark:border-brand-800 dark:bg-neutral-900 dark:text-brand-200',
                    )}
                >
                    {index === 0 && <HomeIcon className="size-4" />}
                    {link.label}
                </a>
            ))}
        </div>
    );
}

function HeroCollage({
    dogs,
    onSelect,
}: {
    dogs: PublicDog[];
    onSelect: (dog: PublicDog) => void;
}) {
    const positions = [
        'lg:col-span-2 lg:row-span-2 rotate-[-2deg]',
        'rotate-[3deg] lg:translate-y-6',
        'rotate-[-3deg] lg:-translate-y-2',
    ];

    return (
        <div className="relative mx-auto grid w-full max-w-lg grid-cols-3 gap-4 lg:grid-cols-3 lg:grid-rows-2">
            <div className="absolute -inset-6 -z-10 rounded-[2.5rem] bg-white/10 blur-2xl" />
            {dogs.map((dog, index) => (
                <button
                    key={dog.id}
                    type="button"
                    onClick={() => onSelect(dog)}
                    className={cn(
                        'group relative overflow-hidden rounded-3xl bg-white p-1.5 text-left shadow-2xl shadow-black/30 transition duration-500 hover:z-10 hover:rotate-0 dark:bg-neutral-900',
                        positions[index],
                    )}
                >
                    <DogPhoto
                        dog={dog}
                        className="aspect-square rounded-[1.25rem]"
                    />
                    <span className="absolute inset-x-1.5 bottom-1.5 rounded-b-[1.25rem] bg-linear-to-t from-black/70 to-transparent px-3 pt-8 pb-2 text-sm font-semibold text-white">
                        {dog.name}
                    </span>
                </button>
            ))}
        </div>
    );
}

function SectionIntro({
    eyebrow,
    title,
    text,
}: {
    eyebrow: string;
    title: string;
    text: string;
}) {
    return (
        <div className="mx-auto max-w-2xl text-center">
            <p className="text-xs font-semibold tracking-[0.14em] text-brand-600 uppercase dark:text-brand-400">
                {eyebrow}
            </p>
            <h2 className="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">
                {title}
            </h2>
            <p className="mt-4 text-base leading-relaxed text-neutral-600 dark:text-neutral-400">
                {text}
            </p>
        </div>
    );
}

/** Dot texture, glows and a paw trail behind dark gradient sections. */
function HeroDecoration() {
    const pawTrail = [
        { left: '6%', top: '78%', rotate: 20, opacity: 0.05 },
        { left: '16%', top: '66%', rotate: 35, opacity: 0.07 },
        { left: '28%', top: '74%', rotate: 25, opacity: 0.09 },
        { left: '40%', top: '62%', rotate: 40, opacity: 0.11 },
        { left: '52%', top: '70%', rotate: 30, opacity: 0.08 },
    ];

    return (
        <div
            aria-hidden="true"
            className="pointer-events-none absolute inset-0 -z-10"
        >
            <div className="absolute inset-0 [background-image:radial-gradient(circle,white_1px,transparent_1px)] [background-size:22px_22px] opacity-[0.07]" />
            <div className="absolute -top-32 -left-24 size-96 rounded-full bg-brand-400/30 blur-3xl" />
            <div className="absolute -right-24 -bottom-40 size-[28rem] rounded-full bg-brand-500/25 blur-3xl" />
            {pawTrail.map((paw) => (
                <PawIcon
                    key={paw.left}
                    className="absolute size-10 text-white"
                    style={{
                        left: paw.left,
                        top: paw.top,
                        opacity: paw.opacity,
                        transform: `rotate(${paw.rotate}deg)`,
                    }}
                />
            ))}
        </div>
    );
}
