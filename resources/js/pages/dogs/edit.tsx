import { update } from '@/actions/App/Http/Controllers/DogController';
import { PencilIcon } from '@/components/icons';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { AuthenticatedLayout } from '@/layouts/authenticated-layout';
import dogsRoutes from '@/routes/dogs';
import type { Dog, Option } from '@/types/dog';
import { Form, Head, Link } from '@inertiajs/react';
import type { ReactElement } from 'react';

interface DogEditProps {
    dog: Dog;
    speciesOptions: Option[];
    genderOptions: Option[];
    sizeOptions: Option[];
    energyLevelOptions: Option[];
    trainingLevelOptions: Option[];
    statusOptions: Option[];
}

export default function DogEdit({
    dog,
    speciesOptions,
    genderOptions,
    sizeOptions,
    energyLevelOptions,
    trainingLevelOptions,
    statusOptions,
}: DogEditProps) {
    return (
        <>
            <Head title={`Edit ${dog.name}`} />

            <PageHeader
                back={{
                    href: dogsRoutes.show.url(dog.id),
                    label: `Back to ${dog.name}`,
                }}
                title={`Edit ${dog.name}`}
                description="Update this dog's profile details."
                icon={PencilIcon}
            />

            <Form action={update(dog.id)} className="space-y-6">
                {({ errors, processing }) => (
                    <>
                        <Card>
                            <CardHeader>
                                <CardTitle>Basic Information</CardTitle>
                            </CardHeader>
                            <CardContent className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="name">Dog Name *</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={dog.name}
                                        required
                                    />
                                    {errors.name && (
                                        <p className="mt-1 text-sm text-red-600">
                                            {errors.name}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="species">Species *</Label>
                                    <Select
                                        id="species"
                                        name="species"
                                        defaultValue={dog.species}
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
                                </div>
                                <div>
                                    <Label htmlFor="gender">Gender</Label>
                                    <Select
                                        id="gender"
                                        name="gender"
                                        defaultValue={dog.gender ?? ''}
                                    >
                                        <option value="">Unknown</option>
                                        {genderOptions.map((o) => (
                                            <option
                                                key={o.value}
                                                value={o.value}
                                            >
                                                {o.label}
                                            </option>
                                        ))}
                                    </Select>
                                </div>
                                <div>
                                    <Label htmlFor="breed">Breed / Mix</Label>
                                    <Input
                                        id="breed"
                                        name="breed"
                                        defaultValue={dog.breed ?? ''}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="date_of_birth">
                                        Date of Birth
                                    </Label>
                                    <Input
                                        id="date_of_birth"
                                        name="date_of_birth"
                                        type="date"
                                        defaultValue={dog.date_of_birth ?? ''}
                                    />
                                </div>
                                <div className="flex items-end gap-2 pb-2">
                                    <Checkbox
                                        id="date_of_birth_is_approximate"
                                        name="date_of_birth_is_approximate"
                                        value="1"
                                        defaultChecked={
                                            dog.date_of_birth_is_approximate
                                        }
                                    />
                                    <Label
                                        htmlFor="date_of_birth_is_approximate"
                                        className="mb-0"
                                    >
                                        Date of birth is approximate
                                    </Label>
                                </div>
                                <div>
                                    <Label htmlFor="size">Size</Label>
                                    <Select
                                        id="size"
                                        name="size"
                                        defaultValue={dog.size ?? ''}
                                    >
                                        <option value="">Unknown</option>
                                        {sizeOptions.map((o) => (
                                            <option
                                                key={o.value}
                                                value={o.value}
                                            >
                                                {o.label}
                                            </option>
                                        ))}
                                    </Select>
                                </div>
                                <div>
                                    <Label htmlFor="colour_markings">
                                        Colour / Markings
                                    </Label>
                                    <Input
                                        id="colour_markings"
                                        name="colour_markings"
                                        defaultValue={dog.colour_markings ?? ''}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="current_status">
                                        Current Status *
                                    </Label>
                                    <Select
                                        id="current_status"
                                        name="current_status"
                                        defaultValue={dog.current_status}
                                        required
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
                                </div>
                                <div>
                                    <Label htmlFor="current_location">
                                        Current Location
                                    </Label>
                                    <Input
                                        id="current_location"
                                        name="current_location"
                                        defaultValue={
                                            dog.current_location ?? ''
                                        }
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Personality & Behaviour</CardTitle>
                            </CardHeader>
                            <CardContent className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="md:col-span-2">
                                    <Label htmlFor="personality_description">
                                        Personality Description
                                    </Label>
                                    <Textarea
                                        id="personality_description"
                                        name="personality_description"
                                        rows={2}
                                        defaultValue={
                                            dog.personality_description ?? ''
                                        }
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="energy_level">
                                        Energy Level
                                    </Label>
                                    <Select
                                        id="energy_level"
                                        name="energy_level"
                                        defaultValue={dog.energy_level ?? ''}
                                    >
                                        <option value="">Unknown</option>
                                        {energyLevelOptions.map((o) => (
                                            <option
                                                key={o.value}
                                                value={o.value}
                                            >
                                                {o.label}
                                            </option>
                                        ))}
                                    </Select>
                                </div>
                                <div>
                                    <Label htmlFor="training_level">
                                        Training Level
                                    </Label>
                                    <Select
                                        id="training_level"
                                        name="training_level"
                                        defaultValue={dog.training_level ?? ''}
                                    >
                                        <option value="">Unknown</option>
                                        {trainingLevelOptions.map((o) => (
                                            <option
                                                key={o.value}
                                                value={o.value}
                                            >
                                                {o.label}
                                            </option>
                                        ))}
                                    </Select>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="good_with_dogs"
                                        name="good_with_dogs"
                                        value="1"
                                        defaultChecked={!!dog.good_with_dogs}
                                    />
                                    <Label
                                        htmlFor="good_with_dogs"
                                        className="mb-0"
                                    >
                                        Good with dogs
                                    </Label>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="good_with_cats"
                                        name="good_with_cats"
                                        value="1"
                                        defaultChecked={!!dog.good_with_cats}
                                    />
                                    <Label
                                        htmlFor="good_with_cats"
                                        className="mb-0"
                                    >
                                        Good with cats
                                    </Label>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="good_with_children"
                                        name="good_with_children"
                                        value="1"
                                        defaultChecked={
                                            !!dog.good_with_children
                                        }
                                    />
                                    <Label
                                        htmlFor="good_with_children"
                                        className="mb-0"
                                    >
                                        Good with children
                                    </Label>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="good_with_adults"
                                        name="good_with_adults"
                                        value="1"
                                        defaultChecked={!!dog.good_with_adults}
                                    />
                                    <Label
                                        htmlFor="good_with_adults"
                                        className="mb-0"
                                    >
                                        Good with adults
                                    </Label>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="potty_trained"
                                        name="potty_trained"
                                        value="1"
                                        defaultChecked={!!dog.potty_trained}
                                    />
                                    <Label
                                        htmlFor="potty_trained"
                                        className="mb-0"
                                    >
                                        Potty trained
                                    </Label>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="leash_trained"
                                        name="leash_trained"
                                        value="1"
                                        defaultChecked={!!dog.leash_trained}
                                    />
                                    <Label
                                        htmlFor="leash_trained"
                                        className="mb-0"
                                    >
                                        Leash trained
                                    </Label>
                                </div>
                                <div className="md:col-span-2">
                                    <Label htmlFor="behavioural_notes">
                                        Behavioural Notes
                                    </Label>
                                    <Textarea
                                        id="behavioural_notes"
                                        name="behavioural_notes"
                                        rows={2}
                                        defaultValue={
                                            dog.behavioural_notes ?? ''
                                        }
                                    />
                                </div>
                                <div className="md:col-span-2">
                                    <Label htmlFor="special_requirements">
                                        Special Requirements
                                    </Label>
                                    <Textarea
                                        id="special_requirements"
                                        name="special_requirements"
                                        rows={2}
                                        defaultValue={
                                            dog.special_requirements ?? ''
                                        }
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <div className="flex justify-end gap-3">
                            <Link href={dogsRoutes.show.url(dog.id)}>
                                <Button type="button" variant="secondary">
                                    Cancel
                                </Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                Save Changes
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

DogEdit.layout = (page: ReactElement) => (
    <AuthenticatedLayout>{page}</AuthenticatedLayout>
);
