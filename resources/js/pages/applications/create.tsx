import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEvent, type ReactNode } from 'react';
import { store } from '@/actions/App/Http/Controllers/ApplicationController';
import InputError from '@/components/input-error';
import { PageFrame } from '@/components/talent/page-frame';
import { Badge } from '@/components/ui/badge';
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
    textareaClassName,
} from '@/lib/talent';
import { create } from '@/routes/applications';
import { edit as candidateProfile } from '@/routes/candidate-profile';
import { index as programsIndex, show } from '@/routes/programs';
import type { TalentCandidate, TalentProgram } from '@/types';

type ApplicationForm = {
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
    motivation_text: string;
    use_profile_cv: boolean;
    cv: File | null;
    documents: Record<string, File | null>;
    skills: { id: string; level: string }[];
};

function initialSkills(
    program: TalentProgram,
    candidate: TalentCandidate | null,
): { id: string; level: string }[] {
    const profileLevels = new Map(
        (candidate?.skills ?? []).map((skill) => [skill.id, skill.level]),
    );

    const selected = program.skills
        .filter(
            (skill) =>
                skill.is_required || profileLevels.has(skill.id),
        )
        .map((skill) => ({
            id: skill.id,
            level: profileLevels.get(skill.id) ?? 'beginner',
        }));

    return selected.length > 0
        ? selected
        : program.skills
              .filter((skill) => skill.is_required)
              .map((skill) => ({ id: skill.id, level: 'beginner' }));
}

