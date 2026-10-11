import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { PageEnter } from '@/components/page-enter';
import { DashboardMainBar } from '@/layouts/dashboard/main-bar';
import { DashboardSidebar } from '@/layouts/dashboard/sidebar';
import type { BreadcrumbItem } from '@/types';

export function DashboardLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    return (
        <AppShell variant="sidebar">
            <DashboardSidebar />
            <AppContent
                variant="sidebar"
                className="talent-shell min-w-0 overflow-x-clip"
            >
                <DashboardMainBar breadcrumbs={breadcrumbs} />
                <PageEnter>{children}</PageEnter>
            </AppContent>
        </AppShell>
    );
}
