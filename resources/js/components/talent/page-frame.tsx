import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function PageFrame({
    eyebrow,
    title,
    description,
    actions,
    size = 'default',
    children,
}: {
    eyebrow?: string;
    title: string;
    description?: string;
    actions?: ReactNode;
    size?: 'default' | 'narrow';
    children: ReactNode;
}) {
    return (
        <div
            className={cn(
                'mx-auto flex w-full flex-col gap-6 p-4 md:p-6',
                size === 'narrow' && 'max-w-3xl',
            )}
        >
            <section className="talent-hero relative overflow-hidden rounded-3xl px-5 py-7 text-white sm:px-6 sm:py-8 md:px-10 md:py-10">
                {eyebrow && (
                    <p className="text-xs font-medium tracking-wide text-[#b7c48a] uppercase">
                        {eyebrow}
                    </p>
                )}
                <h1 className="mt-2 max-w-3xl text-2xl font-semibold tracking-tight text-balance text-white sm:text-3xl md:text-4xl">
                    {title}
                </h1>
                {description && (
                    <p className="mt-3 max-w-2xl text-sm leading-6 text-white/85 sm:text-base sm:leading-7">
                        {description}
                    </p>
                )}
                {actions && (
                    <div className="mt-6 flex flex-wrap items-center gap-3">
                        {actions}
                    </div>
                )}
            </section>
            <div className="text-foreground flex flex-col gap-6">
                {children}
            </div>
        </div>
    );
}
