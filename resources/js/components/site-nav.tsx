import { Link, usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { useEffect, useState } from 'react';
import AppLogo from '@/components/app-logo';
import { NotificationBell } from '@/components/notification-bell';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { StableName } from '@/components/stable-name';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { toUrl } from '@/lib/utils';
import { login, register } from '@/routes';
import { index as applicationsIndex } from '@/routes/applications';
import type { NavItem } from '@/types';

type SiteLink = NavItem & {
    emphasis?: boolean;
    match: (path: string, hash: string) => boolean;
};

function pillClass(active: boolean, emphasis = false): string {
    if (active || emphasis) {
        return 'rounded-full bg-[#b7c48a] px-3 py-2 text-sm font-medium text-[#023940] xl:px-4';
    }

    return 'rounded-full px-3 py-2 text-sm font-medium text-foreground hover:bg-muted xl:px-4';
}

export function SiteNav() {
    const page = usePage();
    const { auth, talent, name } = page.props;
    const getInitials = useInitials();
    const [hash, setHash] = useState('');
    const [open, setOpen] = useState(false);
    const path = page.url.split('?')[0].split('#')[0];
    const dashboardUrl = '/dashboard';

    useEffect(() => {
        const sync = () => setHash(window.location.hash);

        sync();
        window.addEventListener('hashchange', sync);

        return () => window.removeEventListener('hashchange', sync);
    }, [page.url]);

    const links: SiteLink[] = [
        {
            title: 'Accueil',
            href: '/#accueil',
            match: (currentPath, currentHash) =>
                currentPath === '/' &&
                (currentHash === '' || currentHash === '#accueil'),
        },
        {
            title: 'Cours',
            href: '/courses',
            match: (currentPath) => currentPath.startsWith('/courses'),
        },
        {
            title: 'Programmes',
            href: '/programs',
            match: (currentPath) => currentPath.startsWith('/programs'),
        },
        {
            title: 'Offres',
            href: '/offers',
            match: (currentPath) => currentPath.startsWith('/offers'),
        },
        {
            title: 'À propos',
            href: '/a-propos',
            match: (currentPath) => currentPath === '/a-propos',
        },
        {
            title: 'Tableau de bord',
            href: auth.user ? dashboardUrl : login(),
            match: (currentPath) => currentPath.endsWith('/dashboard'),
        },
    ];

    if (auth.user) {
        links.push({
            title: 'Mes candidatures',
            href: applicationsIndex(),
            match: (currentPath) =>
                currentPath === '/applications' ||
                currentPath.startsWith('/applications/'),
        });

        if (
            talent.roles.includes('recruiter') &&
            !talent.roles.includes('super_admin')
        ) {
            links.push({
                title: 'Candidatures',
                href: '/admin/applications',
                match: (currentPath) => currentPath.startsWith('/admin/applications'),
            });
        }
    }

    const account: SiteLink[] = auth.user
        ? []
        : [
              {
                  title: 'Connexion',
                  href: login(),
                  match: (currentPath) => currentPath === '/login',
              },
              {
                  title: 'Créer un compte',
                  href: register(),
                  emphasis: true,
                  match: (currentPath) => currentPath === '/register',
              },
          ];

    const renderLink = (item: SiteLink, onNavigate?: () => void) => {
        const href = toUrl(item.href);
        const active = item.match(path, hash);
        const className = pillClass(active, item.emphasis);
        const isAnchor = href.startsWith('/#') || href.startsWith('#');

        if (isAnchor) {
            return (
                <a
                    key={item.title}
                    href={href}
                    className={className}
                    onClick={onNavigate}
                >
                    <StableName>{item.title}</StableName>
                </a>
            );
        }

        return (
            <Link
                key={item.title}
                href={item.href}
                className={className}
                onClick={onNavigate}
            >
                <StableName>{item.title}</StableName>
            </Link>
        );
    };

    return (
        <header className="sticky top-0 z-40 border-b border-border bg-background/95 backdrop-blur">
            <div className="grid h-16 w-full grid-cols-[1fr_auto_1fr] items-center px-4 md:px-16">
                <div className="flex min-w-0 items-center gap-2 sm:gap-3">
                    <Sheet open={open} onOpenChange={setOpen}>
                        <SheetTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="lg:hidden"
                                aria-label="Ouvrir la navigation"
                            >
                                <Menu className="size-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" className="bg-background">
                            <SheetHeader>
                                <SheetTitle>{name}</SheetTitle>
                            </SheetHeader>
                            <nav className="flex flex-col gap-2 px-4">
                                {links.map((item) =>
                                    renderLink(item, () => setOpen(false)),
                                )}
                                {account.map((item) =>
                                    renderLink(item, () => setOpen(false)),
                                )}
                            </nav>
                        </SheetContent>
                    </Sheet>

                    <a href="/#accueil" className="flex min-w-0 items-center">
                        <AppLogo />
                    </a>
                </div>

                <nav className="hidden items-center justify-center gap-2 lg:flex xl:gap-4">
                    {links.map((item) => renderLink(item))}
                </nav>

                <div className="flex items-center justify-end gap-2">
                    <div className="hidden items-center gap-1 lg:flex">
                        {account
                            .filter((item) => !item.emphasis)
                            .map((item) => renderLink(item))}
                    </div>
                    {account
                        .filter((item) => item.emphasis)
                        .map((item) => renderLink(item))}
                    {auth.user && <NotificationBell />}
                    {auth.user && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    className="size-10 rounded-full p-1"
                                    aria-label="Menu du compte"
                                >
                                    <Avatar className="size-8 overflow-hidden rounded-full">
                                        <AvatarImage
                                            src={auth.user.avatar}
                                            alt={
                                                auth.user.display_name ||
                                                auth.user.name
                                            }
                                        />
                                        <AvatarFallback className="rounded-full bg-[#b7c48a] text-[#023940]">
                                            {getInitials(
                                                auth.user.display_name ||
                                                    auth.user.name,
                                            )}
                                        </AvatarFallback>
                                    </Avatar>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-56" align="end">
                                <UserMenuContent user={auth.user} />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </div>
            </div>
        </header>
    );
}
