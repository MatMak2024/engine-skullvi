import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { PageEnter } from '@/components/page-enter';
import { SiteNav } from '@/components/site-nav';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="talent-shell flex min-h-svh flex-col">
            <SiteNav />
            <div className="flex flex-1 flex-col items-center justify-center gap-6 p-6 md:p-10">
            <PageEnter>
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="flex flex-col items-center gap-2 font-medium"
                        >
                            <div className="mb-1 flex size-12 items-center justify-center rounded-full bg-[#b7c48a] text-[#023940]">
                                <AppLogoIcon className="size-7 fill-current" />
                            </div>
                            <span className="sr-only">{title}</span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-medium">{title}</h1>
                            <p className="text-center text-sm text-muted-foreground">
                                {description}
                            </p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
            </PageEnter>
            </div>
        </div>
    );
}
