import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { PublicPage } from '@/components/public-page';
import { StableName } from '@/components/stable-name';
import { Pagination } from '@/components/talent/pagination';
import { workPlaceLabel } from '@/lib/talent';
import type { Page, WorkMode } from '@/types';

type ProgramSummary = {
    id: string;
    name: string;
    description: string | null;
    courses_count: number;
    work_mode: WorkMode;
    work_city: string | null;
    work_country: string | null;
};

export default function ProgramsIndex({
    programs,
}: {
    programs: Page<ProgramSummary>;
}) {
    return (
        <PublicPage title="Programmes">
            <section className="talent-hero overflow-hidden px-6 py-16 text-white md:px-16">
                <p className="text-sm font-medium tracking-wide text-[#b7c48a] uppercase">
                    Programmes
                </p>
                <h1 className="mt-4 max-w-3xl text-4xl font-semibold tracking-tight text-balance md:text-6xl">
                    Les programmes ouverts
                </h1>
                <p className="mt-4 max-w-2xl text-base leading-8 text-white/80 md:text-lg">
                    Un programme rassemble plusieurs cours. Vous pouvez
                    postuler au programme entier.
                </p>
            </section>

            <section className="px-6 py-16 md:px-16">
                {programs.total === 0 ? (
                    <p className="text-muted-foreground">
                        Aucun programme n'est ouvert pour le moment.
                    </p>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {programs.data.map((program) => (
                            <article
                                key={program.id}
                                className="flex flex-col rounded-3xl bg-card p-6 text-center shadow-sm"
                            >
                                <h2 className="text-xl font-semibold text-foreground">
                                    <StableName>{program.name}</StableName>
                                </h2>
                                <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                    {workPlaceLabel(
                                        program.work_mode,
                                        program.work_city,
                                        program.work_country,
                                    )}{' '}
                                    · {program.courses_count} cours
                                </p>
                                {program.description && (
                                    <p className="mt-3 text-sm leading-6 text-muted-foreground">
                                        {program.description}
                                    </p>
                                )}
                                <Link
                                    href={`/programs/${program.id}`}
                                    className="mt-6 inline-flex items-center justify-center gap-2 self-center rounded-full bg-[#b7c48a] px-5 py-2 text-sm font-medium text-foreground"
                                >
                                    Voir le programme
                                    <ArrowRight className="size-4" />
                                </Link>
                            </article>
                        ))}
                    </div>
                )}
                <div className="mt-8">
                    <Pagination page={programs} />
                </div>
            </section>
        </PublicPage>
    );
}
