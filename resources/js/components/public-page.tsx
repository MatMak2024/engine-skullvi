import { Head } from '@inertiajs/react';
import { type ReactNode } from 'react';
import { PageEnter } from '@/components/page-enter';
import { SiteFooter } from '@/components/site-footer';
import { SiteNav } from '@/components/site-nav';

export function PublicPage({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <>
            <Head title={title} />
            <div className="bg-background text-foreground min-h-screen">
                <SiteNav />
                <PageEnter>
                    <main className="flex w-full flex-col">{children}</main>
                    <SiteFooter />
                </PageEnter>
            </div>
        </>
    );
}
