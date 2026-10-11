import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import type { User } from '@/types';

export function UserInfo({
    user,
    showEmail = false,
}: {
    user: User;
    showEmail?: boolean;
}) {
    const getInitials = useInitials();
    const showAvatar = Boolean(user.avatar && user.avatar !== '');
    const label = user.display_name || user.name;

    return (
        <>
            <Avatar className="h-8 w-8 overflow-hidden rounded-full">
                {showAvatar ? (
                    <AvatarImage src={user.avatar} alt={label} />
                ) : null}
                <AvatarFallback className="rounded-full bg-[#b7c48a] text-[#023940]">
                    {getInitials(label)}
                </AvatarFallback>
            </Avatar>
            <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-medium">{label}</span>
                {showEmail ? (
                    <span className="truncate text-xs text-current/70">
                        {user.email}
                    </span>
                ) : null}
            </div>
        </>
    );
}
