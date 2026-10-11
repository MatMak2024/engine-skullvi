import type { Page } from '@/types/pagination';

export function paginate<T>(items: T[], page: number, perPage: number): Page<T> {
    const total = items.length;
    const last = Math.max(1, Math.ceil(total / perPage));
    const current = Math.min(Math.max(1, page), last);
    const start = (current - 1) * perPage;
    const data = items.slice(start, start + perPage);

    return {
        data,
        current_page: current,
        last_page: last,
        per_page: perPage,
        total,
        from: total === 0 ? null : start + 1,
        to: total === 0 ? null : start + data.length,
    };
}

export function pageContaining(index: number, perPage: number): number {
    if (index < 0) {
        return 1;
    }

    return Math.floor(index / perPage) + 1;
}
