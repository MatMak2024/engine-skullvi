import { Link } from '@inertiajs/react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import { useDashboardNavigation } from '@/layouts/dashboard/navigation';

export function DashboardSidebar() {
    const groups = useDashboardNavigation();

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarTrigger className="border-background absolute top-1/2 -right-3 z-30 hidden size-12 -translate-y-1/2 translate-x-1/2 rounded-full border-2 bg-[#b7c48a] text-[#023940] shadow-lg hover:bg-[#c9d4a3] md:inline-flex [&_svg]:size-6" />
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="min-h-0 flex-1 gap-4 pb-8">
                {groups.map((group) => (
                    <NavMain
                        key={group.label}
                        label={group.label}
                        items={group.items}
                    />
                ))}
            </SidebarContent>

            <SidebarFooter className="mt-auto pt-2">
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
