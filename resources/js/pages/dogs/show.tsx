import {
    store as storeAdoptionRecord,
    update as updateAdoptionRecord,
} from '@/actions/App/Http/Controllers/Dogs/AdoptionRecordController';
import {
    destroy as restoreDog,
    store as archiveDog,
} from '@/actions/App/Http/Controllers/Dogs/ArchiveController';
import {
    close as closeFosterRecord,
    store as storeFosterRecord,
    update as updateFosterRecord,
} from '@/actions/App/Http/Controllers/Dogs/FosterRecordController';
import { update as updateIdentification } from '@/actions/App/Http/Controllers/Dogs/IdentificationController';
import { update as updateMedicalProfile } from '@/actions/App/Http/Controllers/Dogs/MedicalProfileController';
import { update as updateRescueIntake } from '@/actions/App/Http/Controllers/Dogs/RescueIntakeController';
import {
    destroy as destroyVaccination,
    store as storeVaccination,
    update as updateVaccination,
} from '@/actions/App/Http/Controllers/Dogs/VaccinationController';
import { DogAvatar } from '@/components/dogs/dog-avatar';
import { DocumentsTab } from '@/components/dogs/documents-tab';
import { HistoryTab } from '@/components/dogs/history-tab';
import { PhotoGallery } from '@/components/dogs/photo-gallery';
import { StatusBadge } from '@/components/dogs/status-badge';
import {
    ArchiveIcon,
    ArrowLeftIcon,
    CalendarIcon,
    CheckIcon,
    CloseIcon,
    GenderIcon,
    HomeIcon,
    PawIcon,
    PencilIcon,
    PlusIcon,
    ResetIcon,
    RulerIcon,
    TrashIcon,
} from '@/components/icons';
import { ConfirmButton } from '@/components/ui/confirm-button';
import { Field } from '@/components/ui/field';
import { Modal } from '@/components/ui/modal';
import { useCan } from '@/lib/permissions';
import type { Permission } from '@/types/auth';
import { Badge } from '@/components/ui/badge';
import { Button, buttonClasses } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { TabPanel, Tabs } from '@/components/ui/tabs';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeaderCell,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { AuthenticatedLayout } from '@/layouts/authenticated-layout';
import { cn } from '@/lib/utils';
import dogsRoutes from '@/routes/dogs';
import {
    DOG_STATUS_LABELS,
    formatAge,
    formatDate,
    isVaccinationsUpToDate,
    nextVaccinationDate,
    placementLabel,
    type AuditLogEntry,
    type Dog,
    type DogAdoptionRecord,
    type DogFosterRecord,
    type DogVaccination,
    type Option,
    type PlacementHistoryEvent,
} from '@/types/dog';
import { Form, Head, Link, usePage } from '@inertiajs/react';
import { type ReactElement, type ReactNode, useState } from 'react';

interface DogShowProps {
    dog: Dog;
    placementHistory: PlacementHistoryEvent[];
    documentCategoryOptions: Option[];
    /** Deferred: undefined until loaded after the first render. */
    history?: AuditLogEntry[];
}

type TabValue =
    | 'overview'
    | 'medical'
    | 'identification'
    | 'rescue-history'
    | 'foster-history'
    | 'adoption-history'
    | 'documents'
    | 'history';

const TAB_ITEMS: { value: TabValue; label: string; title: string }[] = [
    { value: 'overview', label: 'Overview', title: 'Overview' },
    { value: 'medical', label: 'Medical', title: 'Medical Records' },
    {
        value: 'identification',
        label: 'Identification',
        title: 'Identification',
    },
    {
        value: 'rescue-history',
        label: 'Rescue History',
        title: 'Rescue & Intake History',
    },
    {
        value: 'foster-history',
        label: 'Foster History',
        title: 'Foster History',
    },
    {
        value: 'adoption-history',
        label: 'Adoption History',
        title: 'Adoption History',
    },
    { value: 'documents', label: 'Documents', title: 'Documents' },
    { value: 'history', label: 'History', title: 'Record History' },
];

/** Tabs whose "Edit Record" button toggles an inline form, with the permission it needs. */
const INLINE_EDIT_TABS: Partial<Record<TabValue, Permission>> = {
    medical: 'manage-medical',
    identification: 'manage-dogs',
    'rescue-history': 'manage-dogs',
};

function isTabValue(value: string | null): value is TabValue {
    return TAB_ITEMS.some((item) => item.value === value);
}

