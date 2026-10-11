import { Head, Link } from '@inertiajs/react';
import { PageFrame } from '@/components/talent/page-frame';
import { StatusBadge } from '@/components/talent/status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    availabilityLabels,
    educationLabels,
    formatDate,
    statusLabels,
} from '@/lib/talent';
import { index, show } from '@/routes/applications';
import type { TalentApplicationDetail } from '@/types';

export default function ApplicationShow({
    application,
}: {
    application: TalentApplicationDetail;
}) {
    return (
        <>
            <Head title={application.program.name} />
            <PageFrame
                eyebrow="Dossier"
                title={application.program.name}
                description={`Déposée le ${formatDate(application.submitted_at)}`}
            >

                <Card>
                    <CardHeader>
                        <CardTitle>Décision en cours</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-3">
                        <StatusBadge status={application.status} />
                        {!application.suggested_status && (
                            <p className="text-sm text-muted-foreground">
                                Dossier reçu. Le recruteur autorise l’examen
                                avant le score individuel.
                            </p>
                        )}
                        {application.suggested_status && (
                            <p className="text-muted-foreground text-sm">
                                Indication du système :{' '}
                                {application.score ?? '—'}/100, statut suggéré{' '}
                                {statusLabels[application.suggested_status]}.
                                La décision affichée au-dessus reste humaine.
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Dossier</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-3 text-sm">
                        <p>
                            Formation :{' '}
                            {educationLabels[application.education_level] ??
                                application.education_level}
                        </p>
                        <p>
                            Disponibilité :{' '}
                            {availabilityLabels[application.availability] ??
                                application.availability}
                        </p>
                        <p className="whitespace-pre-wrap">
                            {application.motivation_text}
                        </p>
                        {application.cv_url && (
                            <Button variant="outline" className="w-full sm:w-fit" asChild>
                                <a href={application.cv_url}>Télécharger le CV</a>
                            </Button>
                        )}
                    </CardContent>
                </Card>

                <Button variant="ghost" className="w-fit" asChild>
                    <Link href={index()}>Retour à mes candidatures</Link>
                </Button>
            </PageFrame>
        </>
    );
}

ApplicationShow.layout = (props: { application: TalentApplicationDetail }) => ({
    breadcrumbs: [
        {
            title: 'Mes candidatures',
            href: index(),
        },
        {
            title: props.application.program.name,
            href: show(props.application.id),
        },
    ],
});
