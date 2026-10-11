import { NotificationBell } from '@/components/notification-bell';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

export function DashboardMainBar({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItem[];
}) {
    const title = breadcrumbs.at(-1)?.title ?? '';

    return (
        <header className="border-border bg-background/95 sticky top-0 z-20 border-b backdrop-blur">
            <div className="flex h-14 items-center gap-3 px-3 sm:h-16 sm:px-4 md:px-6">
                <SidebarTrigger className="text-foreground -ml-1 md:hidden" />
                <h1 className="text-foreground min-w-0 flex-1 truncate text-base font-semibold sm:text-lg">
                    {title}
                </h1>
                <NotificationBell />
            </div>
        </header>
    );
}
