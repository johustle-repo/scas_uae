import { ResetIcon } from '@/components/icons';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import dogsRoutes from '@/routes/dogs';
import type { Option } from '@/types/dog';
import { router } from '@inertiajs/react';
import { useState } from 'react';

export type FilterValue = string | string[] | undefined;

export interface DogFilters {
    status?: FilterValue;
    placement?: FilterValue;
    gender?: FilterValue;
    size?: FilterValue;
    breed?: string;
    q?: string;
    age_from?: string;
    age_to?: string;
    needs_review?: string;
    archived?: string;
}

export type MultiFilterKey = 'status' | 'placement' | 'gender' | 'size';

export interface FilterOptions {
    statusOptions: Option[];
    placementOptions: Option[];
    genderOptions: Option[];
    sizeOptions: Option[];
}

export function filterValues(value: FilterValue): string[] {
    if (value === undefined || value === '') {
        return [];
    }

    return Array.isArray(value) ? value : [value];
}

/**
 * Send the given filters to the dog list, dropping empty values.
 */
export function visitDogList(filters: DogFilters): void {
    const query = Object.fromEntries(
        Object.entries(filters).filter(([, value]) =>
            Array.isArray(value) ? value.length > 0 : Boolean(value),
        ),
    );

    router.get(dogsRoutes.index.url(), query, {
        preserveState: true,
        replace: true,
    });
}

interface FilterPanelProps extends FilterOptions {
    filters: DogFilters;
    onClose: () => void;
}

export function FilterPanel({
    filters,
    statusOptions,
    placementOptions,
    genderOptions,
    sizeOptions,
    onClose,
}: FilterPanelProps) {
    const [draft, setDraft] = useState<DogFilters>(filters);

    function toggle(key: MultiFilterKey, value: string) {
        const current = filterValues(draft[key]);
        setDraft({
            ...draft,
            [key]: current.includes(value)
                ? current.filter((item) => item !== value)
                : [...current, value],
        });
    }

    function apply() {
        visitDogList({ ...draft, q: filters.q });
        onClose();
    }

    function clear() {
        setDraft({});
        visitDogList({ q: filters.q });
        onClose();
    }

    const groups: { key: MultiFilterKey; label: string; options: Option[] }[] =
        [
            { key: 'status', label: 'Status', options: statusOptions },
            { key: 'placement', label: 'Placement', options: placementOptions },
            { key: 'gender', label: 'Gender', options: genderOptions },
            { key: 'size', label: 'Size', options: sizeOptions },
        ];

    return (
        <Card className="mb-6 p-5">
            <div className="mb-4 flex items-center justify-between">
                <div>
                    <h2 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        Search & Filter
                    </h2>
                    <p className="text-xs text-neutral-500 dark:text-neutral-400">
                        Find dogs using specific criteria.
                    </p>
                </div>
                <button
                    type="button"
                    onClick={() => setDraft({})}
                    className="inline-flex items-center gap-1 text-xs text-neutral-500 hover:text-brand-700 dark:text-neutral-400"
                >
                    <ResetIcon className="size-3.5" />
                    Reset Filters
                </button>
            </div>

            <div className="grid grid-cols-2 gap-6 md:grid-cols-4">
                {groups.map((group) => (
                    <fieldset key={group.key}>
                        <legend className="mb-2 text-xs font-semibold text-neutral-900 dark:text-neutral-100">
                            {group.label}
                        </legend>
                        <div className="space-y-2">
                            {group.options.map((option) => {
                                const id = `filter-${group.key}-${option.value}`;

                                return (
                                    <label
                                        key={option.value}
                                        htmlFor={id}
                                        className="flex cursor-pointer items-center gap-2 text-xs text-neutral-600 dark:text-neutral-300"
                                    >
                                        <Checkbox
                                            id={id}
                                            checked={filterValues(
                                                draft[group.key],
                                            ).includes(option.value)}
                                            onChange={() =>
                                                toggle(group.key, option.value)
                                            }
                                        />
                                        {option.label}
                                    </label>
                                );
                            })}
                        </div>
                    </fieldset>
                ))}
            </div>

            <div className="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <Label htmlFor="filter-breed">Breed</Label>
                    <Input
                        id="filter-breed"
                        placeholder="All breeds"
                        value={draft.breed ?? ''}
                        onChange={(e) =>
                            setDraft({
                                ...draft,
                                breed: e.target.value || undefined,
                            })
                        }
                    />
                </div>
                <div>
                    <Label htmlFor="filter-age-from">Age Range (years)</Label>
                    <div className="flex items-center gap-2">
                        <Input
                            id="filter-age-from"
                            type="number"
                            min={0}
                            placeholder="From"
                            aria-label="Minimum age"
                            value={draft.age_from ?? ''}
                            onChange={(e) =>
                                setDraft({
                                    ...draft,
                                    age_from: e.target.value || undefined,
                                })
                            }
                        />
                        <Input
                            type="number"
                            min={0}
                            placeholder="To"
                            aria-label="Maximum age"
                            value={draft.age_to ?? ''}
                            onChange={(e) =>
                                setDraft({
                                    ...draft,
                                    age_to: e.target.value || undefined,
                                })
                            }
                        />
                    </div>
                </div>
            </div>

            <div className="mt-5 flex justify-end gap-2">
                <Button size="sm" onClick={apply}>
                    Apply Filters
                </Button>
                <Button variant="secondary" size="sm" onClick={clear}>
                    Clear
                </Button>
            </div>
        </Card>
    );
}
