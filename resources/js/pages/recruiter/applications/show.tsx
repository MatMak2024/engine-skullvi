import { Form, Head, Link } from '@inertiajs/react';
import RecruiterApplicationController from '@/actions/App/Http/Controllers/RecruiterApplicationController';
import ReviewNoteController from '@/actions/App/Http/Controllers/ReviewNoteController';
import InputError from '@/components/input-error';
import { PageFrame } from '@/components/talent/page-frame';
import { ScoreBar } from '@/components/talent/score-bar';
import { StatusBadge } from '@/components/talent/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    availabilityLabels,
    criterionLabels,
    educationLabels,
    fieldClassName,
    formatDate,
    skillLevelLabels,
    statusLabels,
    textareaClassName,
} from '@/lib/talent';
import { index, show } from '@/routes/recruiter/applications';
import type { RecruiterApplicationDetail, TalentStatus } from '@/types';

export default function RecruiterApplicationShow({
    application,
    canReview = true,
}: {
    application: RecruiterApplicationDetail;
    canReview?: boolean;
}) {
    const listHref = canReview ? index() : '/admin/applications';
    const place = [application.candidate.city, application.candidate.country]
        .filter(Boolean)
        .join(', ');

    return (
        <>
            <Head title={application.candidate.name} />
            <PageFrame
                eyebrow={canReview ? 'Examen' : 'Administration'}
                title={application.candidate.name}
                description={`${application.program.name} · déposée le ${formatDate(application.submitted_at)}`}
            >

                <div className="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                    <div className="flex flex-col gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Profil</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-2 text-sm">
                                <p>{application.candidate.email}</p>
                                {application.candidate.phone && (
                                    <p>{application.candidate.phone}</p>
                                )}
                                {place && <p>{place}</p>}
                                <p>
                                    {educationLabels[
                                        application.education_level
                                    ] ?? application.education_level}
                                    {application.field_of_study
                                        ? ` · ${application.field_of_study}`
                                        : ''}
                                </p>
                                <p>
                                    {application.years_experience} an
                                    {application.years_experience > 1
                                        ? 's'
                                        : ''}{' '}
                                    · {application.projects_count} projet
                                    {application.projects_count > 1 ? 's' : ''}
                                </p>
                                <p>
                                    Disponibilité :{' '}
                                    {availabilityLabels[
                                        application.availability
                                    ] ?? application.availability}
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Compétences</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-wrap gap-2">
                                {application.skills.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">
                                        Aucune compétence déclarée.
                                    </p>
                                ) : (
                                    application.skills.map((skill) => (
                                        <Badge
                                            key={skill.id}
                                            variant="secondary"
                                        >
                                            {skill.name} ·{' '}
                                            {skillLevelLabels[skill.level ?? ''] ??
                                                skill.level}
                                        </Badge>
                                    ))
                                )}
                                {(application.candidate.other_skills ?? []).map(
                                    (skill) => (
                                        <Badge
                                            key={`other-${skill.name}`}
                                            variant="outline"
                                        >
                                            {skill.name} ·{' '}
                                            {skillLevelLabels[skill.level] ??
                                                skill.level}
                                        </Badge>
                                    ),
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Diplômes et formations</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4 text-sm">
                                {(application.candidate.diplomas ?? []).length ===
                                    0 &&
                                (application.candidate.trainings ?? []).length ===
                                    0 ? (
                                    <p className="text-muted-foreground">
                                        Aucun diplôme ni formation sur le profil.
                                    </p>
                                ) : (
                                    <>
                                        {(application.candidate.diplomas ?? []).map(
                                            (diploma) => (
                                                <div
                                                    key={`${diploma.title}-${diploma.year}`}
                                                >
                                                    <p className="font-medium">
                                                        {diploma.title}
                                                        {diploma.year
                                                            ? ` · ${diploma.year}`
                                                            : ''}
                                                    </p>
                                                    {diploma.institution && (
                                                        <p className="text-muted-foreground">
                                                            {diploma.institution}
                                                        </p>
                                                    )}
                                                    {diploma.description && (
                                                        <p className="text-muted-foreground mt-1">
                                                            {diploma.description}
                                                        </p>
                                                    )}
                                                </div>
                                            ),
                                        )}
                                        {(application.candidate.trainings ?? []).map(
                                            (training) => (
                                                <div
                                                    key={`${training.title}-${training.year}`}
                                                >
                                                    <p className="font-medium">
                                                        {training.title}
                                                        {training.year
                                                            ? ` · ${training.year}`
                                                            : ''}
                                                    </p>
                                                    {training.organization && (
                                                        <p className="text-muted-foreground">
                                                            {training.organization}
                                                        </p>
                                                    )}
                                                    {training.description && (
                                                        <p className="text-muted-foreground mt-1">
                                                            {training.description}
                                                        </p>
                                                    )}
                                                </div>
                                            ),
                                        )}
                                    </>
                                )}
                                {(application.candidate.interests ?? []).length >
                                    0 && (
                                    <div>
                                        <p className="mb-2 font-medium">
                                            Centres d’intérêt
                                        </p>
                                        <div className="flex flex-wrap gap-2">
                                            {application.candidate.interests!.map(
                                                (interest) => (
                                                    <Badge
                                                        key={interest}
                                                        variant="secondary"
                                                    >
                                                        {interest}
                                                    </Badge>
                                                ),
                                            )}
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Preuves et motivation</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-3 text-sm">
                                <Proof
                                    label="GitHub"
                                    url={application.github_url}
                                />
                                <Proof
                                    label="Portfolio"
                                    url={application.portfolio_url}
                                />
                                <Proof
                                    label="LinkedIn"
                                    url={application.linkedin_url}
                                />
                                {application.cv_url ? (
                                    <Button
                                        variant="outline"
                                        className="w-full sm:w-fit"
                                        asChild
                                    >
                                        <a href={application.cv_url}>
                                            Télécharger le CV
                                        </a>
                                    </Button>
                                ) : (
                                    <p className="text-muted-foreground">
                                        Aucun CV.
                                    </p>
                                )}
                                {application.documents.length === 0 ? (
                                    <p className="text-muted-foreground">
                                        Aucun document de qualification.
                                    </p>
                                ) : (
                                    application.documents.map((document) => (
                                        <Button
                                            key={document.id}
                                            variant="outline"
                                            className="w-full sm:w-fit"
                                            asChild
                                        >
                                            <a href={document.url}>
                                                {document.label}
                                            </a>
                                        </Button>
                                    ))
                                )}
                                <p className="whitespace-pre-wrap">
                                    {application.motivation_text}
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="flex flex-col gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Score</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                {!application.examined && (
                                    <p className="text-muted-foreground text-sm">
                                        Le score est individuel. Il est calculé
                                        quand vous autorisez l’examen, à partir
                                        des compétences, de la lettre de
                                        motivation, du CV et des documents de
                                        qualification demandés.
                                    </p>
                                )}
                                {canReview && !application.examined && (
                                    <Form
                                        action={`/admin/applications/${application.id}/examination`}
                                        method="patch"
                                        options={{ preserveScroll: true }}
                                    >
                                        <Button
                                            type="submit"
                                            className="h-12 bg-[#b7c48a] px-6 text-base font-semibold text-[#023940] shadow-md hover:bg-[#c9d4a3]"
                                        >
                                            Autoriser l’examen
                                        </Button>
                                    </Form>
                                )}
                                {application.evaluation ? (
                                    <>
                                        <p className="text-3xl font-semibold tabular-nums">
                                            {application.evaluation.total_score}
                                            <span className="text-muted-foreground text-base font-normal">
                                                /100
                                            </span>
                                        </p>
                                        <p className="text-muted-foreground text-sm">
                                            Dossier renseigné à{' '}
                                            {
                                                application.evaluation
                                                    .completeness_rate
                                            }
                                            %. Suggestion :{' '}
                                            {
                                                statusLabels[
                                                    application.evaluation
                                                        .suggested_status
                                                ]
                                            }
                                            . Grille{' '}
                                            {
                                                application.evaluation
                                                    .rubric_version
                                            }
                                            .
                                        </p>
                                        <ul className="flex flex-col gap-3">
                                            {application.evaluation.scores.map(
                                                (score) => (
                                                    <li
                                                        key={score.criterion}
                                                        className="flex flex-col gap-1"
                                                    >
                                                        <div className="flex justify-between text-sm">
                                                            <span>
                                                                {criterionLabels[
                                                                    score
                                                                        .criterion
                                                                ] ??
                                                                    score.criterion}
                                                            </span>
                                                            <span className="tabular-nums">
                                                                {Number(
                                                                    score.points,
                                                                )}
                                                                /
                                                                {Number(
                                                                    score.max_points,
                                                                )}
                                                            </span>
                                                        </div>
                                                        <ScoreBar
                                                            points={
                                                                score.points
                                                            }
                                                            max={
                                                                score.max_points
                                                            }
                                                        />
                                                        <p className="text-muted-foreground text-xs">
                                                            {score.explanation}
                                                        </p>
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    </>
                                ) : (
                                    <p className="text-muted-foreground text-sm">
                                        Aucun score pour le moment.
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Décision</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                <StatusBadge status={application.status} />
                                {canReview && (
                                <Form
                                    {...RecruiterApplicationController.updateStatus.form(
                                        application.id,
                                    )}
                                    className="flex flex-col gap-3"
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <Label htmlFor="status">
                                                Statut
                                            </Label>
                                            <select
                                                id="status"
                                                name="status"
                                                className={fieldClassName}
                                                defaultValue={application.status}
                                            >
                                                {(
                                                    Object.keys(
                                                        statusLabels,
                                                    ) as TalentStatus[]
                                                ).map((status) => (
                                                    <option
                                                        key={status}
                                                        value={status}
                                                    >
                                                        {statusLabels[status]}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={errors.status}
                                            />
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                className="w-fit"
                                            >
                                                Enregistrer le statut
                                            </Button>
                                        </>
                                    )}
                                </Form>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Notes</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                {application.notes.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">
                                        Aucune note.
                                    </p>
                                ) : (
                                    <ul className="flex flex-col gap-3">
                                        {application.notes.map((note) => (
                                            <li
                                                key={note.id}
                                                className="flex flex-col gap-1 text-sm"
                                            >
                                                <p className="whitespace-pre-wrap">
                                                    {note.note}
                                                </p>
                                                <p className="text-muted-foreground text-xs">
                                                    {note.author} ·{' '}
                                                    {formatDate(note.created_at)}
                                                </p>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                                {canReview && (
                                <Form
                                    {...ReviewNoteController.store.form(
                                        application.id,
                                    )}
                                    className="flex flex-col gap-3"
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <Label htmlFor="note">
                                                Ajouter une note
                                            </Label>
                                            <textarea
                                                id="note"
                                                name="note"
                                                className={textareaClassName}
                                                required
                                                maxLength={5000}
                                            />
                                            <InputError message={errors.note} />
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                className="w-fit"
                                            >
                                                Ajouter
                                            </Button>
                                        </>
                                    )}
                                </Form>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <Button variant="ghost" className="w-fit" asChild>
                    <Link href={listHref}>Retour à la liste</Link>
                </Button>
            </PageFrame>
        </>
    );
}

function Proof({ label, url }: { label: string; url: string | null }) {
    if (!url) {
        return (
            <p className="text-muted-foreground">
                {label} : absent
            </p>
        );
    }

    return (
        <a href={url} className="underline" target="_blank" rel="noreferrer">
            {label}
        </a>
    );
}

RecruiterApplicationShow.layout = (props: {
    application: RecruiterApplicationDetail;
    canReview?: boolean;
}) => ({
    breadcrumbs: [
        {
            title: props.canReview === false ? 'Candidatures' : 'À examiner',
            href: props.canReview === false ? '/admin/applications' : index(),
        },
        {
            title: props.application.candidate.name,
            href:
                props.canReview === false
                    ? `/admin/applications/${props.application.id}`
                    : show(props.application.id),
        },
    ],
});