export default function DogShow({
    dog,
    placementHistory,
    documentCategoryOptions,
    history,
}: DogShowProps) {
    const page = usePage();
    const can = useCan();
    const requestedTab = new URLSearchParams(page.url.split('?')[1] ?? '').get(
        'tab',
    );
    const [activeTab, setActiveTab] = useState<TabValue>(
        isTabValue(requestedTab) ? requestedTab : 'overview',
    );
    const [editing, setEditing] = useState(false);

    function changeTab(value: string) {
        if (isTabValue(value)) {
            setActiveTab(value);
            setEditing(false);
        }
    }

    const activeTabItem = TAB_ITEMS.find((item) => item.value === activeTab);
    const inlineEditPermission = INLINE_EDIT_TABS[activeTab];
    let editAction: ReactNode = null;

    if (inlineEditPermission !== undefined) {
        editAction = can(inlineEditPermission) && (
            <Button size="sm" onClick={() => setEditing((value) => !value)}>
                {editing ? 'Cancel' : 'Edit Record'}
            </Button>
        );
    } else if (
        activeTab !== 'documents' &&
        activeTab !== 'history' &&
        can('manage-dogs')
    ) {
        editAction = (
            <Link
                href={dogsRoutes.edit.url(dog.id)}
                className={buttonClasses('primary', 'sm')}
            >
                Edit Record
            </Link>
        );
    }

    return (
        <>
            <Head title={dog.name} />

            {dog.archived_at && (
                <div
                    role="status"
                    className="mb-6 flex flex-wrap items-center gap-3 rounded-xl border border-neutral-300 bg-neutral-100 px-4 py-3 text-sm text-neutral-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200"
                >
                    <ArchiveIcon className="size-4 shrink-0" />
                    <span className="flex-1">
                        This record was archived on{' '}
                        {formatDate(dog.archived_at)}. It is hidden from lists
                        and dashboard statistics.
                    </span>
                    {can('manage-dogs') && (
                        <ConfirmButton
                            variant="secondary"
                            size="sm"
                            action={restoreDog(dog.id)}
                            title={`Restore ${dog.name}?`}
                            message="The record will appear in lists and statistics again."
                            confirmLabel="Restore"
                            destructive={false}
                        >
                            <ResetIcon className="size-3.5" />
                            Restore
                        </ConfirmButton>
                    )}
                </div>
            )}

            <ProfileHeader dog={dog} />

            {dog.import_flags && dog.import_flags.length > 0 && (
                <div className="mb-6 rounded-xl border border-warning-500/40 bg-warning-50 px-4 py-3 dark:bg-warning-700/10">
                    <p className="mb-1 text-xs font-semibold text-warning-700">
                        Needs review
                    </p>
                    <ul className="list-inside list-disc space-y-0.5 text-xs text-neutral-700 dark:text-neutral-300">
                        {dog.import_flags.map((flag) => (
                            <li key={flag.id}>{flag.message}</li>
                        ))}
                    </ul>
                </div>
            )}

            <Tabs items={TAB_ITEMS} value={activeTab} onChange={changeTab} />

            {activeTab !== 'overview' && (
                <div className="mt-6 flex flex-wrap items-center justify-between gap-3">
                    <h2 className="flex items-center gap-2 text-lg font-semibold tracking-tight text-neutral-900 before:h-5 before:w-1 before:rounded-full before:bg-linear-to-b before:from-brand-400 before:to-brand-700 dark:text-neutral-50 dark:before:from-brand-300 dark:before:to-brand-500">
                        {activeTabItem?.title}
                    </h2>
                    {editAction}
                </div>
            )}

            <TabPanel active={activeTab === 'overview'}>
                <OverviewTab dog={dog} />
            </TabPanel>

            <TabPanel active={activeTab === 'medical'}>
                <MedicalTab
                    dog={dog}
                    editing={editing}
                    onDone={() => setEditing(false)}
                />
            </TabPanel>

            <TabPanel active={activeTab === 'identification'}>
                <IdentificationTab
                    dog={dog}
                    editing={editing}
                    onDone={() => setEditing(false)}
                />
            </TabPanel>

            <TabPanel active={activeTab === 'rescue-history'}>
                <RescueHistoryTab
                    dog={dog}
                    editing={editing}
                    onDone={() => setEditing(false)}
                />
            </TabPanel>

            <TabPanel active={activeTab === 'foster-history'}>
                <FosterHistoryTab
                    dog={dog}
                    placementHistory={placementHistory}
                />
            </TabPanel>

            <TabPanel active={activeTab === 'adoption-history'}>
                <AdoptionHistoryTab dog={dog} />
            </TabPanel>

            <TabPanel active={activeTab === 'documents'}>
                <DocumentsTab
                    dog={dog}
                    categoryOptions={documentCategoryOptions}
                />
            </TabPanel>

            <TabPanel active={activeTab === 'history'}>
                <HistoryTab history={history} />
            </TabPanel>
        </>
    );
}

DogShow.layout = (page: ReactElement) => (
    <AuthenticatedLayout>{page}</AuthenticatedLayout>
);

function ProfileHeader({ dog }: { dog: Dog }) {
    const can = useCan();

    return (
        <>
            <Link
                href={dogsRoutes.index.url()}
                className="mb-3 inline-flex items-center gap-1.5 text-xs text-neutral-500 hover:text-brand-700 dark:text-neutral-400 dark:hover:text-brand-300"
            >
                <ArrowLeftIcon className="size-3.5" />
                Back to All Dogs
            </Link>

            <Card className="relative mb-6 overflow-hidden p-5">
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-x-0 top-0 h-24 overflow-hidden bg-linear-to-r from-brand-700 via-brand-800 to-brand-950"
                >
                    <div className="absolute inset-0 [background-image:radial-gradient(circle,white_1px,transparent_1px)] [background-size:18px_18px] opacity-10" />
                    <PawIcon
                        className="absolute top-3 right-10 size-24 rotate-12 text-white/10"
                        strokeWidth={1}
                    />
                    <PawIcon
                        className="absolute top-10 right-40 size-10 -rotate-12 text-white/10"
                        strokeWidth={1.25}
                    />
                </div>
                <div className="relative flex flex-col gap-5 pt-8 md:flex-row">
                    <DogAvatar
                        dog={dog}
                        rounded="xl"
                        className="size-28 shrink-0 shadow-lg ring-4 ring-white md:size-32 dark:ring-neutral-900"
                    />

                    <div className="min-w-0 flex-1 md:pt-14">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-50">
                                {dog.name}
                            </h1>
                            <StatusBadge status={dog.current_status} />
                        </div>
                        <p className="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                            {dog.scas_id}
                        </p>
                        <div className="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-neutral-600 dark:text-neutral-300">
                            <span className="inline-flex items-center gap-1 capitalize">
                                <GenderIcon className="size-3.5 text-brand-600" />
                                {dog.gender ?? 'Unknown'}
                            </span>
                            <span className="inline-flex items-center gap-1">
                                <CalendarIcon className="size-3.5 text-brand-600" />
                                {formatAge(dog.date_of_birth)}
                                {dog.date_of_birth_is_approximate &&
                                    ' (approx.)'}
                            </span>
                            <span className="inline-flex items-center gap-1 capitalize">
                                <RulerIcon className="size-3.5 text-brand-600" />
                                {dog.size ?? 'Unknown size'}
                            </span>
                        </div>
                        {dog.breed && (
                            <p className="mt-2 inline-flex items-center gap-1 text-xs text-neutral-600 dark:text-neutral-300">
                                <PawIcon className="size-3.5 text-brand-600" />
                                {dog.breed}
                            </p>
                        )}
                        {can('manage-dogs') && (
                            <div className="mt-4 flex flex-wrap gap-2">
                                <Link
                                    href={dogsRoutes.edit.url(dog.id)}
                                    className={buttonClasses('secondary', 'sm')}
                                >
                                    <PencilIcon className="size-3.5" />
                                    Edit Profile
                                </Link>
                                {!dog.archived_at && (
                                    <ConfirmButton
                                        variant="ghost"
                                        size="sm"
                                        action={archiveDog(dog.id)}
                                        title={`Archive ${dog.name}?`}
                                        message="Archived records are hidden from lists and dashboard statistics but nothing is deleted. You can restore the record at any time."
                                        confirmLabel="Archive"
                                    >
                                        <ArchiveIcon className="size-3.5" />
                                        Archive
                                    </ConfirmButton>
                                )}
                            </div>
                        )}
                    </div>

                    <dl className="grid w-full shrink-0 grid-cols-1 gap-3 text-xs sm:grid-cols-3 md:w-52 md:grid-cols-1 md:pt-14">
                        <SummaryField label="Current Status">
                            {DOG_STATUS_LABELS[dog.current_status]}
                        </SummaryField>
                        <SummaryField label="Placement">
                            {placementLabel(dog.current_status) ?? '—'}
                        </SummaryField>
                        <SummaryField label="Location">
                            {dog.current_location ?? '—'}
                        </SummaryField>
                    </dl>
                </div>
            </Card>
        </>
    );
}

