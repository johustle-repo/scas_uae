export type DogStatus =
    | 'at_scas'
    | 'local_foster'
    | 'international_foster'
    | 'adopted_uae'
    | 'adopted_internationally'
    | 'returned_rehoming'
    | 'memorial'
    | 'archived';

export type PlacementType = 'local' | 'international';
export type Gender = 'male' | 'female';
export type Size = 'small' | 'medium' | 'large' | 'giant';
export type Species = 'canine' | 'canine_puppy';
export type EnergyLevel = 'low' | 'medium' | 'high';
export type TrainingLevel = 'untrained' | 'basic' | 'well_trained';
export type RegistrationStatus =
    | 'not_started'
    | 'pending'
    | 'registered'
    | 'expired';
export type ImportFlagType =
    | 'approximate_dob'
    | 'placeholder_microchip'
    | 'possible_placement_unconfirmed'
    | 'incomplete_record';

export interface Option<T extends string = string> {
    value: T;
    label: string;
}

export interface DogMedicalProfile {
    id: number;
    dog_id: number;
    spayed_neutered: boolean | null;
    spayed_neutered_date: string | null;
    last_vet_check_date: string | null;
    veterinary_clinic: string | null;
    initial_health_assessment: string | null;
    medical_concerns: string | null;
    treatment_required: string | null;
    medications: string | null;
    medical_notes: string | null;
}

export interface DogIdentification {
    id: number;
    dog_id: number;
    microchip_number: string | null;
    microchip_date: string | null;
    microchip_location: string | null;
    microchip_registration_status: RegistrationStatus | null;
    passport_number: string | null;
    passport_issue_date: string | null;
    passport_expiry_date: string | null;
    passport_issuing_authority: string | null;
    passport_status: RegistrationStatus | null;
    pcc_number: string | null;
    pcc_registration_date: string | null;
    pcc_registration_status: RegistrationStatus | null;
    supporting_documents_notes: string | null;
}

export interface DogRescueIntake {
    id: number;
    dog_id: number;
    date_received: string | null;
    rescue_location: string | null;
    how_found: string | null;
    initial_condition: string | null;
    source_of_intake: string | null;
    previous_owner_surrender_details: string | null;
    intake_date: string | null;
    intake_notes: string | null;
    rescue_story: string | null;
}

export interface DogVaccination {
    id: number;
    dog_id: number;
    vaccine_details: string;
    administered_date: string | null;
    next_due_date: string | null;
    expiry_annotation: string | null;
    notes: string | null;
}

export interface DogFosterRecord {
    id: number;
    dog_id: number;
    foster_type: PlacementType;
    foster_family_name: string;
    location: string | null;
    start_date: string | null;
    end_date: string | null;
    notes: string | null;
}

export interface DogAdoptionRecord {
    id: number;
    dog_id: number;
    adoption_type: PlacementType;
    adopter_name: string;
    adopter_location: string | null;
    adopter_contact: string | null;
    adoption_date: string | null;
    return_date: string | null;
    return_reason: string | null;
    notes: string | null;
}

export interface DogImportFlag {
    id: number;
    dog_id: number;
    flag_type: ImportFlagType;
    message: string;
    created_at: string;
}

export interface Dog {
    id: number;
    scas_id: string;
    legacy_source_row: number | null;
    name: string;
    species: Species;
    breed: string | null;
    colour_markings: string | null;
    gender: Gender | null;
    size: Size | null;
    date_of_birth: string | null;
    date_of_birth_is_approximate: boolean;
    estimated_age_notes: string | null;
    current_status: DogStatus;
    current_location: string | null;
    photo_path: string | null;
    /** Authenticated URL for the profile photo, or null when there is none. */
    photo_url: string | null;
    personality_description: string | null;
    energy_level: EnergyLevel | null;
    temperament: string | null;
    good_with_dogs: boolean | null;
    good_with_cats: boolean | null;
    good_with_children: boolean | null;
    good_with_adults: boolean | null;
    training_level: TrainingLevel | null;
    potty_trained: boolean | null;
    leash_trained: boolean | null;
    basic_commands_notes: string | null;
    behavioural_notes: string | null;
    special_requirements: string | null;
    archived_at: string | null;
    created_at: string;
    updated_at: string;
    medical_profile?: DogMedicalProfile | null;
    identification?: DogIdentification | null;
    rescue_intake?: DogRescueIntake | null;
    vaccinations?: DogVaccination[];
    foster_records?: DogFosterRecord[];
    adoption_records?: DogAdoptionRecord[];
    import_flags?: DogImportFlag[];
    documents?: DogDocument[];
    photos?: DogPhoto[];
}

export type DocumentCategory =
    | 'vaccination_certificate'
    | 'veterinary_record'
    | 'microchip'
    | 'adoption_form'
    | 'foster_agreement'
    | 'travel'
    | 'pet_passport'
    | 'identification'
    | 'other';

export interface DogDocument {
    id: number;
    dog_id: number;
    category: DocumentCategory;
    title: string;
    original_name: string;
    mime_type: string;
    size: number;
    uploaded_by: number | null;
    uploader?: { id: number; name: string } | null;
    view_url: string;
    download_url: string;
    created_at: string;
    updated_at: string;
}

