import { store } from '@/actions/App/Http/Controllers/DogController';
import {
    ArrowLeftIcon,
    ArrowRightIcon,
    CheckIcon,
    PlusIcon,
} from '@/components/icons';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { AuthenticatedLayout } from '@/layouts/authenticated-layout';
import { cn } from '@/lib/utils';
import type { Option } from '@/types/dog';
import { Form, Head } from '@inertiajs/react';
import {
    type KeyboardEvent,
    type ReactElement,
    type ReactNode,
    useRef,
    useState,
} from 'react';

interface DogCreateProps {
    speciesOptions: Option[];
    genderOptions: Option[];
    sizeOptions: Option[];
    statusOptions: Option[];
}

/** Registration steps, in the order from the feature spec (§9.2). */
const STEPS = [
    'Basic Information',
    'Identification',
    'Rescue Details',
    'Medical Information',
    'Photo',
    'Placement',
];

/**
 * Which step holds the field behind a validation error key.
 */
function stepForField(field: string): number {
    if (field.startsWith('identification')) {
        return 1;
    }

    if (field.startsWith('rescue_intake')) {
        return 2;
    }

    if (field.startsWith('medical')) {
        return 3;
    }

    if (field === 'photo') {
        return 4;
    }

    if (field === 'current_status' || field === 'current_location') {
        return 5;
    }

    return 0;
}