function SummaryField({
    label,
    children,
}: {
    label: string;
    children: ReactNode;
}) {
    return (
        <div>
            <dt className="mb-1 text-neutral-500 dark:text-neutral-400">
                {label}
            </dt>
            <dd className="rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-1.5 font-medium text-neutral-800 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                {children}
            </dd>
        </div>
    );
}

/**
 * Two-column label/value list used across the record tabs.
 */
function DetailList({ rows }: { rows: [string, ReactNode][] }) {
    return (
        <dl className="divide-y divide-neutral-100 overflow-hidden rounded-lg border border-neutral-200 text-xs dark:divide-neutral-800 dark:border-neutral-800">
            {rows.map(([label, value]) => (
                <div
                    key={label}
                    className="grid grid-cols-1 sm:grid-cols-[minmax(0,200px)_1fr]"
                >
                    <dt className="bg-neutral-50 px-4 py-2.5 font-medium text-neutral-600 dark:bg-neutral-800/60 dark:text-neutral-300">
                        {label}
                    </dt>
                    <dd className="px-4 py-2.5 text-neutral-800 dark:text-neutral-100">
                        {value}
                    </dd>
                </div>
            ))}
        </dl>
    );
}

function SectionTitle({
    children,
    action,
}: {
    children: ReactNode;
    action?: ReactNode;
}) {
    return (
        <div className="mb-3 flex items-center justify-between gap-3">
            <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                {children}
            </h3>
            {action}
        </div>
    );
}

function Chip({ active, children }: { active: boolean; children: ReactNode }) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-md px-2.5 py-1 text-xs',
                active
                    ? 'bg-brand-100 font-medium text-brand-800 dark:bg-brand-900 dark:text-brand-200'
                    : 'bg-neutral-100 text-neutral-400 line-through decoration-neutral-300 dark:bg-neutral-800 dark:text-neutral-500',
            )}
        >
            {children}
        </span>
    );
}

function QuickNote({ ok, children }: { ok: boolean; children: ReactNode }) {
    return (
        <li className="flex items-start gap-2">
            <span
                className={cn(
                    'mt-px flex size-4 shrink-0 items-center justify-center rounded-full',
                    ok
                        ? 'bg-success-50 text-success-600 dark:bg-success-700/20 dark:text-success-500'
                        : 'bg-neutral-100 text-neutral-400 dark:bg-neutral-800',
                )}
            >
                {ok ? (
                    <CheckIcon className="size-2.5" strokeWidth={3} />
                ) : (
                    <CloseIcon className="size-2.5" strokeWidth={3} />
                )}
            </span>
            <span>{children}</span>
        </li>
    );
}

function FormActions({
    processing,
    onCancel,
}: {
    processing: boolean;
    onCancel: () => void;
}) {
    return (
        <div className="flex justify-end gap-2 md:col-span-2">
            <Button
                type="button"
                variant="secondary"
                size="sm"
                onClick={onCancel}
            >
                Cancel
            </Button>
            <Button type="submit" size="sm" disabled={processing}>
                Save Changes
            </Button>
        </div>
    );
}

