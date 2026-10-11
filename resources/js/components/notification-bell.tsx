import { router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

export function NotificationBell({
    className = 'relative text-foreground',
}: {
    className?: string;
}) {
    const { auth, notifications } = usePage().props;

    if (!auth.user) {
        return null;
    }

    const unread = notifications?.unread ?? 0;
    const items = notifications?.items ?? [];

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Notifications"
                    className={className}
                >
                    <Bell />
                    {unread > 0 && (
                        <span className="bg-sidebar text-sidebar-foreground absolute top-1 right-1 flex size-4 items-center justify-center rounded-full text-[10px] font-semibold">
                            {unread > 9 ? '9+' : unread}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-[min(20rem,calc(100vw-1.5rem))]">
                <DropdownMenuLabel>Notifications</DropdownMenuLabel>
                {items.length === 0 ? (
                    <DropdownMenuItem disabled>
                        Aucune notification.
                    </DropdownMenuItem>
                ) : (
                    items.map((item) => (
                        <DropdownMenuItem
                            key={item.id}
                            className="items-start whitespace-normal"
                            onSelect={() =>
                                router.post(`/notifications/${item.id}/read`, {
                                    preserveScroll: true,
                                })
                            }
                        >
                            <span className="flex flex-col gap-0.5">
                                <span
                                    className={
                                        item.read
                                            ? 'font-medium text-foreground'
                                            : 'font-semibold text-foreground'
                                    }
                                >
                                    {item.title}
                                </span>
                                <span className="text-sm text-muted-foreground">
                                    {item.body}
                                </span>
                            </span>
                        </DropdownMenuItem>
                    ))
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
