import { Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { Page } from '@/types/pagination';

function hrefFor(page: number, currentUrl: string, pageName: string): string {
    const [path, query = ''] = currentUrl.split('?');
    const params = new URLSearchParams(query);

    if (page <= 1) {
        params.delete(pageName);
    } else {
        params.set(pageName, String(page));
    }

    const search = params.toString();

    return search ? `${path}?${search}` : path;
}

function visiblePages(current: number, last: number): (number | 'gap')[] {
    if (last <= 7) {
        return Array.from({ length: last }, (_, index) => index + 1);
    }

    const pages = [1, last, current - 1, current, current + 1].filter(
        (page) => page >= 1 && page <= last,
    );
    const unique = [...new Set(pages)].sort((left, right) => left - right);
    const visible: (number | 'gap')[] = [];

    unique.forEach((page, index) => {
        if (index > 0 && page - unique[index - 1] > 1) {
            visible.push('gap');
        }

        visible.push(page);
    });

    return visible;
}

export function Pagination<T>({
    page,
    pageName = 'page',
    onPage,
}: {
    page: Page<T>;
    pageName?: string;
    onPage?: (page: number) => void;
}) {
    const { url } = usePage();

    if (page.total === 0 || page.last_page <= 1) {
        return null;
    }

    const previous = page.current_page > 1 ? page.current_page - 1 : null;
    const next =
        page.current_page < page.last_page ? page.current_page + 1 : null;

    function go(target: number) {
        if (onPage) {
            onPage(target);

            return;
        }
    }

    function pageControl(target: number, label: string, prefetchMount = false) {
        const current = target === page.current_page;

        if (onPage) {
            return (
                <Button
                    type="button"
                    size="sm"
                    variant={current ? 'default' : 'outline'}
                    aria-current={current ? 'page' : undefined}
                    onClick={() => go(target)}
                >
                    {label}
                </Button>
            );
        }

        return (
            <Button
                size="sm"
                variant={current ? 'default' : 'outline'}
                asChild
            >
                <Link
                    href={hrefFor(target, url, pageName)}
                    preserveScroll
                    prefetch={prefetchMount ? 'mount' : 'hover'}
                    aria-current={current ? 'page' : undefined}
                >
                    {label}
                </Link>
            </Button>
        );
    }

    return (
        <nav
            className="flex flex-wrap items-center justify-between gap-3"
            aria-label="Pagination"
        >
            <p className="text-muted-foreground text-sm">
                {page.from ?? 0}–{page.to ?? 0} sur {page.total}
            </p>
            <div className="flex flex-wrap items-center gap-2">
                {previous ? (
                    pageControl(previous, 'Précédent')
                ) : (
                    <Button variant="outline" size="sm" disabled>
                        Précédent
                    </Button>
                )}
                {visiblePages(page.current_page, page.last_page).map(
                    (item, index) =>
                        item === 'gap' ? (
                            <span
                                key={`gap-${index}`}
                                className="text-muted-foreground px-1 text-sm"
                            >
                                …
                            </span>
                        ) : (
                            <span key={item}>
                                {pageControl(item, String(item), item === next)}
                            </span>
                        ),
                )}
                {next ? (
                    pageControl(next, 'Suivant', true)
                ) : (
                    <Button variant="outline" size="sm" disabled>
                        Suivant
                    </Button>
                )}
            </div>
        </nav>
    );
}
