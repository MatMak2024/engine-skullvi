import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { StableName } from '@/components/stable-name';
import { PageFrame } from '@/components/talent/page-frame';
import { ScoreBar } from '@/components/talent/score-bar';
import { StatusBadge } from '@/components/talent/status-badge';
import { Pagination } from '@/components/talent/pagination';
import { Button } from '@/components/ui/button';
import { pageContaining, paginate } from '@/lib/paginate';
import {
    criterionLabels,
    formatDate,
    workPlaceLabel,
} from '@/lib/talent';
import type { TalentStatus } from '@/types';

type ScoreLine = {
    criterion: string;
    points: number | null;
    max_points: number;
    explanation: string | null;
};

type RankedApplication = {
    rank: number;
    id: string;
    name: string;
    email: string;
    city: string | null;
    country: string | null;
    href: string;
    score: number | null;
    completeness_rate: number | null;
    suggested_status: TalentStatus | null;
    status: TalentStatus;
    submitted_at: string | null;
    flags: string[];
    scores: ScoreLine[];
};

type ScoreBoard = {
    id: string;
    name: string;
    open: boolean;
    program_name: string | null;
    work_mode: string;
    work_country: string | null;
    work_city: string | null;
    skills: { name: string; required: boolean }[];
    summary: {
        count: number;
        priority: number;
        to_review: number;
        low_fit: number;
        incomplete: number;
        ineligible: number;
        unscored: number;
        best: number | null;
    };
    items: RankedApplication[];
};

type Examination = {
    rubric_version: string;
    thresholds: { priority: number; to_review: number };
    criteria: { key: string; max: number }[];
    offers: ScoreBoard[];
    programs: ScoreBoard[];
    courses: ScoreBoard[];
};

const kinds = [
    { key: 'offers', label: 'Offres' },
    { key: 'programs', label: 'Programmes' },
    { key: 'courses', label: 'Cours' },
] as const;

type Kind = (typeof kinds)[number]['key'];

const bestPerPage = 6;
const listPerPage = 8;
const rankPerPage = 5;

function formatPoints(value: number | null): string {
    if (value === null) {
        return '—';
    }

    return Number.isInteger(value)
        ? String(value)
        : value.toLocaleString('fr-FR', { maximumFractionDigits: 2 });
}

function openingKind(examination: Examination): Kind {
    return (
        kinds.find((kind) =>
            examination[kind.key].some((board) => board.items.length > 0),
        )?.key ??
        kinds.find((kind) => examination[kind.key].length > 0)?.key ??
        'offers'
    );
}

function openingTarget(boards: ScoreBoard[]): string | null {
    return (
        boards.find((board) => board.items.length > 0)?.id ??
        boards[0]?.id ??
        null
    );
}

