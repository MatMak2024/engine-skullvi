import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { AccountFields } from '@/components/talent/account-fields';
import {
    AdminOverview,
    type AdministrationOverview,
} from '@/components/talent/admin-overview';
import { PageFrame } from '@/components/talent/page-frame';
import { StatusBadge } from '@/components/talent/status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { journeyAccess } from '@/lib/journeys';
import { formatDate } from '@/lib/talent';
import { index as applicationsIndex, show as applicationShow } from '@/routes/applications';
import { edit as candidateProfile } from '@/routes/candidate-profile';
import { edit as editSecurity } from '@/routes/security';
import type { TalentApplicationSummary } from '@/types';

type SavedOpening = {
    id: string;
    kind: string;
    kind_label: string;
    target_id: string;
    name: string;
    is_open: boolean;
    href: string;
    apply_href: string;
    unsave_url: string;
};

function JourneyCard({
    title,
    description,
    points,
    actions,
}: {
    title: string;
    description: string;
    points: string[];
    actions: { href: string; label: string }[];
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent className="flex flex-col gap-4">
                <ul className="text-muted-foreground list-disc space-y-1 pl-5 text-sm">
                    {points.map((point) => (
                        <li key={point}>{point}</li>
                    ))}
                </ul>
                <div className="flex flex-wrap gap-2">
                    {actions.map((action) => (
                        <Button key={action.label} asChild>
                            <Link href={action.href}>{action.label}</Link>
                        </Button>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}

export default function Dashboard({
    overview = null,
    applications = [],
    savedOpenings = [],
}: {
    overview?: AdministrationOverview | null;
    applications?: TalentApplicationSummary[];
    savedOpenings?: SavedOpening[];
}) {
    const { auth, talent } = usePage().props;
    const access = journeyAccess(talent);
    const journeys = [
        access.isUser ? 'Utilisateur' : null,
        access.isCandidate ? 'Candidat' : null,
        access.isSuperAdmin ? 'Super administrateur' : null,
        access.isRecruiter ? 'Recruteur' : null,
    ].filter((label): label is string => label !== null);
    const showPersonalSpace = access.isUser || access.isCandidate;

    return (
        <>
            <Head title="Tableau de bord" />
            <PageFrame
                eyebrow={journeys.join(' · ') || 'Tableau de bord'}
                title={
                    showPersonalSpace
                        ? 'Votre espace'
                        : 'Que faire maintenant'
                }
                description={
                    showPersonalSpace
                        ? 'Suivez vos candidatures et les dossiers que vous avez mis de côté. Programmes, cours et offres se consultent sur le site.'
                        : 'Un programme rassemble des cours. Une offre porte ses compétences. Le score classe, la décision reste humaine.'
                }
            >
                {talent.isSuspended && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Dossier suspendu</CardTitle>
                            <CardDescription>
                                Situation à résoudre :{' '}
                                {talent.suspensionReason ??
                                    'le recruteur n’a pas précisé le motif'}
                                . Réglez cette situation. Le recruteur pourra
                                ensuite lever la suspension. Votre compte
                                utilisateur reste actif.
                            </CardDescription>
                        </CardHeader>
                    </Card>
                )}
                {talent.candidateRemoved && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Vous n’êtes plus candidat</CardTitle>
                            <CardDescription>
                                Votre compte reste un compte utilisateur, mais
                                vous ne pouvez plus déposer de candidature.
                            </CardDescription>
                        </CardHeader>
                    </Card>
                )}
                {overview && <AdminOverview overview={overview} />}

                {access.isCandidate && (
                    <Card>
                        <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
                            <div>
                                <CardTitle>Mes candidatures</CardTitle>
                                <CardDescription>
                                    Le statut affiché est la décision en cours.
                                    Le score reste une indication.
                                </CardDescription>
                            </div>
                            <Button variant="outline" asChild>
                                <Link href={applicationsIndex()}>
                                    Tout voir
                                </Link>
                            </Button>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-3">
                            {applications.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Aucune candidature pour le moment. Parcourez
                                    le site pour postuler, ou enregistrez un
                                    dossier pour plus tard.
                                </p>
                            ) : (
                                applications.map((application) => (
                                    <div
                                        key={application.id}
                                        className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border px-4 py-3"
                                    >
                                        <div className="min-w-0">
                                            <p className="font-medium text-foreground">
                                                {application.program.name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                Déposée le{' '}
                                                {formatDate(
                                                    application.submitted_at,
                                                )}
                                            </p>
                                        </div>
                                        <div className="flex flex-wrap items-center gap-3">
                                            <StatusBadge
                                                status={application.status}
                                            />
                                            {application.score !== null ? (
                                                <span className="text-sm text-foreground">
                                                    {application.score}/100
                                                </span>
                                            ) : null}
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={applicationShow(
                                                        application.id,
                                                    )}
                                                >
                                                    Ouvrir
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                )}

                {showPersonalSpace && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Enregistrés pour plus tard</CardTitle>
                            <CardDescription>
                                Offres, cours ou programmes mis de côté depuis
                                le site. Postulez quand vous êtes prêt.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-3">
                            {savedOpenings.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Rien d’enregistré. Sur une fiche du site,
                                    choisissez « Enregistrer pour plus tard ».
                                </p>
                            ) : (
                                savedOpenings.map((opening) => (
                                    <div
                                        key={opening.id}
                                        className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border px-4 py-3"
                                    >
                                        <div className="min-w-0">
                                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                                {opening.kind_label}
                                            </p>
                                            <p className="font-medium text-foreground">
                                                {opening.name}
                                            </p>
                                            {!opening.is_open && (
                                                <p className="text-sm text-muted-foreground">
                                                    N’accepte plus de
                                                    candidatures.
                                                </p>
                                            )}
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={opening.href}>
                                                    Voir
                                                </Link>
                                            </Button>
                                            {opening.is_open && (
                                                <Button size="sm" asChild>
                                                    <Link
                                                        href={
                                                            opening.apply_href
                                                        }
                                                    >
                                                        Postuler
                                                    </Link>
                                                </Button>
                                            )}
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    router.delete(
                                                        opening.unsave_url,
                                                        {
                                                            preserveScroll:
                                                                true,
                                                        },
                                                    )
                                                }
                                            >
                                                Retirer
                                            </Button>
                                        </div>
                                    </div>
                                ))
                            )}
                            <div className="flex flex-wrap gap-2 pt-2">
                                <Button variant="outline" asChild>
                                    <Link href="/programs">Programmes</Link>
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href="/courses">Cours</Link>
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href="/offers">Offres</Link>
                                </Button>
                                {access.isCandidate && (
                                    <Button variant="outline" asChild>
                                        <Link href={candidateProfile()}>
                                            Profil
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-4 md:grid-cols-2">
                    {access.isSuperAdmin && (
                        <JourneyCard
                            title="Super administrateur"
                            description="Vous gérez les comptes. Le reste du système se consulte, sans le modifier."
                            points={[
                                'Attribuer les rôles des comptes',
                                'Consulter les candidatures et les candidats',
                                'Consulter les cours, les offres, les programmes et les compétences',
                            ]}
                            actions={[
                                {
                                    href: '/admin/users',
                                    label: 'Utilisateurs',
                                },
                                {
                                    href: '/admin/applications',
                                    label: 'Candidatures',
                                },
                                {
                                    href: '/admin/candidates',
                                    label: 'Candidats',
                                },
                                {
                                    href: '/admin/courses',
                                    label: 'Cours',
                                },
                                {
                                    href: '/admin/offers',
                                    label: 'Offres',
                                },
                                {
                                    href: '/admin/programs',
                                    label: 'Programmes',
                                },
                                {
                                    href: '/admin/skills',
                                    label: 'Compétences',
                                },
                            ]}
                        />
                    )}
                    {access.isRecruiter && (
                        <JourneyCard
                            title="Recruteur"
                            description="Vous créez les cours, les programmes et les offres, puis vous examinez les candidatures."
                            points={[
                                'Créer un programme et y rattacher des cours',
                                'Donner à un cours ses compétences prérequises',
                                'Publier une offre avec ses compétences',
                                'Classer les candidatures du plus qualifié au moins qualifié',
                            ]}
                            actions={[
                                {
                                    href: '/admin/applications',
                                    label: 'Candidatures',
                                },
                                {
                                    href: '/admin/candidates',
                                    label: 'Candidats',
                                },
                                {
                                    href: '/admin/courses',
                                    label: 'Cours',
                                },
                                {
                                    href: '/admin/offers',
                                    label: 'Offres',
                                },
                                {
                                    href: '/admin/programs',
                                    label: 'Programmes',
                                },
                                {
                                    href: '/admin/skills',
                                    label: 'Compétences',
                                },
                            ]}
                        />
                    )}
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Mon compte</CardTitle>
                        <CardDescription>
                            Prénom, nom, e-mail et coordonnées du compte. Le mot
                            de passe se modifie dans{' '}
                            <Link
                                href={editSecurity()}
                                className="text-foreground underline underline-offset-4"
                            >
                                Sécurité
                            </Link>
                            .
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...ProfileController.update.form()}
                            options={{ preserveScroll: true }}
                            className="grid gap-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <AccountFields
                                        user={auth.user}
                                        errors={errors}
                                    />
                                    <Button
                                        disabled={processing}
                                        className="w-fit"
                                        data-test="dashboard-profile-button"
                                    >
                                        Enregistrer
                                    </Button>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </PageFrame>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Tableau de bord',
            href: '/dashboard',
        },
    ],
};
