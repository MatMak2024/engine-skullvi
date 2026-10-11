import { DashboardLayout } from '@/layouts/dashboard/dashboard-layout';
import type { BreadcrumbItem } from '@/types';

export default function AppLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    return (
        <DashboardLayout breadcrumbs={breadcrumbs}>{children}</DashboardLayout>
    );
}