function BestCandidates({
    examination,
    pages,
    onOpen,
    onPage,
}: {
    examination: Examination;
    pages: Record<Kind, number>;
    onOpen: (kind: Kind, id: string) => void;
    onPage: (kind: Kind, page: number) => void;
}) {
    return (
        <section className="flex flex-col gap-8">
            <div>
                <h2 className="text-2xl font-semibold text-card-foreground">
                    Meilleurs candidats
                </h2>
                <p className="mt-1 text-card-foreground">
                    Le premier de chaque offre, de chaque programme et de chaque
                    cours, parmi les examens autorisés.
                </p>
            </div>
            {kinds.map((kind) => {
                const boards = examination[kind.key].filter(
                    (board) => board.items.length > 0,
                );
                const slice = paginate(boards, pages[kind.key], bestPerPage);

                return (
                    <div key={kind.key} className="flex flex-col gap-3">
                        <h3 className="text-lg font-semibold text-card-foreground">
                            {kind.label}
                        </h3>
                        {boards.length === 0 ? (
                            <p className="text-card-foreground">
                                Aucun examen autorisé pour le moment.
                            </p>
                        ) : (
                            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                {slice.data.map((board) => {
                                    const best = board.items[0];

                                    return (
                                        <article
                                            key={board.id}
                                            className="flex flex-col gap-4 rounded-3xl border bg-card p-6 text-card-foreground"
                                        >
                                            <h4 className="text-lg font-semibold">
                                                <StableName>
                                                    {board.name}
                                                </StableName>
                                            </h4>
                                            <p className="text-3xl font-semibold tabular-nums">
                                                {formatPoints(best.score)}
                                                <span className="text-base font-normal">
                                                    /100
                                                </span>
                                            </p>
                                            <p>
                                                <StableName className="font-medium">
                                                    {best.name}
                                                </StableName>
                                                <span className="block text-sm">
                                                    Meilleur sur{' '}
                                                    {board.summary.count}{' '}
                                                    {board.summary.count > 1
                                                        ? 'dossiers'
                                                        : 'dossier'}
                                                </span>
                                            </p>
                                            <div className="mt-auto flex flex-col gap-2">
                                                <Button
                                                    asChild
                                                    className="h-12 bg-[#b7c48a] text-base font-semibold text-[#023940] shadow-md hover:bg-[#c9d4a3]"
                                                >
                                                    <Link href={best.href}>
                                                        Ouvrir le dossier
                                                    </Link>
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    className="h-12 text-base"
                                                    onClick={() =>
                                                        onOpen(
                                                            kind.key,
                                                            board.id,
                                                        )
                                                    }
                                                >
                                                    Voir le classement
                                                </Button>
                                            </div>
                                        </article>
                                    );
                                })}
                            </div>
                        )}
                        <Pagination
                            page={slice}
                            onPage={(page) => onPage(kind.key, page)}
                        />
                    </div>
                );
            })}
        </section>
    );
}

function SummaryFigure({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-2xl bg-muted px-3 py-2">
            <p className="text-sm font-medium text-card-foreground">{label}</p>
            <p className="text-lg font-semibold text-card-foreground">{value}</p>
        </div>
    );
}