export type PhotoKind = 'profile' | 'rescue' | 'gallery';

export interface DogPhoto {
    id: number;
    dog_id: number;
    kind: PhotoKind;
    caption: string | null;
    url: string;
    /** Whether this is the dog's current profile photo. */
    is_profile: boolean;
    created_at: string;
}

export interface AuditLogEntry {
    id: number;
    user_id: number | null;
    user?: { id: number; name: string } | null;
    dog?: { id: number; name: string; scas_id: string } | null;
    action: string;
    dog_id: number | null;
    description: string;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string;
}

export interface PlacementHistoryEvent {
    date: string;
    type: 'rescue' | 'foster' | 'adoption';
    location: string | null;
    notes: string | null;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

export const DOG_STATUS_LABELS: Record<DogStatus, string> = {
    at_scas: 'At SCAS',
    local_foster: 'Local Foster',
    international_foster: 'International Foster',
    adopted_uae: 'Adopted in UAE',
    adopted_internationally: 'Adopted Internationally',
    returned_rehoming: 'Returned / Rehoming',
    memorial: 'Memorial',
    archived: 'Archived',
};

export function calculateAge(dateOfBirth: string | null): number | null {
    if (!dateOfBirth) {
        return null;
    }

    const dob = new Date(dateOfBirth);
    const diffMs = Date.now() - dob.getTime();

    return Math.max(0, Math.floor(diffMs / (1000 * 60 * 60 * 24 * 365.25)));
}

/**
 * Human-friendly age, e.g. "3 yrs", or "6 mos" for dogs under a year.
 */
export function formatAge(dateOfBirth: string | null): string {
    const age = calculateAge(dateOfBirth);

    if (age === null || dateOfBirth === null) {
        return '—';
    }

    if (age === 0) {
        const dob = new Date(dateOfBirth);
        const now = new Date();
        const months = Math.max(
            0,
            (now.getFullYear() - dob.getFullYear()) * 12 +
                now.getMonth() -
                dob.getMonth() -
                (now.getDate() < dob.getDate() ? 1 : 0),
        );

        return months <= 1 ? `${months} mo` : `${months} mos`;
    }

    return age === 1 ? '1 yr' : `${age} yrs`;
}

const MONTHS = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
];

/**
 * Format a date and time as e.g. "21 Sep 2026, 14:05".
 */
export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    const time = date.toLocaleTimeString('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
    });

    return `${formatDate(value)}, ${time}`;
}

/**
 * Human-readable file size, e.g. "1.2 MB".
 */
export function formatFileSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

/**
 * Format a date as e.g. "21 Sep 2026", or an em dash when missing.
 */
export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    return `${date.getDate()} ${MONTHS[date.getMonth()]} ${date.getFullYear()}`;
}

/**
 * Placement type implied by a dog's current status, if any.
 */
export function placementLabel(status: DogStatus): string | null {
    switch (status) {
        case 'local_foster':
        case 'adopted_uae':
            return 'Local';
        case 'international_foster':
        case 'adopted_internationally':
            return 'International';
        default:
            return null;
    }
}

/** Today's date as "YYYY-MM-DD", for comparing against date-only fields. */
function todayDateString(): string {
    const now = new Date();

    return [
        now.getFullYear(),
        String(now.getMonth() + 1).padStart(2, '0'),
        String(now.getDate()).padStart(2, '0'),
    ].join('-');
}

/**
 * The most recent dose of each distinct vaccine. Earlier doses of the same
 * vaccine have been superseded by a booster, so their due dates no longer
 * count. Mirrors Dog::currentVaccinations().
 */
export function currentVaccinations(
    vaccinations: DogVaccination[] | undefined,
): DogVaccination[] {
    const latestByVaccine = new Map<string, DogVaccination>();
    const doseDate = (v: DogVaccination) =>
        (v.administered_date ?? v.next_due_date ?? '').slice(0, 10);

    for (const vaccination of vaccinations ?? []) {
        const key = vaccination.vaccine_details
            .trim()
            .replace(/\s+/g, ' ')
            .toLowerCase();
        const latest = latestByVaccine.get(key);

        if (
            !latest ||
            doseDate(vaccination) > doseDate(latest) ||
            (doseDate(vaccination) === doseDate(latest) &&
                vaccination.id > latest.id)
        ) {
            latestByVaccine.set(key, vaccination);
        }
    }

    return [...latestByVaccine.values()];
}

export function isVaccinationsUpToDate(
    vaccinations: DogVaccination[] | undefined,
): boolean {
    if (!vaccinations || vaccinations.length === 0) {
        return false;
    }

    const today = todayDateString();

    return !currentVaccinations(vaccinations).some(
        (v) => v.next_due_date !== null && v.next_due_date.slice(0, 10) < today,
    );
}

export function nextVaccinationDate(
    vaccinations: DogVaccination[] | undefined,
): string | null {
    const today = todayDateString();
    const upcoming = currentVaccinations(vaccinations)
        .map((v) => v.next_due_date)
        .filter(
            (dueDate): dueDate is string =>
                dueDate !== null && dueDate.slice(0, 10) >= today,
        )
        .sort((a, b) => a.slice(0, 10).localeCompare(b.slice(0, 10)));

    return upcoming[0] ?? null;
}
