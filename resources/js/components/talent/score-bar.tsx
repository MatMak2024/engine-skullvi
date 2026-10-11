export function ScoreBar({
    points,
    max,
}: {
    points: string | number;
    max: string | number;
}) {
    const value = Number(points);
    const limit = Number(max);
    const ratio = limit > 0 ? Math.min(100, Math.max(0, (value / limit) * 100)) : 0;

    return (
        <div
            className="bg-muted h-2 overflow-hidden rounded-full"
            role="meter"
            aria-valuenow={value}
            aria-valuemin={0}
            aria-valuemax={limit}
        >
            <div
                className="bg-primary h-full rounded-full"
                style={{ width: `${ratio}%` }}
            />
        </div>
    );
}