export default function AdminScoresIndex({
    examination,
}: {
    examination: Examination;
}) {
    const [kind, setKind] = useState<Kind>(() => openingKind(examination));
    const boards = examination[kind];
    const [targetId, setTargetId] = useState<string | null>(() =>
        openingTarget(examination[openingKind(examination)]),
    );
    const [bestPages, setBestPages] = useState<Record<Kind, number>>({
        offers: 1,
        programs: 1,
        courses: 1,
    });
    const [listPage, setListPage] = useState(() => {
        const opening = openingKind(examination);
        const id = openingTarget(examination[opening]);

        return pageContaining(
            examination[opening].findIndex((item) => item.id === id),
            listPerPage,
        );
    });
    const [rankPage, setRankPage] = useState(1);
    const board = boards.find((item) => item.id === targetId) ?? null;
    const list = paginate(boards, listPage, listPerPage);
    const ranked = paginate(board?.items ?? [], rankPage, rankPerPage);
    const place = board
        ? workPlaceLabel(board.work_mode, board.work_city, board.work_country)
        : '';

    function chooseKind(next: Kind) {
        setKind(next);
        setTargetId(openingTarget(examination[next]));
        setListPage(1);
        setRankPage(1);
    }

    function openRanking(next: Kind, id: string) {
        const index = examination[next].findIndex((item) => item.id === id);

        setKind(next);
        setTargetId(id);
        setListPage(pageContaining(index, listPerPage));
        setRankPage(1);
        document.getElementById('classement')?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    }

    return (
        <>
            <Head title="Scores" />
            <PageFrame
                eyebrow="Résultat"
                title="Scores"
                description="Le recruteur voit le meilleur candidat de chaque offre, de chaque programme et de chaque cours. Le classement complet suit, du meilleur au moins bon."
            >
                <BestCandidates
                    examination={examination}
                    pages={bestPages}
                    onOpen={openRanking}
                    onPage={(next, page) =>
                        setBestPages((current) => ({
                            ...current,
                            [next]: page,
                        }))
                    }
                />
                <p className="text-card-foreground text-sm">
                    Grille {examination.rubric_version}. Prioritaire à partir de{' '}
                    {examination.thresholds.priority}/100, à examiner à partir
                    de {examination.thresholds.to_review}/100. À score égal,
                    les preuves vérifiables départagent, puis la date de
                    candidature.
                </p>
                <div
                    id="classement"
                    className="grid min-w-0 items-start gap-6 lg:grid-cols-[minmax(0,24rem)_minmax(0,1fr)]"
                >
                    <aside className="bg-card text-card-foreground min-w-0 rounded-3xl border p-4 sm:p-5">
                        <div
                            className="mb-4 grid gap-2"
                            role="tablist"
                            aria-label="Type de classement"
                        >
                            {kinds.map((item) => {
                                const selected = kind === item.key;
                                const count = examination[item.key].reduce(
                                    (total, current) =>
                                        total + current.summary.count,
                                    0,
                                );

                                return (
                                    <button
                                        key={item.key}
                                        type="button"
                                        role="tab"
                                        aria-selected={selected}
                                        onClick={() => chooseKind(item.key)}
                                        className={`h-12 rounded-full px-4 text-base font-semibold shadow-sm ${
                                            selected
                                                ? 'bg-[#b7c48a] text-[#023940]'
                                                : 'border-2 border-[#b7c48a] bg-card text-[#f4f7f6]'
                                        }`}
                                    >
                                        {item.label} {count}
                                    </button>
                                );
                            })}
                        </div>
                        {boards.length === 0 ? (
                            <p className="text-card-foreground px-2 py-3 text-sm">
                                Aucun élément pour le moment.
                            </p>
                        ) : (
                            <ul className="flex flex-col gap-2">
                                {list.data.map((item) => {
                                    const selected = item.id === board?.id;

                                    return (
                                        <li key={item.id}>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    setTargetId(item.id);
                                                    setRankPage(1);
                                                }}
                                                className={`flex w-full min-w-0 items-center justify-between gap-3 rounded-2xl px-4 py-3 text-left text-base ${
                                                    selected
                                                        ? 'bg-[#b7c48a] text-[#023940] shadow-sm'
                                                        : 'border border-[#b7c48a]/40 text-card-foreground hover:bg-muted'
                                                }`}
                                            >
                                                <span className="min-w-0">
                                                    <StableName className="block truncate font-medium">
                                                        {item.name}
                                                    </StableName>
                                                    <span
                                                        className={`block text-xs ${selected ? 'text-[#023940]/80' : 'text-card-foreground'}`}
                                                    >
                                                        {item.summary.count}{' '}
                                                        {item.summary.count > 1
                                                            ? 'dossiers'
                                                            : 'dossier'}
                                                        {!item.open
                                                            ? ' · fermé'
                                                            : ''}
                                                    </span>
                                                </span>
                                                <span className="shrink-0 font-semibold">
                                                    {formatPoints(
                                                        item.summary.best,
                                                    )}
                                                </span>
                                            </button>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                        <Pagination page={list} onPage={setListPage} />
                    </aside>

                    {board === null ? (
                        <p className="text-card-foreground text-sm">
                            Choisissez une offre, un programme ou un cours.
                        </p>
                    ) : (
                        <div className="flex min-w-0 flex-col gap-6">
                            <section className="rounded-3xl border bg-card text-card-foreground p-5 md:p-6">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h2 className="text-xl font-semibold text-card-foreground">
                                            <StableName>{board.name}</StableName>
                                        </h2>
                                        <p className="text-card-foreground mt-1 text-sm">
                                            {board.program_name && (
                                                <>
                                                    Programme{' '}
                                                    <StableName>
                                                        {board.program_name}
                                                    </StableName>
                                                    {' · '}
                                                </>
                                            )}
                                            {place}
                                            {!board.open ? ' · Fermé' : ''}
                                        </p>
                                    </div>
                                </div>
                                {board.skills.length > 0 && (
                                    <ul className="mt-4 flex flex-wrap gap-2">
                                        {board.skills.map((skill) => (
                                            <li
                                                key={skill.name}
                                                className="rounded-full bg-muted px-3 py-1 text-sm text-card-foreground"
                                            >
                                                <StableName>
                                                    {skill.name}
                                                </StableName>
                                                {skill.required
                                                    ? ' · requise'
                                                    : ' · bonus'}
                                            </li>
                                        ))}
                                    </ul>
                                )}
                                <div className="mt-5 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                    <SummaryFigure
                                        label="Dossiers"
                                        value={String(board.summary.count)}
                                    />
                                    <SummaryFigure
                                        label="Meilleur score"
                                        value={formatPoints(board.summary.best)}
                                    />
                                    <SummaryFigure
                                        label="Prioritaire"
                                        value={String(board.summary.priority)}
                                    />
                                    <SummaryFigure
                                        label="À examiner"
                                        value={String(board.summary.to_review)}
                                    />
                                    <SummaryFigure
                                        label="Adéquation faible"
                                        value={String(board.summary.low_fit)}
                                    />
                                    <SummaryFigure
                                        label="Incomplet"
                                        value={String(board.summary.incomplete)}
                                    />
                                    <SummaryFigure
                                        label="Non éligible"
                                        value={String(board.summary.ineligible)}
                                    />
                                    <SummaryFigure
                                        label="Sans score"
                                        value={String(board.summary.unscored)}
                                    />
                                </div>
                            </section>

                            {board.items.length === 0 ? (
                                <p className="text-card-foreground text-sm">
                                    Aucune candidature à classer pour le moment.
                                </p>
                            ) : (
                                <>
                                    <div className="bg-card text-card-foreground hidden overflow-x-auto rounded-3xl border lg:block">
                                        <table className="w-full min-w-[64rem] text-left text-sm">
                                            <caption className="sr-only">
                                                Classement de {board.name}
                                            </caption>
                                            <thead className="border-b text-sm font-semibold text-card-foreground">
                                                <tr>
                                                    <th className="px-3 py-3 font-medium">
                                                        Rang
                                                    </th>
                                                    <th className="px-3 py-3 font-medium">
                                                        Candidat
                                                    </th>
                                                    {examination.criteria.map(
                                                        (criterion) => (
                                                            <th
                                                                key={
                                                                    criterion.key
                                                                }
                                                                className="px-3 py-3 font-medium"
                                                            >
                                                                {criterionLabels[
                                                                    criterion
                                                                        .key
                                                                ] ??
                                                                    criterion.key}
                                                                <span className="block font-normal">
                                                                    /
                                                                    {formatPoints(
                                                                        criterion.max,
                                                                    )}
                                                                </span>
                                                            </th>
                                                        ),
                                                    )}
                                                    <th className="px-3 py-3 font-medium">
                                                        Total
                                                    </th>
                                                    <th className="px-3 py-3 font-medium">
                                                        Fiabilité
                                                    </th>
                                                    <th className="px-3 py-3 font-medium">
                                                        Suggestion
                                                    </th>
                                                    <th className="px-3 py-3 font-medium">
                                                        Décision
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {ranked.data.map((item) => (
                                                    <tr
                                                        key={item.id}
                                                        className="border-b last:border-0"
                                                    >
                                                        <td className="px-3 py-3 font-medium text-card-foreground">
                                                            {item.rank}
                                                        </td>
                                                        <td className="px-3 py-3">
                                                            <StableName className="font-medium">
                                                                {item.name}
                                                            </StableName>
                                                            {item.flags
                                                                .length > 0 && (
                                                                <span className="text-card-foreground block text-xs">
                                                                    {item.flags.join(
                                                                        ' · ',
                                                                    )}
                                                                </span>
                                                            )}
                                                        </td>
                                                        {item.scores.map(
                                                            (score) => (
                                                                <td
                                                                    key={
                                                                        score.criterion
                                                                    }
                                                                    className="px-3 py-3 tabular-nums"
                                                                >
                                                                    {formatPoints(
                                                                        score.points,
                                                                    )}
                                                                </td>
                                                            ),
                                                        )}
                                                        <td className="px-3 py-3 text-base font-semibold text-card-foreground tabular-nums">
                                                            {formatPoints(
                                                                item.score,
                                                            )}
                                                        </td>
                                                        <td className="px-3 py-3 tabular-nums">
                                                            {item.completeness_rate ===
                                                            null
                                                                ? '—'
                                                                : `${item.completeness_rate} %`}
                                                        </td>
                                                        <td className="px-3 py-3">
                                                            {item.suggested_status ? (
                                                                <StatusBadge
                                                                    status={
                                                                        item.suggested_status
                                                                    }
                                                                />
                                                            ) : (
                                                                '—'
                                                            )}
                                                        </td>
                                                        <td className="px-3 py-3">
                                                            <StatusBadge
                                                                status={
                                                                    item.status
                                                                }
                                                            />
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>

                                    <div className="flex flex-col gap-4 lg:hidden">
                                        {ranked.data.map((item) => {
                                            const location = [
                                                item.city,
                                                item.country,
                                            ]
                                                .filter(Boolean)
                                                .join(', ');

                                            return (
                                                <article
                                                    key={item.id}
                                                    className="bg-card text-card-foreground rounded-3xl border p-4 sm:p-5"
                                                >
                                                    <div className="flex flex-wrap items-start justify-between gap-4">
                                                        <div>
                                                            <p className="text-card-foreground text-sm">
                                                                Rang {item.rank}
                                                            </p>
                                                            <h3 className="text-lg font-semibold text-card-foreground">
                                                                <StableName>
                                                                    {item.name}
                                                                </StableName>
                                                            </h3>
                                                            <p className="text-card-foreground text-sm break-words">
                                                                <StableName>
                                                                    {item.email}
                                                                </StableName>
                                                                {location
                                                                    ? ` · ${location}`
                                                                    : ''}
                                                                {' · '}
                                                                déposé le{' '}
                                                                {formatDate(
                                                                    item.submitted_at,
                                                                )}
                                                            </p>
                                                        </div>
                                                        <p className="text-3xl font-semibold text-card-foreground tabular-nums">
                                                            {formatPoints(
                                                                item.score,
                                                            )}
                                                            <span className="text-card-foreground text-base font-normal">
                                                                /100
                                                            </span>
                                                        </p>
                                                    </div>
                                                    <div className="mt-3 flex flex-wrap items-center gap-2 text-sm">
                                                        <span className="text-card-foreground">
                                                            Suggestion
                                                        </span>
                                                        {item.suggested_status ? (
                                                            <StatusBadge
                                                                status={
                                                                    item.suggested_status
                                                                }
                                                            />
                                                        ) : (
                                                            <span>—</span>
                                                        )}
                                                        <span className="text-card-foreground">
                                                            Décision
                                                        </span>
                                                        <StatusBadge
                                                            status={item.status}
                                                        />
                                                        <span className="text-card-foreground">
                                                            Dossier renseigné à{' '}
                                                            {item.completeness_rate ??
                                                                '—'}
                                                            %
                                                        </span>
                                                    </div>
                                                    {item.flags.length > 0 && (
                                                        <p className="text-card-foreground mt-2 text-sm">
                                                            {item.flags.join(
                                                                ' · ',
                                                            )}
                                                        </p>
                                                    )}
                                                    <ul className="mt-4 grid gap-4 md:grid-cols-2">
                                                        {item.scores.map(
                                                            (score) => (
                                                                <li
                                                                    key={
                                                                        score.criterion
                                                                    }
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
                                                                            {formatPoints(
                                                                                score.points,
                                                                            )}
                                                                            /
                                                                            {formatPoints(
                                                                                score.max_points,
                                                                            )}
                                                                        </span>
                                                                    </div>
                                                                    <ScoreBar
                                                                        points={
                                                                            score.points ??
                                                                            0
                                                                        }
                                                                        max={
                                                                            score.max_points
                                                                        }
                                                                    />
                                                                    <p className="text-sm text-card-foreground">
                                                                        {score.explanation ??
                                                                            'Aucun détail pour ce critère.'}
                                                                    </p>
                                                                </li>
                                                            ),
                                                        )}
                                                    </ul>
                                                    <Button
                                                        variant="outline"
                                                        className="mt-4 w-fit"
                                                        asChild
                                                    >
                                                        <Link href={item.href}>
                                                            Ouvrir le dossier
                                                        </Link>
                                                    </Button>
                                                </article>
                                            );
                                        })}
                                    </div>
                                    <Pagination
                                        page={ranked}
                                        onPage={setRankPage}
                                    />
                                </>
                            )}
                        </div>
                    )}
                </div>
            </PageFrame>
        </>
    );
}

AdminScoresIndex.layout = {
    breadcrumbs: [{ title: 'Scores', href: '/admin/scores' }],
};
