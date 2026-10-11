import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export type StatPoint = {
    label: string;
    value: number;
};

export type AdministrationOverview = {
    counts: StatPoint[];
    statuses: StatPoint[];
    scores: StatPoint[];
    months: StatPoint[];
};

const scoreColors = ['#f4f7f6', '#b7c48a', '#7ec8c0', '#c5d15a'];

export function AdminOverview({ overview }: { overview: AdministrationOverview }) {
    return (
        <section className="grid gap-4">
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {overview.counts.map((item) => (
                    <Card key={item.label} className="gap-2 py-5">
                        <CardHeader>
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                {item.label}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-semibold tracking-tight text-foreground">
                                {item.value}
                            </p>
                        </CardContent>
                    </Card>
                ))}
            </div>
            <div className="grid items-stretch gap-4 lg:grid-cols-2">
                <Card className="h-full">
                    <CardHeader>
                        <CardTitle>Candidatures par statut</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-1 flex-col justify-center">
                        <BarList points={overview.statuses} />
                    </CardContent>
                </Card>
                <Card className="h-full">
                    <CardHeader>
                        <CardTitle>Répartition des scores</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-1 flex-col items-center justify-center gap-6 sm:flex-row">
                        <ScoreDonut points={overview.scores} />
                        <ul className="grid w-full gap-3 text-sm">
                            {overview.scores.map((point, index) => (
                                <li
                                    key={point.label}
                                    className="flex items-center justify-between gap-3"
                                >
                                    <span className="flex items-center gap-2">
                                        <span
                                            className="size-3 shrink-0 rounded-full"
                                            style={{
                                                backgroundColor:
                                                    scoreColors[index % scoreColors.length],
                                            }}
                                        />
                                        {point.label}
                                    </span>
                                    <span className="font-medium text-foreground tabular-nums">
                                        {point.value}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </CardContent>
                </Card>
            </div>
            <Card>
                <CardHeader>
                    <CardTitle>Candidatures des six derniers mois</CardTitle>
                </CardHeader>
                <CardContent>
                    <ColumnChart points={overview.months} />
                </CardContent>
            </Card>
        </section>
    );
}

function BarList({ points }: { points: StatPoint[] }) {
    const max = Math.max(1, ...points.map((point) => point.value));

    if (points.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                Aucune candidature pour le moment.
            </p>
        );
    }

    return (
        <div className="grid gap-3">
            {points.map((point) => (
                <div key={point.label} className="grid grid-cols-[10.5rem_1fr_2rem] items-center gap-3">
                    <span className="truncate text-sm">{point.label}</span>
                    <div className="h-2.5 overflow-hidden rounded-full bg-muted">
                        <div
                            className="h-full rounded-full bg-[#b7c48a]"
                            style={{
                                width:
                                    point.value === 0
                                        ? '0%'
                                        : `${Math.max(8, (point.value / max) * 100)}%`,
                            }}
                        />
                    </div>
                    <span className="text-right text-sm font-medium text-foreground tabular-nums">
                        {point.value}
                    </span>
                </div>
            ))}
        </div>
    );
}

function ScoreDonut({ points }: { points: StatPoint[] }) {
    const total = points.reduce((sum, point) => sum + point.value, 0);
    const radius = 42;
    const circumference = 2 * Math.PI * radius;
    const gap = total > 0 ? 4 : 0;
    let offset = 0;

    return (
        <div className="relative size-40 shrink-0">
            <svg
                viewBox="0 0 120 120"
                className="size-full"
                role="img"
                aria-label="Répartition des scores"
            >
                <circle
                    cx="60"
                    cy="60"
                    r={radius}
                    fill="none"
                    stroke="#e7eeec"
                    strokeWidth="14"
                />
                {total > 0 &&
                    points.map((point, index) => {
                        if (point.value === 0) {
                            return null;
                        }

                        const raw = (point.value / total) * circumference;
                        const length = Math.max(0, raw - gap);
                        const circle = (
                            <circle
                                key={point.label}
                                cx="60"
                                cy="60"
                                r={radius}
                                fill="none"
                                stroke={scoreColors[index % scoreColors.length]}
                                strokeWidth="14"
                                strokeDasharray={`${length} ${circumference - length}`}
                                strokeDashoffset={-offset}
                                strokeLinecap="butt"
                                transform="rotate(-90 60 60)"
                            />
                        );
                        offset += raw;

                        return circle;
                    })}
            </svg>
            <span className="absolute inset-0 flex items-center justify-center text-2xl font-semibold text-foreground tabular-nums">
                {total}
            </span>
        </div>
    );
}

function ColumnChart({ points }: { points: StatPoint[] }) {
    const max = Math.max(1, ...points.map((point) => point.value));

    return (
        <div>
            <div className="grid grid-cols-6 items-end gap-3 border-b border-[#e7eeec] px-1">
                {points.map((point) => (
                    <div key={point.label} className="flex flex-col items-center">
                        <span className="mb-2 text-sm font-medium text-foreground tabular-nums">
                            {point.value}
                        </span>
                        <div className="flex h-36 w-full items-end justify-center">
                            <div
                                className="w-10 rounded-t-2xl bg-[#b7c48a]"
                                style={{
                                    height:
                                        point.value === 0
                                            ? '0px'
                                            : `${Math.max(12, (point.value / max) * 100)}%`,
                                }}
                            />
                        </div>
                    </div>
                ))}
            </div>
            <div className="grid grid-cols-6 gap-3 px-1 pt-2">
                {points.map((point) => (
                    <p
                        key={point.label}
                        className="text-center text-xs text-muted-foreground"
                    >
                        {point.label}
                    </p>
                ))}
            </div>
        </div>
    );
}
