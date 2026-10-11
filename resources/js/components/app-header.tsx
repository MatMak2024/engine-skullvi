import { SiteNav } from '@/components/site-nav';
import { Breadcrumbs } from '@/components/breadcrumbs';
import type { BreadcrumbItem } from '@/types';

export function AppHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItem[];
}) {
    return (
        <>
            <SiteNav />
            {breadcrumbs.length > 1 && (
                <div className="border-b border-border bg-background">
                    <div className="mx-auto flex h-12 w-full max-w-6xl items-center px-4 text-[#3d5c62] md:px-6">
                        <Breadcrumbs breadcrumbs={breadcrumbs} />
                    </div>
                </div>
            )}
        </>
    );
}
