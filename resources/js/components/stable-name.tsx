import { type ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function StableName({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    return (
        <span translate="no" className={cn('notranslate', className)}>
            {children}
        </span>
    );
}
