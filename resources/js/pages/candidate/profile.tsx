import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEvent, type ReactNode, useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { PageFrame } from '@/components/talent/page-frame';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    availabilityLabels,
    educationLabels,
    fieldClassName,
    skillLevelLabels,
} from '@/lib/talent';
import { edit } from '@/routes/candidate-profile';
import { edit as editAccount } from '@/routes/profile';
import type {
    TalentCandidate,
    TalentDiploma,
    TalentTraining,
} from '@/types';

type SkillOption = { id: string; name: string };

type AccountSummary = {
    first_name: string;
    name: string;
    email: string;
    phone: string | null;
    address: string | null;
    city: string | null;
    state: string | null;
    zip: string | null;
    country: string | null;
};

type OtherSkill = { name: string; level: string };

type ProfileForm = {
    phone: string;
    country: string;
    city: string;
    education_level: string;
    field_of_study: string;
    years_experience: string;
    projects_count: string;
    github_url: string;
    portfolio_url: string;
    linkedin_url: string;
    availability: string;
    cv: File | null;
    interests: string[];
    other_skills: OtherSkill[];
    skills: { id: string; level: string }[];
    diplomas: TalentDiploma[];
    trainings: TalentTraining[];
};

const STEPS = [
    { key: 'identity', label: 'Coordonnées' },
    { key: 'path', label: 'Parcours' },
    { key: 'education', label: 'Diplômes' },
    { key: 'skills', label: 'Compétences' },
    { key: 'extras', label: 'Intérêts' },
] as const;

