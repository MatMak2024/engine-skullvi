import { Form, Head, Link } from '@inertiajs/react';
import { PageFrame } from '@/components/talent/page-frame';
import { Pagination } from '@/components/talent/pagination';
import { StatusBadge } from '@/components/talent/status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { fieldClassName, formatDate, statusLabels } from '@/lib/talent';
import { index, show } from '@/routes/recruiter/applications';
import type {
    Page,
    RecruiterApplicationSummary,
    RecruiterFilters,
    TalentProgramSummary,
    TalentStatus,
} from '@/types';

export default function RecruiterApplicationsIndex({
    applications,
    programs,
    offers = [],
    courses = [],
    filters,
    canReview = true,
}: {
    applications: Page<RecruiterApplicationSummary>;
    programs: TalentProgramSummary[];
    offers?: TalentProgramSummary[];
    courses?: TalentProgramSummary[];
    filters: RecruiterFilters;
    canReview?: boolean;
}) {
    const listHref = canReview ? index() : '/admin/applications';

    return (
        <>
            <Head title="Candidatures" />
            <PageFrame
                eyebrow={canReview ? 'Recrutement' : 'Administration'}
                title={canReview ? 'Candidatures à examiner' : 'Candidatures'}
                description="Chaque dossier reçu attend votre autorisation. L’examen calcule alors le score individuel, et le classement devient effectif."
            >

                <Card>
                    <CardContent>
                        <form
                            method="get"
                            action={canReview ? index.url() : '/admin/applications'}
                            className="grid gap-3 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_1fr_auto]"
                        >
                            <select
                                name="offer"
                                className={fieldClassName}
                                defaultValue={filters.offer}
                                aria-label="Offre"
                            >
                                <option value="">Toutes les offres</option>
                                {offers.map((offer) => (
                                    <option key={offer.id} value={offer.id}>
                                        {offer.name}
                                    </option>
                                ))}
                            </select>
                            <select
                                name="course"
                                className={fieldClassName}
                                defaultValue={filters.course}
                                aria-label="Cours"
                            >
                                <option value="">Tous les cours</option>
                                {courses.map((course) => (
                                    <option key={course.id} value={course.id}>
                                        {course.name}
                                    </option>
                                ))}
                            </select>
                            <select
                                name="program"
                                className={fieldClassName}
                                defaultValue={filters.program}
                                aria-label="Programme"
                            >
                                <option value="">Tous les programmes</option>
                                {programs.map((program) => (
                                    <option key={program.id} value={program.id}>
                                        {program.name}
                                    </option>
                                ))}
                            </select>
                            <select
                                name="status"
                                className={fieldClassName}
                                defaultValue={filters.status}
                                aria-label="Statut"
                            >
                                <option value="">Tous les statuts</option>
                                {Object.entries(statusLabels).map(
                                    ([value, label]) => (
                                        <option key={value} value={value}>
                                            {label}
                                        </option>
                                    ),
                                )}
                            </select>
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                                <Button type="submit" className="w-full sm:w-auto">
                                    Filtrer
                                </Button>
                                {(filters.program ||
                                    filters.offer ||
                                    filters.course ||
                                    filters.status) && (
                                    <Button
                                        variant="outline"
                                        className="w-full sm:w-auto"
                                        asChild
                                    >
                                        <Link href={listHref}>Effacer</Link>
                                    </Button>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {applications.data.length === 0 ? (
                    <Card>
                        <CardContent>
                            <p>Aucune candidature pour ces filtres.</p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4">
                        {applications.data.map((application) => (
                            <Card key={application.id}>
                                <CardHeader>
                                    <CardTitle>
                                        {application.candidate.name}
                                    </CardTitle>
                                    <CardDescription>
                                        {application.program.name} ·{' '}
                                        {formatDate(application.submitted_at)}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="flex flex-col gap-4">
                                    <div className="flex flex-wrap items-center gap-3">
                                        {application.examined ? (
                                            <span className="text-3xl font-semibold text-card-foreground tabular-nums">
                                                {application.score}
                                                <span className="text-muted-foreground text-base font-normal">
                                                    /100
                                                </span>
                                            </span>
                                        ) : (
                                            <span className="text-base font-semibold text-card-foreground">
                                                Examen en attente
                                            </span>
                                        )}
                                        <StatusBadge
                                            status={application.status}
                                        />
                                        {application.examined &&
                                            application.suggested_status && (
                                                <span className="text-sm text-card-foreground">
                                                    Suggéré :{' '}
                                                    {
                                                        statusLabels[
                                                            application.suggested_status as TalentStatus
                                                        ]
                                                    }
                                                </span>
                                            )}
                                    </div>
                                    <div className="flex flex-wrap gap-3">
                                        {canReview && !application.examined && (
                                            <Form
                                                action={`/admin/applications/${application.id}/examination`}
                                                method="patch"
                                                options={{
                                                    preserveScroll: true,
                                                }}
                                            >
                                                <Button
                                                    type="submit"
                                                    className="h-12 bg-[#b7c48a] px-6 text-base font-semibold text-[#023940] shadow-md hover:bg-[#c9d4a3]"
                                                >
                                                    Autoriser l’examen
                                                </Button>
                                            </Form>
                                        )}
                                        <Button
                                            variant="outline"
                                            className="h-12 px-6 text-base"
                                            asChild
                                        >
                                            <Link
                                                href={
                                                    canReview
                                                        ? show(application.id)
                                                        : `/admin/applications/${application.id}`
                                                }
                                            >
                                                Ouvrir le dossier
                                            </Link>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
                <Pagination page={applications} />
            </PageFrame>
        </>
    );
}

RecruiterApplicationsIndex.layout = (props: { canReview?: boolean }) => ({
    breadcrumbs: [
        {
            title: props.canReview === false ? 'Candidatures' : 'À examiner',
            href: props.canReview === false ? '/admin/applications' : index(),
        },
    ],
});