export default function DogCreate({
    speciesOptions,
    genderOptions,
    sizeOptions,
    statusOptions,
}: DogCreateProps) {
    const [photoPreview, setPhotoPreview] = useState<string | null>(null);
    const [step, setStep] = useState(0);
    const stepRefs = useRef<(HTMLDivElement | null)[]>([]);
    const saveAndAddMedicalRef = useRef<HTMLInputElement>(null);
    const isLastStep = step === STEPS.length - 1;

    function currentStepIsValid(): boolean {
        const fields =
            stepRefs.current[step]?.querySelectorAll<
                HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
            >('input, select, textarea') ?? [];

        for (const field of fields) {
            if (!field.checkValidity()) {
                field.reportValidity();

                return false;
            }
        }

        return true;
    }

    function goNext() {
        if (currentStepIsValid()) {
            setStep((value) => Math.min(value + 1, STEPS.length - 1));
        }
    }

    function handleKeyDown(e: KeyboardEvent<HTMLFormElement>) {
        const isTextInput =
            e.target instanceof HTMLInputElement ||
            e.target instanceof HTMLSelectElement;

        if (e.key === 'Enter' && isTextInput && !isLastStep) {
            e.preventDefault();
            goNext();
        }
    }

    function stepPanel(index: number, children: ReactNode) {
        return (
            <div
                ref={(element) => {
                    stepRefs.current[index] = element;
                }}
                className={cn(
                    'grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-2',
                    step !== index && 'hidden',
                )}
            >
                {children}
            </div>
        );
    }

    return (
        <>
            <Head title="Add New Dog" />

            <PageHeader
                title="Add New Dog"
                icon={PlusIcon}
                description="Enter the details for a new dog record."
            />

            <ol className="mb-6 flex flex-wrap items-center gap-2 text-xs">
                {STEPS.map((label, index) => (
                    <li key={label} className="flex items-center gap-2">
                        <span
                            className={cn(
                                'flex size-5 items-center justify-center rounded-full text-[10px] font-semibold',
                                index < step &&
                                    'bg-brand-200 text-brand-800 dark:bg-brand-800 dark:text-brand-100',
                                index === step &&
                                    'bg-brand-800 text-white dark:bg-brand-600',
                                index > step &&
                                    'bg-neutral-100 text-neutral-500 dark:bg-neutral-800',
                            )}
                        >
                            {index < step ? (
                                <CheckIcon className="size-3" />
                            ) : (
                                index + 1
                            )}
                        </span>
                        <span
                            className={cn(
                                index === step
                                    ? 'font-medium text-neutral-900 dark:text-neutral-100'
                                    : 'text-neutral-500 dark:text-neutral-400',
                            )}
                        >
                            {label}
                        </span>
                        {index < STEPS.length - 1 && (
                            <span className="mx-1 h-px w-6 bg-neutral-200 dark:bg-neutral-700" />
                        )}
                    </li>
                ))}
            </ol>

            <Form
                action={store()}
                onKeyDown={handleKeyDown}
                onError={(errors) =>
                    setStep(Math.min(...Object.keys(errors).map(stepForField)))
                }
            >
                {({ errors, processing }) => (
                    <Card className="max-w-3xl p-5">
                        <h2 className="mb-5 text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                            {STEPS[step]}
                        </h2>

                        {stepPanel(
                            0,
                            <>
                                <Field
                                    label="Dog Name *"
                                    htmlFor="name"
                                    error={errors.name}
                                >
                                    <Input
                                        id="name"
                                        name="name"
                                        placeholder="Enter name"
                                        required
                                    />
                                </Field>
                                <Field
                                    label="Gender *"
                                    htmlFor="gender"
                                    error={errors.gender}
                                >
                                    <Select
                                        id="gender"
                                        name="gender"
                                        required
                                        defaultValue=""
                                    >
                                        <option value="" disabled>
                                            Select gender
                                        </option>
                                        {genderOptions.map((o) => (
                                            <option
                                                key={o.value}
                                                value={o.value}
                                            >
                                                {o.label}
                                            </option>
                                        ))}
                                    </Select>
                                </Field>
                                <Field
                                    label="Date of Birth (or estimated)"
                                    htmlFor="date_of_birth"
                                    error={errors.date_of_birth}
                                >
                                    <Input
                                        id="date_of_birth"
                                        name="date_of_birth"
                                        type="date"
                                    />
                                    <label className="mt-2 flex items-center gap-2 text-xs text-neutral-600 dark:text-neutral-300">
                                        <Checkbox
                                            name="date_of_birth_is_approximate"
                                            value="1"
                                        />
                                        Date of birth is approximate
                                    </label>
                                </Field>
                                <Field
                                    label="Breed / Mix *"
                                    htmlFor="breed"
                                    error={errors.breed}
                                >
                                    <Input
                                        id="breed"
                                        name="breed"
                                        placeholder="Enter breed or mix"
                                        required
                                    />
                                </Field>
                                <Field
                                    label="Colour / Markings"
                                    htmlFor="colour_markings"
                                    error={errors.colour_markings}
                                >
                                    <Input
                                        id="colour_markings"
                                        name="colour_markings"
                                        placeholder="Enter colour / markings"
                                    />
                                </Field>
                                <Field
                                    label="Size *"
                                    htmlFor="size"
                                    error={errors.size}
                                >
                                    <Select
                                        id="size"
                                        name="size"
                                        required
                                        defaultValue=""
                                    >
                                        <option value="" disabled>
                                            Select size
                                        </option>
                                        {sizeOptions.map((o) => (
                                            <option
                                                key={o.value}
                                                value={o.value}
                                            >
                                                {o.label}
                                            </option>
                                        ))}
                                    </Select>
                                </Field>
                                <Field
                                    label="Species *"
                                    htmlFor="species"
                                    error={errors.species}
                                >
                                    <Select
                                        id="species"
                                        name="species"
                                        defaultValue="canine"
                                        required
                                    >
                                        {speciesOptions.map((o) => (
                                            <option
                                                key={o.value}
                                                value={o.value}
                                            >
                                                {o.label}
                                            </option>
                                        ))}
                                    </Select>
                                </Field>
                            </>,
                        )}

                        {stepPanel(
                            2,
                            <>
                                <Field
                                    label="Date Received"
                                    htmlFor="rescue_intake.date_received"
                                >
                                    <Input
                                        id="rescue_intake.date_received"
                                        name="rescue_intake[date_received]"
                                        type="date"
                                    />
                                </Field>
                                <Field
                                    label="Rescue Location"
                                    htmlFor="rescue_intake.rescue_location"
                                >
                                    <Input
                                        id="rescue_intake.rescue_location"
                                        name="rescue_intake[rescue_location]"
                                    />
                                </Field>
                                <Field
                                    label="Source of Intake"
                                    htmlFor="rescue_intake.source_of_intake"
                                >
                                    <Input
                                        id="rescue_intake.source_of_intake"
                                        name="rescue_intake[source_of_intake]"
                                    />
                                </Field>
                                <Field
                                    label="Intake Date"
                                    htmlFor="rescue_intake.intake_date"
                                >
                                    <Input
                                        id="rescue_intake.intake_date"
                                        name="rescue_intake[intake_date]"
                                        type="date"
                                    />
                                </Field>
                                <Field
                                    label="Initial Condition"
                                    htmlFor="rescue_intake.initial_condition"
                                    className="md:col-span-2"
                                >
                                    <Textarea
                                        id="rescue_intake.initial_condition"
                                        name="rescue_intake[initial_condition]"
                                        rows={2}
                                    />
                                </Field>
                                <Field
                                    label="Rescue Story"
                                    htmlFor="rescue_intake.rescue_story"
                                    className="md:col-span-2"
                                >
                                    <Textarea
                                        id="rescue_intake.rescue_story"
                                        name="rescue_intake[rescue_story]"
                                        rows={3}
                                    />
                                </Field>
                            </>,
                        )}

                        {stepPanel(
                            3,
                            <>
                                <Field
                                    label="Date of Neuter / Spay"
                                    htmlFor="medical.spayed_neutered_date"
                                >
                                    <Input
                                        id="medical.spayed_neutered_date"
                                        name="medical[spayed_neutered_date]"
                                        type="date"
                                    />
                                    <label className="mt-2 flex items-center gap-2 text-xs text-neutral-600 dark:text-neutral-300">
                                        <Checkbox
                                            name="medical[spayed_neutered]"
                                            value="1"
                                        />
                                        Spayed / Neutered
                                    </label>
                                </Field>
                                <Field
                                    label="Veterinary Clinic"
                                    htmlFor="medical.veterinary_clinic"
                                >
                                    <Input
                                        id="medical.veterinary_clinic"
                                        name="medical[veterinary_clinic]"
                                    />
                                </Field>
                                <Field
                                    label="Initial Health Assessment"
                                    htmlFor="medical.initial_health_assessment"
                                    className="md:col-span-2"
                                >
                                    <Textarea
                                        id="medical.initial_health_assessment"
                                        name="medical[initial_health_assessment]"
                                        rows={2}
                                    />
                                </Field>
                                <Field
                                    label="Medical Concerns"
                                    htmlFor="medical.medical_concerns"
                                    className="md:col-span-2"
                                >
                                    <Textarea
                                        id="medical.medical_concerns"
                                        name="medical[medical_concerns]"
                                        rows={2}
                                    />
                                </Field>
                                <Field
                                    label="Treatment Required"
                                    htmlFor="medical.treatment_required"
                                    className="md:col-span-2"
                                >
                                    <Textarea
                                        id="medical.treatment_required"
                                        name="medical[treatment_required]"
                                        rows={2}
                                    />
                                </Field>
                            </>,
                        )}

                        {stepPanel(
                            1,
                            <>
                                <Field
                                    label="Microchip Number"
                                    htmlFor="identification.microchip_number"
                                >
                                    <Input
                                        id="identification.microchip_number"
                                        name="identification[microchip_number]"
                                    />
                                </Field>
                                <Field
                                    label="Date of Microchipping"
                                    htmlFor="identification.microchip_date"
                                >
                                    <Input
                                        id="identification.microchip_date"
                                        name="identification[microchip_date]"
                                        type="date"
                                    />
                                </Field>
                                <Field
                                    label="Chip Location"
                                    htmlFor="identification.microchip_location"
                                >
                                    <Input
                                        id="identification.microchip_location"
                                        name="identification[microchip_location]"
                                    />
                                </Field>
                            </>,
                        )}

                        {stepPanel(
                            4,
                            <>
                                <Field
                                    label="Profile photo"
                                    htmlFor="photo"
                                    error={errors.photo}
                                    hint="Optional. JPG, PNG or WebP, up to 8 MB. You can add more photos later."
                                    className="md:col-span-2"
                                >
                                    <Input
                                        id="photo"
                                        name="photo"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={(event) => {
                                            const file =
                                                event.target.files?.[0];
                                            setPhotoPreview(
                                                file
                                                    ? URL.createObjectURL(file)
                                                    : null,
                                            );
                                        }}
                                        className="file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1 file:text-xs file:font-medium file:text-brand-800 dark:file:bg-brand-900/60 dark:file:text-brand-200"
                                    />
                                </Field>
                                {photoPreview && (
                                    <img
                                        src={photoPreview}
                                        alt="Selected profile photo"
                                        className="size-40 rounded-xl object-cover"
                                    />
                                )}
                            </>,
                        )}

                        {stepPanel(
                            5,
                            <>
                                <Field
                                    label="Current status"
                                    htmlFor="current_status"
                                    error={errors.current_status}
                                    hint="Most new dogs start at SCAS. Foster and adoption details can be added from the dog's profile."
                                >
                                    <Select
                                        id="current_status"
                                        name="current_status"
                                        defaultValue="at_scas"
                                    >
                                        {statusOptions.map((o) => (
                                            <option
                                                key={o.value}
                                                value={o.value}
                                            >
                                                {o.label}
                                            </option>
                                        ))}
                                    </Select>
                                </Field>
                                <Field
                                    label="Current location"
                                    htmlFor="current_location"
                                    error={errors.current_location}
                                >
                                    <Input
                                        id="current_location"
                                        name="current_location"
                                        placeholder="e.g. Ras Al Khaimah"
                                    />
                                </Field>
                            </>,
                        )}

                        <input
                            type="hidden"
                            name="save_and_add_medical"
                            ref={saveAndAddMedicalRef}
                            defaultValue="0"
                        />

                        <div className="mt-6 flex flex-wrap justify-end gap-2">
                            {step === 0 ? (
                                <Button
                                    variant="secondary"
                                    onClick={() => window.history.back()}
                                >
                                    Cancel
                                </Button>
                            ) : (
                                <Button
                                    variant="secondary"
                                    onClick={() =>
                                        setStep((value) => value - 1)
                                    }
                                >
                                    <ArrowLeftIcon className="size-3.5" />
                                    Back
                                </Button>
                            )}

                            {isLastStep ? (
                                <>
                                    <Button
                                        type="submit"
                                        variant="secondary"
                                        disabled={processing}
                                        onClick={() => {
                                            if (saveAndAddMedicalRef.current) {
                                                saveAndAddMedicalRef.current.value =
                                                    '1';
                                            }
                                        }}
                                    >
                                        Save & Add Medical Details
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        Save Dog Record
                                    </Button>
                                </>
                            ) : (
                                <Button onClick={goNext}>
                                    Next
                                    <ArrowRightIcon className="size-3.5" />
                                </Button>
                            )}
                        </div>
                    </Card>
                )}
            </Form>
        </>
    );
}

DogCreate.layout = (page: ReactElement) => (
    <AuthenticatedLayout>{page}</AuthenticatedLayout>
);
