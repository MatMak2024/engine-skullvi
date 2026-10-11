import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { PublicPage } from '@/components/public-page';
import { StableName } from '@/components/stable-name';
import { Pagination } from '@/components/talent/pagination';
import { workPlaceLabel } from '@/lib/talent';
import type { Page, WorkMode } from '@/types';

type CourseSummary = {
    id: string;
    name: string;
    description: string | null;
    program?: { id: string; name: string } | null;
};

type ProgramSummary = {
    id: string;
    name: string;
    description: string | null;
    courses_count: number;
    work_mode: WorkMode;
    work_city: string | null;
    work_country: string | null;
};

export default function CoursesIndex({
    courses,
    programs,
}: {
    courses: Page<CourseSummary>;
    programs: Page<ProgramSummary>;
}) {
    return (
        <PublicPage title="Cours">
            <section className="talent-hero overflow-hidden px-6 py-16 text-white md:px-16">
                <p className="text-sm font-medium tracking-wide text-[#b7c48a] uppercase">
                    Cours
                </p>
                <h1 className="mt-4 max-w-3xl text-4xl font-semibold tracking-tight text-balance md:text-6xl">
                    Tous les cours et les programmes
                </h1>
                <p className="mt-4 max-w-2xl text-base leading-8 text-white/80 md:text-lg">
                    Un cours peut se suivre seul. Un programme rassemble
                    plusieurs cours. Choisissez celui qui vous correspond.
                </p>
            </section>

            <section className="px-6 py-16 md:px-16">
                <h2 className="text-3xl font-semibold tracking-tight">Cours</h2>
                <Catalog
                    empty="Aucun cours n'est ouvert pour le moment."
                    page={courses}
                    pageName="courses_page"
                    items={courses.data.map((course) => ({
                        id: course.id,
                        href: `/courses/${course.id}`,
                        title: course.name,
                        meta: course.program
                            ? `Programme · ${course.program.name}`
                            : 'Cours seul',
                        description: course.description,
                        action: 'Voir le cours',
                    }))}
                />

                <h2 className="mt-16 text-3xl font-semibold tracking-tight">
                    Programmes
                </h2>
                <Catalog
                    empty="Aucun programme n'est ouvert pour le moment."
                    page={programs}
                    pageName="programs_page"
                    items={programs.data.map((program) => ({
                        id: program.id,
                        href: `/programs/${program.id}`,
                        title: program.name,
                        meta: `${workPlaceLabel(program.work_mode, program.work_city, program.work_country)} · ${program.courses_count} cours`,
                        description: program.description,
                        action: 'Voir le programme',
                    }))}
                />
            </section>
        </PublicPage>
    );
}

function Catalog({
    items,
    empty,
    page,
    pageName,
}: {
    items: {
        id: string;
        href: string;
        title: string;
        meta: string;
        description: string | null;
        action: string;
    }[];
    empty: string;
    page: Page<{ id: string }>;
    pageName: string;
}) {
    if (items.length === 0) {
        return <p className="mt-6 text-muted-foreground">{empty}</p>;
    }

    return (
        <>
        <div className="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {items.map((item) => (
                <article
                    key={item.id}
                    className="flex flex-col rounded-3xl bg-card p-6 text-center shadow-sm"
                >
                    <h3 className="text-xl font-semibold text-foreground">
                        <StableName>{item.title}</StableName>
                    </h3>
                    <p className="mt-2 text-sm leading-6 text-muted-foreground">
                        {item.meta}
                    </p>
                    {item.description && (
                        <p className="mt-3 text-sm leading-6 text-muted-foreground">
                            {item.description}
                        </p>
                    )}
                    <Link
                        href={item.href}
                        className="mt-6 inline-flex items-center justify-center gap-2 self-center rounded-full bg-[#b7c48a] px-5 py-2 text-sm font-medium text-foreground"
                    >
                        {item.action}
                        <ArrowRight className="size-4" />
                    </Link>
                </article>
            ))}
        </div>
        <div className="mt-8">
            <Pagination page={page} pageName={pageName} />
        </div>
        </>
    );
}