export default function CandidateProfile({
    account,
    candidate,
    skillOptions,
}: {
    account: AccountSummary;
    candidate: TalentCandidate | null;
    skillOptions: SkillOption[];
}) {
    const [step, setStep] = useState(0);
    const [draft, setDraft] = useState('');
    const [diplomaDraft, setDiplomaDraft] = useState('');
    const [trainingDraft, setTrainingDraft] = useState('');
    const [draftLevel, setDraftLevel] = useState('beginner');
    const [skillFilter, setSkillFilter] = useState('');

    const form = useForm<ProfileForm>({
        phone: candidate?.phone ?? account.phone ?? '',
        country: candidate?.country ?? account.country ?? '',
        city: candidate?.city ?? account.city ?? '',
        education_level: candidate?.education_level ?? '',
        field_of_study: candidate?.field_of_study ?? '',
        years_experience: String(candidate?.years_experience ?? 0),
        projects_count: String(candidate?.projects_count ?? 0),
        github_url: candidate?.github_url ?? '',
        portfolio_url: candidate?.portfolio_url ?? '',
        linkedin_url: candidate?.linkedin_url ?? '',
        availability: candidate?.availability ?? '',
        cv: null,
        interests: candidate?.interests ?? [],
        other_skills: candidate?.other_skills ?? [],
        skills: (candidate?.skills ?? []).map((skill) => ({
            id: skill.id,
            level: skill.level,
        })),
        diplomas: candidate?.diplomas ?? [],
        trainings: candidate?.trainings ?? [],
    });

    const filteredSkills = useMemo(() => {
        const query = skillFilter.trim().toLowerCase();

        if (!query) {
            return skillOptions;
        }

        return skillOptions.filter((skill) =>
            skill.name.toLowerCase().includes(query),
        );
    }, [skillFilter, skillOptions]);

    function save(event?: FormEvent) {
        event?.preventDefault();
        form.transform((data) => ({
            ...data,
            education_level: data.education_level || null,
            availability: data.availability || null,
            diplomas: data.diplomas.filter((item) => item.title.trim()),
            trainings: data.trainings.filter((item) => item.title.trim()),
            cv: data.cv ?? undefined,
        }));
        form.post('/profile', { forceFormData: true });
    }

    function skillLevel(skillId: string): string | undefined {
        return form.data.skills.find((skill) => skill.id === skillId)?.level;
    }

    function toggleSkill(skillId: string, checked: boolean) {
        if (checked) {
            form.setData('skills', [
                ...form.data.skills,
                { id: skillId, level: 'beginner' },
            ]);

            return;
        }

        form.setData(
            'skills',
            form.data.skills.filter((skill) => skill.id !== skillId),
        );
    }

    function setCatalogLevel(skillId: string, level: string) {
        form.setData(
            'skills',
            form.data.skills.map((skill) =>
                skill.id === skillId ? { ...skill, level } : skill,
            ),
        );
    }

    function addTag(
        list: 'interests',
        value: string,
    ): void;
    function addTag(
        list: 'other_skills',
        value: string,
        level: string,
    ): void;
    function addTag(
        list: 'interests' | 'other_skills',
        value: string,
        level = 'beginner',
    ) {
        const trimmed = value.trim();

        if (!trimmed) {
            return;
        }

        if (list === 'interests') {
            if (form.data.interests.includes(trimmed)) {
                return;
            }

            form.setData('interests', [...form.data.interests, trimmed]);
            setDraft('');

            return;
        }

        const matched = skillOptions.find(
            (skill) => skill.name.toLowerCase() === trimmed.toLowerCase(),
        );

        if (matched) {
            if (!skillLevel(matched.id)) {
                form.setData('skills', [
                    ...form.data.skills,
                    { id: matched.id, level },
                ]);
            }

            setDraft('');

            return;
        }

        if (
            form.data.other_skills.some(
                (skill) =>
                    skill.name.toLowerCase() === trimmed.toLowerCase(),
            )
        ) {
            return;
        }

        form.setData('other_skills', [
            ...form.data.other_skills,
            { name: trimmed, level },
        ]);
        setDraft('');
    }

    function addListedItem(kind: 'diplomas' | 'trainings') {
        const title = (
            kind === 'diplomas' ? diplomaDraft : trainingDraft
        ).trim();

        if (!title) {
            return;
        }

        if (kind === 'diplomas') {
            form.setData('diplomas', [
                ...form.data.diplomas,
                {
                    title,
                    institution: null,
                    year: null,
                    description: null,
                },
            ]);
            setDiplomaDraft('');

            return;
        }

        form.setData('trainings', [
            ...form.data.trainings,
            {
                title,
                organization: null,
                year: null,
                description: null,
            },
        ]);
        setTrainingDraft('');
    }

    function updateDiploma(
        index: number,
        patch: Partial<TalentDiploma>,
    ) {
        const diplomas = [...form.data.diplomas];
        diplomas[index] = { ...diplomas[index], ...patch };
        form.setData('diplomas', diplomas);
    }

    function updateTraining(
        index: number,
        patch: Partial<TalentTraining>,
    ) {
        const trainings = [...form.data.trainings];
        trainings[index] = { ...trainings[index], ...patch };
        form.setData('trainings', trainings);
    }

    const place = [account.city, account.country].filter(Boolean).join(', ');
    const isLast = step === STEPS.length - 1;
    const current = STEPS[step];

    return (
        <>
            <Head title="Profil" />
            <PageFrame
                size="narrow"
                eyebrow="Profil"
                title="Vos profils"
                description="Compte pour l’identité. Professionnel pour postuler. La lettre de motivation reste dans chaque candidature."
            >
                <Card>
                    <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
                        <div>
                            <CardTitle>Profil compte</CardTitle>
                            <CardDescription>
                                Identité et coordonnées du compte.
                            </CardDescription>
                        </div>
                        <Button variant="outline" asChild>
                            <Link href={editAccount()}>Modifier le compte</Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="grid gap-1 text-sm text-muted-foreground">
                        <p className="font-medium text-foreground">
                            {account.first_name} {account.name}
                        </p>
                        <p>{account.email}</p>
                        {account.phone && <p>{account.phone}</p>}
                        {place && <p>{place}</p>}
                    </CardContent>
                </Card>

                <form onSubmit={save} className="flex flex-col gap-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Profil professionnel</CardTitle>
                            <CardDescription>
                                Étape {step + 1} sur {STEPS.length} —{' '}
                                {current.label}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            <nav
                                aria-label="Étapes du profil"
                                className="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1"
                            >
                                {STEPS.map((item, index) => (
                                    <button
                                        key={item.key}
                                        type="button"
                                        onClick={() => {
                                            setDraft('');
                                            setStep(index);
                                        }}
                                        className={
                                            index === step
                                                ? 'shrink-0 rounded-full bg-[#b7c48a] px-3 py-1.5 text-sm font-medium text-[#023940]'
                                                : 'shrink-0 rounded-full bg-muted px-3 py-1.5 text-sm text-foreground'
                                        }
                                    >
                                        <span className="sm:hidden">
                                            {index + 1}
                                        </span>
                                        <span className="hidden sm:inline">
                                            {index + 1}. {item.label}
                                        </span>
                                    </button>
                                ))}
                            </nav>

                            {step === 0 && (
                                <div className="grid gap-4">
                                    <Field
                                        label="Téléphone"
                                        error={form.errors.phone}
                                    >
                                        <Input
                                            value={form.data.phone}
                                            onChange={(event) =>
                                                form.setData(
                                                    'phone',
                                                    event.target.value,
                                                )
                                            }
                                            autoComplete="tel"
                                        />
                                    </Field>
                                    <Field
                                        label="Pays"
                                        error={form.errors.country}
                                        required
                                    >
                                        <Input
                                            value={form.data.country}
                                            onChange={(event) =>
                                                form.setData(
                                                    'country',
                                                    event.target.value,
                                                )
                                            }
                                            required
                                            autoComplete="country-name"
                                        />
                                    </Field>
                                    <Field
                                        label="Ville"
                                        error={form.errors.city}
                                    >
                                        <Input
                                            value={form.data.city}
                                            onChange={(event) =>
                                                form.setData(
                                                    'city',
                                                    event.target.value,
                                                )
                                            }
                                            autoComplete="address-level2"
                                        />
                                    </Field>
                                    <Field
                                        label="CV (PDF, 5 Mo)"
                                        error={form.errors.cv}
                                    >
                                        {candidate?.has_cv && (
                                            <p className="mb-2 text-sm text-muted-foreground">
                                                Un CV est déjà enregistré.{' '}
                                                <a
                                                    href="/profile/cv"
                                                    className="underline underline-offset-4"
                                                >
                                                    Télécharger
                                                </a>
                                            </p>
                                        )}
                                        <Input
                                            type="file"
                                            accept="application/pdf"
                                            onChange={(event) =>
                                                form.setData(
                                                    'cv',
                                                    event.target.files?.[0] ??
                                                        null,
                                                )
                                            }
                                        />
                                    </Field>
                                </div>
                            )}

                            {step === 1 && (
                                <div className="grid gap-4">
                                    <Field
                                        label="Niveau d'études"
                                        error={form.errors.education_level}
                                    >
                                        <select
                                            className={fieldClassName}
                                            value={form.data.education_level}
                                            onChange={(event) =>
                                                form.setData(
                                                    'education_level',
                                                    event.target.value,
                                                )
                                            }
                                        >
                                            <option value="">
                                                Non précisé
                                            </option>
                                            {Object.entries(
                                                educationLabels,
                                            ).map(([value, label]) => (
                                                <option
                                                    key={value}
                                                    value={value}
                                                >
                                                    {label}
                                                </option>
                                            ))}
                                        </select>
                                    </Field>
                                    <Field
                                        label="Domaine d'études"
                                        error={form.errors.field_of_study}
                                    >
                                        <Input
                                            value={form.data.field_of_study}
                                            onChange={(event) =>
                                                form.setData(
                                                    'field_of_study',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <Field
                                            label="Années d'expérience"
                                            error={
                                                form.errors.years_experience
                                            }
                                        >
                                            <Input
                                                type="number"
                                                min={0}
                                                max={80}
                                                value={
                                                    form.data.years_experience
                                                }
                                                onChange={(event) =>
                                                    form.setData(
                                                        'years_experience',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </Field>
                                        <Field
                                            label="Projets"
                                            error={form.errors.projects_count}
                                        >
                                            <Input
                                                type="number"
                                                min={0}
                                                max={100}
                                                value={
                                                    form.data.projects_count
                                                }
                                                onChange={(event) =>
                                                    form.setData(
                                                        'projects_count',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </Field>
                                    </div>
                                    <Field
                                        label="Disponibilité"
                                        error={form.errors.availability}
                                    >
                                        <select
                                            className={fieldClassName}
                                            value={form.data.availability}
                                            onChange={(event) =>
                                                form.setData(
                                                    'availability',
                                                    event.target.value,
                                                )
                                            }
                                        >
                                            <option value="">
                                                Non précisée
                                            </option>
                                            {Object.entries(
                                                availabilityLabels,
                                            ).map(([value, label]) => (
                                                <option
                                                    key={value}
                                                    value={value}
                                                >
                                                    {label}
                                                </option>
                                            ))}
                                        </select>
                                    </Field>
                                </div>
                            )}

                            {step === 2 && (
                                <div className="grid gap-6">
                                    <section className="grid gap-3">
                                        <div>
                                            <h3 className="font-medium text-foreground">
                                                Diplômes
                                            </h3>
                                            <p className="text-sm text-muted-foreground">
                                                Ajoutez un intitulé, puis
                                                précisez si besoin.
                                            </p>
                                        </div>
                                        <QuickAdd
                                            value={diplomaDraft}
                                            onChange={setDiplomaDraft}
                                            placeholder="Ex. Licence informatique"
                                            onAdd={() =>
                                                addListedItem('diplomas')
                                            }
                                        />
                                        <InputError
                                            message={form.errors.diplomas}
                                        />
                                        {form.data.diplomas.map(
                                            (diploma, index) => (
                                                <ListedCard
                                                    key={`diploma-${index}`}
                                                    title={diploma.title}
                                                    onRemove={() =>
                                                        form.setData(
                                                            'diplomas',
                                                            form.data.diplomas.filter(
                                                                (_, i) =>
                                                                    i !==
                                                                    index,
                                                            ),
                                                        )
                                                    }
                                                >
                                                    <Input
                                                        placeholder="Établissement"
                                                        value={
                                                            diploma.institution ??
                                                            ''
                                                        }
                                                        onChange={(event) =>
                                                            updateDiploma(
                                                                index,
                                                                {
                                                                    institution:
                                                                        event
                                                                            .target
                                                                            .value ||
                                                                        null,
                                                                },
                                                            )
                                                        }
                                                    />
                                                    <Input
                                                        type="number"
                                                        min={1950}
                                                        max={2100}
                                                        placeholder="Année"
                                                        value={
                                                            diploma.year ?? ''
                                                        }
                                                        onChange={(event) =>
                                                            updateDiploma(
                                                                index,
                                                                {
                                                                    year: event
                                                                        .target
                                                                        .value
                                                                        ? Number(
                                                                              event
                                                                                  .target
                                                                                  .value,
                                                                          )
                                                                        : null,
                                                                },
                                                            )
                                                        }
                                                    />
                                                </ListedCard>
                                            ),
                                        )}
                                    </section>

                                    <section className="grid gap-3">
                                        <div>
                                            <h3 className="font-medium text-foreground">
                                                Formations
                                            </h3>
                                            <p className="text-sm text-muted-foreground">
                                                Bootcamps, certifications,
                                                stages…
                                            </p>
                                        </div>
                                        <QuickAdd
                                            value={trainingDraft}
                                            onChange={setTrainingDraft}
                                            placeholder="Ex. Bootcamp Laravel"
                                            onAdd={() =>
                                                addListedItem('trainings')
                                            }
                                        />
                                        <InputError
                                            message={form.errors.trainings}
                                        />
                                        {form.data.trainings.map(
                                            (training, index) => (
                                                <ListedCard
                                                    key={`training-${index}`}
                                                    title={training.title}
                                                    onRemove={() =>
                                                        form.setData(
                                                            'trainings',
                                                            form.data.trainings.filter(
                                                                (_, i) =>
                                                                    i !==
                                                                    index,
                                                            ),
                                                        )
                                                    }
                                                >
                                                    <Input
                                                        placeholder="Organisme"
                                                        value={
                                                            training.organization ??
                                                            ''
                                                        }
                                                        onChange={(event) =>
                                                            updateTraining(
                                                                index,
                                                                {
                                                                    organization:
                                                                        event
                                                                            .target
                                                                            .value ||
                                                                        null,
                                                                },
                                                            )
                                                        }
                                                    />
                                                    <Input
                                                        type="number"
                                                        min={1950}
                                                        max={2100}
                                                        placeholder="Année"
                                                        value={
                                                            training.year ?? ''
                                                        }
                                                        onChange={(event) =>
                                                            updateTraining(
                                                                index,
                                                                {
                                                                    year: event
                                                                        .target
                                                                        .value
                                                                        ? Number(
                                                                              event
                                                                                  .target
                                                                                  .value,
                                                                          )
                                                                        : null,
                                                                },
                                                            )
                                                        }
                                                    />
                                                </ListedCard>
                                            ),
                                        )}
                                    </section>
                                </div>
                            )}

                            {step === 3 && (
                                <div className="grid gap-5">
                                    <section className="grid gap-3">
                                        <div>
                                            <h3 className="font-medium text-foreground">
                                                Compétences du catalogue
                                            </h3>
                                            <p className="text-sm text-muted-foreground">
                                                Cochez celles qui vous
                                                concernent, ou ajoutez-en une
                                                par saisie.
                                            </p>
                                        </div>
                                        <Input
                                            value={skillFilter}
                                            onChange={(event) =>
                                                setSkillFilter(
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Filtrer le catalogue…"
                                        />
                                        <div className="flex flex-col gap-3 sm:flex-row">
                                            <Input
                                                value={draft}
                                                onChange={(event) =>
                                                    setDraft(
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="Ajouter une compétence…"
                                                onKeyDown={(event) => {
                                                    if (
                                                        event.key === 'Enter'
                                                    ) {
                                                        event.preventDefault();
                                                        addTag(
                                                            'other_skills',
                                                            draft,
                                                            draftLevel,
                                                        );
                                                    }
                                                }}
                                            />
                                            <select
                                                className={fieldClassName}
                                                value={draftLevel}
                                                onChange={(event) =>
                                                    setDraftLevel(
                                                        event.target.value,
                                                    )
                                                }
                                            >
                                                {Object.entries(
                                                    skillLevelLabels,
                                                ).map(([value, label]) => (
                                                    <option
                                                        key={value}
                                                        value={value}
                                                    >
                                                        {label}
                                                    </option>
                                                ))}
                                            </select>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() =>
                                                    addTag(
                                                        'other_skills',
                                                        draft,
                                                        draftLevel,
                                                    )
                                                }
                                            >
                                                Ajouter
                                            </Button>
                                        </div>
                                        <InputError
                                            message={form.errors.skills}
                                        />
                                        <InputError
                                            message={form.errors.other_skills}
                                        />
                                        <div className="max-h-64 space-y-3 overflow-y-auto pr-1">
                                            {filteredSkills.map((skill) => {
                                                const level = skillLevel(
                                                    skill.id,
                                                );

                                                return (
                                                    <div
                                                        key={skill.id}
                                                        className="grid gap-2 sm:grid-cols-[1fr_10rem] sm:items-center"
                                                    >
                                                        <Label className="flex items-center gap-2">
                                                            <Checkbox
                                                                checked={
                                                                    level !==
                                                                    undefined
                                                                }
                                                                onCheckedChange={(
                                                                    checked,
                                                                ) =>
                                                                    toggleSkill(
                                                                        skill.id,
                                                                        checked ===
                                                                            true,
                                                                    )
                                                                }
                                                            />
                                                            <span>
                                                                {skill.name}
                                                            </span>
                                                        </Label>
                                                        <select
                                                            className={
                                                                fieldClassName
                                                            }
                                                            disabled={
                                                                level ===
                                                                undefined
                                                            }
                                                            value={
                                                                level ??
                                                                'beginner'
                                                            }
                                                            onChange={(
                                                                event,
                                                            ) =>
                                                                setCatalogLevel(
                                                                    skill.id,
                                                                    event
                                                                        .target
                                                                        .value,
                                                                )
                                                            }
                                                        >
                                                            {Object.entries(
                                                                skillLevelLabels,
                                                            ).map(
                                                                ([
                                                                    value,
                                                                    label,
                                                                ]) => (
                                                                    <option
                                                                        key={
                                                                            value
                                                                        }
                                                                        value={
                                                                            value
                                                                        }
                                                                    >
                                                                        {
                                                                            label
                                                                        }
                                                                    </option>
                                                                ),
                                                            )}
                                                        </select>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </section>

                                    {form.data.other_skills.length > 0 && (
                                        <section className="grid gap-3">
                                            <h3 className="font-medium text-foreground">
                                                Autres compétences ajoutées
                                            </h3>
                                            {form.data.other_skills.map(
                                                (skill, index) => (
                                                    <div
                                                        key={`${skill.name}-${index}`}
                                                        className="flex flex-wrap items-center gap-2 rounded-2xl border border-border px-3 py-2"
                                                    >
                                                        <span className="min-w-0 flex-1 font-medium text-foreground">
                                                            {skill.name}
                                                        </span>
                                                        <select
                                                            className={
                                                                fieldClassName
                                                            }
                                                            value={skill.level}
                                                            onChange={(
                                                                event,
                                                            ) => {
                                                                const other_skills =
                                                                    [
                                                                        ...form
                                                                            .data
                                                                            .other_skills,
                                                                    ];
                                                                other_skills[
                                                                    index
                                                                ] = {
                                                                    ...skill,
                                                                    level: event
                                                                        .target
                                                                        .value,
                                                                };
                                                                form.setData(
                                                                    'other_skills',
                                                                    other_skills,
                                                                );
                                                            }}
                                                        >
                                                            {Object.entries(
                                                                skillLevelLabels,
                                                            ).map(
                                                                ([
                                                                    value,
                                                                    label,
                                                                ]) => (
                                                                    <option
                                                                        key={
                                                                            value
                                                                        }
                                                                        value={
                                                                            value
                                                                        }
                                                                    >
                                                                        {
                                                                            label
                                                                        }
                                                                    </option>
                                                                ),
                                                            )}
                                                        </select>
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() =>
                                                                form.setData(
                                                                    'other_skills',
                                                                    form.data.other_skills.filter(
                                                                        (
                                                                            _,
                                                                            i,
                                                                        ) =>
                                                                            i !==
                                                                            index,
                                                                    ),
                                                                )
                                                            }
                                                        >
                                                            Retirer
                                                        </Button>
                                                    </div>
                                                ),
                                            )}
                                        </section>
                                    )}
                                </div>
                            )}

                            {step === 4 && (
                                <div className="grid gap-6">
                                    <section className="grid gap-3">
                                        <div>
                                            <h3 className="font-medium text-foreground">
                                                Centres d’intérêt
                                            </h3>
                                            <p className="text-sm text-muted-foreground">
                                                Ajoutez-les un par un.
                                            </p>
                                        </div>
                                        <QuickAdd
                                            value={draft}
                                            onChange={setDraft}
                                            placeholder="Ex. open source"
                                            onAdd={() =>
                                                addTag('interests', draft)
                                            }
                                        />
                                        <InputError
                                            message={form.errors.interests}
                                        />
                                        <div className="flex flex-wrap gap-2">
                                            {form.data.interests.map(
                                                (interest) => (
                                                    <button
                                                        key={interest}
                                                        type="button"
                                                        className="rounded-full bg-muted px-3 py-1 text-sm text-foreground"
                                                        onClick={() =>
                                                            form.setData(
                                                                'interests',
                                                                form.data.interests.filter(
                                                                    (item) =>
                                                                        item !==
                                                                        interest,
                                                                ),
                                                            )
                                                        }
                                                    >
                                                        {interest} ×
                                                    </button>
                                                ),
                                            )}
                                        </div>
                                    </section>

                                    <section className="grid gap-4">
                                        <h3 className="font-medium text-foreground">
                                            Preuves en ligne
                                        </h3>
                                        <Field
                                            label="GitHub"
                                            error={form.errors.github_url}
                                        >
                                            <Input
                                                type="url"
                                                value={form.data.github_url}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'github_url',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </Field>
                                        <Field
                                            label="Portfolio"
                                            error={form.errors.portfolio_url}
                                        >
                                            <Input
                                                type="url"
                                                value={
                                                    form.data.portfolio_url
                                                }
                                                onChange={(event) =>
                                                    form.setData(
                                                        'portfolio_url',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </Field>
                                        <Field
                                            label="LinkedIn"
                                            error={form.errors.linkedin_url}
                                        >
                                            <Input
                                                type="url"
                                                value={form.data.linkedin_url}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'linkedin_url',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </Field>
                                    </section>
                                </div>
                            )}

                            <div className="border-border flex flex-col-reverse gap-2 border-t pt-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="w-full sm:w-auto"
                                    disabled={step === 0}
                                    onClick={() => {
                                        setDraft('');
                                        setStep((currentStep) =>
                                            Math.max(0, currentStep - 1),
                                        );
                                    }}
                                >
                                    Précédent
                                </Button>
                                <div className="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap">
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        className="w-full sm:w-auto"
                                        disabled={form.processing}
                                    >
                                        Enregistrer
                                    </Button>
                                    {!isLast ? (
                                        <Button
                                            type="button"
                                            className="w-full sm:w-auto"
                                            onClick={() => {
                                                setDraft('');
                                                setStep((currentStep) =>
                                                    Math.min(
                                                        STEPS.length - 1,
                                                        currentStep + 1,
                                                    ),
                                                );
                                            }}
                                        >
                                            Suivant
                                        </Button>
                                    ) : (
                                        <Button
                                            type="submit"
                                            className="w-full sm:w-auto"
                                            disabled={form.processing}
                                        >
                                            Terminer
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </PageFrame>
        </>
    );
}

function QuickAdd({
    value,
    onChange,
    placeholder,
    onAdd,
}: {
    value: string;
    onChange: (value: string) => void;
    placeholder: string;
    onAdd: () => void;
}) {
    return (
        <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
            <Input
                className="min-w-0 flex-1"
                value={value}
                onChange={(event) => onChange(event.target.value)}
                placeholder={placeholder}
                onKeyDown={(event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        onAdd();
                    }
                }}
            />
            <Button
                type="button"
                variant="outline"
                className="w-full shrink-0 sm:w-auto"
                onClick={onAdd}
            >
                Ajouter
            </Button>
        </div>
    );
}

function ListedCard({
    title,
    onRemove,
    children,
}: {
    title: string;
    onRemove: () => void;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-3 rounded-2xl border border-border p-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <p className="font-medium text-foreground">{title}</p>
                <Button type="button" variant="ghost" size="sm" onClick={onRemove}>
                    Retirer
                </Button>
            </div>
            <div className="grid gap-3 sm:grid-cols-2">{children}</div>
        </div>
    );
}

function Field({
    label,
    error,
    required = false,
    children,
}: {
    label: string;
    error?: string;
    required?: boolean;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label>
                {label}
                {required ? ' *' : ''}
            </Label>
            {children}
            <InputError message={error} />
        </div>
    );
}

CandidateProfile.layout = {
    breadcrumbs: [
        {
            title: 'Profil',
            href: edit(),
        },
    ],
};