function OverviewTab({ dog }: { dog: Dog }) {
    const microchipped = Boolean(dog.identification?.microchip_number);
    const vaccinationsUpToDate = isVaccinationsUpToDate(dog.vaccinations);

    return (
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-5">
            <Card className="lg:col-span-3">
                <CardHeader>
                    <CardTitle>About {dog.name}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-5 pt-0">
                    <p className="text-sm leading-relaxed text-neutral-600 dark:text-neutral-300">
                        {dog.personality_description ??
                            'No personality description recorded yet.'}
                    </p>

                    <div>
                        <h4 className="mb-2 text-xs font-semibold text-neutral-900 dark:text-neutral-100">
                            Good With
                        </h4>
                        <div className="flex flex-wrap gap-2">
                            <Chip active={Boolean(dog.good_with_dogs)}>
                                Dogs
                            </Chip>
                            <Chip active={Boolean(dog.good_with_cats)}>
                                Cats
                            </Chip>
                            <Chip active={Boolean(dog.good_with_children)}>
                                Kids
                            </Chip>
                            <Chip active={Boolean(dog.good_with_adults)}>
                                Adults
                            </Chip>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <h4 className="text-xs font-semibold text-neutral-900 dark:text-neutral-100">
                            Energy Level
                        </h4>
                        {dog.energy_level ? (
                            <Badge tone="brand" className="capitalize">
                                {dog.energy_level}
                            </Badge>
                        ) : (
                            <span className="text-xs text-neutral-400">
                                Not assessed
                            </span>
                        )}
                    </div>

                    <div>
                        <h4 className="mb-2 text-xs font-semibold text-neutral-900 dark:text-neutral-100">
                            Training
                        </h4>
                        <div className="flex flex-wrap gap-2">
                            <Chip active={Boolean(dog.potty_trained)}>
                                Potty trained
                            </Chip>
                            <Chip
                                active={
                                    dog.training_level === 'basic' ||
                                    dog.training_level === 'well_trained'
                                }
                            >
                                Basic commands
                            </Chip>
                            <Chip active={Boolean(dog.leash_trained)}>
                                Leash trained
                            </Chip>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div className="space-y-6 lg:col-span-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Photos</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-0">
                        <PhotoGallery
                            dog={dog}
                            photos={(dog.photos ?? []).filter(
                                (photo) => photo.kind !== 'rescue',
                            )}
                            uploadKind="gallery"
                            emptyText="No photos yet"
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Quick Notes</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-0">
                        <ul className="space-y-2 text-xs text-neutral-700 dark:text-neutral-300">
                            <QuickNote
                                ok={Boolean(
                                    dog.medical_profile?.spayed_neutered,
                                )}
                            >
                                {dog.medical_profile?.spayed_neutered
                                    ? 'Spayed / neutered'
                                    : 'Not spayed / neutered'}
                            </QuickNote>
                            <QuickNote ok={vaccinationsUpToDate}>
                                {vaccinationsUpToDate
                                    ? 'Vaccinations up to date'
                                    : 'Vaccinations not up to date'}
                            </QuickNote>
                            <QuickNote ok={microchipped}>
                                {microchipped ? 'Microchipped' : 'No microchip'}
                            </QuickNote>
                            <QuickNote ok={!dog.special_requirements}>
                                {dog.special_requirements ??
                                    'No known special requirements'}
                            </QuickNote>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}

interface EditableTabProps {
    dog: Dog;
    editing: boolean;
    onDone: () => void;
}

function MedicalTab({ dog, editing, onDone }: EditableTabProps) {
    const medical = dog.medical_profile;
    const identification = dog.identification;
    const vaccinations = dog.vaccinations ?? [];
    const lastVaccination = vaccinations.find((v) => v.administered_date);
    const upToDate = isVaccinationsUpToDate(vaccinations);
    const canManage = useCan()('manage-medical');
    const [treatment, setTreatment] = useState<DogVaccination | 'new' | null>(
        null,
    );

    return (
        <div className="space-y-6">
            <Card className="p-5">
                <SectionTitle>Medical Information</SectionTitle>
                {editing ? (
                    <Form
                        action={updateMedicalProfile(dog.id)}
                        onSuccess={onDone}
                    >
                        {({ processing }) => (
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="last_vet_check_date">
                                        Last Vet Check
                                    </Label>
                                    <Input
                                        id="last_vet_check_date"
                                        name="last_vet_check_date"
                                        type="date"
                                        defaultValue={
                                            medical?.last_vet_check_date ?? ''
                                        }
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="veterinary_clinic">
                                        Veterinary Clinic
                                    </Label>
                                    <Input
                                        id="veterinary_clinic"
                                        name="veterinary_clinic"
                                        defaultValue={
                                            medical?.veterinary_clinic ?? ''
                                        }
                                    />
                                </div>
                                <div className="md:col-span-2">
                                    <Label htmlFor="medications">
                                        Medications
                                    </Label>
                                    <Textarea
                                        id="medications"
                                        name="medications"
                                        rows={2}
                                        defaultValue={
                                            medical?.medications ?? ''
                                        }
                                    />
                                </div>
                                <div className="md:col-span-2">
                                    <Label htmlFor="medical_notes">
                                        Medical Notes
                                    </Label>
                                    <Textarea
                                        id="medical_notes"
                                        name="medical_notes"
                                        rows={2}
                                        defaultValue={
                                            medical?.medical_notes ?? ''
                                        }
                                    />
                                </div>
                                <FormActions
                                    processing={processing}
                                    onCancel={onDone}
                                />
                            </div>
                        )}
                    </Form>
                ) : (
                    <DetailList
                        rows={[
                            [
                                'Vaccinations',
                                vaccinations.length === 0
                                    ? 'No records'
                                    : `${upToDate ? 'Up to date' : 'Overdue'}${
                                          lastVaccination
                                              ? ` (last: ${formatDate(lastVaccination.administered_date)})`
                                              : ''
                                      }`,
                            ],
                            [
                                'Spayed / Neutered',
                                medical?.spayed_neutered
                                    ? `Spayed (${formatDate(medical.spayed_neutered_date)})`
                                    : 'No',
                            ],
                            [
                                'Microchipped',
                                identification?.microchip_number
                                    ? `Yes (${identification.microchip_number})`
                                    : 'No',
                            ],
                            [
                                'PCC',
                                identification?.pcc_number
                                    ? `Yes (${formatDate(identification.pcc_registration_date)})`
                                    : 'No',
                            ],
                            [
                                'Last Vet Check',
                                formatDate(medical?.last_vet_check_date),
                            ],
                            [
                                'Next Vaccination',
                                formatDate(nextVaccinationDate(vaccinations)),
                            ],
                            [
                                'Veterinary Clinic',
                                medical?.veterinary_clinic ?? '—',
                            ],
                            ['Medications', medical?.medications ?? 'None'],
                            ['Medical Notes', medical?.medical_notes ?? '—'],
                        ]}
                    />
                )}
            </Card>

            <Card className="p-5">
                <SectionTitle>Treatment History</SectionTitle>
                <div className="overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-800">
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableHeaderCell>Date</TableHeaderCell>
                                <TableHeaderCell>
                                    Treatment / Procedure
                                </TableHeaderCell>
                                <TableHeaderCell>Next Due</TableHeaderCell>
                                <TableHeaderCell>Notes</TableHeaderCell>
                                {canManage && (
                                    <TableHeaderCell>
                                        <span className="sr-only">Actions</span>
                                    </TableHeaderCell>
                                )}
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {vaccinations.map((v) => (
                                <TableRow key={v.id}>
                                    <TableCell className="whitespace-nowrap">
                                        {formatDate(v.administered_date)}
                                    </TableCell>
                                    <TableCell>{v.vaccine_details}</TableCell>
                                    <TableCell className="whitespace-nowrap">
                                        {formatDate(v.next_due_date)}
                                    </TableCell>
                                    <TableCell>
                                        {v.notes ?? v.expiry_annotation ?? '—'}
                                    </TableCell>
                                    {canManage && (
                                        <TableCell>
                                            <RowActions>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        setTreatment(v)
                                                    }
                                                    aria-label={`Edit ${v.vaccine_details}`}
                                                >
                                                    <PencilIcon className="size-3.5" />
                                                </Button>
                                                <ConfirmButton
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-red-600 dark:text-red-400"
                                                    action={destroyVaccination([
                                                        dog.id,
                                                        v.id,
                                                    ])}
                                                    title="Delete this treatment?"
                                                    message={`“${v.vaccine_details}” will be permanently removed from ${dog.name}'s treatment history.`}
                                                    confirmLabel="Delete"
                                                    aria-label={`Delete ${v.vaccine_details}`}
                                                >
                                                    <TrashIcon className="size-3.5" />
                                                </ConfirmButton>
                                            </RowActions>
                                        </TableCell>
                                    )}
                                </TableRow>
                            ))}
                            {vaccinations.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={canManage ? 5 : 4}
                                        className="py-6 text-center text-neutral-400"
                                    >
                                        No treatment records yet.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
                {canManage && (
                    <Button
                        size="sm"
                        variant="secondary"
                        className="mt-4"
                        onClick={() => setTreatment('new')}
                    >
                        <PlusIcon className="size-3.5" />
                        Add Treatment / Vaccination
                    </Button>
                )}
            </Card>

            <TreatmentModal
                dogId={dog.id}
                treatment={treatment}
                onClose={() => setTreatment(null)}
            />
        </div>
    );
}

function RowActions({ children }: { children: ReactNode }) {
    return <div className="flex justify-end gap-0.5">{children}</div>;
}

function TreatmentModal({
    dogId,
    treatment,
    onClose,
}: {
    dogId: number;
    treatment: DogVaccination | 'new' | null;
    onClose: () => void;
}) {
    const editing =
        treatment !== null && treatment !== 'new' ? treatment : null;

    return (
        <Modal
            open={treatment !== null}
            onClose={onClose}
            title={editing ? 'Edit treatment' : 'Add treatment / vaccination'}
        >
            {treatment !== null && (
                <Form
                    action={
                        editing
                            ? updateVaccination([dogId, editing.id])
                            : storeVaccination(dogId)
                    }
                    onSuccess={onClose}
                    options={{ preserveScroll: true }}
                >
                    {({ errors, processing }) => (
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <Field
                                label="Treatment / vaccine *"
                                htmlFor="vaccine_details"
                                error={errors.vaccine_details}
                                className="sm:col-span-2"
                            >
                                <Input
                                    id="vaccine_details"
                                    name="vaccine_details"
                                    defaultValue={editing?.vaccine_details}
                                    required
                                />
                            </Field>
                            <Field
                                label="Date"
                                htmlFor="administered_date"
                                error={errors.administered_date}
                            >
                                <Input
                                    id="administered_date"
                                    name="administered_date"
                                    type="date"
                                    defaultValue={editing?.administered_date?.slice(
                                        0,
                                        10,
                                    )}
                                />
                            </Field>
                            <Field
                                label="Next due"
                                htmlFor="next_due_date"
                                error={errors.next_due_date}
                            >
                                <Input
                                    id="next_due_date"
                                    name="next_due_date"
                                    type="date"
                                    defaultValue={editing?.next_due_date?.slice(
                                        0,
                                        10,
                                    )}
                                />
                            </Field>
                            <Field
                                label="Notes"
                                htmlFor="treatment-notes"
                                error={errors.notes}
                                className="sm:col-span-2"
                            >
                                <Textarea
                                    id="treatment-notes"
                                    name="notes"
                                    rows={2}
                                    defaultValue={editing?.notes ?? ''}
                                />
                            </Field>
                            <ModalActions
                                processing={processing}
                                onCancel={onClose}
                            />
                        </div>
                    )}
                </Form>
            )}
        </Modal>
    );
}

function ModalActions({
    processing,
    onCancel,
    label = 'Save',
}: {
    processing: boolean;
    onCancel: () => void;
    label?: string;
}) {
    return (
        <div className="mt-2 flex justify-end gap-2 sm:col-span-2">
            <Button variant="secondary" size="sm" onClick={onCancel}>
                Cancel
            </Button>
            <Button type="submit" size="sm" disabled={processing}>
                {label}
            </Button>
        </div>
    );
}

function IdentificationTab({ dog, editing, onDone }: EditableTabProps) {
    const id = dog.identification;

    return (
        <Card className="p-5">
            <SectionTitle>Identification Details</SectionTitle>
            {editing ? (
                <Form action={updateIdentification(dog.id)} onSuccess={onDone}>
                    {({ processing }) => (
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label htmlFor="microchip_number">
                                    Microchip Number
                                </Label>
                                <Input
                                    id="microchip_number"
                                    name="microchip_number"
                                    defaultValue={id?.microchip_number ?? ''}
                                />
                            </div>
                            <div>
                                <Label htmlFor="microchip_date">
                                    Date of Microchipping
                                </Label>
                                <Input
                                    id="microchip_date"
                                    name="microchip_date"
                                    type="date"
                                    defaultValue={id?.microchip_date ?? ''}
                                />
                            </div>
                            <div>
                                <Label htmlFor="passport_number">
                                    Passport Number
                                </Label>
                                <Input
                                    id="passport_number"
                                    name="passport_number"
                                    defaultValue={id?.passport_number ?? ''}
                                />
                            </div>
                            <div>
                                <Label htmlFor="pcc_number">
                                    PCC Number / Tag Number
                                </Label>
                                <Input
                                    id="pcc_number"
                                    name="pcc_number"
                                    defaultValue={id?.pcc_number ?? ''}
                                />
                            </div>
                            <FormActions
                                processing={processing}
                                onCancel={onDone}
                            />
                        </div>
                    )}
                </Form>
            ) : (
                <DetailList
                    rows={[
                        ['Microchip Number', id?.microchip_number ?? '—'],
                        [
                            'Date of Microchipping',
                            formatDate(id?.microchip_date),
                        ],
                        ['Chip Location', id?.microchip_location ?? '—'],
                        ['Passport Number', id?.passport_number ?? '—'],
                        [
                            'Passport Expiry',
                            formatDate(id?.passport_expiry_date),
                        ],
                        ['PCC Number / Tag Number', id?.pcc_number ?? '—'],
                        [
                            'Supporting Documents',
                            id?.supporting_documents_notes ?? '—',
                        ],
                    ]}
                />
            )}
        </Card>
    );
}

function RescueHistoryTab({ dog, editing, onDone }: EditableTabProps) {
    const rescue = dog.rescue_intake;

    return (
        <Card className="p-5">
            <SectionTitle>Rescue Details</SectionTitle>
            {editing ? (
                <Form action={updateRescueIntake(dog.id)} onSuccess={onDone}>
                    {({ processing }) => (
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label htmlFor="date_received">
                                    Date Rescued
                                </Label>
                                <Input
                                    id="date_received"
                                    name="date_received"
                                    type="date"
                                    defaultValue={rescue?.date_received ?? ''}
                                />
                            </div>
                            <div>
                                <Label htmlFor="rescue_location">
                                    Location
                                </Label>
                                <Input
                                    id="rescue_location"
                                    name="rescue_location"
                                    defaultValue={rescue?.rescue_location ?? ''}
                                />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="how_found">
                                    How They Were Found
                                </Label>
                                <Textarea
                                    id="how_found"
                                    name="how_found"
                                    rows={2}
                                    defaultValue={rescue?.how_found ?? ''}
                                />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="initial_condition">
                                    Initial Condition
                                </Label>
                                <Textarea
                                    id="initial_condition"
                                    name="initial_condition"
                                    rows={2}
                                    defaultValue={
                                        rescue?.initial_condition ?? ''
                                    }
                                />
                            </div>
                            <div>
                                <Label htmlFor="intake_date">Intake Date</Label>
                                <Input
                                    id="intake_date"
                                    name="intake_date"
                                    type="date"
                                    defaultValue={rescue?.intake_date ?? ''}
                                />
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="intake_notes">
                                    Intake Notes
                                </Label>
                                <Textarea
                                    id="intake_notes"
                                    name="intake_notes"
                                    rows={2}
                                    defaultValue={rescue?.intake_notes ?? ''}
                                />
                            </div>
                            <FormActions
                                processing={processing}
                                onCancel={onDone}
                            />
                        </div>
                    )}
                </Form>
            ) : (
                <DetailList
                    rows={[
                        ['Date Rescued', formatDate(rescue?.date_received)],
                        ['Location', rescue?.rescue_location ?? '—'],
                        ['How They Were Found', rescue?.how_found ?? '—'],
                        ['Initial Condition', rescue?.initial_condition ?? '—'],
                        ['Intake Date', formatDate(rescue?.intake_date)],
                        ['Intake Notes', rescue?.intake_notes ?? '—'],
                        ['Rescue Story', rescue?.rescue_story ?? '—'],
                    ]}
                />
            )}

            <div className="mt-6">
                <SectionTitle>Photos from Rescue</SectionTitle>
                <PhotoGallery
                    dog={dog}
                    photos={(dog.photos ?? []).filter(
                        (photo) => photo.kind === 'rescue',
                    )}
                    uploadKind="rescue"
                    emptyText="No rescue or intake photos yet"
                />
            </div>
        </Card>
    );
}

function FosterHistoryTab({
    dog,
    placementHistory,
}: {
    dog: Dog;
    placementHistory: PlacementHistoryEvent[];
}) {
    const canManage = useCan()('manage-placements');
    const [editing, setEditing] = useState<DogFosterRecord | 'new' | null>(
        null,
    );
    const [closing, setClosing] = useState<DogFosterRecord | null>(null);
    const fosterRecords = dog.foster_records ?? [];

    return (
        <div className="space-y-6">
            <Card className="p-5">
                <SectionTitle
                    action={
                        canManage && (
                            <Button size="sm" onClick={() => setEditing('new')}>
                                <PlusIcon className="size-3.5" />
                                Add Foster Record
                            </Button>
                        )
                    }
                >
                    Foster History
                </SectionTitle>

                <div className="overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-800">
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableHeaderCell>Start Date</TableHeaderCell>
                                <TableHeaderCell>End Date</TableHeaderCell>
                                <TableHeaderCell>Foster Family</TableHeaderCell>
                                <TableHeaderCell>Type</TableHeaderCell>
                                <TableHeaderCell>Location</TableHeaderCell>
                                <TableHeaderCell>Notes</TableHeaderCell>
                                {canManage && (
                                    <TableHeaderCell>
                                        <span className="sr-only">Actions</span>
                                    </TableHeaderCell>
                                )}
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {fosterRecords.map((f) => (
                                <TableRow key={f.id}>
                                    <TableCell className="whitespace-nowrap">
                                        {formatDate(f.start_date)}
                                    </TableCell>
                                    <TableCell className="whitespace-nowrap">
                                        {f.end_date ? (
                                            formatDate(f.end_date)
                                        ) : (
                                            <Badge tone="success">
                                                Present
                                            </Badge>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {f.foster_family_name}
                                    </TableCell>
                                    <TableCell className="capitalize">
                                        {f.foster_type}
                                    </TableCell>
                                    <TableCell>{f.location ?? '—'}</TableCell>
                                    <TableCell>{f.notes ?? '—'}</TableCell>
                                    {canManage && (
                                        <TableCell>
                                            <RowActions>
                                                {!f.end_date && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            setClosing(f)
                                                        }
                                                    >
                                                        Close
                                                    </Button>
                                                )}
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        setEditing(f)
                                                    }
                                                    aria-label={`Edit placement with ${f.foster_family_name}`}
                                                >
                                                    <PencilIcon className="size-3.5" />
                                                </Button>
                                            </RowActions>
                                        </TableCell>
                                    )}
                                </TableRow>
                            ))}
                            {fosterRecords.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={canManage ? 7 : 6}
                                        className="py-6 text-center text-neutral-400"
                                    >
                                        No foster placements recorded.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </Card>

            {(dog.adoption_records ?? []).length === 0 && (
                <Card className="p-5">
                    <SectionTitle>Adoption History</SectionTitle>
                    <NotAdoptedNotice dog={dog} />
                </Card>
            )}

            <Card className="p-5">
                <SectionTitle>Placement History</SectionTitle>
                <div className="overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-800">
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableHeaderCell>Placement</TableHeaderCell>
                                <TableHeaderCell>Location</TableHeaderCell>
                                <TableHeaderCell>Since</TableHeaderCell>
                                <TableHeaderCell>Notes</TableHeaderCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {placementHistory.map((event, index) => (
                                <TableRow key={index}>
                                    <TableCell className="capitalize">
                                        {event.type}
                                    </TableCell>
                                    <TableCell>
                                        {event.location ?? '—'}
                                    </TableCell>
                                    <TableCell className="whitespace-nowrap">
                                        {formatDate(event.date)}
                                    </TableCell>
                                    <TableCell>{event.notes ?? '—'}</TableCell>
                                </TableRow>
                            ))}
                            {placementHistory.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={4}
                                        className="py-6 text-center text-neutral-400"
                                    >
                                        No placement history yet.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </Card>

            <FosterModal
                dogId={dog.id}
                record={editing}
                onClose={() => setEditing(null)}
            />

            <Modal
                open={closing !== null}
                onClose={() => setClosing(null)}
                title="Close foster placement"
                description={
                    closing
                        ? `End ${dog.name}'s placement with ${closing.foster_family_name}. If there is no other active placement, ${dog.name} returns to "At SCAS".`
                        : undefined
                }
                size="sm"
            >
                {closing && (
                    <Form
                        action={closeFosterRecord([dog.id, closing.id])}
                        onSuccess={() => setClosing(null)}
                        options={{ preserveScroll: true }}
                    >
                        {({ errors, processing }) => (
                            <div className="grid grid-cols-1 gap-4">
                                <Field
                                    label="End date *"
                                    htmlFor="close-end-date"
                                    error={errors.end_date}
                                >
                                    <Input
                                        id="close-end-date"
                                        name="end_date"
                                        type="date"
                                        min={closing.start_date?.slice(0, 10)}
                                        defaultValue={new Date()
                                            .toISOString()
                                            .slice(0, 10)}
                                        required
                                    />
                                </Field>
                                <Field
                                    label="Closing notes"
                                    htmlFor="close-notes"
                                    error={errors.notes}
                                >
                                    <Textarea
                                        id="close-notes"
                                        name="notes"
                                        rows={2}
                                    />
                                </Field>
                                <ModalActions
                                    processing={processing}
                                    onCancel={() => setClosing(null)}
                                    label="Close placement"
                                />
                            </div>
                        )}
                    </Form>
                )}
            </Modal>
        </div>
    );
}

function FosterModal({
    dogId,
    record,
    onClose,
}: {
    dogId: number;
    record: DogFosterRecord | 'new' | null;
    onClose: () => void;
}) {
    const editing = record !== null && record !== 'new' ? record : null;

    return (
        <Modal
            open={record !== null}
            onClose={onClose}
            title={editing ? 'Edit foster placement' : 'Add foster placement'}
        >
            {record !== null && (
                <Form
                    action={
                        editing
                            ? updateFosterRecord([dogId, editing.id])
                            : storeFosterRecord(dogId)
                    }
                    onSuccess={onClose}
                    options={{ preserveScroll: true }}
                >
                    {({ errors, processing }) => (
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <Field
                                label="Foster type *"
                                htmlFor="foster_type"
                                error={errors.foster_type}
                            >
                                <Select
                                    id="foster_type"
                                    name="foster_type"
                                    required
                                    defaultValue={
                                        editing?.foster_type ?? 'local'
                                    }
                                >
                                    <option value="local">Local</option>
                                    <option value="international">
                                        International
                                    </option>
                                </Select>
                            </Field>
                            <Field
                                label="Foster family *"
                                htmlFor="foster_family_name"
                                error={errors.foster_family_name}
                            >
                                <Input
                                    id="foster_family_name"
                                    name="foster_family_name"
                                    defaultValue={editing?.foster_family_name}
                                    required
                                />
                            </Field>
                            <Field
                                label="Location"
                                htmlFor="foster-location"
                                error={errors.location}
                            >
                                <Input
                                    id="foster-location"
                                    name="location"
                                    defaultValue={editing?.location ?? ''}
                                />
                            </Field>
                            <Field
                                label="Start date"
                                htmlFor="start_date"
                                error={errors.start_date}
                            >
                                <Input
                                    id="start_date"
                                    name="start_date"
                                    type="date"
                                    defaultValue={editing?.start_date?.slice(
                                        0,
                                        10,
                                    )}
                                />
                            </Field>
                            {editing && (
                                <Field
                                    label="End date"
                                    htmlFor="end_date"
                                    error={errors.end_date}
                                    hint="Leave blank while the placement is ongoing."
                                >
                                    <Input
                                        id="end_date"
                                        name="end_date"
                                        type="date"
                                        defaultValue={editing.end_date?.slice(
                                            0,
                                            10,
                                        )}
                                    />
                                </Field>
                            )}
                            <Field
                                label="Notes"
                                htmlFor="foster-notes"
                                error={errors.notes}
                                className="sm:col-span-2"
                            >
                                <Textarea
                                    id="foster-notes"
                                    name="notes"
                                    rows={2}
                                    defaultValue={editing?.notes ?? ''}
                                />
                            </Field>
                            <ModalActions
                                processing={processing}
                                onCancel={onClose}
                            />
                        </div>
                    )}
                </Form>
            )}
        </Modal>
    );
}

function NotAdoptedNotice({ dog }: { dog: Dog }) {
    return (
        <div className="flex flex-col items-center justify-center rounded-lg bg-brand-50 px-4 py-8 text-center dark:bg-brand-950/40">
            <HomeIcon className="mb-2 size-6 text-brand-700 dark:text-brand-300" />
            <p className="text-sm font-medium text-neutral-800 dark:text-neutral-100">
                Not yet adopted
            </p>
            <p className="text-xs text-neutral-500 dark:text-neutral-400">
                This dog is currently: {DOG_STATUS_LABELS[dog.current_status]}.
            </p>
        </div>
    );
}

function AdoptionHistoryTab({ dog }: { dog: Dog }) {
    const canManage = useCan()('manage-placements');
    const [editing, setEditing] = useState<DogAdoptionRecord | 'new' | null>(
        null,
    );
    const records = dog.adoption_records ?? [];

    return (
        <Card className="p-5">
            <SectionTitle
                action={
                    canManage && (
                        <Button size="sm" onClick={() => setEditing('new')}>
                            <PlusIcon className="size-3.5" />
                            Record Adoption
                        </Button>
                    )
                }
            >
                Adoption History
            </SectionTitle>

            {records.length === 0 ? (
                <NotAdoptedNotice dog={dog} />
            ) : (
                <div className="overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-800">
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableHeaderCell>Adoption Date</TableHeaderCell>
                                <TableHeaderCell>Type</TableHeaderCell>
                                <TableHeaderCell>Adopter</TableHeaderCell>
                                <TableHeaderCell>Destination</TableHeaderCell>
                                <TableHeaderCell>Status</TableHeaderCell>
                                {canManage && (
                                    <TableHeaderCell>
                                        <span className="sr-only">Actions</span>
                                    </TableHeaderCell>
                                )}
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {records.map((a) => (
                                <TableRow key={a.id}>
                                    <TableCell className="whitespace-nowrap">
                                        {formatDate(a.adoption_date)}
                                    </TableCell>
                                    <TableCell className="capitalize">
                                        {a.adoption_type}
                                    </TableCell>
                                    <TableCell>
                                        {a.adopter_name}
                                        {a.adopter_contact && (
                                            <span className="block text-xs text-neutral-400">
                                                {a.adopter_contact}
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {a.adopter_location ?? '—'}
                                    </TableCell>
                                    <TableCell className="whitespace-nowrap">
                                        {a.return_date ? (
                                            <Badge tone="warning">
                                                Returned{' '}
                                                {formatDate(a.return_date)}
                                            </Badge>
                                        ) : (
                                            <Badge tone="success">
                                                Adopted
                                            </Badge>
                                        )}
                                    </TableCell>
                                    {canManage && (
                                        <TableCell>
                                            <RowActions>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        setEditing(a)
                                                    }
                                                    aria-label={`Edit adoption by ${a.adopter_name}`}
                                                >
                                                    <PencilIcon className="size-3.5" />
                                                    {a.return_date
                                                        ? 'Edit'
                                                        : 'Edit / record return'}
                                                </Button>
                                            </RowActions>
                                        </TableCell>
                                    )}
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            )}

            <AdoptionModal
                dogId={dog.id}
                record={editing}
                onClose={() => setEditing(null)}
            />
        </Card>
    );
}

function AdoptionModal({
    dogId,
    record,
    onClose,
}: {
    dogId: number;
    record: DogAdoptionRecord | 'new' | null;
    onClose: () => void;
}) {
    const editing = record !== null && record !== 'new' ? record : null;

    return (
        <Modal
            open={record !== null}
            onClose={onClose}
            title={editing ? 'Edit adoption record' : 'Record adoption'}
            description={
                editing
                    ? 'To record a return, fill in the return date and reason. The dog will be marked for rehoming.'
                    : undefined
            }
            size="lg"
        >
            {record !== null && (
                <Form
                    action={
                        editing
                            ? updateAdoptionRecord([dogId, editing.id])
                            : storeAdoptionRecord(dogId)
                    }
                    onSuccess={onClose}
                    options={{ preserveScroll: true }}
                >
                    {({ errors, processing }) => (
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <Field
                                label="Adoption type *"
                                htmlFor="adoption_type"
                                error={errors.adoption_type}
                            >
                                <Select
                                    id="adoption_type"
                                    name="adoption_type"
                                    required
                                    defaultValue={
                                        editing?.adoption_type ?? 'local'
                                    }
                                >
                                    <option value="local">Local (UAE)</option>
                                    <option value="international">
                                        International
                                    </option>
                                </Select>
                            </Field>
                            <Field
                                label="Adoption date"
                                htmlFor="adoption_date"
                                error={errors.adoption_date}
                            >
                                <Input
                                    id="adoption_date"
                                    name="adoption_date"
                                    type="date"
                                    defaultValue={editing?.adoption_date?.slice(
                                        0,
                                        10,
                                    )}
                                />
                            </Field>
                            <Field
                                label="Adopter name *"
                                htmlFor="adopter_name"
                                error={errors.adopter_name}
                            >
                                <Input
                                    id="adopter_name"
                                    name="adopter_name"
                                    defaultValue={editing?.adopter_name}
                                    required
                                />
                            </Field>
                            <Field
                                label="Adopter contact"
                                htmlFor="adopter_contact"
                                error={errors.adopter_contact}
                            >
                                <Input
                                    id="adopter_contact"
                                    name="adopter_contact"
                                    defaultValue={
                                        editing?.adopter_contact ?? ''
                                    }
                                />
                            </Field>
                            <Field
                                label="Destination / location"
                                htmlFor="adopter_location"
                                error={errors.adopter_location}
                                className="sm:col-span-2"
                            >
                                <Input
                                    id="adopter_location"
                                    name="adopter_location"
                                    defaultValue={
                                        editing?.adopter_location ?? ''
                                    }
                                />
                            </Field>
                            <Field
                                label="Notes"
                                htmlFor="adoption-notes"
                                error={errors.notes}
                                className="sm:col-span-2"
                            >
                                <Textarea
                                    id="adoption-notes"
                                    name="notes"
                                    rows={2}
                                    defaultValue={editing?.notes ?? ''}
                                />
                            </Field>
                            {editing && (
                                <>
                                    <Field
                                        label="Return date"
                                        htmlFor="return_date"
                                        error={errors.return_date}
                                    >
                                        <Input
                                            id="return_date"
                                            name="return_date"
                                            type="date"
                                            defaultValue={editing.return_date?.slice(
                                                0,
                                                10,
                                            )}
                                        />
                                    </Field>
                                    <Field
                                        label="Return reason"
                                        htmlFor="return_reason"
                                        error={errors.return_reason}
                                    >
                                        <Input
                                            id="return_reason"
                                            name="return_reason"
                                            defaultValue={
                                                editing.return_reason ?? ''
                                            }
                                        />
                                    </Field>
                                </>
                            )}
                            <ModalActions
                                processing={processing}
                                onCancel={onClose}
                            />
                        </div>
                    )}
                </Form>
            )}
        </Modal>
    );
}
