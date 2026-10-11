import { Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { PublicPage } from '@/components/public-page';
import { StableName } from '@/components/stable-name';
import { SaveOpeningButton } from '@/components/talent/save-opening-button';
import { workPlaceLabel } from '@/lib/talent';
import { login } from '@/routes';
import { create } from '@/routes/applications';
import type { TalentProgram } from '@/types';

type ProgramCourse = {
    id: string;
    name: string;
    description: string | null;
    skills: { id: string; name: string; is_required: boolean }[];
};

export default function ProgramShow({
    program,
    saved = false,
}: {
    program: TalentProgram & { courses?: ProgramCourse[] };
    saved?: boolean;
}) {
    const { auth, talent } = usePage().props;
    const required = program.skills.filter((skill) => skill.is_required);
    const bonus = program.skills.filter((skill) => !skill.is_required);
    const applyHref = auth.user ? create(program.id) : login();

    return (
        <PublicPage title={program.name}>
            <section className="talent-hero overflow-hidden px-6 py-16 text-white md:px-16">
                <p className="text-sm font-medium tracking-wide text-[#b7c48a] uppercase">
                    Programme
                </p>
                <h1 className="mt-4 max-w-3xl text-4xl font-semibold tracking-tight text-balance md:text-6xl">
                    <StableName>{program.name}</StableName>
                </h1>
                {program.description && (
                    <p className="mt-4 max-w-2xl text-base leading-8 text-white/80 md:text-lg">
                        {program.description}
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
                    ) : program.is_open ? (
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
                                saveUrl={`/programs/${program.id}/save`}
                                unsaveUrl={`/programs/${program.id}/save`}
                            />
                        </>
                    ) : (
                        <p className="text-sm text-white/75">
                            Ce programme n'accepte plus de candidatures.
                        </p>
                    )}
                </div>
            </section>

            <section className="px-6 py-16 md:px-16">
                <div className="grid gap-4 lg:grid-cols-3">
                    <article className="rounded-3xl bg-card p-6 shadow-sm">
                        <h2 className="text-xl font-semibold text-foreground">
                            Cours
                        </h2>
                        {(program.courses ?? []).length === 0 ? (
                            <p className="mt-4 text-sm leading-6 text-muted-foreground">
                                Aucun cours n’est encore rattaché. Les
                                compétences du programme servent au score.
                            </p>
                        ) : (
                            <div className="mt-4 flex flex-col gap-4">
                                {program.courses?.map((course) => (
                                    <div key={course.id}>
                                        <Link
                                            href={`/courses/${course.id}`}
                                            className="font-medium text-foreground underline-offset-4 hover:underline"
                                        >
                                            <StableName>{course.name}</StableName>
                                        </Link>
                                        {course.skills.length > 0 && (
                                            <p className="mt-1 text-sm leading-6 text-muted-foreground">
                                                {course.skills
                                                    .map((skill) => skill.name)
                                                    .join(', ')}
                                            </p>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </article>
                    <article className="rounded-3xl bg-card p-6 shadow-sm">
                        <h2 className="text-xl font-semibold text-foreground">
                            Lieu de travail
                        </h2>
                        <p className="mt-4 text-sm leading-6 text-muted-foreground">
                            {workPlaceLabel(
                                program.work_mode,
                                program.work_city,
                                program.work_country,
                            )}
                        </p>
                        <p className="mt-3 text-sm leading-6 text-muted-foreground">
                            Le pays, la ville et l’adresse du compte ajoutent
                            des points selon ce mode. Ce n’est pas un critère
                            d’origine.
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
    skills: TalentProgram['skills'];
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
