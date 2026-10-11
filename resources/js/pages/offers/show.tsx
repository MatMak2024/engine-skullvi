import { Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { PublicPage } from '@/components/public-page';
import { StableName } from '@/components/stable-name';
import { SaveOpeningButton } from '@/components/talent/save-opening-button';
import { workPlaceLabel } from '@/lib/talent';
import { login } from '@/routes';
import type { TalentSkill, WorkMode } from '@/types';

type OfferShow = {
    id: string;
    name: string;
    description: string | null;
    is_open: boolean;
    work_mode: WorkMode;
    work_country: string | null;
    work_city: string | null;
    skills: TalentSkill[];
};

export default function OfferShowPage({
    offer,
    saved = false,
}: {
    offer: OfferShow;
    saved?: boolean;
}) {
    const { auth, talent } = usePage().props;
    const required = offer.skills.filter((skill) => skill.is_required);
    const bonus = offer.skills.filter((skill) => !skill.is_required);
    const applyHref = auth.user ? `/offers/${offer.id}/apply` : login();

    return (
        <PublicPage title={offer.name}>
            <section className="talent-hero overflow-hidden px-6 py-16 text-white md:px-16">
                <p className="text-sm font-medium tracking-wide text-[#b7c48a] uppercase">
                    Offre
                </p>
                <h1 className="mt-4 max-w-3xl text-4xl font-semibold tracking-tight text-balance md:text-6xl">
                    <StableName>{offer.name}</StableName>
                </h1>
                {offer.description && (
                    <p className="mt-4 max-w-2xl text-base leading-8 text-white/80 md:text-lg">
                        {offer.description}
                    </p>
                )}
                <div className="mt-8 flex flex-wrap items-center gap-3">
                    {talent.candidateRemoved ? (
                        <p className="text-sm text-white/75">
                            Vous n’êtes plus candidat. Votre compte utilisateur
                            reste actif, mais vous ne pouvez plus postuler.
                        </p>
                    ) : talent.isSuspended ? (
                        <p className="text-sm text-white/75">
                            Votre dossier est suspendu.
                            {talent.suspensionReason
                                ? ` Situation à résoudre : ${talent.suspensionReason}.`
                                : ' Vous ne pouvez plus postuler.'}
                        </p>
                    ) : offer.is_open ? (
                        <>
                            <Link
                                href={applyHref}
                                className="inline-flex items-center gap-2 rounded-full bg-[#b7c48a] px-6 py-3 text-sm font-medium text-foreground"
                            >
                                {auth.user
                                    ? 'Postuler'
                                    : 'Se connecter pour postuler'}
                                <ArrowRight className="size-4" />
                            </Link>
                            <SaveOpeningButton
                                saved={saved}
                                saveUrl={`/offers/${offer.id}/save`}
                                unsaveUrl={`/offers/${offer.id}/save`}
                            />
                        </>
                    ) : (
                        <p className="text-sm text-white/75">
                            Cette offre n'accepte plus de candidatures.
                        </p>
                    )}
                </div>
            </section>

            <section className="px-6 py-16 md:px-16">
                <div className="grid gap-4 md:grid-cols-2">
                    <article className="rounded-3xl bg-card p-6 shadow-sm">
                        <h2 className="text-xl font-semibold text-foreground">
                            Lieu de travail
                        </h2>
                        <p className="mt-4 text-sm leading-6 text-muted-foreground">
                            {workPlaceLabel(
                                offer.work_mode,
                                offer.work_city,
                                offer.work_country,
                            )}
                        </p>
                        <p className="mt-3 text-sm leading-6 text-muted-foreground">
                            Le CV et la lettre de motivation sont évalués par
                            rapport à cette offre.
                        </p>
                    </article>
                    <article className="rounded-3xl bg-card p-6 shadow-sm">
                        <h2 className="text-xl font-semibold text-foreground">
                            Compétences
                        </h2>
                        <SkillGroup title="Requises" skills={required} />
                        <SkillGroup title="Bonus" skills={bonus} />
                    </article>
                </div>
            </section>
        </PublicPage>
    );
}

function SkillGroup({
    title,
    skills,
}: {
    title: string;
    skills: TalentSkill[];
}) {
    return (
        <div className="mt-4">
            <p className="text-sm font-medium">{title}</p>
            {skills.length === 0 ? (
                <p className="mt-2 text-sm text-muted-foreground">Aucune.</p>
            ) : (
                <div className="mt-2 flex flex-wrap gap-2">
                    {skills.map((skill) => (
                        <StableName
                            key={skill.id}
                            className="rounded-full bg-muted px-3 py-1 text-sm text-foreground"
                        >
                            {skill.name}
                        </StableName>
                    ))}
                </div>
            )}
        </div>
    );
}
