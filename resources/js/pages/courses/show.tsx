import { Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { PublicPage } from '@/components/public-page';
import { StableName } from '@/components/stable-name';
import { SaveOpeningButton } from '@/components/talent/save-opening-button';
import { login } from '@/routes';

type CourseSkill = {
    id: string;
    name: string;
    is_required: boolean;
};

type CourseShow = {
    id: string;
    name: string;
    description: string | null;
    is_open: boolean;
    program: { id: string; name: string } | null;
    skills: CourseSkill[];
};

export default function CourseShow({
    course,
    saved = false,
}: {
    course: CourseShow;
    saved?: boolean;
}) {
    const { auth, talent } = usePage().props;
    const required = course.skills.filter((skill) => skill.is_required);
    const bonus = course.skills.filter((skill) => !skill.is_required);
    const applyHref = auth.user ? `/courses/${course.id}/apply` : login();

    return (
        <PublicPage title={course.name}>
            <section className="talent-hero overflow-hidden px-6 py-16 text-white md:px-16">
                <p className="text-sm font-medium tracking-wide text-[#b7c48a] uppercase">
                    Cours
                </p>
                <h1 className="mt-4 max-w-3xl text-4xl font-semibold tracking-tight text-balance md:text-6xl">
                    <StableName>{course.name}</StableName>
                </h1>
                {course.description && (
                    <p className="mt-4 max-w-2xl text-base leading-8 text-white/80 md:text-lg">
                        {course.description}
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
                    ) : course.is_open ? (
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
                                saveUrl={`/courses/${course.id}/save`}
                                unsaveUrl={`/courses/${course.id}/save`}
                            />
                        </>
                    ) : (
                        <p className="text-sm text-white/75">
                            Ce cours n'accepte plus de candidatures.
                        </p>
                    )}
                </div>
            </section>

            <section className="px-6 py-16 md:px-16">
                <div className="grid gap-4 md:grid-cols-2">
                    <article className="rounded-3xl bg-card p-6 shadow-sm">
                        <h2 className="text-xl font-semibold text-foreground">
                            Programme
                        </h2>
                        {course.program ? (
                            <Link
                                href={`/programs/${course.program.id}`}
                                className="mt-4 inline-flex font-medium text-foreground underline-offset-4 hover:underline"
                            >
                                <StableName>{course.program.name}</StableName>
                            </Link>
                        ) : (
                            <p className="mt-4 text-sm leading-6 text-muted-foreground">
                                Ce cours est proposé seul, sans programme.
                            </p>
                        )}
                    </article>
                    <article className="rounded-3xl bg-card p-6 shadow-sm">
                        <h2 className="text-xl font-semibold text-foreground">
                            Compétences prérequises
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
    skills: CourseSkill[];
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