export default function ApplicationCreate({
    program,
    candidate,
    submitUrl,
    kind = 'program',
}: {
    program: TalentProgram;
    candidate: TalentCandidate | null;
    submitUrl?: string;
    kind?: 'program' | 'course' | 'offer';
}) {
    const hasProfileCv = Boolean(candidate?.has_cv);
    const profileHighlights = [
        candidate?.diplomas?.length
            ? `${candidate.diplomas.length} diplôme${candidate.diplomas.length > 1 ? 's' : ''}`
            : null,
        candidate?.trainings?.length
            ? `${candidate.trainings.length} formation${candidate.trainings.length > 1 ? 's' : ''}`
            : null,
        candidate?.skills?.length
            ? `${candidate.skills.length} compétence${candidate.skills.length > 1 ? 's' : ''}`
            : null,
        candidate?.other_skills?.length
            ? `${candidate.other_skills.length} autre${candidate.other_skills.length > 1 ? 's' : ''}`
            : null,
        candidate?.interests?.length
            ? `${candidate.interests.length} intérêt${candidate.interests.length > 1 ? 's' : ''}`
            : null,
        hasProfileCv ? 'CV prêt' : null,
    ].filter(Boolean) as string[];
    const form = useForm<ApplicationForm>({
        phone: candidate?.phone ?? '',
        country: candidate?.country ?? '',
        city: candidate?.city ?? '',
        education_level: candidate?.education_level || 'licence',
        field_of_study: candidate?.field_of_study ?? '',
        years_experience: String(candidate?.years_experience ?? 0),
        projects_count: String(candidate?.projects_count ?? 0),
        github_url: candidate?.github_url ?? '',
        portfolio_url: candidate?.portfolio_url ?? '',
        linkedin_url: candidate?.linkedin_url ?? '',
        availability: candidate?.availability || 'immediate',
        motivation_text: '',
        use_profile_cv: hasProfileCv,
        cv: null,
        documents: {},
        skills: initialSkills(program, candidate),
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            use_profile_cv: data.use_profile_cv ? 1 : 0,
            cv: data.cv ?? undefined,
        }));
        form.post(submitUrl ?? store.url(program.id), { forceFormData: true });
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

    function setLevel(skillId: string, level: string) {
        form.setData(
            'skills',
            form.data.skills.map((skill) =>
                skill.id === skillId ? { ...skill, level } : skill,
            ),
        );
    }

    return (
        <>
            <Head title={`Postuler — ${program.name}`} />
            <PageFrame
                size="narrow"
                eyebrow="Candidature"
                title={`Postuler à ${program.name}`}
                description="Les champs marqués sont vérifiés côté serveur. Le score sera calculé après l'envoi."
            >

                <form onSubmit={submit} className="flex flex-col gap-6">
                    <InputError
                        message={
                            (form.errors as Record<string, string | undefined>)
                                .program
                        }
                    />

                    <Card>
                        <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <CardTitle>Depuis votre profil</CardTitle>
                                <CardDescription>
                                    Les champs ci-dessous sont préremplis. Diplômes,
                                    formations et intérêts restent visibles pour le
                                    recruteur via votre profil.
                                </CardDescription>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                className="w-full shrink-0 sm:w-auto"
                                asChild
                            >
                                <Link href={candidateProfile()}>
                                    Compléter le profil
                                </Link>
                            </Button>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-3">
                            {profileHighlights.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    Profil encore vide — vous pouvez tout renseigner
                                    ici, ou d’abord sur la page Profil.
                                </p>
                            ) : (
                                <div className="flex flex-wrap gap-2">
                                    {profileHighlights.map((item) => (
                                        <Badge key={item} variant="secondary">
                                            {item}
                                        </Badge>
                                    ))}
                                </div>
                            )}
                            {(candidate?.diplomas?.length ?? 0) > 0 && (
                                <ul className="text-muted-foreground list-inside list-disc text-sm">
                                    {candidate!.diplomas!.slice(0, 3).map((diploma) => (
                                        <li key={`${diploma.title}-${diploma.year}`}>
                                            {diploma.title}
                                            {diploma.year ? ` (${diploma.year})` : ''}
                                        </li>
                                    ))}
                                </ul>
                            )}
                            {(candidate?.interests?.length ?? 0) > 0 && (
                                <p className="text-muted-foreground text-sm">
                                    Intérêts : {candidate!.interests!.join(', ')}
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Coordonnées</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <Field label="Téléphone" error={form.errors.phone}>
                                <Input
                                    name="phone"
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
                                    name="country"
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
                            <Field label="Ville" error={form.errors.city}>
                                <Input
                                    name="city"
                                    value={form.data.city}
                                    onChange={(event) =>
                                        form.setData('city', event.target.value)
                                    }
                                    autoComplete="address-level2"
                                />
                            </Field>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Parcours</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <Field
                                label="Niveau d'études"
                                error={form.errors.education_level}
                                required
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
                                    {Object.entries(educationLabels).map(
                                        ([value, label]) => (
                                            <option key={value} value={value}>
                                                {label}
                                            </option>
                                        ),
                                    )}
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
                                    error={form.errors.years_experience}
                                    required
                                >
                                    <Input
                                        type="number"
                                        min={0}
                                        max={80}
                                        value={form.data.years_experience}
                                        onChange={(event) =>
                                            form.setData(
                                                'years_experience',
                                                event.target.value,
                                            )
                                        }
                                        required
                                    />
                                </Field>
                                <Field
                                    label="Projets"
                                    error={form.errors.projects_count}
                                    required
                                >
                                    <Input
                                        type="number"
                                        min={0}
                                        max={100}
                                        value={form.data.projects_count}
                                        onChange={(event) =>
                                            form.setData(
                                                'projects_count',
                                                event.target.value,
                                            )
                                        }
                                        required
                                    />
                                </Field>
                            </div>
                            <Field
                                label="Disponibilité"
                                error={form.errors.availability}
                                required
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
                                    {Object.entries(availabilityLabels).map(
                                        ([value, label]) => (
                                            <option key={value} value={value}>
                                                {label}
                                            </option>
                                        ),
                                    )}
                                </select>
                            </Field>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Preuves</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <Field
                                label="GitHub"
                                error={form.errors.github_url}
                            >
                                <Input
                                    type="url"
                                    placeholder="https://github.com/…"
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
                                    placeholder="https://"
                                    value={form.data.portfolio_url}
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
                                    placeholder="https://www.linkedin.com/in/…"
                                    value={form.data.linkedin_url}
                                    onChange={(event) =>
                                        form.setData(
                                            'linkedin_url',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                            {hasProfileCv && (
                                <Label className="flex items-center gap-2">
                                    <Checkbox
                                        checked={form.data.use_profile_cv}
                                        onCheckedChange={(checked) =>
                                            form.setData(
                                                'use_profile_cv',
                                                checked === true,
                                            )
                                        }
                                    />
                                    <span>
                                        Utiliser le CV de mon profil
                                        professionnel
                                    </span>
                                </Label>
                            )}
                            <Field
                                label={
                                    kind === 'offer' &&
                                    !form.data.use_profile_cv
                                        ? 'CV (PDF, 5 Mo)'
                                        : 'CV (PDF, 5 Mo, facultatif)'
                                }
                                error={form.errors.cv}
                                required={
                                    kind === 'offer' &&
                                    !form.data.use_profile_cv
                                }
                            >
                                <Input
                                    type="file"
                                    accept="application/pdf"
                                    required={
                                        kind === 'offer' &&
                                        !form.data.use_profile_cv
                                    }
                                    onChange={(event) =>
                                        form.setData(
                                            'cv',
                                            event.target.files?.[0] ?? null,
                                        )
                                    }
                                />
                            </Field>
                            {(program.requested_documents ?? []).map((label) => (
                                <Field
                                    key={label}
                                    label={`${label} (PDF, 5 Mo)`}
                                    error={form.errors[`documents.${label}`]}
                                >
                                    <Input
                                        type="file"
                                        accept="application/pdf"
                                        onChange={(event) =>
                                            form.setData('documents', {
                                                ...form.data.documents,
                                                [label]:
                                                    event.target.files?.[0] ??
                                                    null,
                                            })
                                        }
                                    />
                                </Field>
                            ))}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Compétences</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            <InputError message={form.errors.skills} />
                            {program.skills.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    {kind === 'offer'
                                        ? 'Cette offre ne liste pas encore de compétences.'
                                        : kind === 'course'
                                          ? 'Ce cours ne liste pas encore de compétences.'
                                          : 'Ce programme ne liste pas encore de compétences.'}
                                </p>
                            ) : (
                                program.skills.map((skill) => {
                                    const level = skillLevel(skill.id);

                                    return (
                                        <div
                                            key={skill.id}
                                            className="grid gap-3 sm:grid-cols-[1fr_12rem] sm:items-center"
                                        >
                                            <Label className="flex items-center gap-2">
                                                <Checkbox
                                                    checked={level !== undefined}
                                                    onCheckedChange={(
                                                        checked,
                                                    ) =>
                                                        toggleSkill(
                                                            skill.id,
                                                            checked === true,
                                                        )
                                                    }
                                                />
                                                <span>
                                                    {skill.name}
                                                    {skill.is_required
                                                        ? ' · requise'
                                                        : ' · bonus'}
                                                </span>
                                            </Label>
                                            <select
                                                className={fieldClassName}
                                                disabled={level === undefined}
                                                value={level ?? 'beginner'}
                                                onChange={(event) =>
                                                    setLevel(
                                                        skill.id,
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
                                        </div>
                                    );
                                })
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>
                                {kind === 'offer'
                                    ? 'Lettre de motivation'
                                    : 'Motivation'}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Field
                                label={
                                    kind === 'offer'
                                        ? 'Lettre de motivation pour cette offre'
                                        : kind === 'course'
                                          ? 'Pourquoi ce cours'
                                          : 'Pourquoi ce programme'
                                }
                                error={form.errors.motivation_text}
                                required
                            >
                                <textarea
                                    className={textareaClassName}
                                    value={form.data.motivation_text}
                                    onChange={(event) =>
                                        form.setData(
                                            'motivation_text',
                                            event.target.value,
                                        )
                                    }
                                    required
                                    maxLength={5000}
                                />
                            </Field>
                        </CardContent>
                    </Card>

                    <Button
                        type="submit"
                        className="h-12 w-full sm:w-auto"
                        disabled={form.processing}
                    >
                        Envoyer ma candidature
                    </Button>
                </form>
            </PageFrame>
        </>
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

ApplicationCreate.layout = (props: { program: TalentProgram }) => ({
    breadcrumbs: [
        {
            title: 'Programmes',
            href: programsIndex(),
        },
        {
            title: props.program.name,
            href: show(props.program.id),
        },
        {
            title: 'Postuler',
            href: create(props.program.id),
        },
    ],
});
