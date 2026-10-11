import { Head, Link } from '@inertiajs/react';
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
import { formatDate } from '@/lib/talent';
import { index, show } from '@/routes/applications';
import { index as programsIndex } from '@/routes/programs';
import type { Page, TalentApplicationSummary } from '@/types';

export default function ApplicationsIndex({
    applications,
}: {
    applications: Page<TalentApplicationSummary>;
}) {
    return (
        <>
            <Head title="Mes candidatures" />
            <PageFrame
                eyebrow="Candidatures"
                title="Mes candidatures"
                description="Le statut affiché est la décision en cours. Le score reste une indication."
            >

                {applications.data.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-start gap-4">
                            <p>Vous n'avez pas encore postulé.</p>
                            <Button asChild>
                                <Link href={programsIndex()}>
                                    Voir les programmes
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4">
                        {applications.data.map((application) => (
                            <Card key={application.id}>
                                <CardHeader>
                                    <CardTitle>
                                        {application.program.name}
                                    </CardTitle>
                                    <CardDescription>
                                        Déposée le{' '}
                                        {formatDate(application.submitted_at)}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="flex flex-wrap items-center justify-between gap-3">
                                    <div className="flex items-center gap-3">
                                        <StatusBadge
                                            status={application.status}
                                        />
                                        {application.score !== null ? (
                                            <span className="text-sm text-foreground">
                                                Score individuel {application.score}
                                                /100
                                            </span>
                                        ) : (
                                            <span className="text-sm text-muted-foreground">
                                                Examen en attente
                                            </span>
                                        )}
                                    </div>
                                    <Button variant="outline" asChild>
                                        <Link href={show(application.id)}>
                                            Ouvrir
                                        </Link>
                                    </Button>
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

ApplicationsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Mes candidatures',
            href: index(),
        },
    ],
};
